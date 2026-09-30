<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'password.confirmed' => 'Passwords do not match',
            'password' => 'Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.',
        ]);

        $existingUser = User::withTrashed()->where('email', $request->email)->first();

        if ($existingUser?->email_verified_at && $existingUser->account_status === 'approved') {
            return redirect()->back()->with('error', 'approved');
        }

        $emailVerificationEnabled = config('registration.email_verification_enabled', true);
        $verificationCode = $emailVerificationEnabled ? (string) random_int(100000, 999999) : null;
        $attributes = [
            'password' => Hash::make($request->password),
            'email_verified_at' => $emailVerificationEnabled ? null : now(),
            'account_status' => $emailVerificationEnabled ? 'pending' : 'approved',
            'rejection_reason' => null,
            'email_verification_code_hash' => $emailVerificationEnabled ? Hash::make($verificationCode) : null,
            'email_verification_expires_at' => $emailVerificationEnabled ? now()->addMinutes(10) : null,
            'email_verification_attempts' => 0,
        ];

        if ($existingUser) {
            $existingUser->update($attributes);
            $user = $existingUser;
        } else {
            $user = User::create(array_merge($attributes, [
                'name' => null,
                'student_id' => null,
                'email' => $request->email,
                'role' => 1,
                'registered_attempt' => 1,
            ]));
        }

        if (!$emailVerificationEnabled) {
            return redirect()->route('login')->with(
                'verification_success',
                'Email verified Successfully you can now login'
            );
        }

        Mail::to($user->email)->queue(new EmailVerificationCodeMail($verificationCode));

        $request->session()->put([
            'verification_user_id' => $user->getKey(),
            'verification_email' => $user->email,
        ]);

        return redirect()->route('verification.code')->with('success', 'verification_queued');
    }

    public function showVerificationCode(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('verification_user_id');
        $email = $request->session()->get('verification_email');

        if (!$userId || !$email) {
            return redirect()->route('register');
        }

        return view('auth.verify-code', compact('email'));
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $request->merge([
            'verification_code' => trim((string) $request->input('verification_code', '')),
        ]);

        $request->validate([
            'verification_code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $userId = $request->session()->get('verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user || !$user->email_verification_code_hash) {
            return redirect()->route('register')->with('error', 'verification_missing');
        }

        if (!$user->email_verification_expires_at || $user->email_verification_expires_at->isPast()) {
            return redirect()->back()->with('error', 'verification_expired');
        }

        if ($user->email_verification_attempts >= 5) {
            return redirect()->back()->with('error', 'verification_attempts');
        }

        if (!Hash::check($request->string('verification_code')->trim(), $user->email_verification_code_hash)) {
            $user->increment('email_verification_attempts');
            return redirect()->back()->with('error', 'verification_incorrect');
        }

        $user->update([
            'email_verified_at' => now(),
            'account_status' => 'approved',
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
            'email_verification_attempts' => 0,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget(['verification_user_id', 'verification_email']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user || $user->email_verified_at) {
            return redirect()->route('register');
        }

        $verificationCode = (string) random_int(100000, 999999);
        $user->update([
            'email_verification_code_hash' => Hash::make($verificationCode),
            'email_verification_expires_at' => now()->addMinutes(10),
            'email_verification_attempts' => 0,
        ]);

        Mail::to($user->email)->queue(new EmailVerificationCodeMail($verificationCode));

        return redirect()->back()->with('success', 'verification_resent');
    }
}

