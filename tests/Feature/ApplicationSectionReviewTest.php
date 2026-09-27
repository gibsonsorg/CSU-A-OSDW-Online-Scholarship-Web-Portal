<?php

namespace Tests\Feature;

use App\Mail\ApplicationSectionStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            return $mail->section === 'Personal Information' && $mail->documentName === null;
        });
    }

    public function test_overall_application_is_approved_only_after_all_three_sections_are_approved(): void
    {
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
}
