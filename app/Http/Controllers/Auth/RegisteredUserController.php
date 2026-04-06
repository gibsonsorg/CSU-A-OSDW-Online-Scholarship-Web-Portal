<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
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
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:1024'],
        ], [
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

        $userData = [
            'name' => $request->name,
            'student_id' => $request->student_id,
            'email' => 'student_' . str_replace('-', '', $request->student_id) . '@student.local',
            'password' => Hash::make($request->password),
            'role' => 1, // default = USER
        ];

        // Only include id_document if the column exists in DB (migration may not have been run)
        if ($idPath && Schema::hasColumn('users', 'id_document')) {
            $userData['id_document'] = $idPath;
        }

        $user = User::create($userData);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}


