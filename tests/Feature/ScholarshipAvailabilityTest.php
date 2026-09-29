<?php

namespace Tests\Feature;

use App\Mail\ApplicationSectionStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->assertSessionHas('error', 'You already have two active applications in the non-academic category. Only two open applications per category are allowed.');

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
            ->assertSessionHas('error', 'You already have two active applications in the non-academic category. Only two open applications per category are allowed.');
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
        $dashboard->assertViewHas('activeAcademicApplications', 2);
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
            ->assertSessionHas('error', 'You already have two active applications in the academic category. Only two open applications per category are allowed.');
    }

    private function assertDashboardState(User $student, int $activeCount, array $statuses, array $scholarshipIds): void
    {
        $response = $this->actingAs($student)->get('/dashboard?section=non')->assertOk();
        $response->assertViewHas('activeNonAcademicApplications', $activeCount);
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
        $response->assertSee('You already have two active applications in this category, so this scholarship is not available.', false);
    }

    private function submit(User $student, Scholarship $scholarship, ?StudentProfile $previousProfile = null)
    {
        return $this->actingAs($student)->from('/dashboard?section=non')->post('/student-profiles', array_filter([
            'student_profile_id' => $previousProfile?->id,
            'application_id' => $previousProfile?->application_id,
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
        ], fn ($value) => $value !== null));
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