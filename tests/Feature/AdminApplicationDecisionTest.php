<?php

namespace Tests\Feature;

use App\Mail\ApplicationCorrectionMail;
use App\Mail\ApplicationClosureMail;
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
        $now = now()->startOfSecond();
        $this->travelTo($now);
        config(['scholarships.slot_counting_starts_at' => $now->copy()->subDay()->toDateTimeString()]);

        foreach (['academic', 'non-academic'] as $grantType) {
            [$student, $application, $profile, $scholarship] = $this->makeApplication($grantType, withFamily: true);
            $scholarship->forceFill(['max_slots' => 1])->save();
            $this->assertSame(1, $scholarship->activeApplicationsCount());
            $this->assertSame(0, $scholarship->availableSlotsCount());
            $reason = $grantType === 'academic' ? 'Wrong Uploaded Documents' : 'Wrong Form';
            $remark = 'Please correct the ' . $grantType . ' application details.';
            $documentGroup = $grantType === 'academic' ? 'certificate_of_grades' : 'supporting_document';
            Storage::disk('local')->put('student_profiles/old-document.pdf', 'old');
            $profile->forceFill(['uploads' => [$documentGroup => ['student_profiles/old-document.pdf']]])->save();

            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
                'reason' => $reason,
                'rejection_remark' => $remark,
            ])->assertOk()->assertJsonPath('locked', false);

            $rejected = $profile->fresh();
            $this->assertNull($rejected->locked_at);
            $this->assertSame($remark, $rejected->rejection_remark);
            $this->assertTrue($rejected->resubmission_deadline->equalTo($now->copy()->addDay()));
            $this->assertSame(1, $scholarship->fresh()->activeApplicationsCount());
            $this->assertSame(0, $scholarship->fresh()->availableSlotsCount());
            $this->assertSame('rejected', $rejected->student_information_status);
            $this->assertSame('rejected', $rejected->personal_information_status);
            $this->assertSame('rejected', $rejected->scholarship_requirements_status);
            $dashboard = $this->actingAs($student)->get('/dashboard')->assertOk()
                ->assertViewHas('appliedScholarships', fn (array $items) => collect($items)->contains(fn (array $item) =>
                    (int) $item['scholarship_id'] === $scholarship->id
                    && $item['correction_required'] === true
                    && $item['reopen'] === true
                    && $item['rejection_remark'] === $remark
                ));
            $dashboard->assertSee('ACTION REQUIRED — Your application needs corrections. See Application Status for details.', false);
            $dashboard->assertDontSee('day left to resubmit');
            if ($grantType === 'non-academic') {
                $this->assertSame('rejected', $rejected->family_background_status);
            }

            $payload = $this->resubmissionPayload($student, $scholarship, $application, $profile);
            if ($reason === 'Wrong Uploaded Documents') {
                $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $payload)
                    ->assertRedirect()
                    ->assertSessionHas('error');
                $payload['uploads'] = [UploadedFile::fake()->create('fresh-document.pdf', 10, 'application/pdf')];
            }
            $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $payload)
                ->assertRedirect(route('dashboard', ['section' => $grantType === 'academic' ? 'acad' : 'non'], absolute: false))
                ->assertSessionDoesntHaveErrors();

            $resubmitted = $profile->fresh();
            $this->assertSame(1, (int) $resubmitted->reapply_count);
            $this->assertSame('pending', $resubmitted->application_status);
            $this->assertNull($resubmitted->resubmission_deadline);
            $this->assertNull($resubmitted->rejection_remark);
            $this->assertSame('Updated', $resubmitted->first_name);
            if ($grantType === 'non-academic') {
                $this->assertSame('Prior Mother', $resubmitted->familyBackground?->mother_name);
            }
            if ($reason === 'Wrong Uploaded Documents') {
                $uploadedPaths = collect($resubmitted->uploads)->flatten()->all();
                $this->assertCount(1, $uploadedPaths);
                $this->assertNotContains('student_profiles/old-document.pdf', $uploadedPaths);
                $this->assertFalse(Storage::disk('local')->exists('student_profiles/old-document.pdf'));
            } else {
                $this->assertSame(['student_profiles/old-document.pdf'], $resubmitted->uploads[$documentGroup]);
                $this->assertTrue(Storage::disk('local')->exists('student_profiles/old-document.pdf'));
            }

            $secondReason = $grantType === 'academic' ? 'Wrong Form' : 'Does not meet the eligibility requirements';
            $secondRejection = ['reason' => $secondReason];
            if ($secondReason === 'Wrong Form') {
                $secondRejection['rejection_remark'] = 'The form still needs correction.';
            }
            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", $secondRejection)
                ->assertOk()->assertJsonPath('locked', true);
            $this->assertNotNull($profile->fresh()->locked_at);
            $this->assertSame(0, $scholarship->fresh()->activeApplicationsCount());
            $this->assertSame(1, $scholarship->fresh()->availableSlotsCount());
        }

        $this->assertCount(2, Mail::queued(ApplicationCorrectionMail::class));
        $this->assertCount(1, Mail::queued(ApplicationStatusMail::class));
        $this->assertCount(1, Mail::queued(ApplicationClosureMail::class));
        $documentsMail = Mail::queued(ApplicationCorrectionMail::class)
            ->first(fn (ApplicationCorrectionMail $mail) => $mail->rejectionRemark === 'Please correct the academic application details.');
        $formMail = Mail::queued(ApplicationCorrectionMail::class)
            ->first(fn (ApplicationCorrectionMail $mail) => $mail->rejectionRemark === 'Please correct the non-academic application details.');
        $closureMail = Mail::queued(ApplicationClosureMail::class)->first();
        $this->assertTrue($documentsMail->resubmissionDeadline->equalTo($now->copy()->addDay()));
        $this->assertTrue($formMail->resubmissionDeadline->equalTo($now->copy()->addDay()));
        $this->assertStringContainsString('<strong>What to fix:</strong>', $documentsMail->render());
        $this->assertStringContainsString('Please correct the academic application details.', $documentsMail->render());
        $this->assertStringContainsString('<ol>', $formMail->render());
        $this->assertSame('Your Scholarship Application Has Been Closed', $closureMail->notificationSubject());
        $this->assertSame(
            "Dear Decision Student,\n\nYour application for Decision academic Award has been closed after its final resubmission.\n\nReason for closure: The form still needs correction.\n\nBest regards,\n\nScholarship Office",
            $closureMail->notificationBody()
        );
        $closedProfile = StudentProfile::where('scholarship_name', $closureMail->scholarshipName)->firstOrFail();
        $this->assertSame($closureMail->notificationBody(), $closedProfile->last_notification_body);
        $this->assertStringContainsString('<strong>Reason for closure:</strong> The form still needs correction.', $closureMail->render());
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);
        Mail::assertNotQueued(ApplicationFinalRejectionMail::class);
    }

    public function test_expired_correction_is_locked_on_dashboard_and_server_blocks_resubmission(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$student, $application, $profile, $scholarship] = $this->makeApplication('academic');
        config(['scholarships.slot_counting_starts_at' => now()->subDay()->toDateTimeString()]);
        $scholarship->forceFill(['max_slots' => 1])->save();
        $this->assertSame(1, $scholarship->activeApplicationsCount());
        $this->assertSame(0, $scholarship->availableSlotsCount());
        $deadline = now()->subSecond();
        $profile->forceFill([
            'application_status' => 'rejected',
            'rejection_reason' => 'Wrong Uploaded Documents',
            'rejection_remark' => 'Please upload a readable certificate.',
            'resubmission_deadline' => $deadline,
        ])->save();
        $application->forceFill(['status' => 'rejected'])->save();

        $this->assertSame(0, $scholarship->fresh()->activeApplicationsCount());
        $this->assertSame(1, $scholarship->fresh()->availableSlotsCount());

        $this->actingAs($student)->get('/dashboard')->assertOk()
            ->assertViewHas('appliedScholarships', fn (array $items) => collect($items)->contains(fn (array $item) =>
                (int) $item['scholarship_id'] === $scholarship->id
                && $item['expired'] === true
                && $item['locked'] === true
                && $item['reopen'] === false
            ));
        $this->assertNotNull($profile->fresh()->locked_at);

        $payload = $this->resubmissionPayload($student, $scholarship, $application, $profile);
        $payload['uploads'] = [UploadedFile::fake()->create('replacement-document.pdf', 10, 'application/pdf')];
        $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $payload)
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionHas('error', 'The resubmission deadline has passed. This application is closed and cannot be resubmitted.');
        $this->assertSame('rejected', $profile->fresh()->application_status);
        $this->assertSame('rejected', $application->fresh()->status);
    }

    public function test_rejection_rejects_any_reason_outside_the_two_choices(): void
    {
        Mail::fake();
        [$admin] = $this->makeUsers();
        [, , $profile] = $this->makeApplication('academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Wrong Form',
        ])->assertUnprocessable();

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Other',
        ])->assertUnprocessable();

        $this->assertSame('pending', $profile->fresh()->application_status);
        Mail::assertNothingOutgoing();
    }

    public function test_approved_and_eligibility_locked_applications_reject_later_decisions(): void
    {
        Mail::fake();
        [$admin] = $this->makeUsers();
        [, , $approvedProfile] = $this->makeApplication('academic');
        [, , $eligibilityProfile] = $this->makeApplication('non-academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$approvedProfile->id}/approve")
            ->assertOk();
        $this->postJson("/admin/applications/{$approvedProfile->id}/approve")
            ->assertStatus(409)
            ->assertJsonPath('status', 'error');
        $this->postJson("/admin/applications/{$approvedProfile->id}/reject", [
            'reason' => 'Wrong Uploaded Documents',
            'rejection_remark' => 'Please upload the missing certificate.',
        ])->assertStatus(409);
        $this->assertSame('approved', $approvedProfile->fresh()->application_status);

        $this->postJson("/admin/applications/{$eligibilityProfile->id}/reject", [
            'reason' => 'Does not meet the eligibility requirements',
        ])->assertOk()->assertJsonPath('locked', true);
        $this->postJson("/admin/applications/{$eligibilityProfile->id}/approve")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This application is locked and can no longer be approved or rejected.');
        $this->postJson("/admin/applications/{$eligibilityProfile->id}/reject", [
            'reason' => 'Does not meet the eligibility requirements',
        ])->assertStatus(409);
        $this->assertSame('rejected', $eligibilityProfile->fresh()->application_status);
        $this->assertNotNull($eligibilityProfile->fresh()->locked_at);
    }

    public function test_document_rejection_stays_disabled_until_resubmission_and_locks_after_second_rejection(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$admin] = $this->makeUsers();
        [$student, $application, $profile, $scholarship] = $this->makeApplication('non-academic', withFamily: true);
        Storage::disk('local')->put('student_profiles/old-document.pdf', 'old document');
        $profile->forceFill(['uploads' => ['uploads' => ['student_profiles/old-document.pdf']]])->save();

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Wrong Uploaded Documents',
            'rejection_remark' => 'Please upload a readable document.',
        ])->assertOk()->assertJsonPath('locked', false);
        $this->assertNull($profile->fresh()->locked_at);
        $this->postJson("/admin/applications/{$profile->id}/approve")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This application is awaiting student resubmission before another decision can be made.');
        $this->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Wrong Uploaded Documents',
            'rejection_remark' => 'Please upload a readable document.',
        ])->assertStatus(409);
        $this->assertSame('rejected', $profile->fresh()->application_status);

        $payload = $this->resubmissionPayload($student, $scholarship, $application, $profile);
        $payload['uploads'] = [UploadedFile::fake()->create('corrected-document.pdf', 10, 'application/pdf')];
        $this->actingAs($student)->from('/dashboard?section=non')->post('/student-profiles', $payload)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();

        $resubmitted = $profile->fresh();
        $this->assertSame('pending', $resubmitted->application_status);
        $this->assertSame(1, (int) $resubmitted->reapply_count);
        $this->assertNull($resubmitted->locked_at);
        $this->assertSame('pending', $application->fresh()->status);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Wrong Form',
            'rejection_remark' => 'Please correct the form.',
        ])->assertOk()->assertJsonPath('locked', true);
        $this->assertNotNull($profile->fresh()->locked_at);
        $this->postJson("/admin/applications/{$profile->id}/approve")->assertStatus(409);
        $this->postJson("/admin/applications/{$profile->id}/reject", [
            'reason' => 'Does not meet the eligibility requirements',
        ])->assertStatus(409);
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