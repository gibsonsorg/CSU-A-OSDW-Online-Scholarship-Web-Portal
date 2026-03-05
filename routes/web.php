<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Support\Facades\Route;

//HOME ROUTE
// Welcome page - accessible to all users
Route::get('/', function () {
    return view('welcome');
});

//AUTHENTICATION ROUTES
// Custom auth overrides - Both routes point to combined custom blade file
// From: app/Http/Controllers/Auth/AuthenticatedSessionController & RegisteredUserController
Route::get('/login', function () {
    return view('auth.register'); 
})->name('login');

// From: app/Http/Controllers/Auth/RegisteredUserController
Route::get('/register', function () {
    return view('auth.register'); 
})->name('register');

//STUDENT DASHBOARD ROUTE
// Student dashboard view - requires auth and email verification
// From: app/Http/Controllers/StudentProfileController (display route)
Route::get('/dashboard', function () {
    return view('student.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Models\StudentProfile;

//ADMIN DASHBOARD ROUTE 
// Admin dashboard - shows recent applications
// From: App\Http\Controllers\AdminController (conceptual)
Route::get('/admin', function () {
    $recentApplications = StudentProfile::orderBy('created_at', 'desc')->take(10)->get();
    return view('admin.welcome', compact('recentApplications'));
})->middleware(['auth', 'verified'])->name('admin.dashboard');

// ADMIN APPLICATION MANAGEMENT ROUTES
// Fetch single application details as JSON
// From: app/Http/Controllers/AdminController - application detail view
Route::get('/admin/applications/{id}', function($id){
    $p = StudentProfile::find($id);
    if (!$p) return response()->json(['error'=>'not_found'], 404);
    return response()->json($p);
})->middleware(['auth']);

// Approve application endpoint
// From: app/Http/Controllers/AdminController - approval handler
Route::post('/admin/applications/{id}/approve', function($id){
    $p = StudentProfile::find($id);
    if ($p) { $p->application_status = 'approved'; $p->save(); return response()->json(['status'=>'ok']); }
    return response()->json(['status'=>'not_found'], 404);
})->middleware(['auth']);

// Reject application endpoint
// From: app/Http/Controllers/AdminController - rejection handler
Route::post('/admin/applications/{id}/reject', function($id){
    $p = StudentProfile::find($id);
    if ($p) { $p->application_status = 'rejected'; $p->save(); return response()->json(['status'=>'ok']); }
    return response()->json(['status'=>'not_found'], 404);
})->middleware(['auth']);

//USER PROFILE ROUTES 
// Grouped routes for authenticated users only
// From: app/Http/Controllers/ProfileController
Route::middleware('auth')->group(function () {
    // Edit profile page
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    
    // Update profile
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Delete profile
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// STUDENT PROFILE SUBMISSION ROUTE 
// Store new scholarship application from student
// From: app/Http/Controllers/StudentProfileController - store method
Route::post('/student-profiles', [StudentProfileController::class, 'store'])->name('student-profiles.store');