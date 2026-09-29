<?php

namespace Tests\Feature;

use App\Mail\ApplicationSectionStatusMail;
use App\Mail\ApplicationStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\ApplicationSectionStatusSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationSectionReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_sections_and_documents_independently(): void
    {
        Mail::fake();
        Storage::fake('local');
        $student = User::factory()->approved()->create(['name' => 'Jane Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        Storage::disk('local')->put('student_profiles/grades.pdf', 'grades');

        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'program' => 'Information Technology',
            'scholarship_name' => 'Merit Scholarship',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'uploads' => ['certificate_of_grades' => ['student_profiles/grades.pdf']],
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")
            ->assertOk();
        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/personal-information/reject", [
            'reason' => 'Contact number needs correction.',
        ])->assertOk();
        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/documents/certificate_of_grades/0/approve")
            ->assertOk();

        $fresh = $profile->fresh();
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('rejected', $fresh->personal_information_status);
        $this->assertSame('Contact number needs correction.', $fresh->personal_information_reason);
        $documentReviews = $fresh->document_review ?? $fresh->document_reviews;
        $this->assertSame('approved', $documentReviews['certificate_of_grades.0']['status']);
        Mail::assertQueued(ApplicationSectionStatusMail::class, function (ApplicationSectionStatusMail $mail): bool {
            return $mail->firstName === 'Jane'
                && $mail->scholarshipName === 'Merit Scholarship'
                && $mail->sections === [
                    ['label' => 'Student Information', 'status' => 'Approved', 'reason' => null],
                    ['label' => 'Personal Information', 'status' => 'Rejected', 'reason' => 'Contact number needs correction.'],
                    ['label' => 'Scholarship & Requirements', 'status' => 'Approved', 'reason' => null],
                ];
        });
        $mail = Mail::queued(ApplicationSectionStatusMail::class)->first();
        $this->assertSame(ApplicationSectionStatusSummary::for($fresh)['sections'], $mail->sections);
        $this->assertSame('Update on Your Scholarship Application — Merit Scholarship', $mail->build()->subject);
        $this->assertStringContainsString('Overall Status: Rejected (1 of 3 sections need your attention)', $mail->render());
        $this->assertStringContainsString('Reason: Contact number needs correction.', $mail->render());
    }

    public function test_each_academic_rejection_sends_a_fresh_full_status_snapshot(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create(['name' => 'Avery Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $profile = $this->makeStatusReviewProfile($student, 'academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Correct your contact number.',
        ])->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/personal-information/approve")
            ->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/scholarship-requirements/reject", [
            'reason' => 'Upload the missing certificate.',
        ])->assertOk();
        $this->assertCount(1, Mail::queued(ApplicationSectionStatusMail::class));
        $mail = Mail::queued(ApplicationSectionStatusMail::class)->first();
        $this->assertSame(['Rejected', 'Approved', 'Rejected'], array_column($mail->sections, 'status'));
        $this->assertSame('Correct your contact number.', $mail->sections[0]['reason']);
        $this->assertSame('Upload the missing certificate.', $mail->sections[2]['reason']);
        $this->assertStringContainsString('2 of 3 sections need your attention', $mail->render());

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Updated reason after completion.',
        ])->assertOk();
        $this->assertCount(1, Mail::queued(ApplicationSectionStatusMail::class));

        $profile->fresh()->forceFill([
            'student_information_status' => 'pending',
            'student_information_reason' => null,
        ])->save();
        $this->assertSame('pending', $profile->fresh()->student_information_status);
        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Updated after reopening.',
        ])->assertOk();
        $this->assertSame('rejected', $profile->fresh()->student_information_status);
        $this->assertCount(2, Mail::queued(ApplicationSectionStatusMail::class));
    }

    public function test_completed_rejection_mail_failure_does_not_fail_the_admin_action(): void
    {
        $student = User::factory()->approved()->create(['name' => 'Failure Test Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $profile = $this->makeStatusReviewProfile($student, 'academic', [
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
        ]);
        Mail::shouldReceive('to')->once()->with($student->email)->andThrow(new \RuntimeException('Queue unavailable.'));
        Log::shouldReceive('error')->once()->with('Application rejection notification failed', \Mockery::type('array'));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Correct the student information.',
        ])->assertOk();

        $this->assertSame('rejected', $profile->fresh()->student_information_status);
    }

    public function test_non_academic_review_sends_once_after_all_four_sections_are_decided(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create(['name' => 'Non-Academic Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $profile = $this->makeStatusReviewProfile($student, 'non-academic');

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/reject", [
            'reason' => 'Correct student information.',
        ])->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/personal-information/approve")
            ->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/family-background/reject", [
            'reason' => 'Clarify family details.',
        ])->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/scholarship-requirements/approve")
            ->assertOk();

        $this->assertCount(1, Mail::queued(ApplicationSectionStatusMail::class));
        $mail = Mail::queued(ApplicationSectionStatusMail::class)->first();
        $this->assertSame(['Rejected', 'Approved', 'Rejected', 'Approved'], array_column($mail->sections, 'status'));
        $this->assertStringContainsString('2 of 4 sections need your attention', $mail->render());
    }

    public function test_document_rejection_uses_the_updated_requirements_status_in_combined_email(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create(['name' => 'Document Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $profile = $this->makeStatusReviewProfile($student, 'academic', [
            'uploads' => ['certificate_of_grades' => ['student_profiles/grades.pdf']],
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/documents/certificate_of_grades/0/reject", [
            'reason' => 'The uploaded document is unreadable.',
        ])->assertOk();
        $this->assertCount(0, Mail::queued(ApplicationSectionStatusMail::class));

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")
            ->assertOk();
        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/personal-information/approve")
            ->assertOk();

        $this->assertSame('rejected', $profile->fresh()->scholarship_requirements_status);
        $this->assertCount(1, Mail::queued(ApplicationSectionStatusMail::class));
        $mail = Mail::queued(ApplicationSectionStatusMail::class)->first();
        $this->assertSame('Rejected', $mail->sections[2]['status']);
        $this->assertSame('The uploaded document is unreadable.', $mail->sections[2]['reason']);
        $this->assertStringContainsString('1 of 3 sections need your attention', $mail->render());
    }

    public function test_approval_and_manual_applicant_rejection_send_no_combined_email(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create(['name' => 'Regular Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $profile = $this->makeStatusReviewProfile($student, 'academic');
        $manualProfile = $this->makeStatusReviewProfile($student, 'academic', [
            'user_id' => null,
            'source' => 'manual',
            'email' => 'manual-applicant@example.test',
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")
            ->assertOk();
        $this->actingAs($admin)->postJson("/admin/applications/{$manualProfile->id}/sections/student-information/reject", [
            'reason' => 'Correct the information.',
        ])->assertOk();
        $this->actingAs($admin)->postJson("/admin/applications/{$manualProfile->id}/sections/personal-information/approve")
            ->assertOk();
        $this->actingAs($admin)->postJson("/admin/applications/{$manualProfile->id}/sections/scholarship-requirements/reject", [
            'reason' => 'Correct the requirements.',
        ])->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_application_status_page_uses_shared_section_summary_for_both_grant_types_and_all_statuses(): void
    {
        $student = User::factory()->approved()->create(['name' => 'Status Page Student']);
        $definitions = [
            ['grant_type' => 'academic', 'application_status' => 'pending', 'student_information_status' => 'approved'],
            ['grant_type' => 'academic', 'application_status' => 'approved', 'student_information_status' => 'approved', 'personal_information_status' => 'approved', 'scholarship_requirements_status' => 'approved'],
            ['grant_type' => 'academic', 'application_status' => 'rejected', 'student_information_status' => 'rejected', 'student_information_reason' => 'Correct the student number.', 'personal_information_status' => 'approved', 'scholarship_requirements_status' => 'rejected', 'scholarship_requirements_reason' => 'Replace the certificate.'],
            ['grant_type' => 'non-academic', 'application_status' => 'pending', 'student_information_status' => 'approved', 'personal_information_status' => 'rejected', 'personal_information_reason' => 'Clarify your address.'],
            ['grant_type' => 'non-academic', 'application_status' => 'approved', 'student_information_status' => 'approved', 'personal_information_status' => 'approved', 'scholarship_requirements_status' => 'approved', 'family_background_status' => 'approved'],
            ['grant_type' => 'non-academic', 'application_status' => 'rejected', 'student_information_status' => 'rejected', 'student_information_reason' => 'Correct the student number.', 'personal_information_status' => 'approved', 'scholarship_requirements_status' => 'rejected', 'scholarship_requirements_reason' => 'Replace the certificate.', 'family_background_status' => 'approved'],
        ];

        foreach ($definitions as $index => $definition) {
            $grantType = $definition['grant_type'];
            $attributes = array_merge([
                'application_status' => $definition['application_status'],
                'scholarship_name' => 'Status Page Award ' . $index,
            ], $definition);
            $this->makeStatusReviewProfile($student, $grantType, $attributes);
        }

        $response = $this->actingAs($student)->get(route('application-status'));
        $response->assertOk()
            ->assertSee('Pending — 1 of 3 sections reviewed')
            ->assertSee('Approved — 3 of 3 sections approved')
            ->assertSee('Rejected — 2 of 3 sections need attention')
            ->assertSee('Pending — 2 of 4 sections reviewed')
            ->assertSee('Approved — 4 of 4 sections approved')
            ->assertSee('Rejected — 2 of 4 sections need attention')
            ->assertSee('Family Background')
            ->assertSee('Correct the student number.')
            ->assertSee('Replace the certificate.');

        $response->assertViewHas('academicApplications', function ($applications): bool {
            return $applications->every(fn (StudentProfile $application): bool =>
                $application->section_status_summary === ApplicationSectionStatusSummary::for($application)
            );
        });
        $response->assertViewHas('nonAcademicApplications', function ($applications): bool {
            return $applications->every(fn (StudentProfile $application): bool =>
                $application->section_status_summary === ApplicationSectionStatusSummary::for($application)
            );
        });
    }

    public function test_overall_application_is_approved_only_after_all_three_sections_are_approved(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create(['name' => 'Final Approval Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $scholarship = Scholarship::create(['name' => 'Final Approval Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Final',
            'last_name' => 'Approval',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'student_information_status' => 'pending',
            'personal_information_status' => 'pending',
            'scholarship_requirements_status' => 'pending',
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/student-information/approve")
            ->assertOk();
        $this->assertSame('pending', $profile->fresh()->application_status);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/personal-information/approve")
            ->assertOk();
        $this->assertSame('pending', $profile->fresh()->application_status);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/scholarship-requirements/approve")
            ->assertOk();

        $fresh = $profile->fresh();
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('approved', $fresh->personal_information_status);
        $this->assertSame('approved', $fresh->scholarship_requirements_status);
        $this->assertSame('pending', $fresh->family_background_status);
        $this->assertSame('approved', $fresh->application_status);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertCount(1, Mail::queued(ApplicationStatusMail::class));
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);
        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/family-background/approve")
            ->assertNotFound();
    }

    public function test_non_academic_family_background_review_controls_overall_status(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create(['name' => 'Family Review Student']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $scholarship = Scholarship::create(['name' => 'Family Review Scholarship', 'type' => 'non-academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Family Review',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'grant_type' => 'non-academic',
            'application_status' => 'pending',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
            'family_background_status' => 'approved',
        ]);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/family-background/reject", [
            'reason' => 'Please clarify the family income details.',
        ])->assertOk();

        $rejected = $profile->fresh();
        $this->assertSame('rejected', $rejected->family_background_status);
        $this->assertSame('Please clarify the family income details.', $rejected->family_background_reason);
        $this->assertSame('rejected', $rejected->application_status);
        $this->assertSame('rejected', $application->fresh()->status);
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);

        $this->actingAs($admin)->get('/admin/applications/' . $profile->id)
            ->assertOk()
            ->assertJsonPath('family_background_status', 'rejected')
            ->assertJsonPath('family_background_reason', 'Please clarify the family income details.');
        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertDontSee('data-id="' . $profile->id . '"', false)
            ->assertDontSee('<span class="badge rejected">Rejected</span>', false);

        $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/sections/family-background/approve")
            ->assertOk();

        $approved = $profile->fresh();
        $this->assertSame('approved', $approved->family_background_status);
        $this->assertNull($approved->family_background_reason);
        $this->assertSame('approved', $approved->application_status);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertCount(1, Mail::queued(ApplicationStatusMail::class));
    }

    public function test_summary_report_lists_only_approved_applicants_and_filters_across_types(): void
    {
        $student = User::factory()->approved()->create();
        $admin = User::factory()->approved()->create(['role' => 0]);
        $academicScholarship = Scholarship::create(['name' => 'Summary Academic Award', 'type' => 'academic']);
        $nonAcademicScholarship = Scholarship::create(['name' => 'Summary Community Award', 'type' => 'non-academic']);

        $makeProfile = function (Scholarship $scholarship, string $status, string $firstName, string $createdAt) use ($student): StudentProfile {
            $profile = StudentProfile::create([
                'user_id' => $student->id,
                'first_name' => $firstName,
                'last_name' => 'Summary',
                'sex' => 'Female',
                'status' => 'Single',
                'email' => $firstName . '-' . $scholarship->type . '@example.test',
                'course' => 'BSIT',
                'college_department' => 'College of Computing',
                'year_level' => '2nd Year',
                'scholarship_name' => $scholarship->name,
                'scholarship_type' => ucfirst($scholarship->type),
                'grant_type' => $scholarship->type,
                'application_status' => $status,
                'campus' => 'Aparri',
            ]);
            $profile->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            return $profile;
        };

        $academicProfile = $makeProfile($academicScholarship, 'approved', 'AcademicApproved', '2026-09-20 10:00:00');
        $nonAcademicProfile = $makeProfile($nonAcademicScholarship, 'approved', 'NonAcademicApproved', '2026-09-22 10:00:00');
        $pendingProfile = $makeProfile($academicScholarship, 'pending', 'AcademicPending', '2026-09-23 10:00:00');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Summary Academic Award')
            ->assertSee('Summary Community Award')
            ->assertSee('data-id="' . $academicProfile->id . '"', false)
            ->assertSee('data-id="' . $nonAcademicProfile->id . '"', false)
            ->assertDontSee('data-id="' . $pendingProfile->id . '"', false)
            ->assertSee('<th>Funding</th>', false)
            ->assertSee('<th>Campus</th>', false)
            ->assertSee('<th>Amount</th>', false)
            ->assertSee('data-action="add-amount" data-grant-type="all"', false)
            ->assertDontSee('data-action="add-applicant"', false);

        $this->actingAs($admin)->get('/admin?grant_type=academic')
            ->assertOk()
            ->assertSee('data-id="' . $academicProfile->id . '"', false)
            ->assertDontSee('data-id="' . $nonAcademicProfile->id . '"', false);

        $this->actingAs($admin)->get('/admin?grant_type=non-academic')
            ->assertOk()
            ->assertSee('data-id="' . $nonAcademicProfile->id . '"', false)
            ->assertDontSee('data-id="' . $academicProfile->id . '"', false);

        $this->actingAs($admin)->get('/admin?date_from=2026-09-21&date_to=2026-09-22&scholarship_name=' . urlencode($nonAcademicScholarship->name))
            ->assertOk()
            ->assertSee('data-id="' . $nonAcademicProfile->id . '"', false)
            ->assertDontSee('data-id="' . $academicProfile->id . '"', false);

        $this->actingAs($admin)->get('/admin/academic')
            ->assertOk()
            ->assertSee('data-action="add-applicant"', false)
            ->assertSee('data-grant-type="academic"', false);
        $this->actingAs($admin)->get('/admin/non-academic')
            ->assertOk()
            ->assertSee('data-action="add-applicant"', false)
            ->assertSee('data-grant-type="non-academic"', false);

        $this->actingAs($admin)->get('/admin/academic?status=approved')
            ->assertOk()
            ->assertDontSee('data-action="add-amount"', false)
            ->assertSee('<th>Amount</th>', false)
            ->assertSee('data-action="add-applicant"', false);
        $this->actingAs($admin)->get('/admin/non-academic?status=approved')
            ->assertOk()
            ->assertDontSee('data-action="add-amount"', false)
            ->assertSee('<th>Amount</th>', false)
            ->assertSee('data-action="add-applicant"', false);
    }

    public function test_summary_report_bulk_amount_update_respects_combined_filters(): void
    {
        $student = User::factory()->approved()->create();
        $admin = User::factory()->approved()->create(['role' => 0]);
        $academicScholarship = Scholarship::create(['name' => 'Bulk Academic Award', 'type' => 'academic']);
        $nonAcademicScholarship = Scholarship::create(['name' => 'Bulk Community Award', 'type' => 'non-academic']);
        $makeProfile = function (Scholarship $scholarship, string $status, string $firstName, string $createdAt) use ($student): StudentProfile {
            $profile = StudentProfile::create([
                'user_id' => $student->id,
                'first_name' => $firstName,
                'last_name' => 'Bulk',
                'sex' => 'Male',
                'status' => 'Single',
                'email' => $firstName . '-' . $scholarship->type . '@example.test',
                'course' => 'BSIT',
                'year_level' => '1st Year',
                'scholarship_name' => $scholarship->name,
                'scholarship_type' => ucfirst($scholarship->type),
                'grant_type' => $scholarship->type,
                'application_status' => $status,
                'amount' => 0,
            ]);
            $profile->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            return $profile;
        };
        $target = $makeProfile($academicScholarship, 'approved', 'BulkTarget', '2026-09-20 10:00:00');
        $otherType = $makeProfile($nonAcademicScholarship, 'approved', 'BulkOtherType', '2026-09-20 10:00:00');
        $otherDate = $makeProfile($academicScholarship, 'approved', 'BulkOtherDate', '2026-08-20 10:00:00');
        $notApproved = $makeProfile($academicScholarship, 'rejected', 'BulkRejected', '2026-09-20 10:00:00');

        $this->actingAs($admin)->postJson('/admin/applications/update-scholarship-amounts', [
            'grant_type' => 'all',
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'scholarship_name' => $academicScholarship->name,
            'status' => 'approved',
            'amount' => 7500,
        ])->assertOk()->assertJsonPath('updated', 1);

        $this->assertSame('7500.00', $target->fresh()->amount);
        $this->assertSame('0.00', $otherType->fresh()->amount);
        $this->assertSame('0.00', $otherDate->fresh()->amount);
        $this->assertSame('0.00', $notApproved->fresh()->amount);
    }

    private function makeStatusReviewProfile(User $student, string $grantType, array $attributes = []): StudentProfile
    {
        return StudentProfile::create(array_merge([
            'user_id' => $student->id,
            'first_name' => 'Review',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => 'Status Review Award',
            'scholarship_type' => $grantType === 'academic' ? 'Academic' : 'Non-Academic',
            'grant_type' => $grantType,
            'application_status' => 'pending',
            'student_information_status' => 'pending',
            'personal_information_status' => 'pending',
            'scholarship_requirements_status' => 'pending',
            'family_background_status' => 'pending',
        ], $attributes));
    }
}
