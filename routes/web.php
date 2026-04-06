<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BackupSettingsController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

//HOME ROUTE
// Welcome page - accessible to all users
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes are provided in routes/auth.php
// Custom GET routes were removed to avoid collisions with the auth route definitions.

//STUDENT DASHBOARD ROUTE
// Student dashboard view - requires auth and email verification
// From: app/Http/Controllers/StudentProfileController (display route)
Route::get('/dashboard', function () {
    return view('student.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Models\StudentProfile;

//ADMIN DASHBOARD ROUTE 
// Admin dashboard - shows recent applications
Route::get('/admin', [AdminController::class, 'dashboard'])->middleware(['auth', 'verified'])->name('admin.dashboard');

// Admin dashboard filtered by academic grants
Route::get('/admin/academic', [AdminController::class, 'academicDashboard'])->middleware(['auth', 'verified'])->name('admin.academic');

// Admin dashboard filtered by non-academic grants
Route::get('/admin/non-academic', [AdminController::class, 'nonAcademicDashboard'])->middleware(['auth', 'verified'])->name('admin.non_academic');

// Admin users list
Route::get('/admin/users', [AdminController::class, 'users'])->middleware(['auth', 'verified'])->name('admin.users');

// Delete user (AJAX/POST)
Route::post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser'])->middleware(['auth', 'verified']);

// ADMIN APPLICATION MANAGEMENT ROUTES
// Fetch single application details as JSON
// From: app/Http/Controllers/AdminController - application detail view
Route::get('/admin/applications/{id}', function($id){
    $p = StudentProfile::find($id);
    if (!$p) return response()->json(['error'=>'not_found'], 404);
    return response()->json($p);
})->middleware(['auth']);

// Approve application endpoint 
//from: app/Http/Controllers/AdminController approval handler
 Route::post('/admin/applications/{id}/approve', function($id)
 {
   $p = StudentProfile::find($id);
   if($p) {$p->application_status ='approved'; $p->save(); return response()->json(['status' =>'ok']);}
   return response()->json(['status'=>'not found'],404);
 })->middleware(['auth']);

// Reject application endpoint - uses AdminController
Route::post('/admin/applications/{id}/reject', [AdminController::class, 'reject'])->middleware(['auth']);

// Update application endpoint - uses AdminController
Route::post('/admin/applications/{id}/update', [AdminController::class, 'update'])->middleware(['auth']);

// Delete application endpoint - uses AdminController with DELETE method
Route::delete('/admin/applications/{id}', [AdminController::class, 'destroy'])->middleware(['auth']);

// Delete application endpoint - POST alias for compatibility
Route::post('/admin/applications/{id}/delete', [AdminController::class, 'destroy'])->middleware(['auth']);

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
Route::post('/student-profiles', [StudentProfileController::class, 'store'])->name('student-profiles.store')->middleware(['auth', 'verified', 'web']);

// BACKUP SETTINGS ROUTES
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/settings', [BackupSettingsController::class, 'index'])->name('backup.index');
    Route::post('/admin/backup/generate', [BackupSettingsController::class, 'generateManualBackup'])->name('backup.generate');
    Route::post('/admin/backup/update-settings', [BackupSettingsController::class, 'updateAutoBackupSettings'])->name('backup.update-auto-settings');
    Route::post('/admin/backup/restore/{id}', [BackupSettingsController::class, 'restoreApplicant'])->name('backup.restore-applicant');
    Route::get('/admin/backup/applicant-info/{id}', [BackupSettingsController::class, 'getApplicantInfo'])->name('backup.applicant-info');
});

// BACKUP API ROUTES
Route::middleware(['auth', 'verified'])->prefix('api/backup')->group(function () {
    Route::post('/create', [BackupController::class, 'create']);
    Route::get('/list', [BackupController::class, 'list']);
    Route::get('/{id}/download', [BackupController::class, 'download']);
    Route::post('/{id}/restore', [BackupController::class, 'restore']);
    Route::delete('/{id}', [BackupController::class, 'delete']);
    Route::get('/schedule/get', [BackupController::class, 'getSchedule']);
    Route::post('/schedule/update', [BackupController::class, 'updateSchedule']);
});