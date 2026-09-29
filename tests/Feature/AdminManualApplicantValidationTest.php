<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManualApplicantValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);
    }

    protected function makeScholarship(string $name, string $type, ?string $fundingType = null): void
    {
        Scholarship::create([
            'name' => $name,
            'type' => $type,
            'funding_type' => $fundingType,
            'status' => 'active',
        ]);
    }

    public function test_manual_applicant_rejects_non_canonical_scholarship_name(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('Socio-Cultural Scholarship', 'non-academic');

        $response = $this->actingAs($admin)->postJson('/admin/manual-applicants', [
            'grant_type' => 'non-academic',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'sex' => 'Female',
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => 'Socio cultural',
            'date' => '2026-09-22',
            'application_status' => 'pending',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scholarship_name']);
    }

    public function test_manual_applicant_accepts_canonical_scholarship_name(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('Socio-Cultural Scholarship', 'non-academic');

        $response = $this->actingAs($admin)->postJson('/admin/manual-applicants', [
            'grant_type' => 'non-academic',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'sex' => 'Female',
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'date' => '2026-09-22',
            'application_status' => 'pending',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('student_profiles', [
            'source' => 'manual',
            'grant_type' => 'non-academic',
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'application_status' => 'pending',
        ]);
    }

    public function test_academic_manual_applicant_uses_derived_fields_filters_and_supports_editing(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('University Based Scholarship', 'academic', 'In-house');
        $this->makeScholarship('Cagayan Valley Assoc. of Hawaii', 'academic', 'International');

        $this->actingAs($admin)
            ->getJson(route('admin.academic.manual_applicants.college_department', [
                'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            ]))
            ->assertOk()
            ->assertJson(['college_department' => 'College of Information and Computing Science']);

        $created = $this->postJson(route('admin.academic.manual_applicants.store'), [
            'last_name' => 'Santos',
            'first_name' => 'Mara',
            'middle_name' => 'Lopez',
            'suffix' => 'Jr.',
            'sex' => 'Female',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'college_department' => 'Untrusted value',
            'year_level' => '2nd Year',
            'scholarship_name' => 'University Based Scholarship',
            'scholarship_type' => 'Non-Academic',
            'funding' => 'Untrusted value',
            'campus' => 'Wrong campus',
            'date' => '2026-09-22',
            'application_status' => 'approved',
        ])->assertOk();

        $profileId = $created->json('data.id');
        $this->assertDatabaseHas('student_profiles', [
            'id' => $profileId,
            'source' => 'manual',
            'last_name' => 'Santos',
            'suffix' => 'Jr.',
            'grant_type' => 'academic',
            'college_department' => 'College of Information and Computing Science',
            'scholarship_type' => 'Academic',
            'scholarship_name' => 'University Based Scholarship',
            'campus' => 'Aparri',
            'created_at' => '2026-09-22 00:00:00',
            'application_status' => 'approved',
        ]);

        $studentProfile = StudentProfile::create([
            'source' => 'application',
            'first_name' => 'Student',
            'middle_name' => 'Submitted',
            'last_name' => 'Applicant',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => 'student-submitted@example.test',
            'course' => 'BACHELOR OF SCIENCE IN FISHERIES',
            'college_department' => 'College of Fisheries and Aquatic Sciences',
            'year_level' => '1st Year',
            'scholarship_name' => 'University Based Scholarship',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'approved',
        ]);
        $studentProfile->forceFill(['created_at' => '2026-09-22 00:00:00'])->save();

        $this->get(route('admin.academic', [
            'date_from' => '2026-09-22',
            'date_to' => '2026-09-22',
            'scholarship_name' => 'University Based Scholarship',
            'status' => 'approved',
        ]))->assertOk()
            ->assertSee('Santos')
            ->assertSee('Academic')
            ->assertSee('In-house')
            ->assertSee('Aparri')
            ->assertSee('2026-09-22')
            ->assertSee('Applicant info');

        $this->putJson(route('admin.academic.manual_applicants.update', ['id' => $profileId]), [
            'last_name' => 'Reyes',
            'first_name' => 'Mara',
            'middle_name' => 'Lopez',
            'suffix' => '',
            'sex' => 'Female',
            'course' => 'BACHELOR OF SCIENCE IN FISHERIES',
            'year_level' => '3rd Year',
            'scholarship_name' => 'Cagayan Valley Assoc. of Hawaii',
            'date' => '2026-09-24',
            'application_status' => 'rejected',
        ])->assertOk();

        $this->assertDatabaseHas('student_profiles', [
            'id' => $profileId,
            'last_name' => 'Reyes',
            'suffix' => null,
            'course' => 'BACHELOR OF SCIENCE IN FISHERIES',
            'college_department' => 'College of Fisheries and Aquatic Sciences',
            'year_level' => '3rd Year',
            'scholarship_name' => 'Cagayan Valley Assoc. of Hawaii',
            'scholarship_type' => 'Academic',
            'campus' => 'Aparri',
            'created_at' => '2026-09-24 00:00:00',
            'application_status' => 'rejected',
        ]);

        $this->get(route('admin.academic', [
            'date_from' => '2026-09-24',
            'date_to' => '2026-09-24',
            'scholarship_name' => 'Cagayan Valley Assoc. of Hawaii',
            'status' => 'rejected',
        ]))->assertOk()
            ->assertSee('Reyes')
            ->assertSee('International')
            ->assertSee('2026-09-24');
    }

    public function test_academic_manual_applicant_rejects_courses_outside_the_allowed_list(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('University Based Scholarship', 'academic', 'In-house');

        $this->actingAs($admin)->postJson(route('admin.academic.manual_applicants.store'), [
            'last_name' => 'Santos',
            'first_name' => 'Mara',
            'sex' => 'Female',
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => 'University Based Scholarship',
            'date' => '2026-09-22',
            'application_status' => 'pending',
        ])->assertUnprocessable()->assertJsonValidationErrors(['course']);
    }

    public function test_non_academic_manual_applicant_uses_type_scoped_funding_on_create_edit_and_filter(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('Tulong Dunong Program', 'academic', 'University');
        $this->makeScholarship('Tulong Dunong Program', 'non-academic', 'Government');

        $this->actingAs($admin)
            ->getJson(route('admin.academic.manual_applicants.college_department', [
                'course' => 'BACHELOR OF ELEMENTARY EDUCATION',
            ]))
            ->assertOk()
            ->assertJson(['college_department' => 'College of Teacher Education']);

        $created = $this->postJson(route('admin.non_academic.manual_applicants.store'), [
            'last_name' => 'Garcia',
            'first_name' => 'Lina',
            'middle_name' => 'Reyes',
            'suffix' => 'III',
            'sex' => 'Female',
            'course' => 'BACHELOR OF ELEMENTARY EDUCATION',
            'college_department' => 'Untrusted value',
            'year_level' => '1st Year',
            'scholarship_name' => 'Tulong Dunong Program',
            'scholarship_type' => 'Academic',
            'funding' => 'University',
            'campus' => 'Wrong campus',
            'date' => '2026-09-25',
            'application_status' => 'pending',
        ])->assertOk();

        $profileId = $created->json('data.id');
        $this->assertDatabaseHas('student_profiles', [
            'id' => $profileId,
            'source' => 'manual',
            'grant_type' => 'non-academic',
            'scholarship_type' => 'Non-Academic',
            'college_department' => 'College of Teacher Education',
            'campus' => 'Aparri',
            'created_at' => '2026-09-25 00:00:00',
            'application_status' => 'pending',
        ]);

        $this->get(route('admin.non_academic', [
            'date_from' => '2026-09-25',
            'date_to' => '2026-09-25',
            'scholarship_name' => 'Tulong Dunong Program',
            'status' => 'pending',
        ]))->assertOk()
            ->assertSee('Garcia')
            ->assertSee('Non-Academic')
            ->assertSee('Government')
            ->assertDontSee('University')
            ->assertSee('Aparri')
            ->assertSee('2026-09-25')
            ->assertSee('data-non-academic-scholarships');

        $this->putJson(route('admin.non_academic.manual_applicants.update', ['id' => $profileId]), [
            'last_name' => 'Garcia-Santos',
            'first_name' => 'Lina',
            'middle_name' => 'Reyes',
            'suffix' => 'III',
            'sex' => 'Female',
            'course' => 'BACHELOR OF SCIENCE IN FISHERIES',
            'year_level' => '2nd Year',
            'scholarship_name' => 'Tulong Dunong Program',
            'date' => '2026-09-26',
            'application_status' => 'approved',
        ])->assertOk();

        $this->assertDatabaseHas('student_profiles', [
            'id' => $profileId,
            'last_name' => 'Garcia-Santos',
            'course' => 'BACHELOR OF SCIENCE IN FISHERIES',
            'college_department' => 'College of Fisheries and Aquatic Sciences',
            'grant_type' => 'non-academic',
            'scholarship_type' => 'Non-Academic',
            'campus' => 'Aparri',
            'created_at' => '2026-09-26 00:00:00',
            'application_status' => 'approved',
        ]);

        $this->get(route('admin.non_academic', [
            'date_from' => '2026-09-26',
            'date_to' => '2026-09-26',
            'scholarship_name' => 'Tulong Dunong Program',
            'status' => 'approved',
        ]))->assertOk()
            ->assertSee('Garcia-Santos')
            ->assertSee('Government')
            ->assertSee('2026-09-26');
    }
}
