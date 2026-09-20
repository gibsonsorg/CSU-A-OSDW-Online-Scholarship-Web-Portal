<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScholarshipCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_and_open_add_scholarship_page(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.scholarships.create'));

        $response->assertOk()
            ->assertSee('Add Scholarship')
            ->assertSee('Academic Grants')
            ->assertSee('Non-Academic Grants')
            ->assertSee(route('admin.scholarships.store'));
    }

    public function test_non_admin_cannot_open_add_scholarship_page(): void
    {
        $student = User::factory()->approved()->create();

        $this->actingAs($student)
            ->get(route('admin.scholarships.create'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_scholarship_with_details_and_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.scholarships.store'), [
            'type' => 'academic',
            'name' => 'Cagayan Valley Assoc. of Hawaii',
            'type_label' => 'International Educational Grant',
            'funding_type' => 'Internationally Funded',
            'scholarship_classification' => 'Academic',
            'short_information' => 'A short scholarship summary.',
            'description' => 'A detailed scholarship description.',
            'requirements' => [
                'Must be officially enrolled.',
                'Must meet the required GWA.',
            ],
            'required_documents' => ['Certificate of Enrollment', 'School ID'],
            'logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
        ]);

        $response->assertRedirect(route('admin.scholarships.create'));
        $scholarship = Scholarship::where('name', 'Cagayan Valley Assoc. of Hawaii')->firstOrFail();

        $this->assertSame('academic', $scholarship->type);
        $this->assertSame(['Must be officially enrolled.', 'Must meet the required GWA.'], $scholarship->requirements);
        $this->assertSame(['Certificate of Enrollment', 'School ID'], $scholarship->required_documents);
        Storage::disk('public')->assertExists($scholarship->logo_path);
    }

    public function test_admin_can_create_a_scholarship_with_explicit_funding_and_classification(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.scholarships.store'), [
            'type' => 'academic',
            'name' => 'Private Academic Grant',
            'type_label' => 'Academic Excellence Grant',
            'funding_type' => 'Private based',
            'scholarship_classification' => 'Academic-Merit',
            'short_information' => 'A short scholarship summary.',
            'description' => 'A detailed scholarship description.',
            'requirements' => ['Must be officially enrolled.'],
            'required_documents' => ['Certificate of Enrollment'],
        ]);

        $response->assertRedirect(route('admin.scholarships.create'));

        $scholarship = Scholarship::where('name', 'Private Academic Grant')->firstOrFail();
        $this->assertSame('Private based', $scholarship->funding_type);
        $this->assertSame('Academic-Merit', $scholarship->scholarship_classification);

        $this->actingAs(User::factory()->approved()->create())
            ->get(route('dashboard', ['section' => 'acad']))
            ->assertOk()
            ->assertSee('Private based')
            ->assertSee('Academic-Merit');
    }

    public function test_admin_can_create_a_non_academic_scholarship_with_its_own_option_values(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.scholarships.store'), [
            'type' => 'non-academic',
            'name' => 'Athletic Performance Grant',
            'type_label' => 'Sports Excellence Program',
            'funding_type' => 'In house',
            'scholarship_classification' => 'Sport-based',
            'short_information' => 'A short non-academic summary.',
            'description' => 'A detailed non-academic description.',
            'requirements' => ['Must represent the university in competitions.'],
            'required_documents' => ['Athletic certification'],
        ]);

        $response->assertRedirect(route('admin.scholarships.create'));
        $scholarship = Scholarship::where('name', 'Athletic Performance Grant')->firstOrFail();

        $this->assertSame('non-academic', $scholarship->type);
        $this->assertSame('In house', $scholarship->funding_type);
        $this->assertSame('Sport-based', $scholarship->scholarship_classification);

        $this->actingAs(User::factory()->approved()->create())
            ->get(route('dashboard', ['section' => 'non']))
            ->assertOk()
            ->assertSee('Athletic Performance Grant')
            ->assertSee('In house')
            ->assertSee('Sport-based');
    }

    public function test_new_scholarship_is_rendered_in_the_matching_student_category(): void
    {
        $student = User::factory()->approved()->create();
        Scholarship::create([
            'name' => 'Dynamic Academic Grant',
            'type' => 'academic',
            'type_label' => 'Academic Excellence Grant',
            'funding_type' => 'Internationally Funded',
            'scholarship_classification' => 'Academic',
            'short_information' => 'Created from the admin dashboard.',
            'description' => 'Detailed information.',
            'requirements' => ['Must be enrolled.'],
            'required_documents' => ['School ID'],
        ]);

        $this->actingAs($student)
            ->get(route('dashboard', ['section' => 'acad']))
            ->assertOk()
            ->assertSee('Dynamic Academic Grant')
            ->assertSee('Created from the admin dashboard.')
            ->assertSee('Internationally Funded')
            ->assertSee('Academic');
    }

    public function test_management_supports_search_and_category_filtering(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        Scholarship::create([
            'name' => 'Academic Search Grant',
            'type' => 'academic',
            'type_label' => 'Merit Grant',
            'short_information' => 'Academic summary',
            'description' => 'Academic details',
            'requirements' => ['Enrolled'],
            'status' => 'active',
        ]);
        Scholarship::create([
            'name' => 'Community Grant',
            'type' => 'non-academic',
            'type_label' => 'Community Service',
            'short_information' => 'Community summary',
            'description' => 'Community details',
            'requirements' => ['Serving'],
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.scholarships.create', ['search' => 'Academic Search', 'type' => 'academic']))
            ->assertOk()
            ->assertSee('Academic Search Grant')
            ->assertDontSee('Community Grant');
    }

    public function test_delete_permanently_removes_a_scholarship_and_cascades_its_application(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create([
            'name' => 'Safe Archive Grant',
            'type' => 'academic',
            'type_label' => 'Archive Type',
            'short_information' => 'Summary',
            'description' => 'Details',
            'requirements' => ['Enrolled'],
            'status' => 'active',
        ]);
        $application = \App\Models\Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.scholarships.destroy', $scholarship))
            ->assertRedirect(route('admin.scholarships.create'));

        $this->assertDatabaseMissing('scholarships', ['id' => $scholarship->id]);
        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_admin_can_update_active_and_inactive_status_without_changing_applications(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        $student = User::factory()->approved()->create();
        $academic = Scholarship::create([
            'name' => 'Status Academic Grant',
            'type' => 'academic',
            'type_label' => 'Academic Grant',
            'short_information' => 'Summary',
            'description' => 'Details',
            'requirements' => ['Enrolled'],
            'status' => 'active',
        ]);
        $nonAcademic = Scholarship::create([
            'name' => 'Status Non-Academic Grant',
            'type' => 'non-academic',
            'type_label' => 'Community Grant',
            'short_information' => 'Summary',
            'description' => 'Details',
            'requirements' => ['Enrolled'],
            'status' => 'inactive',
        ]);
        $application = \App\Models\Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $academic->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.scholarships.status', $academic), ['status' => 'inactive'])
            ->assertOk()
            ->assertJson(['status' => 'inactive']);
        $this->assertDatabaseHas('scholarships', ['id' => $academic->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'approved']);

        $this->actingAs($admin)
            ->patchJson(route('admin.scholarships.status', $nonAcademic), ['status' => 'active'])
            ->assertOk()
            ->assertJson(['status' => 'active']);
        $this->assertDatabaseHas('scholarships', ['id' => $nonAcademic->id, 'status' => 'active']);
    }

    public function test_inactive_scholarship_remains_visible_on_the_student_dashboard_and_shows_unavailable_state(): void
    {
        $student = User::factory()->approved()->create();

        Scholarship::create([
            'name' => 'Archived Student Scholarship',
            'type' => 'academic',
            'type_label' => 'Academic Excellence Grant',
            'funding_type' => 'In-house',
            'scholarship_classification' => 'Academic',
            'short_information' => 'Archived scholarship summary.',
            'description' => 'Archived scholarship details.',
            'requirements' => ['Must be enrolled.'],
            'status' => 'inactive',
        ]);

        $this->actingAs($student)
            ->get(route('dashboard', ['section' => 'acad']))
            ->assertOk()
            ->assertSee('Archived Student Scholarship')
            ->assertSee('UNAVAILABLE');
    }

    public function test_inactive_scholarship_cannot_receive_a_new_application(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create([
            'name' => 'Closed Scholarship',
            'type' => 'non-academic',
            'type_label' => 'Community Support Grant',
            'funding_type' => 'Private',
            'scholarship_classification' => 'Leadership',
            'short_information' => 'Closed scholarship summary.',
            'description' => 'Closed scholarship details.',
            'requirements' => ['Must be enrolled.'],
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($student)->post(route('student-profiles.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '3rd Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Community Support Grant',
            'grant_type' => 'non-academic',
            'home_address' => 'Sample address',
            'contact_number' => '09123456789',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This scholarship is currently unavailable and is not accepting new applications.');
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('student_profiles', 0);
    }
}
