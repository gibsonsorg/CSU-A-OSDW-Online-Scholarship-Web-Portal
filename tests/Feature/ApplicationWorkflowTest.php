<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejected_application_can_be_reapplied_with_history_preserved(): void
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
            'status' => 'rejected',
        ]);
        $this->assertSame(2, Application::where('user_id', $student->id)->where('scholarship_id', $scholarship->id)->count());
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
    }

    public function test_selected_scholarship_id_is_authoritative_for_submission(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-10001',
        ]);
        $scholarship = Scholarship::create(['name' => 'Athletic Scholarship', 'type' => 'non-academic']);

        $response = $this->actingAs($student)->post('/student-profiles', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '09123456789',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => 'Tampered Scholarship Name',
            'scholarship_type' => 'Tampered scholarship type',
        ]);

        $response->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false));
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'scholarship_name' => 'Athletic Scholarship',
            'scholarship_type' => 'Sports Excellence Program In-House',
        ]);
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
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

        $this->assertSame(1, Application::where('user_id', $student->id)->where('scholarship_id', $scholarship->id)->count());
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

    public function test_admin_academic_table_orders_by_application_creation_time(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $firstUser = User::factory()->approved()->create();
        $secondUser = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Order Scholarship', 'type' => 'academic']);
        $firstApplication = Application::create(['user_id' => $firstUser->id, 'scholarship_id' => $scholarship->id, 'status' => 'pending']);
        $secondApplication = Application::create(['user_id' => $secondUser->id, 'scholarship_id' => $scholarship->id, 'status' => 'pending']);
        $firstApplication->created_at = now()->subDay();
        $firstApplication->save();
        $secondApplication->created_at = now();
        $secondApplication->save();
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

    private function createProfile(User $user, Application $application, string $firstName): void
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
            'year_level' => '1st Year',
            'scholarship_name' => 'Order Scholarship',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
        ]);
    }
}
