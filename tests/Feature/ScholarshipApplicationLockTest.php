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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ScholarshipApplicationLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_partial_rejection_stays_unlocked_and_can_be_resubmitted_without_mail(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$student, $admin] = $this->makeUsers();
        [$application, $profile, $scholarship] = $this->makeApplication($student, 'First Rejection Award', 'academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Correct the student information.',
        ])->assertOk();

        $this->assertSame(0, (int) $profile->fresh()->reapply_count);
        $this->assertNull($profile->fresh()->locked_at);
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));
        Mail::assertNotQueued(ApplicationFinalRejectionMail::class);

        $this->assertDashboardLockState($student, $scholarship, false);
        $this->submitAcademic($student, $scholarship, $application, $profile)
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($profile->id, StudentProfile::where('user_id', $student->id)->where('application_id', $application->id)->firstOrFail()->id);
        $this->assertSame(1, (int) $profile->fresh()->reapply_count);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_rejection_after_resubmission_locks_once_sends_final_mail_and_blocks_direct_post(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$student, $admin] = $this->makeUsers();
        [$application, $profile, $scholarship] = $this->makeApplication($student, 'Final Lock Award', 'academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Correct the student information.',
        ])->assertOk();
        $this->submitAcademic($student, $scholarship, $application, $profile)->assertRedirect();
        $this->assertSame(1, (int) $profile->fresh()->reapply_count);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'The correction is incomplete.',
        ])->assertOk();

        $locked = $profile->fresh();
        $this->assertNotNull($locked->locked_at);
        $this->assertSame(1, (int) $locked->reapply_count);
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));
        $this->assertCount(1, Mail::queued(ApplicationFinalRejectionMail::class));
        $finalMail = Mail::queued(ApplicationFinalRejectionMail::class)->first();
        $this->assertSame('Update on Your Scholarship Application', $finalMail->build()->subject);
        $this->assertStringContainsString('Final Lock Award', $finalMail->render());
        $this->assertDashboardLockState($student, $scholarship, true);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Further rejection.',
        ])->assertOk();
        $this->postJson("/admin/applications/{$profile->id}/reject")->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));
        $this->assertCount(1, Mail::queued(ApplicationFinalRejectionMail::class));

        $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $this->academicPayload($student, $scholarship, $application, $profile))
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionHas('error', "You've reached the maximum number of applications for this scholarship.");
    }

    public function test_reject_approve_reject_without_resubmission_never_locks(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [, $profile] = $this->makeApplication($student, 'No Resubmit Award', 'academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'First rejection.',
        ])->assertOk();
        $this->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")->assertOk();
        $this->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Rejected again without resubmission.',
        ])->assertOk();

        $this->assertSame(0, (int) $profile->fresh()->reapply_count);
        $this->assertNull($profile->fresh()->locked_at);
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));
        Mail::assertNotQueued(ApplicationFinalRejectionMail::class);
    }

    public function test_overall_and_document_rejections_use_the_shared_final_notification(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [$overallApplication, $overallProfile] = $this->makeApplication($student, 'Overall Final Award', 'academic', [
            'reapply_count' => 1,
        ]);
        [$documentApplication, $documentProfile] = $this->makeApplication($student, 'Document Final Award', 'academic', [
            'reapply_count' => 1,
            'uploads' => ['certificate_of_grades' => ['student_profiles/grades.pdf']],
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$overallProfile->id}/reject")->assertOk();
        $this->postJson("/admin/applications/{$documentProfile->id}/documents/certificate_of_grades/0/reject", [
            'reason' => 'The document is unreadable.',
        ])->assertOk();

        $this->assertNotNull($overallProfile->fresh()->locked_at);
        $this->assertNotNull($documentProfile->fresh()->locked_at);
        $this->assertCount(2, Mail::queued(ApplicationFinalRejectionMail::class));
        Mail::assertNotQueued(ApplicationStatusMail::class);
        $this->assertSame('rejected', $overallApplication->fresh()->status);
        $this->assertSame('rejected', $documentApplication->fresh()->status);
    }

    public function test_locked_applications_count_toward_academic_and_non_academic_limits(): void
    {
        Mail::fake();
        Storage::fake('local');

        foreach (['academic', 'non-academic'] as $grantType) {
            $student = User::factory()->approved()->create();
            $lockedScholarship = $this->makeScholarship("Locked {$grantType} Award", $grantType);
            [$lockedApplication, $lockedProfile] = $this->makeApplication($student, $lockedScholarship->name, $grantType, [
                'application_status' => 'rejected',
                'reapply_count' => 1,
                'locked_at' => now(),
            ], $lockedScholarship);
            $activeScholarship = $this->makeScholarship("Active {$grantType} Award", $grantType);
            [$activeApplication] = $this->makeApplication($student, $activeScholarship->name, $grantType, [], $activeScholarship);
            $thirdScholarship = $this->makeScholarship("Third {$grantType} Award", $grantType);

            $response = $grantType === 'academic'
                ? $this->submitNewAcademic($student, $thirdScholarship)
                : $this->submitNonAcademic($student, $thirdScholarship);
            $response->assertRedirect(route('dashboard', ['section' => $grantType === 'academic' ? 'acad' : 'non'], absolute: false))
                ->assertSessionHas('error', "You already have two active applications in the {$grantType} category. Only two open applications per category are allowed.");

            $dashboard = $this->actingAs($student)->get('/dashboard' . ($grantType === 'academic' ? '' : '?section=non'))->assertOk();
            $counterName = $grantType === 'academic' ? 'activeAcademicApplications' : 'activeNonAcademicApplications';
            $dashboard->assertViewHas($counterName, 2);
            $dashboard->assertViewHas('appliedScholarships', function (array $items) use ($lockedScholarship, $grantType): bool {
                $locked = collect($items)->first(fn (array $item) =>
                    (int) $item['scholarship_id'] === $lockedScholarship->id
                    && $item['grant_type'] === $grantType
                );

                return ($locked['locked'] ?? false) === true;
            });
            $this->assertSame('pending', $activeApplication->fresh()->status);
            $this->assertSame('rejected', $lockedApplication->fresh()->status);
            $this->assertNotNull($lockedProfile->fresh()->locked_at);
        }
    }

    public function test_admin_delete_removes_the_locked_application_and_allows_a_new_application(): void
    {
        Storage::fake('local');
        [$student, $admin] = $this->makeUsers();
        $scholarship = $this->makeScholarship('Delete Reset Award', 'non-academic');
        [$application, $profile] = $this->makeApplication($student, $scholarship->name, 'non-academic', [
            'application_status' => 'rejected',
            'reapply_count' => 1,
            'locked_at' => now(),
        ], $scholarship);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/delete")->assertOk();
        $this->assertSoftDeleted('student_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('applications', ['id' => $application->id]);

        $this->submitNonAcademic($student, $scholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
    }

    public function test_lock_is_scoped_to_student_scholarship_id_and_grant_type(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$student, $admin] = $this->makeUsers();
        $otherStudent = User::factory()->approved()->create();
        $academicTulong = $this->makeScholarship('Tulong Dunong Program', 'academic');
        $nonAcademicTulong = $this->makeScholarship('Tulong Dunong Program', 'non-academic');
        [, $academicProfile] = $this->makeApplication($student, $academicTulong->name, 'academic', [
            'reapply_count' => 1,
        ], $academicTulong);
        [, $otherStudentProfile] = $this->makeApplication($otherStudent, $academicTulong->name, 'academic', [], $academicTulong);

        $this->actingAs($admin)->postJson("/admin/applications/{$academicProfile->id}/sections/student-information/reject", [
            'reason' => 'Final rejection for this student.',
        ])->assertOk();

        $this->assertNotNull($academicProfile->fresh()->locked_at);
        $this->assertNull($otherStudentProfile->fresh()->locked_at);
        $this->submitNonAcademic($student, $nonAcademicTulong)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();

        $dashboard = $this->actingAs($student)->get('/dashboard?section=non')->assertOk();
        $dashboard->assertViewHas('appliedScholarships', function (array $items) use ($academicTulong, $nonAcademicTulong): bool {
            $academic = collect($items)->first(fn (array $item) => (int) $item['scholarship_id'] === $academicTulong->id);
            $nonAcademic = collect($items)->first(fn (array $item) => (int) $item['scholarship_id'] === $nonAcademicTulong->id);

            return ($academic['grant_type'] ?? null) === 'academic'
                && ($academic['locked'] ?? false) === true
                && ($nonAcademic['grant_type'] ?? null) === 'non-academic'
                && ($nonAcademic['locked'] ?? true) === false;
        });
    }

    public function test_manual_applicant_rejection_does_not_lock_or_send_mail(): void
    {
        Mail::fake();
        [, $admin] = $this->makeUsers();
        $profile = StudentProfile::create([
            'source' => 'manual',
            'first_name' => 'Manual',
            'last_name' => 'Applicant',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => 'manual-applicant@local.invalid',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => 'Manual Award',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'reapply_count' => 1,
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Manual row review.',
        ])->assertOk();

        $this->assertNull($profile->fresh()->locked_at);
        Mail::assertNothingOutgoing();
    }

    public function test_notification_queue_failure_does_not_fail_or_undo_rejection_lock(): void
    {
        [$student, $admin] = $this->makeUsers();
        [, $profile] = $this->makeApplication($student, 'Queue Failure Award', 'academic', [
            'reapply_count' => 1,
        ]);
        Mail::shouldReceive('to')->once()->with($student->email)->andThrow(new RuntimeException('Queue unavailable.'));
        Log::shouldReceive('error')->once()->with('Application rejection notification failed', Mockery::type('array'));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'The resubmitted information is incomplete.',
        ])->assertOk();

        $this->assertSame('rejected', $profile->fresh()->application_status);
        $this->assertNotNull($profile->fresh()->locked_at);
    }

    private function makeUsers(): array
    {
        return [
            User::factory()->approved()->create(),
            User::factory()->approved()->create(['role' => 0]),
        ];
    }

    private function makeScholarship(string $name, string $grantType): Scholarship
    {
        return Scholarship::create([
            'name' => $name,
            'type' => $grantType,
            'status' => 'active',
        ]);
    }

    private function makeApplication(User $student, string $name, string $grantType, array $overrides = [], ?Scholarship $scholarship = null): array
    {
        $scholarship ??= $this->makeScholarship($name, $grantType);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => $overrides['application_status'] ?? 'pending',
        ]);
        $profile = StudentProfile::create(array_merge([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Lock',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'program' => $grantType === 'academic' ? 'Information Technology' : null,
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => ucfirst($grantType),
            'grant_type' => $grantType,
            'application_status' => 'pending',
            'student_information_status' => 'pending',
            'personal_information_status' => 'pending',
            'scholarship_requirements_status' => 'pending',
            'family_background_status' => 'pending',
        ], $overrides));

        return [$application, $profile, $scholarship];
    }

    private function assertDashboardLockState(User $student, Scholarship $scholarship, bool $locked): void
    {
        $response = $this->actingAs($student)->get('/dashboard' . ($scholarship->type === 'academic' ? '' : '?section=non'))->assertOk();
        $response->assertViewHas('appliedScholarships', function (array $items) use ($scholarship, $locked): bool {
            return collect($items)->contains(fn (array $item) =>
                (int) $item['scholarship_id'] === $scholarship->id
                && $item['grant_type'] === $scholarship->type
                && $item['locked'] === $locked
            );
        });
    }

    private function submitAcademic(User $student, Scholarship $scholarship, Application $application, StudentProfile $profile)
    {
        return $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $this->academicPayload($student, $scholarship, $application, $profile));
    }

    private function academicPayload(User $student, Scholarship $scholarship, Application $application, StudentProfile $profile): array
    {
        return [
            'student_profile_id' => $profile->id,
            'application_id' => $application->id,
            'first_name' => 'Lock',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-12345',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'religion' => 'None',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
        ];
    }

    private function submitNewAcademic(User $student, Scholarship $scholarship)
    {
        return $this->actingAs($student)->from('/dashboard')->post('/student-profiles', [
            'first_name' => 'Lock',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-12345',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'religion' => 'None',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'uploads' => [UploadedFile::fake()->create('grades.pdf', 10, 'application/pdf')],
        ]);
    }

    private function submitNonAcademic(User $student, Scholarship $scholarship)
    {
        return $this->actingAs($student)->from('/dashboard?section=non')->post('/student-profiles', [
            'first_name' => 'Lock',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-12345',
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'uploads' => [UploadedFile::fake()->create('supporting-document.pdf', 10, 'application/pdf')],
        ]);
    }
}