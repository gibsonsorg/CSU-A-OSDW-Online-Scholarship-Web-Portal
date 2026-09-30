<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_a_pending_user_account(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'account_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/users/' . $user->id . '/approve');

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame('approved', $user->fresh()->account_status);
    }

    public function test_admin_can_reject_a_user_and_retain_their_account(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'account_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/users/' . $user->id . '/reject', [
            'reason' => 'Incomplete requirements',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame('rejected', $user->fresh()->account_status);
        $this->assertSame('Incomplete requirements', $user->fresh()->rejection_reason);
    }

    public function test_second_rejection_keeps_the_registration_visible(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'account_status' => 'pending',
            'registered_attempt' => 2,
            'student_id' => '2024-999',
            'email' => 'second.reject@example.com',
            'name' => 'Juan Miguel Dela Cruz',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/users/' . $user->id . '/reject', [
            'reason' => 'Maximum attempts reached',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame('rejected', $user->fresh()->account_status);
        $this->assertSame(2, $user->fresh()->registered_attempt);
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    public function test_manual_delete_preserves_attempt_history_and_blocks_third_registration(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $user = User::factory()->create([
            'role' => 1,
            'account_status' => 'rejected',
            'registered_attempt' => 2,
            'student_id' => '2024-888',
            'email' => 'delete.block@example.com',
        ]);

        $deleteResponse = $this->actingAs($admin)->postJson('/admin/users/' . $user->id . '/delete');
        $deleteResponse->assertOk()->assertJsonPath('status', 'ok');

        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertSame(2, User::withTrashed()->find($user->id)->registered_attempt);
        $this->assertTrue(User::withTrashed()->where('id', $user->id)->exists());
    }

    public function test_admin_dashboard_uses_real_statistics_from_the_database(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $currentMonthScholarship = \App\Models\Scholarship::create([
            'name' => 'Academic Excellence Grant',
            'type' => 'academic',
        ]);
        $currentMonthScholarship->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        $oldScholarship = \App\Models\Scholarship::create([
            'name' => 'Community Service Grant',
            'type' => 'non-academic',
        ]);
        $oldScholarship->forceFill([
            'created_at' => now()->subMonth()->startOfMonth(),
            'updated_at' => now()->subMonth()->startOfMonth(),
        ])->save();

        \App\Models\StudentProfile::create([
            'first_name' => 'Alice',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => 'alice@example.com',
            'course' => 'BSIT',
            'scholarship_name' => 'Academic Excellence Grant',
            'scholarship_type' => 'academic',
            'grant_type' => 'academic',
            'application_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\StudentProfile::create([
            'first_name' => 'Bob',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => 'bob@example.com',
            'course' => 'BSCS',
            'scholarship_name' => 'Community Service Grant',
            'scholarship_type' => 'non-academic',
            'grant_type' => 'non-academic',
            'application_status' => 'pending',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        \App\Models\StudentProfile::create([
            'first_name' => 'Charlie',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => 'charlie@example.com',
            'course' => 'BSEd',
            'scholarship_name' => 'Academic Excellence Grant',
            'scholarship_type' => 'academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'created_at' => now()->subWeek(),
            'updated_at' => now()->subWeek(),
        ]);

        User::factory()->create([
            'role' => 1,
            'email' => 'student1@example.com',
            'account_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::factory()->create([
            'role' => 1,
            'email' => 'student2@example.com',
            'account_status' => 'pending',
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);

        User::factory()->create([
            'role' => 0,
            'email' => 'admin2@example.com',
            'account_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['total_grants'] === 1
                    && $stats['total_applications'] === 3
                    && $stats['pending_applications'] === 1
                    && $stats['registered_users'] === 2
                    && $stats['new_registered_this_month'] === 1;
            });
    }

    public function test_dashboard_excludes_admins_and_soft_deleted_users_from_registered_count(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        User::factory()->create([
            'role' => 1,
            'email' => 'keep@example.com',
            'account_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deletedUser = User::factory()->create([
            'role' => 1,
            'email' => 'deleted@example.com',
            'account_status' => 'rejected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deletedUser->delete();

        User::factory()->create([
            'role' => 0,
            'email' => 'not-student@example.com',
            'account_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk()->assertViewHas('stats', function ($stats) {
            return $stats['registered_users'] === 1
                && $stats['new_registered_this_month'] === 1;
        });
    }

    public function test_registered_users_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $pendingUser = User::factory()->create([
            'role' => 1,
            'name' => 'John Pending',
            'email' => 'pending@example.com',
            'student_id' => '23-1001',
            'account_status' => 'pending',
        ]);

        $approvedUser = User::factory()->create([
            'role' => 1,
            'name' => 'Jane Approved',
            'email' => 'approved@example.com',
            'student_id' => '23-1002',
            'account_status' => 'approved',
        ]);

        $rejectedUser = User::factory()->create([
            'role' => 1,
            'name' => 'Bob Rejected',
            'email' => 'rejected@example.com',
            'student_id' => '23-1003',
            'account_status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?status=pending');
        $response->assertOk()->assertViewHas('users', function ($users) use ($pendingUser) {
            return $users->pluck('id')->contains($pendingUser->id);
        });

        $response = $this->actingAs($admin)->get('/admin/users?status=approved');
        $response->assertOk()->assertViewHas('users', function ($users) use ($approvedUser) {
            return $users->pluck('id')->contains($approvedUser->id);
        });

        $response = $this->actingAs($admin)->get('/admin/users?status=rejected');
        $response->assertOk()->assertViewHas('users', function ($users) use ($rejectedUser) {
            return $users->pluck('id')->contains($rejectedUser->id);
        });
    }

    public function test_registered_users_can_be_searched_by_name_email_student_id(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $user1 = User::factory()->create([
            'role' => 1,
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'student_id' => '23-5001',
            'account_status' => 'approved',
        ]);

        $user2 = User::factory()->create([
            'role' => 1,
            'name' => 'Maria Garcia',
            'email' => 'maria@example.com',
            'student_id' => '24-5002',
            'account_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=Juan');
        $response->assertOk()->assertViewHas('users', function ($users) use ($user1) {
            return $users->pluck('id')->contains($user1->id);
        });

        $response = $this->actingAs($admin)->get('/admin/users?search=maria@example.com');
        $response->assertOk()->assertViewHas('users', function ($users) use ($user2) {
            return $users->pluck('id')->contains($user2->id);
        });

        $response = $this->actingAs($admin)->get('/admin/users?search=23-5001');
        $response->assertOk()->assertViewHas('users', function ($users) use ($user1) {
            return $users->pluck('id')->contains($user1->id);
        });
    }

    public function test_registered_users_search_and_status_filter_work_together(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $user1 = User::factory()->create([
            'role' => 1,
            'name' => 'Juan Approved',
            'email' => 'juan.approved@example.com',
            'student_id' => '23-6001',
            'account_status' => 'approved',
        ]);

        $user2 = User::factory()->create([
            'role' => 1,
            'name' => 'Juan Rejected',
            'email' => 'juan.rejected@example.com',
            'student_id' => '23-6002',
            'account_status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=Juan&status=approved');
        $response->assertOk()->assertViewHas('users', function ($users) use ($user1) {
            return $users->pluck('id')->contains($user1->id);
        });

        $response = $this->actingAs($admin)->get('/admin/users?search=Juan&status=rejected');
        $response->assertOk()->assertViewHas('users', function ($users) use ($user2) {
            return $users->pluck('id')->contains($user2->id);
        });
    }

    public function test_registered_users_filters_exclude_admins(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $studentUser = User::factory()->create([
            'role' => 1,
            'name' => 'Student User',
            'email' => 'student@example.com',
            'account_status' => 'approved',
        ]);

        $anotherAdmin = User::factory()->create([
            'role' => 0,
            'name' => 'Another Admin',
            'email' => 'admin2@example.com',
            'account_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk()->assertViewHas('users', function ($users) use ($studentUser, $anotherAdmin) {
            return $users->pluck('id')->contains($studentUser->id)
                && !$users->pluck('id')->contains($anotherAdmin->id);
        });
    }
}
