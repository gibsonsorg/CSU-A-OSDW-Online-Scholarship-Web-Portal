<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicantPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_pagination_preserves_status_date_and_scholarship_filters(): void
    {
        $admin = $this->createAdmin();
        $firstScholarship = $this->createScholarship('Academic Pagination One', 'academic');
        $secondScholarship = $this->createScholarship('Academic Pagination Two', 'academic');

        for ($index = 1; $index <= 83; $index++) {
            $this->createPaginationApplication($firstScholarship, 'pending', $index);
        }
        for ($index = 101; $index <= 110; $index++) {
            $this->createPaginationApplication($secondScholarship, 'approved', $index);
        }

        $this->actingAs($admin);
        $allScholarshipsUrl = '/admin/academic?status=pending&date_from=2026-09-01&date_to=2026-09-30';

        $this->get($allScholarshipsUrl . '&page=1')
            ->assertOk()
            ->assertSee('Showing 1 to 10 of 83 results')
            ->assertSee('aria-current="page">1', false)
            ->assertSee('admin-pagination__previous is-disabled', false)
            ->assertSee('page=2', false)
            ->assertSee('...', false)
            ->assertSee('status=pending', false)
            ->assertSee('date_from=2026-09-01', false)
            ->assertSee('date_to=2026-09-30', false)
            ->assertViewHas('recentApplications', function ($applications) use ($firstScholarship): bool {
                return $applications->total() === 83
                    && $applications->count() === 10
                    && $applications->getCollection()->every(
                        fn (StudentProfile $profile) => $profile->scholarship_name === $firstScholarship->name
                    );
            });

        $this->get($allScholarshipsUrl . '&page=2')
            ->assertOk()
            ->assertSee('Showing 11 to 20 of 83 results')
            ->assertSee('&lsaquo;</span> Previous', false)
            ->assertSee('page=1', false)
            ->assertSee('page=3', false)
            ->assertSee('rel="next"', false);

        $this->get($allScholarshipsUrl . '&page=9')
            ->assertOk()
            ->assertSee('Showing 81 to 83 of 83 results')
            ->assertSee('aria-current="page">9', false)
            ->assertSee('page=8', false)
            ->assertSee('aria-disabled="true"', false);

        $specificScholarshipUrl = '/admin/academic?scholarship_name=' . rawurlencode($firstScholarship->name)
            . '&status=pending&date_from=2026-09-01&date_to=2026-09-30';

        $this->get($specificScholarshipUrl . '&page=2')
            ->assertOk()
            ->assertSee('Showing 11 to 20 of 83 results')
            ->assertSee('scholarship_name=' . rawurlencode($firstScholarship->name), false)
            ->assertSee('status=pending', false)
            ->assertSee('date_from=2026-09-01', false)
            ->assertSee('date_to=2026-09-30', false);
    }

    public function test_non_academic_pagination_renders_the_last_page_and_disabled_next_control(): void
    {
        $admin = $this->createAdmin();
        $scholarship = $this->createScholarship('Non-Academic Pagination', 'non-academic');

        for ($index = 1; $index <= 23; $index++) {
            $this->createPaginationApplication($scholarship, 'approved', $index);
        }

        $url = '/admin/non-academic?scholarship_name=' . rawurlencode($scholarship->name)
            . '&status=approved&date_from=2026-09-01&date_to=2026-09-30';

        $this->actingAs($admin)->get($url . '&page=3')
            ->assertOk()
            ->assertSee('Showing 21 to 23 of 23 results')
            ->assertSee('aria-current="page">3', false)
            ->assertSee('&lsaquo;</span> Previous', false)
            ->assertSee('page=2', false)
            ->assertSee('Next', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('scholarship_name=' . rawurlencode($scholarship->name), false)
            ->assertSee('status=approved', false)
            ->assertSee('date_from=2026-09-01', false)
            ->assertSee('date_to=2026-09-30', false);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);
    }

    private function createScholarship(string $name, string $type): Scholarship
    {
        return Scholarship::create([
            'name' => $name,
            'type' => $type,
            'status' => 'active',
        ]);
    }

    private function createPaginationApplication(Scholarship $scholarship, string $status, int $sequence): void
    {
        $user = User::factory()->create([
            'role' => 1,
            'email' => sprintf('pagination.student.%s@example.com', $sequence),
            'account_status' => 'approved',
        ]);
        $application = Application::create([
            'user_id' => $user->id,
            'scholarship_id' => $scholarship->id,
            'status' => $status,
        ]);
        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'first_name' => 'Pagination',
            'last_name' => 'Student ' . $sequence,
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $user->email,
            'course' => 'Test Course',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => ucfirst($scholarship->type) . ' Scholarship',
            'grant_type' => $scholarship->type,
            'application_status' => $status,
        ]);

        $profile->forceFill([
            'created_at' => '2026-09-15 12:00:00',
            'updated_at' => '2026-09-15 12:00:00',
        ])->save();
        $application->forceFill([
            'created_at' => '2026-09-15 12:00:00',
            'updated_at' => '2026-09-15 12:00:00',
        ])->save();
    }
}