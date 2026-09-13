<?php

namespace App\Http\Requests\Auth;

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
        $this->ensureIsNotRateLimited();

        $loginType = $this->input('login_type', 'student');
        
        if ($loginType === 'admin') {
            // Admin login using email
            if (! Auth::attempt(['email' => $this->input('email'), 'password' => $this->input('password')], $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'Invalid email or password.',
                ]);
            }
        } else {
            $identifier = $this->input('login_identifier') ?: ($this->input('email') ?: $this->input('student_id'));
            $credentialField = str_contains($identifier, '@') ? 'email' : 'student_id';
            if (! Auth::attempt([$credentialField => $identifier, 'password' => $this->input('password')], $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    $credentialField => 'Invalid email or password.',
                ]);
            }
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
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'student_id' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login_identifier', $this->input('email', $this->input('student_id', '')))).'|'.$this->ip());
    }
}

