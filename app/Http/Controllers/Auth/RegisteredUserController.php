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
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'student_id' => ['required', 'string', 'regex:/^[0-9]{1,2}-[0-9]{1,5}$/', 'unique:users,student_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:1024'],
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
            $idPath = Storage::disk('public')->putFile('ids', $request->file('id_document'));
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
        ]);

        event(new Registered($user));

        // Don't auto-login. Redirect to approval waiting page instead
        return redirect(route('approval.waiting'))->with('status', 'Registration successful! Your account is under review. Please wait for admin approval.');
    }
}

