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

    public function test_admin_can_reject_a_user_and_remove_their_account(): void
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

        $this->assertNull($user->fresh());
    }
}
