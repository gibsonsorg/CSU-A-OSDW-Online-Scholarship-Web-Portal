<?php

namespace Tests\Feature;

use App\Mail\ApplicationSectionStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScholarshipAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_academic_cards_match_static_scholarships_and_follow_the_two_application_flow(): void
    {
        Mail::fake();
        Storage::fake('local');
        $student = User::factory()->approved()->create(['student_id' => '23-12001']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $firstScholarship = Scholarship::create([
            'name' => 'Talamayan Scholarship',
            'type' => 'non-academic',
            'status' => 'active',
        ]);
        $secondScholarship = Scholarship::create([
            'name' => "Employees' Grant",
            'type' => 'non-academic',
            'status' => 'active',
        ]);
        $thirdScholarship = Scholarship::create([
            'name' => 'Student Council Federation Grant',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $this->submit($student, $firstScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();

        $firstProfile = StudentProfile::where('user_id', $student->id)
            ->where('application_id', Application::where('user_id', $student->id)->where('scholarship_id', $firstScholarship->id)->value('id'))
            ->firstOrFail();
        $this->assertDashboardState($student, 1, [
            $firstScholarship->id => 'pending',
        ], [
            'Talamayan Scholarship' => $firstScholarship->id,
            "Employees' Grant" => $secondScholarship->id,
            'Student Council Federation Grant' => $thirdScholarship->id,
        ]);

        $this->submit($student, $secondScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $secondProfile = StudentProfile::where('user_id', $student->id)
            ->where('application_id', Application::where('user_id', $student->id)->where('scholarship_id', $secondScholarship->id)->value('id'))
            ->firstOrFail();

        $this->assertDashboardState($student, 2, [
            $firstScholarship->id => 'pending',
            $secondScholarship->id => 'pending',
        ], [
            'Talamayan Scholarship' => $firstScholarship->id,
            "Employees' Grant" => $secondScholarship->id,
            'Student Council Federation Grant' => $thirdScholarship->id,
        ]);

        $thirdSubmission = $this->submit($student, $thirdScholarship);
        $thirdSubmission->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionHas('error', 'You already have two active applications, so this scholarship is not available.');

        $this->actingAs($admin)->postJson("/admin/applications/{$firstProfile->id}/sections/student-information/reject", [
            'reason' => 'Please correct the submitted information.',
        ])->assertOk();
        Mail::assertNotQueued(ApplicationSectionStatusMail::class);

        $this->assertDashboardState($student, 1, [
            $firstScholarship->id => 'rejected',
            $secondScholarship->id => 'pending',
        ], [
            'Talamayan Scholarship' => $firstScholarship->id,
            "Employees' Grant" => $secondScholarship->id,
            'Student Council Federation Grant' => $thirdScholarship->id,
        ]);

        $this->submit($student, $firstScholarship, $firstProfile)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('pending', $firstProfile->fresh()->application_status);
        $this->assertDashboardState($student, 2, [
            $firstScholarship->id => 'pending',
            $secondScholarship->id => 'pending',
        ], [
            'Talamayan Scholarship' => $firstScholarship->id,
            "Employees' Grant" => $secondScholarship->id,
            'Student Council Federation Grant' => $thirdScholarship->id,
        ]);

        $this->submit($student, $thirdScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionHas('error', 'You already have two active applications, so this scholarship is not available.');
    }

    public function test_academic_limit_and_duplicate_scholarship_names_remain_scoped_by_id_and_grant_type(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create(['student_id' => '23-12002']);
        $academicTulongDunong = Scholarship::create([
            'name' => 'Tulong Dunong Program',
            'type' => 'academic',
            'status' => 'active',
        ]);
        $nonAcademicTulongDunong = Scholarship::create([
            'name' => 'Tulong Dunong Program',
            'type' => 'non-academic',
            'status' => 'active',
        ]);
        $secondAcademicScholarship = Scholarship::create([
            'name' => 'Second Academic Scholarship',
            'type' => 'academic',
            'status' => 'active',
        ]);
        $thirdAcademicScholarship = Scholarship::create([
            'name' => 'Third Academic Scholarship',
            'type' => 'academic',
            'status' => 'active',
        ]);

        $this->submitAcademic($student, $academicTulongDunong)
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $this->submitAcademic($student, $secondAcademicScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionDoesntHaveErrors();

        $dashboard = $this->actingAs($student)->get('/dashboard')->assertOk();
        $dashboard->assertViewHas('activeApplications', 2);
        $dashboard->assertViewHas('appliedScholarships', function (array $applications) use ($academicTulongDunong, $nonAcademicTulongDunong, $secondAcademicScholarship): bool {
            $academicApplication = collect($applications)->first(fn (array $item) =>
                (int) $item['scholarship_id'] === $academicTulongDunong->id
                && $item['grant_type'] === 'academic'
            );
            $secondApplication = collect($applications)->first(fn (array $item) =>
                (int) $item['scholarship_id'] === $secondAcademicScholarship->id
                && $item['grant_type'] === 'academic'
            );
            $nonAcademicApplication = collect($applications)->first(fn (array $item) =>
                (int) $item['scholarship_id'] === $nonAcademicTulongDunong->id
            );

            return ($academicApplication['status'] ?? null) === 'pending'
                && ($secondApplication['status'] ?? null) === 'pending'
                && $nonAcademicApplication === null;
        });
        $dashboard->assertViewHas('academicScholarshipIdMap', function (array $idMap) use ($academicTulongDunong, $nonAcademicTulongDunong): bool {
            return (int) ($idMap['Tulong Dunong Program'] ?? 0) === $academicTulongDunong->id
                && $academicTulongDunong->id !== $nonAcademicTulongDunong->id;
        });

        $this->submitAcademic($student, $thirdAcademicScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionHas('error', 'You already have two active applications, so this scholarship is not available.');
    }

    public function test_active_application_limit_is_shared_across_categories_and_rejection_frees_a_slot(): void
    {
        Mail::fake();
        Storage::fake('local');
        $student = User::factory()->approved()->create(['student_id' => '23-12003']);
        $admin = User::factory()->approved()->create(['role' => 0]);
        $academicScholarship = Scholarship::create([
            'name' => 'Academic Active Grant',
            'type' => 'academic',
            'status' => 'active',
        ]);
        $anotherAcademicScholarship = Scholarship::create([
            'name' => 'Another Academic Grant',
            'type' => 'academic',
            'status' => 'active',
        ]);
        $nonAcademicScholarship = Scholarship::create([
            'name' => 'Non-Academic Active Grant',
            'type' => 'non-academic',
            'status' => 'active',
        ]);
        $anotherNonAcademicScholarship = Scholarship::create([
            'name' => 'Another Non-Academic Grant',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $this->submitAcademic($student, $academicScholarship)->assertSessionDoesntHaveErrors();
        $this->submit($student, $nonAcademicScholarship)->assertSessionDoesntHaveErrors();

        foreach (['/dashboard', '/dashboard?section=non'] as $dashboardPath) {
            $this->actingAs($student)->get($dashboardPath)
                ->assertOk()
                ->assertViewHas('activeApplications', 2)
                ->assertSee('You already have two active applications, so this scholarship is not available.', false);
        }

        $this->submitAcademic($student, $anotherAcademicScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionHas('error', 'You already have two active applications, so this scholarship is not available.');

        $academicApplication = Application::where('user_id', $student->id)
            ->where('scholarship_id', $academicScholarship->id)
            ->firstOrFail();
        $academicProfile = StudentProfile::where('user_id', $student->id)
            ->where('application_id', $academicApplication->id)
            ->firstOrFail();

        $this->actingAs($admin)->postJson("/admin/applications/{$academicProfile->id}/sections/student-information/reject", [
            'reason' => 'Please correct the submitted information.',
        ])->assertOk();

        $this->actingAs($student)->get('/dashboard?section=non')
            ->assertOk()
            ->assertViewHas('activeApplications', 1);

        $this->submit($student, $anotherNonAcademicScholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_orphan_application_does_not_block_reapplication_after_direct_profile_delete(): void
    {
        Storage::fake('local');
        $scholarship = Scholarship::create([
            'name' => 'Athletic Scholarship',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        foreach (['approved', 'pending'] as $status) {
            $student = User::factory()->approved()->create([
                'student_id' => $status === 'approved' ? '23-12010' : '23-12013',
            ]);
            $orphan = Application::create([
                'user_id' => $student->id,
                'scholarship_id' => $scholarship->id,
                'status' => $status,
            ]);
            $profile = $this->createLinkedProfile($student, $scholarship, $orphan);

            DB::table('student_profiles')->where('id', $profile->id)->delete();
            $this->assertDatabaseHas('applications', ['id' => $orphan->id, 'status' => $status]);

            $this->submit($student, $scholarship)
                ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
                ->assertSessionDoesntHaveErrors()
                ->assertSessionMissing('error');

            $newApplication = Application::where('user_id', $student->id)
                ->where('scholarship_id', $scholarship->id)
                ->where('id', '!=', $orphan->id)
                ->latest('id')
                ->firstOrFail();
            $this->assertDatabaseHas('student_profiles', [
                'user_id' => $student->id,
                'application_id' => $newApplication->id,
            ]);
            $this->assertDatabaseHas('applications', ['id' => $orphan->id, 'status' => $status]);
        }
    }

    public function test_stale_application_id_from_another_scholarship_does_not_trigger_duplicate(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create(['student_id' => '23-12011']);
        $firstScholarship = Scholarship::create(['name' => 'First Non-Academic Award', 'type' => 'non-academic', 'status' => 'active']);
        $selectedScholarship = Scholarship::create(['name' => 'Selected Non-Academic Award', 'type' => 'non-academic', 'status' => 'active']);
        $staleApplication = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $firstScholarship->id,
            'status' => 'pending',
        ]);
        $this->createLinkedProfile($student, $firstScholarship, $staleApplication);

        $this->submit($student, $selectedScholarship, null, $staleApplication->id)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $firstScholarship->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $selectedScholarship->id,
            'status' => 'pending',
        ]);
    }

    public function test_active_application_with_linked_profile_still_blocks_same_scholarship(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create(['student_id' => '23-12012']);
        $scholarship = Scholarship::create(['name' => 'Active Linked Award', 'type' => 'non-academic', 'status' => 'active']);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'pending',
        ]);
        $this->createLinkedProfile($student, $scholarship, $application);

        $this->submit($student, $scholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionHas('error', 'You have already applied for the Active Linked Award scholarship. Only one application per scholarship is allowed.');
        $this->assertSame(1, Application::where('user_id', $student->id)->where('scholarship_id', $scholarship->id)->count());
    }

    private function assertDashboardState(User $student, int $activeCount, array $statuses, array $scholarshipIds): void
    {
        $response = $this->actingAs($student)->get('/dashboard?section=non')->assertOk();
        $response->assertViewHas('activeApplications', $activeCount);
        $response->assertViewHas('nonAcademicScholarshipIdMap', function (array $idMap) use ($scholarshipIds): bool {
            foreach ($scholarshipIds as $name => $id) {
                if ((int) ($idMap[$name] ?? 0) !== (int) $id) {
                    return false;
                }
            }

            return true;
        });
        $response->assertViewHas('appliedScholarships', function (array $applications) use ($statuses): bool {
            foreach ($statuses as $scholarshipId => $status) {
                $application = collect($applications)->first(fn (array $item) =>
                    (int) $item['scholarship_id'] === (int) $scholarshipId
                    && $item['grant_type'] === 'non-academic'
                );
                if (($application['status'] ?? null) !== $status) {
                    return false;
                }
            }

            return true;
        });
        $response->assertSee('const cardScholarshipId = String(card.dataset.scholarshipId || scholarshipIdMap?.[scholarshipTitle] || \'\');', false);
        $response->assertSee('You already have two active applications, so this scholarship is not available.', false);
    }

    private function submit(User $student, Scholarship $scholarship, ?StudentProfile $previousProfile = null, ?int $applicationIdOverride = null)
    {
        return $this->actingAs($student)->from('/dashboard?section=non')->post('/student-profiles', array_filter([
            'student_profile_id' => $previousProfile?->id,
            'application_id' => $applicationIdOverride ?? $previousProfile?->application_id,
            'first_name' => 'Availability',
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
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'uploads' => [UploadedFile::fake()->create('supporting-document.pdf', 10, 'application/pdf')],
            'certificate_of_adviser' => $scholarship->name === 'Athletic Scholarship'
                ? UploadedFile::fake()->create('adviser-certificate.pdf', 10, 'application/pdf')
                : null,
        ], fn ($value) => $value !== null));
    }

    private function createLinkedProfile(User $student, Scholarship $scholarship, Application $application): StudentProfile
    {
        return StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Availability',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => $scholarship->type,
            'grant_type' => $scholarship->type,
            'application_status' => $application->status,
        ]);
    }

    private function submitAcademic(User $student, Scholarship $scholarship)
    {
        return $this->actingAs($student)->from('/dashboard')->post('/student-profiles', [
            'first_name' => 'Academic',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
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
}