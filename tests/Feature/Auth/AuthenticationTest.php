<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->approved()->create(['student_id' => '23-11941']);

        $response = $this->post('/login', [
            'student_id' => $user->student_id,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_students_receive_generic_error_for_invalid_credentials(): void
    {
        $user = User::factory()->approved()->create();

        $response = $this->from('/login')->post('/login', [
            'login_identifier' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['login_identifier']);
        $response->assertSessionHas('errors', function ($errors) {
            $message = $errors->get('login_identifier')[0] ?? null;
            return $message === 'Your email or password is incorrect.';
        });
        $this->assertGuest();
    }

    public function test_student_login_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->approved()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from('/login')->post('/login', [
                'login_identifier' => $user->email,
                'password' => 'wrong-password',
            ]);

            if ($attempt < 5) {
                $response->assertSessionHasErrors(['login_identifier']);
                continue;
            }

            $response->assertSessionHasErrors(['login_identifier']);
            $this->assertStringContainsString('Too many failed login attempts', $response->getSession()->get('errors')->get('login_identifier')[0]);
        }

        $response = $this->from('/login')->post('/login', [
            'login_identifier' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['login_identifier']);
        $this->assertStringContainsString('Too many failed login attempts', $response->getSession()->get('errors')->get('login_identifier')[0]);
        $this->assertGuest();
    }

    public function test_admin_login_shows_generic_error_without_lockout(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('StrongP@ss123'),
            'role' => 0,
            'account_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $response = $this->from('/login')->post('/login', [
                'login_type' => 'admin',
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors(['email']);
            $response->assertSessionHas('errors', function ($errors) {
                $message = $errors->get('email')[0] ?? null;
                return $message === 'Your email or password is incorrect.';
            });
        }

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->approved()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
