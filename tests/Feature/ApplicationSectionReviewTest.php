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
        $this->assertSame('approved', $fresh->application_status);
        $this->assertSame('approved', $application->fresh()->status);
    }
}
