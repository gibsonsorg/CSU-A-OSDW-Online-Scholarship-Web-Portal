<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['required', 'string', 'regex:/^[0-9]{1,2}-[0-9]{1,5}$/', 'unique:users,student_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'student_id.regex' => 'Student ID must be in format like 23-11941 (digits-digits)',
            'student_id.unique' => 'This Student ID is already registered',
            'password.confirmed' => 'Passwords do not match',
        ]);

        $user = User::create([
            'name' => $request->name,
            'student_id' => $request->student_id,
            'email' => 'student_' . str_replace('-', '', $request->student_id) . '@student.local',
            'password' => Hash::make($request->password),
            'role' => 1, // default = USER
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}


