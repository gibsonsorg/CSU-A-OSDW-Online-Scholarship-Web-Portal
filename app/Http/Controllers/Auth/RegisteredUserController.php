<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        // Check for existing registration with the same credentials (including soft-deleted)
        $existingUser = User::withTrashed()
            ->where('email', $request->email)
            ->orWhere('student_id', $request->student_id)
            ->first();

        if ($existingUser) {
            // User exists - check if they can register again
            if ($existingUser->account_status === 'approved') {
                // Already approved - cannot register again
                return redirect()->back()->with('error', 'approved');
            } elseif ($existingUser->account_status === 'pending') {
                // Registration still pending - cannot register again
                return redirect()->back()->with('error', 'pending');
            } elseif ($existingUser->account_status === 'rejected') {
                // Rejected - check attempt number
                if ($existingUser->registered_attempt >= 2) {
                    // Second attempt already rejected - cannot register again
                    return redirect()->back()->with('error', 'max_attempts');
                }
                // First attempt was rejected - allow second attempt
                // Validate the new registration data
                $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255'],
                    'student_id' => ['required', 'string', 'regex:/^[0-9]{1,2}-[0-9]{1,5}$/'],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:1024'],
                ], [
                    'name.required' => 'Full name is required',
                    'email.required' => 'Email address is required',
                    'email.email' => 'Please enter a valid email address',
                    'student_id.regex' => 'Student ID must be in format like 23-11941 (digits-digits)',
                    'password.confirmed' => 'Passwords do not match',
                    'id_document.required' => 'School ID upload is required',
                    'id_document.mimes' => 'School ID must be a JPG, PNG or PDF file',
                    'id_document.max' => 'School ID must be no larger than 1MB',
                ]);

                // Update the existing registration for second attempt
                $idPath = null;
                if ($request->hasFile('id_document') && $request->file('id_document')->isValid()) {
                    $idPath = Storage::disk('local')->putFile('ids', $request->file('id_document'));
                }

                $existingUser->update([
                    'name' => $request->name,
                    'student_id' => $request->student_id,
                    'password' => Hash::make($request->password),
                    'account_status' => 'pending',
                    'rejection_reason' => null,
                    'id_document' => $idPath,
                    'registered_attempt' => 2, // Second attempt
                ]);

                return redirect()->back()->with('success', true);
            }
        }

        // No existing user - validate and create new registration
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'student_id' => ['required', 'string', 'regex:/^[0-9]{1,2}-[0-9]{1,5}$/', 'unique:users,student_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:1024'],
        ], [
            'name.unique' => 'This full name is already registered',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address is already registered',
            'student_id.regex' => 'Student ID must be in format like 23-11941 (digits-digits)',
            'student_id.unique' => 'This Student ID is already registered',
            'password.confirmed' => 'Passwords do not match',
            'id_document.required' => 'School ID upload is required',
            'id_document.mimes' => 'School ID must be a JPG, PNG or PDF file',
            'id_document.max' => 'School ID must be no larger than 1MB',
        ]);

        // store uploaded ID document
        $idPath = null;
        if ($request->hasFile('id_document') && $request->file('id_document')->isValid()) {
            $idPath = Storage::disk('local')->putFile('ids', $request->file('id_document'));
        }

        $user = User::create([
            'name' => $request->name,
            'student_id' => $request->student_id,
            'email' => $request->email,
            'email_verified_at' => now(),
            'password' => Hash::make($request->password),
            'role' => 1, // default = USER
            'account_status' => 'pending',
            'id_document' => $idPath,
            'registered_attempt' => 1, // First attempt
        ]);

        event(new Registered($user));

        return redirect()->back()->with('success', true);
    }
}

