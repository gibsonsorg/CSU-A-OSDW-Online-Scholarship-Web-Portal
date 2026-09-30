<?php

namespace Tests\Feature;

use App\Models\OldGrantRecord;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_application_data_produces_zero_grant_application_and_pending_counts(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('stats', [
                'total_grants' => 0,
                'total_applications' => 0,
                'pending_applications' => 0,
                'registered_users' => 0,
                'new_registered_this_month' => 0,
            ]);
    }

    public function test_grants_and_pending_counts_follow_overall_application_status(): void
    {
        $admin = $this->createAdmin();
        Scholarship::create(['name' => 'Definition Only', 'type' => 'academic']);

        $this->createProfile('Approved', 'approved', now());
        $this->createProfile('Pending', 'pending', now());
        $this->createProfile('Rejected', 'rejected', now());

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('stats', [
                'total_grants' => 1,
                'total_applications' => 3,
                'pending_applications' => 1,
                'registered_users' => 0,
                'new_registered_this_month' => 0,
            ]);
    }

    public function test_academic_and_non_academic_applications_are_counted_together(): void
    {
        $admin = $this->createAdmin();

        $this->createProfile('Academic Approved', 'approved', now(), null, 'academic');
        $this->createProfile('Academic Pending', 'pending', now(), null, 'academic');
        $this->createProfile('Non-Academic Approved', 'approved', now(), null, 'non-academic');
        $this->createProfile('Non-Academic Pending One', 'pending', now(), null, 'non-academic');
        $this->createProfile('Non-Academic Pending Two', 'pending', now(), null, 'non-academic');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('stats', [
                'total_grants' => 2,
                'total_applications' => 5,
                'pending_applications' => 3,
                'registered_users' => 0,
                'new_registered_this_month' => 0,
            ]);
    }

    public function test_approved_applications_for_one_student_count_as_separate_grants(): void
    {
        $admin = $this->createAdmin();
        $student = User::factory()->create(['role' => 1]);

        $this->createProfile('Academic Grant', 'approved', now(), $student->id, 'academic');
        $this->createProfile('Non-Academic Grant', 'approved', now(), $student->id, 'non-academic');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('stats', [
                'total_grants' => 2,
                'total_applications' => 2,
                'pending_applications' => 0,
                'registered_users' => 1,
                'new_registered_this_month' => 1,
            ]);
    }

    public function test_scholarship_definitions_do_not_increase_total_grants(): void
    {
        $admin = $this->createAdmin();
        Scholarship::create(['name' => 'Academic Definition', 'type' => 'academic']);
        Scholarship::create(['name' => 'Inactive Definition', 'type' => 'non-academic', 'status' => 'inactive']);
        $this->createProfile('Pending Application', 'pending', now());

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('stats.total_grants', 0)
            ->assertViewHas('stats.total_applications', 1)
            ->assertViewHas('stats.pending_applications', 1);
    }

    public function test_all_admin_pages_receive_the_same_stats_and_approved_is_all_time(): void
    {
        $admin = $this->createAdmin();

        Scholarship::create(['name' => 'Current Grant', 'type' => 'academic']);
        Scholarship::create(['name' => 'Inactive Grant', 'type' => 'non-academic', 'status' => 'inactive']);

        $this->createProfile('Current Approved', 'approved', now());
        $this->createProfile('Older Approved', 'approved', now()->subWeeks(2));
        $this->createProfile('Older Pending', 'pending', now()->subWeeks(2));
        $deletedProfile = $this->createProfile('Deleted Approved', 'approved', now());
        $deletedProfile->delete();

        User::factory()->create([
            'role' => 1,
            'account_status' => 'approved',
        ]);
        User::factory()->create([
            'role' => 1,
            'account_status' => 'pending',
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);
        User::factory()->create([
            'role' => 1,
            'account_status' => 'rejected',
        ]);
        User::factory()->create([
            'role' => 0,
            'account_status' => 'approved',
        ]);

        OldGrantRecord::create([
            'first_name' => 'Historical',
            'scholarship_type' => 'academic',
        ]);

        $expectedStats = [
            'total_grants' => 2,
            'total_applications' => 3,
            'pending_applications' => 1,
            'registered_users' => 3,
            'new_registered_this_month' => 2,
        ];

        $pages = [
            '/admin',
            '/admin/academic',
            '/admin/non-academic',
            '/admin?section=reports',
            '/admin/old-academic',
            '/admin/old-non-academic',
            '/admin/users',
        ];

        $this->actingAs($admin);

        foreach ($pages as $page) {
            $response = $this->get($page);

            $response->assertOk()
                ->assertViewIs('admin.welcome')
                ->assertViewHas('stats', $expectedStats);
        }
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);
    }

    private function createProfile(
        string $name,
        string $applicationStatus,
        $createdAt,
        ?int $userId = null,
        string $grantType = 'academic'
    ): StudentProfile
    {
        $profile = StudentProfile::create([
            'user_id' => $userId,
            'first_name' => $name,
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'course' => 'BSIT',
            'scholarship_name' => 'Current Grant',
            'scholarship_type' => $grantType,
            'grant_type' => $grantType,
            'application_status' => $applicationStatus,
        ]);

        $profile->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $profile;
    }
}