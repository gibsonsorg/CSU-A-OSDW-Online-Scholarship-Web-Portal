<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Mail\EmailVerificationCodeMail;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'email' => 'test.user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test.user@example.com',
            'email_verified_at' => null,
            'account_status' => 'pending',
        ]);
        $response->assertRedirect(route('verification.code'));
        Mail::assertSent(EmailVerificationCodeMail::class);
    }

    public function test_verification_code_activates_the_account(): void
    {
        Mail::fake();

        $this->post('/register', [
            'email' => 'test.user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $mail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $user = User::where('email', 'test.user@example.com')->firstOrFail();
        $this->get('/verify-code')->assertOk();

        $response = $this->withSession([
            'verification_user_id' => $user->id,
            'verification_email' => $user->email,
        ])->post('/verify-code', ['verification_code' => $mail->code]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('verification_success', 'Email verified Successfully you can now login');
        $this->assertDatabaseHas('users', [
            'email' => 'test.user@example.com',
            'account_status' => 'approved',
        ]);

        $this->withSession([
            'verification_success' => 'Email verified Successfully you can now login',
        ])->get('/login')
            ->assertSee('Email verified Successfully you can now login');
    }

    public function test_verification_accepts_the_exact_code_and_preserves_a_leading_zero(): void
    {
        $user = User::create([
            'name' => null,
            'student_id' => null,
            'email' => 'otp@example.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'account_status' => 'pending',
            'registered_attempt' => 1,
            'email_verification_code_hash' => Hash::make('012345'),
            'email_verification_expires_at' => now()->addMinutes(10),
            'email_verification_attempts' => 0,
        ]);

        $response = $this->withSession(['verification_user_id' => $user->id, 'verification_email' => $user->email])
            ->post('/verify-code', ['verification_code' => '012345']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('verification_success', 'Email verified Successfully you can now login');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verification_accepts_the_code_reported_in_email(): void
    {
        $user = User::create([
            'name' => null,
            'student_id' => null,
            'email' => 'otp-reported@example.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'account_status' => 'pending',
            'registered_attempt' => 1,
            'email_verification_code_hash' => Hash::make('483921'),
            'email_verification_expires_at' => now()->addMinutes(10),
            'email_verification_attempts' => 0,
        ]);

        $response = $this->withSession(['verification_user_id' => $user->id, 'verification_email' => $user->email])
            ->post(route('verification.submit'), ['verification_code' => '483921']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('verification_success', 'Email verified Successfully you can now login');
        $this->assertSame('approved', $user->fresh()->account_status);
    }

    public function test_incorrect_and_expired_codes_are_rejected(): void
    {
        $user = User::create([
            'name' => null,
            'student_id' => null,
            'email' => 'otp-errors@example.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'account_status' => 'pending',
            'registered_attempt' => 1,
            'email_verification_code_hash' => Hash::make('232881'),
            'email_verification_expires_at' => now()->addMinutes(10),
            'email_verification_attempts' => 0,
        ]);

        $response = $this->withSession(['verification_user_id' => $user->id, 'verification_email' => $user->email])
            ->post('/verify-code', ['verification_code' => '232882']);

        $response->assertSessionHas('error', 'verification_incorrect');
        $this->assertSame('pending', $user->fresh()->account_status);

        $user->update(['email_verification_expires_at' => Carbon::now()->subMinute()]);
        $response = $this->withSession(['verification_user_id' => $user->id, 'verification_email' => $user->email])
            ->post('/verify-code', ['verification_code' => '232881']);

        $response->assertSessionHas('error', 'verification_expired');
    }

    public function test_resending_code_replaces_the_previous_code(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => null,
            'student_id' => null,
            'email' => 'otp-resend@example.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'account_status' => 'pending',
            'registered_attempt' => 1,
            'email_verification_code_hash' => Hash::make('483921'),
            'email_verification_expires_at' => now()->addMinutes(10),
            'email_verification_attempts' => 2,
        ]);

        $response = $this->withSession(['verification_user_id' => $user->id, 'verification_email' => $user->email])
            ->post(route('verification.resend'));

        $response->assertSessionHas('success', 'verification_resent');
        $newCode = Mail::sent(EmailVerificationCodeMail::class)->last()->code;
        $this->assertFalse(Hash::check('483921', $user->fresh()->email_verification_code_hash));
        $this->assertTrue(Hash::check($newCode, $user->fresh()->email_verification_code_hash));
        $this->assertSame(0, $user->fresh()->email_verification_attempts);
    }
}
