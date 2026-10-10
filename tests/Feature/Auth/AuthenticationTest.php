<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\StudentProfile;
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

    public function test_admins_are_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_admin_with_student_profile_ignores_student_intended_url(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        StudentProfile::create([
            'user_id' => $admin->id,
            'source' => 'application',
            'first_name' => 'Admin',
            'last_name' => 'Profile',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $admin->email,
            'course' => 'Information Technology',
            'scholarship_name' => 'Test Scholarship',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
        ]);

        $response = $this->withSession(['url.intended' => '/dashboard?section=non'])
            ->post('/login', [
                'login_type' => 'admin',
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_student_login_uses_student_dashboard_even_with_admin_intended_url(): void
    {
        $student = User::factory()->approved()->create(['role' => 1]);

        $response = $this->withSession(['url.intended' => '/admin'])
            ->post('/login', [
                'login_type' => 'student',
                'login_identifier' => $student->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_student_login_does_not_follow_admin_backup_intended_url(): void
    {
        $student = User::factory()->approved()->create(['role' => 1]);

        $response = $this->withSession(['url.intended' => '/api/backup/logs'])
            ->post('/login', [
                'login_type' => 'student',
                'login_identifier' => $student->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->approved()->create(['role' => 1]);

        $this->actingAs($student)
            ->get(route('admin.dashboard', absolute: false))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);

        $loginResponse = $this->post('/login', [
            'login_type' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $loginResponse->assertRedirect(route('admin.dashboard', absolute: false));

        $this->get(route('admin.dashboard', absolute: false))->assertOk();
    }

    public function test_admin_can_logout_and_login_again(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);

        $this->post('/login', [
            'login_type' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->post('/logout')
            ->assertRedirect(route('login', ['login_type' => 'admin']));
        $this->assertGuest();

        $this->post('/login', [
            'login_type' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
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
            $response->assertRedirect('/login');
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
