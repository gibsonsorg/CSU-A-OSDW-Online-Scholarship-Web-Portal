<?php

namespace Tests\Feature;

use App\Mail\ApplicationFinalRejectionMail;
use App\Mail\ApplicationSectionStatusMail;
use App\Mail\ApplicationStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminApplicationDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_approval_approves_every_section_for_both_grant_types(): void
    {
        Mail::fake();
        [$admin] = $this->makeUsers();

        foreach (['academic', 'non-academic'] as $grantType) {
            [$student, $application, $profile] = $this->makeApplication($grantType);

            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/approve")
                ->assertOk();

            $approved = $profile->fresh();
            $this->assertSame('approved', $approved->application_status);
            $this->assertSame('approved', $approved->student_information_status);
            $this->assertSame('approved', $approved->personal_information_status);
            $this->assertSame('approved', $approved->scholarship_requirements_status);
            $this->assertSame(
                $grantType === 'non-academic' ? 'approved' : 'pending',
                $approved->family_background_status
            );
            $this->assertSame('approved', $application->fresh()->status);
            Mail::assertQueued(ApplicationStatusMail::class, fn (ApplicationStatusMail $mail) =>
                $mail->status === 'approved' && $mail->scholarshipName === $profile->scholarship_name
            );
            $approvalMail = Mail::queued(ApplicationStatusMail::class)->last();
            $this->assertStringContainsString(
                'Congratulations! Your application for the "' . $profile->scholarship_name . '" has been <strong>APPROVED</strong>.',
                $approvalMail->render()
            );
        }

        $this->assertCount(2, Mail::queued(ApplicationStatusMail::class));
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);
        Mail::assertNotQueued(ApplicationFinalRejectionMail::class);
    }

    public function test_eligibility_rejection_locks_immediately_and_sends_eligibility_email(): void
    {
        Mail::fake();
        [$admin] = $this->makeUsers();

        foreach (['academic', 'non-academic'] as $grantType) {
            [$student, , $profile, $scholarship] = $this->makeApplication($grantType);

            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
                'reason' => 'Does not meet the eligibility requirements',
            ])->assertOk()->assertJsonPath('locked', true);

            $rejected = $profile->fresh();
            $this->assertSame('rejected', $rejected->application_status);
            $this->assertSame('Does not meet the eligibility requirements', $rejected->rejection_reason);
            $this->assertNotNull($rejected->locked_at);

            $dashboard = $this->actingAs($student)->get('/dashboard')->assertOk();
            $dashboard->assertViewHas('appliedScholarships', fn (array $items) => collect($items)->contains(fn (array $item) =>
                (int) $item['scholarship_id'] === $scholarship->id
                && $item['locked'] === true
                && $item['rejection_reason'] === 'Does not meet the eligibility requirements'
            ));
        }

        $eligibilityMail = Mail::queued(ApplicationStatusMail::class)->first();
        $this->assertStringContainsString(
            'This is to inform you that your application for Decision academic Award did not meet certain eligibility requirements. You are welcome to apply for another scholarship offered by the government. If you have any questions, feel free to visit the OSDW office in Aparri.',
            $eligibilityMail->render()
        );
        $this->assertCount(2, Mail::queued(ApplicationStatusMail::class));
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);
        Mail::assertNotQueued(ApplicationFinalRejectionMail::class);
    }

    public function test_documents_rejection_reopens_every_step_requires_fresh_uploads_and_locks_after_resubmission(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$admin] = $this->makeUsers();

        foreach (['academic', 'non-academic'] as $grantType) {
            [$student, $application, $profile, $scholarship] = $this->makeApplication($grantType, withFamily: true);
            Storage::disk('local')->put('student_profiles/old-document.pdf', 'old');
            $profile->forceFill([
                'uploads' => ['certificate_of_grades' => ['student_profiles/old-document.pdf']],
            ])->save();

            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
                'reason' => 'Wrong uploaded documents or incomplete form',
            ])->assertOk()->assertJsonPath('locked', false);

            $rejected = $profile->fresh();
            $this->assertNull($rejected->locked_at);
            $this->assertSame('rejected', $rejected->student_information_status);
            $this->assertSame('rejected', $rejected->personal_information_status);
            $this->assertSame('rejected', $rejected->scholarship_requirements_status);
            if ($grantType === 'non-academic') {
                $this->assertSame('rejected', $rejected->family_background_status);
            }

            $payload = $this->resubmissionPayload($student, $scholarship, $application, $profile);
            $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $payload)
                ->assertRedirect()
                ->assertSessionHas('error');

            $payload['uploads'] = [UploadedFile::fake()->create('fresh-document.pdf', 10, 'application/pdf')];
            $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $payload)
                ->assertRedirect(route('dashboard', ['section' => $grantType === 'academic' ? 'acad' : 'non'], absolute: false))
                ->assertSessionDoesntHaveErrors();

            $resubmitted = $profile->fresh();
            $this->assertSame(1, (int) $resubmitted->reapply_count);
            $this->assertSame('pending', $resubmitted->application_status);
            $this->assertSame('Updated', $resubmitted->first_name);
            if ($grantType === 'non-academic') {
                $this->assertSame('Prior Mother', $resubmitted->familyBackground?->mother_name);
            }
            $this->assertCount(1, $resubmitted->uploads['certificate_of_grades']);
            $this->assertNotSame('student_profiles/old-document.pdf', $resubmitted->uploads['certificate_of_grades'][0]);
            $this->assertFalse(Storage::disk('local')->exists('student_profiles/old-document.pdf'));

            $secondReason = $grantType === 'academic'
                ? 'Wrong uploaded documents or incomplete form'
                : 'Does not meet the eligibility requirements';
            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
                'reason' => $secondReason,
            ])->assertOk()->assertJsonPath('locked', true);
            $this->assertNotNull($profile->fresh()->locked_at);
        }

        $this->assertCount(4, Mail::queued(ApplicationStatusMail::class));
        $documentsMail = Mail::queued(ApplicationStatusMail::class)
            ->first(fn (ApplicationStatusMail $mail) => $mail->rejectionReason === 'Wrong uploaded documents or incomplete form');
        $this->assertStringContainsString(
            "We regret to inform you that your application for 'Decision academic Award' has been REJECTED. Please ensure your submitted documents are clear and complete and that you fill out the application form correctly. You may check your application status for more details and reapply once all requirements are met.",
            $documentsMail->render()
        );
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);
        Mail::assertNotQueued(ApplicationFinalRejectionMail::class);
    }

    public function test_rejection_rejects_any_reason_outside_the_two_choices(): void
    {
        Mail::fake();
        [$admin] = $this->makeUsers();
        [, , $profile] = $this->makeApplication('academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Other',
        ])->assertUnprocessable();

        $this->assertSame('pending', $profile->fresh()->application_status);
        Mail::assertNothingOutgoing();
    }

    private function makeUsers(): array
    {
        return [
            User::factory()->approved()->create(['role' => 0]),
            User::factory()->approved()->create(),
        ];
    }

    private function makeApplication(string $grantType, bool $withFamily = false): array
    {
        $student = User::factory()->approved()->create(['name' => 'Decision Student']);
        $scholarshipName = $grantType === 'academic' ? 'Decision academic Award' : 'Open Heart Scholarship';
        $scholarship = Scholarship::create([
            'name' => $scholarshipName,
            'type' => $grantType,
            'status' => 'active',
        ]);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Original',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-12345',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'program' => $grantType === 'academic' ? 'Information Technology' : null,
            'religion' => $grantType === 'academic' ? 'None' : null,
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'home_address' => 'Aparri',
            'contact_number' => '+63 912345678',
            'scholarship_name' => $scholarshipName,
            'scholarship_type' => ucfirst($grantType),
            'grant_type' => $grantType,
            'application_status' => 'pending',
            'student_information_status' => 'pending',
            'personal_information_status' => 'pending',
            'scholarship_requirements_status' => 'pending',
            'family_background_status' => 'pending',
        ]);

        if ($withFamily && $grantType === 'non-academic') {
            $profile->familyBackground()->create([
                'mother_name' => 'Prior Mother',
                'father_name' => 'Prior Father',
            ]);
        }

        return [$student, $application, $profile, $scholarship];
    }

    private function resubmissionPayload(User $student, Scholarship $scholarship, Application $application, StudentProfile $profile): array
    {
        return [
            'student_profile_id' => $profile->id,
            'application_id' => $application->id,
            'first_name' => 'Updated',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-12345',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => ucfirst($scholarship->type),
            'program' => $scholarship->type === 'academic' ? 'Information Technology' : null,
            'religion' => $scholarship->type === 'academic' ? 'None' : null,
            'family_background' => [
                'mother_name' => 'Prior Mother',
                'father_name' => 'Prior Father',
            ],
        ];
    }
}