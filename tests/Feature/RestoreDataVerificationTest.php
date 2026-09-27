<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\AcademicScholarshipCatalogSeeder;
use Database\Seeders\NonAcademicScholarshipCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RestoreDataVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restored_scholarship_catalog_accepts_real_submissions_and_admin_fields(): void
    {
        $this->seed(AcademicScholarshipCatalogSeeder::class);
        $this->seed(NonAcademicScholarshipCatalogSeeder::class);

        $admin = User::factory()->approved()->create(['role' => 0, 'email' => 'restore-admin@example.com']);
        $studentAcademic = User::factory()->approved()->create([
            'student_id' => '23-10001',
            'email' => 'restore-acad-student@example.com',
        ]);
        $studentNonAcademic = User::factory()->approved()->create([
            'student_id' => '23-10002',
            'email' => 'restore-non-student@example.com',
        ]);

        $academicScholarship = Scholarship::where('name', 'Tulong Dunong Program')->where('type', 'academic')->firstOrFail();
        $nonAcademicScholarship = Scholarship::where('name', 'Socio-Cultural Scholarship')->where('type', 'non-academic')->firstOrFail();

        $academicResponse = $this->actingAs($studentAcademic)
            ->from(route('dashboard', ['section' => 'acad']))
            ->post('/student-profiles', [
                'first_name' => 'Academic',
                'last_name' => 'Student',
                'middle_name' => 'Restore',
                'suffix' => 'Jr.',
                'sex' => 'Female',
                'status' => 'Single',
                'email' => $studentAcademic->email,
                'student_id_number' => $studentAcademic->student_id,
                'home_address' => 'Aparri',
                'contact_number' => '123456789',
                'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
                'college_department' => 'College of Information Technology',
                'year_level' => '2nd Year',
                'program' => 'Information Technology',
                'id_number' => $studentAcademic->student_id,
                'birthdate' => '2000-01-01',
                'birthplace' => 'Aparri',
                'religion' => 'None',
                'scholarship_id' => $academicScholarship->id,
                'scholarship_name' => $academicScholarship->name,
                'scholarship_type' => 'Kuya Win Scholarship Program',
                'uploads' => [UploadedFile::fake()->create('grades.pdf', 512, 'application/pdf')],
            ]);

        $academicResponse->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $academicResponse->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('applications', [
            'user_id' => $studentAcademic->id,
            'scholarship_id' => $academicScholarship->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $studentAcademic->id,
            'scholarship_name' => $academicScholarship->name,
            'suffix' => 'Jr.',
            'college_department' => 'College of Information and Computing Science',
        ]);

        $nonAcademicResponse = $this->actingAs($studentNonAcademic)
            ->from(route('dashboard', ['section' => 'non']))
            ->post('/student-profiles', [
                'first_name' => 'Non',
                'last_name' => 'Academic',
                'middle_name' => 'Restore',
                'suffix' => 'III',
                'sex' => 'Male',
                'status' => 'Single',
                'email' => $studentNonAcademic->email,
                'student_id_number' => $studentNonAcademic->student_id,
                'home_address' => 'Aparri',
                'contact_number' => '123456789',
                'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
                'college_department' => 'College of Information Technology',
                'year_level' => '2nd Year',
                'birthdate' => '2000-01-01',
                'birthplace' => 'Aparri',
                'scholarship_id' => $nonAcademicScholarship->id,
                'scholarship_name' => $nonAcademicScholarship->name,
                'scholarship_type' => 'Arts & Culture Program In-House',
                'family_background' => [
                    'mother_status' => 'Living',
                    'mother_name' => 'Lina Restore',
                    'mother_age' => '49',
                    'mother_birthdate' => '1977-04-18',
                    'mother_address' => 'Aparri Proper',
                    'mother_occupation' => 'Accountant',
                    'mother_office_address' => 'Municipal Hall',
                    'mother_education' => 'College Graduate',
                    'father_status' => 'Living',
                    'father_name' => 'Robert Restore',
                    'father_age' => '52',
                    'father_birthdate' => '1974-03-12',
                    'father_address' => 'Aparri',
                    'father_occupation' => 'Teacher',
                    'father_office_address' => 'CSU Aparri',
                    'father_education' => 'College Graduate',
                    'parent_contact_number' => '09123456789',
                    'parent_email' => 'parent@example.test',
                    'monthly_income' => '25000',
                    'has_differently_abled_member' => 'yes',
                    'disability_details' => 'Visual impairment',
                    'siblings' => [
                        ['name' => 'Sibling One', 'age' => '18', 'marital_status' => 'Single', 'occupation' => 'Student'],
                        ['name' => 'Sibling Two', 'age' => '22', 'marital_status' => 'Married', 'occupation' => 'Nurse'],
                    ],
                ],
                'uploads' => [UploadedFile::fake()->create('socio.pdf', 512, 'application/pdf')],
            ]);

        $nonAcademicResponse->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $nonAcademicResponse->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('applications', [
            'user_id' => $studentNonAcademic->id,
            'scholarship_id' => $nonAcademicScholarship->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $studentNonAcademic->id,
            'scholarship_name' => $nonAcademicScholarship->name,
            'suffix' => 'III',
            'college_department' => 'College of Information and Computing Science',
            'birthdate' => '2000-01-01 00:00:00',
            'birthplace' => 'Aparri',
            'contact_number' => '+63 9123456789',
        ]);

        $adminAcademicView = $this->actingAs($admin)->get('/admin/academic?scholarship_name=' . urlencode('Tulong Dunong Program'));
        $adminAcademicView->assertOk();
        $adminAcademicView->assertSee('Funding');
        $adminAcademicView->assertSee('Tulong Dunong Program');
        $adminAcademicView->assertSee('College or Department');
        $adminAcademicView->assertSee('Jr.');

        $adminNonAcademicView = $this->actingAs($admin)->get('/admin/non-academic');
        $adminNonAcademicView->assertOk();
        $adminNonAcademicView->assertSee('Socio-Cultural Scholarship');
        $adminNonAcademicView->assertSee('College or Department');
        $adminNonAcademicView->assertSee('III');

        $nonAcademicProfile = StudentProfile::where('user_id', $studentNonAcademic->id)->firstOrFail();
        $familyBackground = $nonAcademicProfile->familyBackground;
        $this->assertNotNull($familyBackground);
        $this->assertSame('Living', $familyBackground->father_status);
        $this->assertSame('Robert Restore', $familyBackground->father_name);
        $this->assertSame(52, $familyBackground->father_age);
        $this->assertSame('1974-03-12', $familyBackground->father_birthdate->format('Y-m-d'));
        $this->assertSame('Aparri', $familyBackground->father_address);
        $this->assertSame('Teacher', $familyBackground->father_occupation);
        $this->assertSame('CSU Aparri', $familyBackground->father_office_address);
        $this->assertSame('College Graduate', $familyBackground->father_education);
        $this->assertSame('09123456789', $familyBackground->parent_contact_number);
        $this->assertSame('parent@example.test', $familyBackground->parent_email);
        $this->assertSame(25000.0, (float) $familyBackground->monthly_income);
        $this->assertTrue($familyBackground->has_differently_abled_member);
        $this->assertSame('Visual impairment', $familyBackground->disability_details);
        $this->assertCount(2, $familyBackground->siblings);
        $this->assertSame('Sibling One', $familyBackground->siblings[0]['name']);
        $this->assertSame('Nurse', $familyBackground->siblings[1]['occupation']);

        $nonAcademicPrefill = $this->actingAs($studentNonAcademic)
            ->getJson('/student-application-prefill?grant_type=non-academic&scholarship_id=' . ($nonAcademicScholarship->id + 1) . '&scholarship_name=Different%20Non-Academic%20Scholarship');
        $nonAcademicPrefill->assertOk()
            ->assertJsonPath('data.birthplace', 'Aparri');
        $this->assertStringContainsString('2000-01-01', $nonAcademicPrefill->json('data.birthdate'));
        $nonAcademicPrefill->assertJsonPath('data.family_background.mother_name', 'Lina Restore')
            ->assertJsonPath('data.family_background.mother_status', 'Living')
            ->assertJsonPath('data.family_background.mother_age', 49)
            ->assertJsonPath('data.family_background.mother_birthdate', '1977-04-18')
            ->assertJsonPath('data.family_background.mother_address', 'Aparri Proper')
            ->assertJsonPath('data.family_background.mother_occupation', 'Accountant')
            ->assertJsonPath('data.family_background.mother_office_address', 'Municipal Hall')
            ->assertJsonPath('data.family_background.mother_education', 'College Graduate')
            ->assertJsonPath('data.family_background.father_name', 'Robert Restore')
            ->assertJsonPath('data.family_background.parent_contact_number', '09123456789')
            ->assertJsonPath('data.family_background.parent_email', 'parent@example.test')
            ->assertJsonPath('data.family_background.monthly_income', '25000.00')
            ->assertJsonPath('data.family_background.has_differently_abled_member', true)
            ->assertJsonPath('data.family_background.disability_details', 'Visual impairment')
            ->assertJsonPath('data.family_background.siblings.0.name', 'Sibling One')
            ->assertJsonPath('data.family_background.siblings.1.name', 'Sibling Two');

        $sameScholarshipPrefill = $this->actingAs($studentNonAcademic)
            ->getJson('/student-application-prefill?grant_type=non-academic&scholarship_id=' . $nonAcademicScholarship->id . '&scholarship_name=' . urlencode($nonAcademicScholarship->name));
        $sameScholarshipPrefill->assertOk()->assertJsonPath('data', null);

        $applicantInfo = $this->actingAs($admin)->getJson('/admin/applications/' . $nonAcademicProfile->id);
        $applicantInfo->assertOk()->assertJsonPath('birthplace', 'Aparri');
        $this->assertStringContainsString('2000-01-01', $applicantInfo->json('birthdate'));
        $applicantInfo->assertJsonPath('family_background.father_name', 'Robert Restore')
            ->assertJsonPath('family_background.has_differently_abled_member', true)
            ->assertJsonPath('family_background.siblings.1.name', 'Sibling Two');
    }
}
