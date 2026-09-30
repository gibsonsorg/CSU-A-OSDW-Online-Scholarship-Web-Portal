<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $loginType = $this->input('login_type', 'student');

        if ($loginType === 'admin') {
            return [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ];
        }

        return [
            'login_identifier' => ['required_without_all:email,student_id', 'nullable', 'string'],
            'email' => ['nullable', 'string', 'email'],
            'student_id' => ['nullable', 'string', 'regex:/^[0-9]{1,2}-[0-9]{1,5}$/'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $loginType = $this->input('login_type', 'student');

        if ($loginType === 'student') {
            $this->ensureIsNotRateLimited();
        }

        if ($loginType === 'admin') {
            if (! Auth::attempt(['email' => $this->input('email'), 'password' => $this->input('password')], $this->boolean('remember'))) {
                throw ValidationException::withMessages([
                    'email' => 'Your email or password is incorrect.',
                ]);
            }

            return;
        }

        $identifier = $this->input('login_identifier') ?: ($this->input('email') ?: $this->input('student_id'));
        $credentialField = str_contains((string) $identifier, '@') ? 'email' : 'student_id';

        if (! Auth::attempt([$credentialField => $identifier, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 600);

            if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
                throw ValidationException::withMessages([
                    'login_identifier' => $this->formatLockoutMessage(),
                ]);
            }

            $attemptCount = RateLimiter::attempts($this->throttleKey());
            $message = $this->studentFailureMessage($identifier, $credentialField, $attemptCount);
            $field = $this->studentFailureField($identifier, $credentialField);

            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if ($this->input('login_type', 'student') !== 'student') {
            return;
        }

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'login_identifier' => $this->formatLockoutMessage(),
        ]);
    }

    protected function formatLockoutMessage(): string
    {
        $seconds = RateLimiter::availableIn($this->throttleKey());
        $minutes = (int) ceil($seconds / 60);

        if ($minutes >= 1) {
            return sprintf('Too many failed login attempts. Please try again in %s minute%s.', $minutes, $minutes === 1 ? '' : 's');
        }

        return sprintf('Too many failed login attempts. Please try again in %s seconds.', $seconds);
    }

    protected function studentFailureField(string $identifier, string $credentialField): string
    {
        $user = User::where($credentialField, $identifier)->first();

        if ($user) {
            return 'password';
        }

        return 'login_identifier';
    }

    protected function studentFailureMessage(string $identifier, string $credentialField, int $attemptCount): string
    {
        $user = User::where($credentialField, $identifier)->first();
        $message = $user ? 'The password you entered is incorrect.' : 'The email you entered is incorrect.';

        if ($attemptCount < 3 || $attemptCount >= 5) {
            return $message;
        }

        $remaining = 5 - $attemptCount;
        $attemptLabel = $remaining === 1 ? 'attempt' : 'attempts';

        return $message . ' You have ' . $remaining . ' ' . $attemptLabel . ' remaining before your account is temporarily locked.';
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identifier = trim((string) ($this->input('login_identifier') ?: ($this->input('email') ?: $this->input('student_id', ''))));

        return Str::transliterate(Str::lower($identifier)).'|'.$this->ip();
    }
}

