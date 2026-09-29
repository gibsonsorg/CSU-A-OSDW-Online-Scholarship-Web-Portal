<?php

namespace Tests\Feature;

use App\Mail\ApplicationStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ApplicationApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_approval_mail_queues_only_after_all_three_sections_are_approved(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [$application, $profile] = $this->makeApplication($student, 'Academic Approval Award', 'academic');

        foreach (['student-information', 'personal-information', 'scholarship-requirements'] as $index => $section) {
            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/{$section}/approve")
                ->assertOk();
            $this->assertApprovalMailCount($index === 2 ? 1 : 0);
        }

        $this->assertSame('approved', $profile->fresh()->application_status);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertApprovalMailFor($student, $application, 'Academic Approval Award');
    }

    public function test_non_academic_approval_mail_queues_only_after_all_four_sections_are_approved(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [$application, $profile] = $this->makeApplication($student, 'Non-Academic Approval Award', 'non-academic');

        foreach ([
            'student-information',
            'personal-information',
            'scholarship-requirements',
            'family-background',
        ] as $index => $section) {
            $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/{$section}/approve")
                ->assertOk();
            $this->assertApprovalMailCount($index === 3 ? 1 : 0);
        }

        $this->assertSame('approved', $profile->fresh()->application_status);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertApprovalMailFor($student, $application, 'Non-Academic Approval Award');
    }

    public function test_approving_the_last_document_queues_one_mail_when_all_other_sections_are_approved(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [$application, $profile] = $this->makeApplication($student, 'Document Approval Award', 'academic', [
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'pending',
            'uploads' => ['certificate_of_grades' => ['student_profiles/grades.pdf']],
        ]);
        $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
        $profile->forceFill([
            $reviewColumn => ['certificate_of_grades.0' => ['status' => 'pending', 'reason' => null]],
        ])->save();

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/documents/certificate_of_grades/0/approve")
            ->assertOk();

        $this->assertSame('approved', $profile->fresh()->application_status);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertApprovalMailCount(1);
        $this->assertApprovalMailFor($student, $application, 'Document Approval Award');
    }

    public function test_partial_approval_does_not_queue_mail(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [, $profile] = $this->makeApplication($student, 'Partial Approval Award', 'academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")
            ->assertOk();

        $this->assertSame('pending', $profile->fresh()->application_status);
        $this->assertApprovalMailCount(0);
    }

    public function test_queue_failure_does_not_interrupt_or_undo_approval(): void
    {
        [$student, $admin] = $this->makeUsers();
        [, $profile] = $this->makeApplication($student, 'Approval Queue Failure Award', 'academic', [
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'pending',
        ]);
        Mail::shouldReceive('to')->once()->with($student->email)->andThrow(new RuntimeException('Queue unavailable.'));
        Log::shouldReceive('error')->once()->with('Application approval notification failed', Mockery::type('array'));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/scholarship-requirements/approve")
            ->assertOk();

        $this->assertSame('approved', $profile->fresh()->application_status);
    }

    public function test_overall_approve_sends_once_on_transition_and_repeated_approval_does_not_duplicate(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [$application, $profile] = $this->makeApplication($student, 'Overall Approval Award', 'academic', [
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/approve")->assertOk();
        $this->assertApprovalMailCount(1);

        $this->postJson("/admin/applications/{$profile->id}/approve")->assertOk();
        $this->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")->assertOk();
        $this->assertApprovalMailCount(1);
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_rejected_application_queues_one_approval_mail_when_it_becomes_fully_approved_again(): void
    {
        Mail::fake();
        [$student, $admin] = $this->makeUsers();
        [, $profile] = $this->makeApplication($student, 'Reapproved Award', 'academic', [
            'application_status' => 'rejected',
            'student_information_status' => 'rejected',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")
            ->assertOk();

        $this->assertSame('approved', $profile->fresh()->application_status);
        $this->assertApprovalMailCount(1);
    }

    public function test_manual_applicant_approval_does_not_queue_mail_or_error(): void
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
            'scholarship_name' => 'Manual Academic Award',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/approve")
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame('approved', $profile->fresh()->application_status);
        $this->assertApprovalMailCount(0);
    }

    private function makeUsers(): array
    {
        return [
            User::factory()->approved()->create(),
            User::factory()->approved()->create(['role' => 0]),
        ];
    }

    private function makeApplication(User $student, string $name, string $grantType, array $profileOverrides = []): array
    {
        $scholarship = Scholarship::create([
            'name' => $name,
            'type' => $grantType,
            'status' => 'active',
        ]);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => $profileOverrides['application_status'] ?? 'pending',
        ]);
        $profile = StudentProfile::create(array_merge([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Approval',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => $grantType === 'academic' ? 'Information Technology' : null,
            'scholarship_name' => $name,
            'scholarship_type' => ucfirst($grantType),
            'grant_type' => $grantType,
            'application_status' => 'pending',
            'student_information_status' => 'pending',
            'personal_information_status' => 'pending',
            'scholarship_requirements_status' => 'pending',
            'family_background_status' => 'pending',
        ], $profileOverrides));

        return [$application, $profile];
    }

    private function assertApprovalMailCount(int $expected): void
    {
        $this->assertCount($expected, Mail::queued(ApplicationStatusMail::class));
    }

    private function assertApprovalMailFor(User $student, Application $application, string $scholarshipName): void
    {
        $mailable = Mail::queued(ApplicationStatusMail::class)->first(
            fn (ApplicationStatusMail $mail): bool =>
                $mail->status === 'approved'
                && $mail->userName === $student->name
                && $mail->scholarshipName === $scholarshipName
        );
        $this->assertNotNull($mailable);
        $this->assertStringContainsString($scholarshipName, $mailable->render());
        $this->assertSame('approved', $application->fresh()->status);
    }
}