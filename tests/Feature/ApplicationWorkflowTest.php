<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_athletic_scholarship_requires_sports_coordinator_and_adviser_documents(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-11942',
        ]);
        $scholarship = Scholarship::create(['name' => 'Athletic Scholarship', 'type' => 'non-academic']);

        $response = $this->actingAs($student)->from(route('dashboard', ['section' => 'non']))->post('/student-profiles', [
            'first_name' => 'Athlete',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => 'Athletic Scholarship',
            'scholarship_type' => 'Sports Excellence Program In-House',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Certificate from Sports Coordinator', session('error'));
        $this->assertStringContainsString('Certificate of Adviser', session('error'));
    }

    public function test_rejected_application_can_be_reopened_in_place(): void
    {
        Mail::fake();
        $student = User::factory()->approved()->create([
            'student_id' => '23-11941',
        ]);
        $scholarship = Scholarship::create(['name' => 'Merit Scholarship', 'type' => 'academic']);
        $oldApplication = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $oldApplication->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $this->assertDatabaseHas('applications', [
            'id' => $oldApplication->id,
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
    }

    public function test_non_academic_family_background_rejection_reopens_only_that_section(): void
    {
        $student = User::factory()->approved()->create(['student_id' => '23-11941']);
        $scholarship = Scholarship::create(['name' => 'Family Reapplication Scholarship', 'type' => 'non-academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Approved First',
            'last_name' => 'Approved Last',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'contact_number' => '+63 9123456789',
            'home_address' => 'Approved Address',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'grant_type' => 'non-academic',
            'application_status' => 'rejected',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
            'family_background_status' => 'rejected',
            'family_background_reason' => 'Update the family details.',
        ]);
        $profile->familyBackground()->create([
            'mother_name' => 'Old Mother',
            'father_name' => 'Old Father',
            'monthly_income' => 15000,
            'siblings' => [['name' => 'Old Sibling', 'age' => 10]],
        ]);

        $this->actingAs($student)->get('/dashboard?section=non')
            ->assertOk()
            ->assertSee('family_background_status', false)
            ->assertSee('Old Mother');

        $response = $this->actingAs($student)->post('/student-profiles', [
            'student_profile_id' => $profile->id,
            'application_id' => $application->id,
            'first_name' => 'Tampered First',
            'last_name' => 'Tampered Last',
            'sex' => 'Male',
            'status' => 'Married',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'course' => 'Different Course',
            'year_level' => '3rd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'birthdate' => '2001-02-02',
            'birthplace' => 'New Place',
            'contact_number' => '987654321',
            'home_address' => 'Tampered Address',
            'family_background' => [
                'mother_name' => 'Updated Mother',
                'father_name' => 'Updated Father',
                'monthly_income' => 20000,
                'siblings' => [['name' => 'Updated Sibling', 'age' => 12]],
            ],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $fresh = $profile->fresh();
        $family = $fresh->familyBackground;
        $this->assertSame('Approved First', $fresh->first_name);
        $this->assertSame('BSIT', $fresh->course);
        $this->assertSame('Approved Address', $fresh->home_address);
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('approved', $fresh->personal_information_status);
        $this->assertSame('approved', $fresh->scholarship_requirements_status);
        $this->assertSame('pending', $fresh->family_background_status);
        $this->assertNull($fresh->family_background_reason);
        $this->assertSame('pending', $fresh->application_status);
        $this->assertSame('Updated Mother', $family->mother_name);
        $this->assertSame('Updated Father', $family->father_name);
        $this->assertSame('20000.00', $family->monthly_income);
        $this->assertSame('Updated Sibling', $family->siblings[0]['name']);
    }

    public function test_approved_family_background_is_preserved_when_another_section_is_reopened(): void
    {
        $student = User::factory()->approved()->create(['student_id' => '23-11942']);
        $scholarship = Scholarship::create(['name' => 'Preserved Family Scholarship', 'type' => 'non-academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Original First',
            'last_name' => 'Original Last',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'contact_number' => '+63 9123456789',
            'home_address' => 'Original Address',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'grant_type' => 'non-academic',
            'application_status' => 'rejected',
            'student_information_status' => 'approved',
            'personal_information_status' => 'rejected',
            'scholarship_requirements_status' => 'approved',
            'family_background_status' => 'approved',
        ]);
        $profile->familyBackground()->create([
            'mother_name' => 'Approved Mother',
            'father_name' => 'Approved Father',
            'monthly_income' => 18000,
            'siblings' => [['name' => 'Approved Sibling', 'age' => 14]],
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'student_profile_id' => $profile->id,
            'application_id' => $application->id,
            'first_name' => 'Tampered First',
            'last_name' => 'Tampered Last',
            'sex' => 'Male',
            'status' => 'Married',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'course' => 'Different Course',
            'year_level' => '3rd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'birthdate' => '2001-02-02',
            'birthplace' => 'New Place',
            'contact_number' => '987654321',
            'home_address' => 'Updated Address',
            'family_background' => [
                'mother_name' => 'Tampered Mother',
                'father_name' => 'Tampered Father',
                'monthly_income' => 1,
            ],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $fresh = $profile->fresh();
        $family = $fresh->familyBackground;
        $this->assertSame('Original First', $fresh->first_name);
        $this->assertSame('Updated Address', $fresh->home_address);
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('pending', $fresh->personal_information_status);
        $this->assertSame('approved', $fresh->scholarship_requirements_status);
        $this->assertSame('approved', $fresh->family_background_status);
        $this->assertSame('Approved Mother', $family->mother_name);
        $this->assertSame('Approved Father', $family->father_name);
        $this->assertSame('18000.00', $family->monthly_income);
        $this->assertSame('Approved Sibling', $family->siblings[0]['name']);
    }

    public function test_admin_delete_removes_the_reapply_block_for_student_submitted_applications(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);
        $student = User::factory()->approved()->create([
            'student_id' => '23-11943',
        ]);
        $scholarship = Scholarship::create(['name' => 'Community Service Scholarship', 'type' => 'non-academic']);

        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Delete',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'grant_type' => 'non-academic',
            'application_status' => 'pending',
            'home_address' => 'Aparri',
            'contact_number' => '09123456789',
        ]);

        $deleteResponse = $this->actingAs($admin)->postJson('/admin/applications/' . $profile->id . '/delete');
        $deleteResponse->assertOk();

        $this->assertSoftDeleted('student_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('applications', ['id' => $application->id]);

        $response = $this->actingAs($student)->from(route('dashboard', ['section' => 'non']))->post('/student-profiles', [
            'first_name' => 'Delete',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_selected_scholarship_id_is_authoritative_for_submission(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-10001',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Athletic Scholarship',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->from(route('dashboard', ['section' => 'non']))->post('/student-profiles', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => 'Tampered Scholarship Name',
            'scholarship_type' => 'Tampered scholarship type',
            'uploads' => [UploadedFile::fake()->create('sports.pdf', 512, 'application/pdf')],
            'certificate_of_adviser' => UploadedFile::fake()->create('adviser.pdf', 512, 'application/pdf'),
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'scholarship_name' => 'Athletic Scholarship',
            'scholarship_type' => 'Sports Excellence Program In-House',
            'campus' => 'Aparri',
        ]);
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
    }

    public function test_student_submission_rejects_invalid_id_phone_and_birthdate_formats(): void
    {
        $student = User::factory()->approved()->create(['student_id' => '23-10009']);
        $scholarship = Scholarship::create(['name' => 'Validation Scholarship', 'type' => 'non-academic']);
        $payload = [
            'first_name' => 'Validation',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '2311978',
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
        ];

        $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $payload)
            ->assertSessionHasErrors('student_id_number');

        $payload['student_id_number'] = '23-11978';
        $payload['contact_number'] = '09123456789';
        $this->from('/dashboard')->post('/student-profiles', $payload)
            ->assertSessionHasErrors('contact_number');

        $payload['contact_number'] = '123456789';
        $payload['birthdate'] = '2011-01-01';
        $this->from('/dashboard')->post('/student-profiles', $payload)
            ->assertSessionHasErrors('birthdate');
    }

    public function test_academic_submission_still_requires_program_and_religion_with_birthday_present(): void
    {
        $student = User::factory()->approved()->create(['student_id' => '23-10011']);
        $scholarship = Scholarship::create(['name' => 'Academic Validation Scholarship', 'type' => 'academic']);

        $this->actingAs($student)->from('/dashboard')->post('/student-profiles', [
            'first_name' => 'Academic',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-10011',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'contact_number' => '123456789',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
        ])->assertSessionHasErrors(['program', 'religion']);
    }

    public function test_student_dashboard_renders_id_phone_birthday_and_non_academic_personal_fields(): void
    {
        $student = User::factory()->approved()->create(['student_id' => '23-10010']);
        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'maxlength="8"'));
        $this->assertSame(2, substr_count($html, 'pattern="\d{2}-\d{5}"'));
        $this->assertSame(2, substr_count($html, 'max="2010-12-31"'));
        $this->assertSame(2, substr_count($html, 'maxlength="9"'));
        $this->assertSame(2, substr_count($html, 'pattern="\d{9}"'));
        $this->assertSame(2, substr_count($html, '+63 9</span>'));
        $this->assertStringContainsString('name="age" readonly', $html);

        preg_match('/<div class="apply-row academic-step-panel" data-non-academic-step="2">(.*?)<div class="apply-row academic-step-panel" data-non-academic-step="3">/s', $html, $matches);
        $stepTwo = $matches[1] ?? '';
        $labels = ['Sex', 'Status', 'Contact number', 'Birthday', 'Birthplace', 'Age', 'Home address'];
        $previousPosition = -1;
        foreach ($labels as $label) {
            $position = strpos($stepTwo, '<label>' . $label . '</label>');
            $this->assertNotFalse($position, 'Missing Non-Academic Step 2 field: ' . $label);
            $this->assertGreaterThan($previousPosition, $position);
            $previousPosition = $position;
        }
    }

    public function test_family_background_is_an_optional_fourth_non_academic_step_only(): void
    {
        $student = User::factory()->approved()->create(['student_id' => '23-10012']);
        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $html = $response->getContent();
        preg_match_all('/class="apply-row academic-step-panel(?: active)?" data-non-academic-step="(\d+)"/', $html, $nonAcademicSteps);
        preg_match_all('/class="apply-row academic-step-panel(?: active)?" data-academic-step="(\d+)"/', $html, $academicSteps);
        $this->assertSame(['1', '2', '4'], $nonAcademicSteps[1]);
        $this->assertSame(['1', '2', '3'], $academicSteps[1]);
        $this->assertStringContainsString('Step <span id="nonAcademicStepCounter">1</span> of 4', $html);
        $this->assertStringContainsString('Step <span id="academicStepCounter">1</span> of 3:', $html);
        $this->assertStringContainsString('Family Background', $html);
        $this->assertStringContainsString('id="familySiblingsBody"></tbody>', $html);
        $this->assertStringContainsString('id="addFamilySibling"', $html);
        $this->assertStringContainsString('data-family-background-page="1"', $html);
        $this->assertStringContainsString('data-family-background-page="2"', $html);
        $this->assertStringContainsString('data-family-background-page="3"', $html);
        $this->assertStringContainsString('id="familyBackgroundNext">Next →</button>', $html);
        $this->assertStringNotContainsString('familyBackgroundPrevious', $html);
        $this->assertStringNotContainsString('familyBackgroundPageCounter', $html);
        $this->assertStringContainsString('id="familyDisabilityDetails" hidden', $html);

        preg_match('/<div class="family-background-step academic-step-panel" data-non-academic-step="3">(.*?)<div class="apply-row academic-step-panel" data-non-academic-step="4">/s', $html, $familyStepMatches);
        $familyStep = $familyStepMatches[1] ?? '';
        $this->assertNotSame('', $familyStep);
        $this->assertStringNotContainsString('required', $familyStep);
        $this->assertLessThan(strpos($familyStep, '<legend>FATHER</legend>'), strpos($familyStep, '<legend>MOTHER</legend>'));
        $this->assertLessThan(
            strpos($familyStep, 'name="family_background[mother_age]"'),
            strpos($familyStep, 'name="family_background[mother_birthdate]"')
        );
        $this->assertLessThan(
            strpos($familyStep, 'name="family_background[father_age]"'),
            strpos($familyStep, 'name="family_background[father_birthdate]"')
        );
        $this->assertStringContainsString('name="family_background[mother_age]" placeholder="Age" readonly', $familyStep);
        $this->assertStringContainsString('name="family_background[father_age]" placeholder="Age" readonly', $familyStep);
        $this->assertSame(2, substr_count($familyStep, 'class="office-address-desktop-note"'));
        $this->assertSame(2, substr_count($familyStep, 'class="office-address-mobile-note"'));
        foreach ([
            'Last Name, First Name, M.I.',
            'placeholder="Age"',
            'Complete address',
            'Current job/profession',
            'Company address (if applicable)',
            'e.g. College Graduate, High School',
            '+63 9XX XXX XXXX',
            'example@email.com',
            '₱ Amount',
            'Type here...',
        ] as $placeholder) {
            $this->assertStringContainsString($placeholder, $familyStep);
        }
        $this->assertStringNotContainsString('Number of Siblings', $html);
        $this->assertStringNotContainsString('List all brothers and sisters.', $html);
        $this->assertFileExists(public_path('images/delete.png'));
        $this->assertStringContainsString('src="${siblingDeleteIconUrl}"', file_get_contents(public_path('js/users.js')));
    }

    public function test_socio_cultural_submission_requires_uploaded_document(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-20002',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Socio-Cultural Scholarship',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->from(route('dashboard', ['section' => 'non']))->post('/student-profiles', [
            'first_name' => 'Socio',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'scholarship_type' => 'Arts & Culture Program In-House',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Required documents', session('error'));
        $this->assertDatabaseMissing('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
        ]);
    }

    public function test_socio_cultural_submission_succeeds_when_required_document_is_uploaded(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-20003',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Socio-Cultural Scholarship',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->from(route('dashboard', ['section' => 'non']))->post('/student-profiles', [
            'first_name' => 'Socio',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'scholarship_type' => 'Arts & Culture Program In-House',
            'uploads' => [UploadedFile::fake()->create('socio.pdf', 512, 'application/pdf')],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'grant_type' => 'non-academic',
            'birthdate' => '2000-01-01 00:00:00',
            'birthplace' => 'Aparri',
            'contact_number' => '+63 9123456789',
        ]);
        $profile = StudentProfile::where('user_id', $student->id)->firstOrFail();
        $this->assertDatabaseMissing('family_backgrounds', ['student_profile_id' => $profile->id]);
    }

    public function test_tulong_dunong_program_uses_existing_database_record(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-20001',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Tulong Dunong Program',
            'type' => 'academic',
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->from(route('dashboard', ['section' => 'acad']))->post('/student-profiles', [
            'first_name' => 'Tulong',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => 'Tulong Dunong Program',
            'scholarship_type' => 'Kuya Win Scholarship Program',
            'program' => 'Information Technology',
            'id_number' => '23-20001',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'Catholic',
            'uploads' => [UploadedFile::fake()->create('grades.pdf', 512, 'application/pdf')],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'scholarship_name' => 'Tulong Dunong Program',
            'scholarship_type' => 'Kuya Win Scholarship Program',
        ]);
    }

    public function test_rejected_section_can_be_resubmitted_without_resetting_approved_section(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Section Review Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Old',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'student_information_status' => 'rejected',
            'personal_information_status' => 'approved',
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Corrected',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-11941',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $fresh = $profile->fresh();
        $this->assertSame($profile->id, $fresh->id);
        $this->assertSame('Corrected', $fresh->first_name);
        $this->assertSame('pending', $fresh->student_information_status);
        $this->assertSame('approved', $fresh->personal_information_status);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_reapplication_updates_rejected_sections_and_keeps_approved_requirements_pending(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Exact Reapply Scenario', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'OLD NAME',
            'middle_name' => 'MIDDLE',
            'last_name' => 'LAST',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'OLD COURSE',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Old City',
            'religion' => 'Catholic',
            'contact_number' => '09123456789',
            'home_address' => 'Old Address',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'student_information_status' => 'rejected',
            'personal_information_status' => 'rejected',
            'scholarship_requirements_status' => 'approved',
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'NEW NAME',
            'middle_name' => 'NEW MIDDLE',
            'last_name' => 'LAST',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-11941',
            'course' => 'NEW COURSE',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2001-02-02',
            'birthplace' => 'New City',
            'religion' => 'Protestant',
            'contact_number' => '987654321',
            'home_address' => 'New Address',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));

        $fresh = $profile->fresh();
        $this->assertSame('NEW NAME', $fresh->first_name);
        $this->assertSame('NEW COURSE', $fresh->course);
        $this->assertSame('2nd Year', $fresh->year_level);
        $this->assertSame('pending', $fresh->student_information_status);
        $this->assertSame('New Address', $fresh->home_address);
        $this->assertSame('pending', $fresh->personal_information_status);
        $this->assertSame('approved', $fresh->scholarship_requirements_status);
        $this->assertSame('pending', $fresh->application_status);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_stale_application_rejection_does_not_reopen_reapply_when_sections_are_currently_approved(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Stale Rejection Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Approved',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'contact_number' => '09123456789',
            'home_address' => 'Aparri',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'scholarship_requirements_status' => 'approved',
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('APPLY NOW');
        $this->assertStringNotContainsString("reopen\":true", $response->getContent());
    }

    public function test_reapplication_updates_the_authoritative_profile_and_admin_reads_same_record(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Authoritative Profile Scholarship', 'type' => 'academic']);

        $currentApplication = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);

        $currentProfile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $currentApplication->id,
            'first_name' => 'CURRENT NAME',
            'middle_name' => 'MIDDLE',
            'last_name' => 'LAST',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'OLD COURSE',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Old Place',
            'religion' => 'Catholic',
            'contact_number' => '09123456789',
            'home_address' => 'Old Address',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'student_information_status' => 'rejected',
            'personal_information_status' => 'rejected',
            'scholarship_requirements_status' => 'approved',
        ]);

        $staleProfile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $currentApplication->id,
            'first_name' => 'STALE NAME',
            'middle_name' => 'STALE',
            'last_name' => 'LAST',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'STALE COURSE',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '1999-01-01',
            'birthplace' => 'Stale Place',
            'religion' => 'Christian',
            'contact_number' => '987654321',
            'home_address' => 'Stale Address',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'student_information_status' => 'rejected',
            'personal_information_status' => 'rejected',
            'scholarship_requirements_status' => 'approved',
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'application_id' => $currentApplication->id,
            'student_profile_id' => $currentProfile->id,
            'first_name' => 'NEW NAME',
            'middle_name' => 'NEW MIDDLE',
            'last_name' => 'LAST',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => '23-11941',
            'course' => 'NEW COURSE',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2001-02-02',
            'birthplace' => 'New Place',
            'religion' => 'Protestant',
            'contact_number' => '987654321',
            'home_address' => 'New Address',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));

        $this->assertSame('NEW NAME', $currentProfile->fresh()->first_name);
        $this->assertSame('STALE NAME', $staleProfile->fresh()->first_name);
        $this->assertSame('pending', $currentProfile->fresh()->student_information_status);
        $this->assertSame('pending', $currentProfile->fresh()->personal_information_status);
        $this->assertSame('approved', $currentProfile->fresh()->scholarship_requirements_status);
        $this->assertSame('pending', $currentProfile->fresh()->application_status);

        $adminUser = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
        ]);

        $adminResponse = $this->actingAs($adminUser)
            ->get('/admin/applications/' . $currentProfile->fresh()->id);

        $adminResponse->assertOk();
        $this->assertStringContainsString('NEW NAME', $adminResponse->getContent());
        $this->assertStringContainsString('New Address', $adminResponse->getContent());
        $this->assertStringContainsString('pending', strtolower($adminResponse->getContent()));
        $this->assertStringContainsString('approved', strtolower($adminResponse->getContent()));
    }

    public function test_reapplication_preserves_approved_section_and_document_against_submitted_changes(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('student_profiles/approved-grades.pdf', 'approved grades');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Protected Reapplication Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        $documentReviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Original',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'student_information_status' => 'approved',
            'personal_information_status' => 'rejected',
            'scholarship_requirements_status' => 'approved',
            'uploads' => ['certificate_of_grades' => ['student_profiles/approved-grades.pdf']],
            $documentReviewColumn => [
                'certificate_of_grades.0' => ['status' => 'approved', 'reason' => null],
            ],
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Tampered',
            'last_name' => 'Changed',
            'sex' => 'Male',
            'status' => 'Married',
            'email' => $student->email,
            'student_id_number' => '23-11941',
            'course' => 'Different Course',
            'year_level' => '4th Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Tampered Type',
            'program' => 'Tampered Program',
            'id_number' => 'tampered-id',
            'birthdate' => '2001-01-01',
            'birthplace' => 'Different Place',
            'religion' => 'Different Religion',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));

        $fresh = $profile->fresh();
        $this->assertSame('Original', $fresh->first_name);
        $this->assertSame('Male', $fresh->sex);
        $this->assertSame('BSIT', $fresh->course);
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('pending', $fresh->personal_information_status);
        $this->assertSame('approved', $fresh->scholarship_requirements_status);
        $this->assertSame('student_profiles/approved-grades.pdf', $fresh->uploads['certificate_of_grades'][0]);
        $this->assertSame('approved', $fresh->{$documentReviewColumn}['certificate_of_grades.0']['status']);
        $this->assertSame('pending', $fresh->application_status);
    }

    public function test_rejected_document_can_be_replaced_without_resubmitting_approved_document(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('student_profiles/old-enrollment.pdf', 'old enrollment');
        Storage::disk('local')->put('student_profiles/old-grades.pdf', 'approved grades');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Document Review Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $documentReviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
        $profileData = [
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'uploads' => ['certificate_of_grades' => ['old-grades.pdf'], 'certificate_of_enrollment' => ['old-enrollment.pdf']],
        ];
        $profileData[$documentReviewColumn] = [
                'certificate_of_grades.0' => ['status' => 'approved', 'reason' => null],
                'certificate_of_enrollment.0' => ['status' => 'rejected', 'reason' => 'Wrong semester.'],
        ];
        $profile = StudentProfile::create($profileData);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Test', 'last_name' => 'Student', 'sex' => 'Female', 'status' => 'Single',
            'email' => $student->email, 'course' => 'BSIT', 'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id, 'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic', 'program' => 'Information Technology',
            'id_number' => '23-11941', 'birthdate' => '2000-01-01', 'birthplace' => 'Aparri', 'religion' => 'None',
            'certificate_of_enrollment' => UploadedFile::fake()->create('new-enrollment.pdf', 10, 'application/pdf'),
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $fresh = $profile->fresh();
        $this->assertCount(1, $fresh->uploads['certificate_of_grades']);
        $this->assertCount(1, $fresh->uploads['certificate_of_enrollment']);
        $documentReviews = $fresh->{$documentReviewColumn};
        $this->assertSame('approved', $documentReviews['certificate_of_grades.0']['status']);
        $this->assertSame('pending', $documentReviews['certificate_of_enrollment.0']['status']);
        $this->assertNull($documentReviews['certificate_of_enrollment.0']['reason']);
        $this->assertNotSame('student_profiles/old-enrollment.pdf', $fresh->uploads['certificate_of_enrollment'][0]);
        $this->assertTrue(Storage::disk('local')->exists($fresh->uploads['certificate_of_enrollment'][0]));

        $admin = User::factory()->approved()->create(['role' => 0]);
        $adminView = $this->actingAs($admin)->getJson('/admin/applications/' . $fresh->id);
        $adminView->assertOk();
        $this->assertSame(
            $fresh->uploads['certificate_of_enrollment'][0],
            $adminView->json('uploads.certificate_of_enrollment.0')
        );
        $adminReviews = $adminView->json('document_review') ?? $adminView->json('document_reviews');
        $this->assertSame('pending', $adminReviews['certificate_of_enrollment.0']['status']);

        $documentView = $this->actingAs($admin)->get(
            '/admin/applications/' . $fresh->id . '/document/certificate_of_enrollment/0'
        );
        $documentView->assertOk();
        $documentView->assertHeader('content-disposition', 'inline; filename="' . basename($fresh->uploads['certificate_of_enrollment'][0]) . '"');
    }

    public function test_legacy_uploads_group_is_replaced_in_the_same_current_document_slot(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('student_profiles/legacy-old.pdf', 'old');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Legacy Upload Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $documentReviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
        $profileData = [
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'uploads' => ['legacy-old.pdf'],
        ];
        $profileData[$documentReviewColumn] = [
            'uploads.0' => ['status' => 'rejected', 'reason' => 'Wrong file.'],
        ];
        $profile = StudentProfile::create($profileData);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Test', 'last_name' => 'Student', 'sex' => 'Female', 'status' => 'Single',
            'email' => $student->email, 'course' => 'BSIT', 'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id, 'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic', 'program' => 'Information Technology',
            'id_number' => '23-11941', 'birthdate' => '2000-01-01', 'birthplace' => 'Aparri', 'religion' => 'None',
            'uploads' => [UploadedFile::fake()->create('legacy-new.pdf', 10, 'application/pdf')],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $fresh = $profile->fresh();
        $this->assertCount(1, $fresh->uploads['uploads']);
        $this->assertNotSame('legacy-old.pdf', $fresh->uploads['uploads'][0]);
        $this->assertSame('pending', $fresh->{$documentReviewColumn}['uploads.0']['status']);
    }

    public function test_rejected_document_replacement_updates_current_file_and_resets_status_to_pending(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Resubmission Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $documentReviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'uploads' => ['certificate_of_grades' => ['student_profiles/old-grades.pdf']],
            $documentReviewColumn => [
                'certificate_of_grades.0' => ['status' => 'rejected', 'reason' => 'blurred'],
            ],
        ]);

        Storage::disk('local')->put('student_profiles/old-grades.pdf', 'old');

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'uploads' => [UploadedFile::fake()->create('new-grades.pdf', 10, 'application/pdf')],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));

        $fresh = $profile->fresh();
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('approved', $fresh->personal_information_status);
        $this->assertCount(1, $fresh->uploads['certificate_of_grades']);
        $this->assertNotSame('student_profiles/old-grades.pdf', $fresh->uploads['certificate_of_grades'][0]);
        $this->assertSame('pending', $fresh->{$documentReviewColumn}['certificate_of_grades.0']['status']);
        $this->assertNull($fresh->{$documentReviewColumn}['certificate_of_grades.0']['reason']);
        $this->assertSame('pending', $fresh->application_status);

        $admin = User::factory()->approved()->create(['role' => 0]);
        $adminView = $this->actingAs($admin)->getJson('/admin/applications/' . $fresh->id);
        $adminView->assertOk();
        $adminView->assertJsonPath('uploads.certificate_of_grades.0', $fresh->uploads['certificate_of_grades'][0]);
        $adminReviews = $adminView->json($documentReviewColumn);
        $this->assertSame('pending', $adminReviews['certificate_of_grades.0']['status']);
        $adminView->assertJsonMissing(['reason' => 'blurred']);

        $this->actingAs($admin)->postJson('/admin/applications/' . $fresh->id . '/documents/certificate_of_grades/0/reject', [
            'reason' => 'Second rejection reason',
        ])->assertOk();

        $secondResponse = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'uploads' => [UploadedFile::fake()->create('second-grades.pdf', 10, 'application/pdf')],
        ]);

        $secondResponse->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $secondFresh = $profile->fresh();
        $this->assertNotSame($fresh->uploads['certificate_of_grades'][0], $secondFresh->uploads['certificate_of_grades'][0]);
        $this->assertSame('pending', $secondFresh->{$documentReviewColumn}['certificate_of_grades.0']['status']);
        $this->assertNull($secondFresh->{$documentReviewColumn}['certificate_of_grades.0']['reason']);
        $this->assertSame('pending', $secondFresh->scholarship_requirements_status);
        $this->assertSame('pending', $secondFresh->application_status);

        $adminSecondView = $this->actingAs($admin)->getJson('/admin/applications/' . $secondFresh->id);
        $adminSecondView->assertOk();
        $adminSecondView->assertJsonPath('uploads.certificate_of_grades.0', $secondFresh->uploads['certificate_of_grades'][0]);
        $adminSecondReviews = $adminSecondView->json($documentReviewColumn);
        $this->assertSame('pending', $adminSecondReviews['certificate_of_grades.0']['status']);

        $this->actingAs($admin)->postJson('/admin/applications/' . $secondFresh->id . '/documents/certificate_of_grades/0/approve')
            ->assertOk();

        $approved = $profile->fresh();
        $this->assertSame('approved', $approved->student_information_status);
        $this->assertSame('approved', $approved->personal_information_status);
        $this->assertSame('approved', $approved->scholarship_requirements_status);
        $this->assertSame('approved', $approved->application_status);
        $this->assertSame('approved', $application->fresh()->status);

        $adminDashboard = $this->actingAs($admin)->get('/admin/academic');
        $adminDashboard->assertOk()->assertSee('Approved');

        $studentDashboard = $this->actingAs($student)->get('/dashboard');
        $studentDashboard->assertOk()->assertSee('Your application for this scholarship is approved');
    }

    public function test_legacy_upload_group_replacement_keeps_current_file_and_clears_rejected_status(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Legacy Replacement Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $documentReviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Legacy',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'student_information_status' => 'approved',
            'personal_information_status' => 'approved',
            'uploads' => ['uploads' => ['student_profiles/legacy-old.pdf']],
            $documentReviewColumn => [
                'uploads.0' => ['status' => 'rejected', 'reason' => 'blurred'],
            ],
        ]);

        Storage::disk('local')->put('student_profiles/legacy-old.pdf', 'old');

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Legacy',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'program' => 'Information Technology',
            'id_number' => '23-11941',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'uploads' => [UploadedFile::fake()->create('legacy-new.pdf', 10, 'application/pdf')],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));

        $fresh = $profile->fresh();
        $this->assertSame('approved', $fresh->student_information_status);
        $this->assertSame('approved', $fresh->personal_information_status);
        $this->assertSame('pending', $fresh{$documentReviewColumn}['uploads.0']['status']);
        $this->assertNull($fresh{$documentReviewColumn}['uploads.0']['reason']);
        $this->assertNotSame('student_profiles/legacy-old.pdf', $fresh->uploads['uploads'][0]);
        $this->assertTrue(Storage::disk('local')->exists($fresh->uploads['uploads'][0]));
    }

    public function test_pending_application_cannot_be_duplicated(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Pending Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
        ]);

        }

    public function test_approved_application_cannot_be_duplicated(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Approved Scholarship', 'type' => 'academic']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'approved',
        ]);
        StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'approved',
        ]);

        $this->assertSame(1, Application::where('user_id', $student->id)->where('scholarship_id', $scholarship->id)->count());
    }

    public function test_static_academic_card_uses_existing_scholarship_id(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-21001',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'DOST SEI Project Strand',
            'type' => 'academic',
            'status' => 'active',
        ]);
        $scholarshipCount = Scholarship::count();

        $dashboard = $this->actingAs($student)->get(route('dashboard', ['section' => 'acad']));
        $dashboard->assertOk()->assertSee('data-scholarship-id="' . $scholarship->id . '"', false);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Academic',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'program' => 'Information Technology',
            'id_number' => $student->student_id,
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Graduate School Scholarship',
            'uploads' => [UploadedFile::fake()->create('grades.pdf', 512, 'application/pdf')],
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false));
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $this->assertSame($scholarshipCount, Scholarship::count());
    }

    public function test_academic_and_non_academic_admin_tables_display_funding_and_campus_metadata(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $user = User::factory()->approved()->create();

        $international = Scholarship::create(['name' => 'Cagayan Valley Assoc. of Hawaii', 'type' => 'academic', 'funding_type' => 'International']);
        $inHouse = Scholarship::create(['name' => 'University Based Scholarship', 'type' => 'academic', 'funding_type' => 'In-house']);
        $localGovt = Scholarship::create(['name' => 'ACEF – GIAHEP', 'type' => 'academic', 'funding_type' => 'Local-Government']);
        $localPrivate = Scholarship::create(['name' => 'Andres Tamayo Scholarship', 'type' => 'academic', 'funding_type' => 'Local-Private']);

        $internationalApp = Application::create(['user_id' => $user->id, 'scholarship_id' => $international->id, 'status' => 'pending']);
        $inHouseApp = Application::create(['user_id' => $user->id, 'scholarship_id' => $inHouse->id, 'status' => 'pending']);
        $localGovtApp = Application::create(['user_id' => $user->id, 'scholarship_id' => $localGovt->id, 'status' => 'pending']);
        $localPrivateApp = Application::create(['user_id' => $user->id, 'scholarship_id' => $localPrivate->id, 'status' => 'pending']);

        $this->createProfile($user, $internationalApp, 'International', $international->name);
        $this->createProfile($user, $inHouseApp, 'InHouse', $inHouse->name);
        $this->createProfile($user, $localGovtApp, 'Government', $localGovt->name);
        $this->createProfile($user, $localPrivateApp, 'Private', $localPrivate->name);

        StudentProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Manual',
            'last_name' => 'Applicant',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $user->email,
            'course' => 'BSIT',
            'campus' => 'Aparri',
            'year_level' => '1st Year',
            'scholarship_name' => 'Manual Academic',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
            'source' => 'manual',
            'campus' => 'Aparri',
        ]);

        $academicResponse = $this->actingAs($admin)->get('/admin/academic');
        $academicResponse->assertOk();
        $academicResponse->assertSee('Funding');
        $academicResponse->assertSee('International');
        $academicResponse->assertSee('In-house');
        $academicResponse->assertSee('Local-Government');
        $academicResponse->assertSee('Local-Private');
        $academicResponse->assertSee('Campus');
        $academicResponse->assertSee('Aparri');
        $academicResponse->assertSee('—');
        $this->assertMatchesRegularExpression('/<th>Funding<\/th>\s*<th>Campus<\/th>/', $academicResponse->getContent());

        $nonAcademicResponse = $this->actingAs($admin)->get('/admin/non-academic');
        $nonAcademicResponse->assertOk();
        $nonAcademicResponse->assertSee('Funding');
        $nonAcademicResponse->assertSee('Campus');
        $this->assertMatchesRegularExpression('/<th>Funding<\/th>\s*<th>Campus<\/th>/', $nonAcademicResponse->getContent());
    }

    public function test_non_academic_admin_table_displays_funding_metadata_after_scholarship_name(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $user = User::factory()->approved()->create();

        $athletic = Scholarship::create(['name' => 'Athletic Scholarship', 'type' => 'non-academic', 'funding_type' => 'In-house']);
        $tulong = Scholarship::create(['name' => 'Tulong Dunong Program', 'type' => 'non-academic', 'funding_type' => 'Government']);

        $athleticApp = Application::create(['user_id' => $user->id, 'scholarship_id' => $athletic->id, 'status' => 'pending']);
        $tulongApp = Application::create(['user_id' => $user->id, 'scholarship_id' => $tulong->id, 'status' => 'pending']);

        $this->createProfile($user, $athleticApp, 'Athlete', $athletic->name, 'non-academic');
        $this->createProfile($user, $tulongApp, 'Tulong', $tulong->name, 'non-academic');

        StudentProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Manual',
            'last_name' => 'Applicant',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $user->email,
            'course' => 'BSIT',
            'campus' => 'Aparri',
            'year_level' => '1st Year',
            'scholarship_name' => 'Manual Non-Academic',
            'scholarship_type' => 'Non-Academic',
            'grant_type' => 'non-academic',
            'application_status' => 'pending',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($admin)->get('/admin/non-academic');

        $response->assertOk();
        $response->assertSee('Funding');
        $response->assertSee('Name of Scholarship');
        $response->assertSee('In-house');
        $response->assertSee('Government');
        $response->assertSee('Campus');
        $response->assertSee('Aparri');
        $response->assertSee('—');
        $this->assertMatchesRegularExpression('/<th>Funding<\/th>\s*<th>Campus<\/th>/', $response->getContent());
    }

    public function test_manual_applicant_create_and_update_always_set_campus_to_aparri(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        Scholarship::create(['name' => 'Manual Campus Scholarship', 'type' => 'academic']);

        $payload = [
            'grant_type' => 'academic',
            'last_name' => 'Applicant',
            'first_name' => 'Manual',
            'sex' => 'Female',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => 'Manual Campus Scholarship',
            'date' => '2026-09-26',
            'application_status' => 'pending',
        ];

        $createResponse = $this->actingAs($admin)->postJson('/admin/manual-applicants', $payload);
        $createResponse->assertOk()->assertJsonPath('data.campus', 'Aparri');
        $profileId = $createResponse->json('data.id');

        $this->putJson('/admin/manual-applicants/' . $profileId, $payload)
            ->assertOk()
            ->assertJsonPath('data.campus', 'Aparri');

        $this->assertDatabaseHas('student_profiles', [
            'id' => $profileId,
            'source' => 'manual',
            'campus' => 'Aparri',
        ]);
    }

    public function test_admin_scholarship_name_filter_orders_by_submission_time_ascending(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $firstUser = User::factory()->approved()->create();
        $secondUser = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Filtered Scholarship', 'type' => 'academic']);
        $firstApplication = Application::create(['user_id' => $firstUser->id, 'scholarship_id' => $scholarship->id, 'status' => 'pending']);
        $secondApplication = Application::create(['user_id' => $secondUser->id, 'scholarship_id' => $scholarship->id, 'status' => 'pending']);
        DB::table('applications')->where('id', $firstApplication->id)->update(['created_at' => now()->subDay()]);
        DB::table('applications')->where('id', $secondApplication->id)->update(['created_at' => now()]);
        $this->createProfile($firstUser, $firstApplication, 'Earliest', 'Filtered Scholarship');
        $this->createProfile($secondUser, $secondApplication, 'Latest', 'Filtered Scholarship');

        $response = $this->actingAs($admin)->get('/admin/academic?scholarship_name=' . urlencode('Filtered Scholarship'));

        $response->assertOk();
        preg_match('/<th>Last Name<\/th>.*?<tbody>(.*?)<\/tbody>/s', $response->getContent(), $matches);
        $table = $matches[1] ?? '';
        $this->assertLessThan(
            strpos($table, '<td>Latest</td>'),
            strpos($table, '<td>Earliest</td>')
        );
    }

    public function test_admin_academic_table_orders_by_application_creation_time(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $firstUser = User::factory()->approved()->create();
        $secondUser = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Order Scholarship', 'type' => 'academic']);
        $firstApplication = Application::create(['user_id' => $firstUser->id, 'scholarship_id' => $scholarship->id, 'status' => 'pending']);
        $secondApplication = Application::create(['user_id' => $secondUser->id, 'scholarship_id' => $scholarship->id, 'status' => 'pending']);
        DB::table('applications')->where('id', $firstApplication->id)->update(['created_at' => now()->subDay()]);
        DB::table('applications')->where('id', $secondApplication->id)->update(['created_at' => now()]);
        $this->createProfile($firstUser, $firstApplication, 'Earliest');
        $this->createProfile($secondUser, $secondApplication, 'Latest');

        $response = $this->actingAs($admin)->get('/admin/academic');

        $response->assertOk();
        preg_match('/<th>Last Name<\/th>.*?<tbody>(.*?)<\/tbody>/s', $response->getContent(), $matches);
        $table = $matches[1] ?? '';
        $this->assertLessThan(
            strpos($table, '<td>Earliest</td>'),
            strpos($table, '<td>Latest</td>')
        );
    }

    private function createProfile(User $user, Application $application, string $firstName, ?string $scholarshipName = null, string $grantType = 'academic'): void
    {
        StudentProfile::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'first_name' => $firstName,
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $user->email,
            'course' => 'BSIT',
            'campus' => 'Aparri',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarshipName ?? 'Order Scholarship',
            'scholarship_type' => 'Academic',
            'grant_type' => $grantType,
            'application_status' => 'pending',
        ]);
    }
}
