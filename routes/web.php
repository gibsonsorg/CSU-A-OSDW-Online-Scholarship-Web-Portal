<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OldGrantController;
use App\Http\Controllers\BackupSettingsController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ScholarshipController;
use App\Models\StudentProfile;
use App\Models\Application;
use App\Models\Scholarship;
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
    $section = request()->query('section', 'acad');
    $appliedScholarships = [];
    $activeAcademicApplications = 0;
    $activeNonAcademicApplications = 0;

    $scholarshipIdMap = [];

    if (auth()->check()) {
        $appliedScholarships = StudentProfile::where('user_id', auth()->id())
            ->whereIn('application_status', ['pending', 'approved', 'rejected'])
            ->with('application:id,scholarship_id,created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('application_id')
            ->get(['id', 'scholarship_name', 'application_status', 'application_id', 'grant_type', 'reapply_count', 'locked_at', 'created_at'])
            ->groupBy(fn ($profile) => $profile->grant_type . '|' . ($profile->application?->scholarship_id ?? $profile->scholarship_name))
            ->map(function ($records) {
                $latest = $records->sortByDesc('created_at')->first();
                $profileQuery = StudentProfile::where('user_id', auth()->id())
                    ->where('grant_type', $latest->grant_type)
                    ->with('familyBackground');
                if ($latest->application_id) {
                    $profileQuery->where('application_id', $latest->application_id);
                } else {
                    $profileQuery->where('scholarship_name', $latest->scholarship_name);
                }
                $profile = $profileQuery->orderByDesc('id')->first();
                $documentReviews = $profile?->document_review ?? $profile?->document_reviews ?? [];
                $hasRejectedDocument = collect($documentReviews)->contains(fn ($review) => ($review['status'] ?? null) === 'rejected');
                $sectionStatuses = [
                    $profile?->student_information_status,
                    $profile?->personal_information_status,
                    $profile?->scholarship_requirements_status,
                ];
                if ($profile?->grant_type === 'non-academic') {
                    $sectionStatuses[] = $profile->family_background_status;
                }
                $hasRejectedSection = in_array('rejected', $sectionStatuses, true);

                return [
                    'name' => trim($latest->scholarship_name),
                    'status' => $latest->application_status ?? 'pending',
                    'scholarship_id' => $latest->application?->scholarship_id,
                    'grant_type' => $latest->grant_type,
                    'locked' => !is_null($profile?->locked_at),
                    'reopen' => $hasRejectedSection || $hasRejectedDocument,
                    'profile' => $profile,
                ];
            })
            ->values()
            ->toArray();

        $activeAcademicApplications = Application::where('applications.user_id', auth()->id())
            ->join('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
            ->leftJoin('student_profiles', function ($join) {
                $join->on('student_profiles.application_id', '=', 'applications.id')
                    ->whereNull('student_profiles.deleted_at');
            })
            ->where('scholarships.type', 'academic')
            ->where(function ($query) {
                $query->whereIn('applications.status', ['pending', 'approved'])
                    ->orWhereNotNull('student_profiles.locked_at');
            })
            ->distinct('applications.id')
            ->count();

        $activeNonAcademicApplications = StudentProfile::where('user_id', auth()->id())
            ->where('grant_type', 'non-academic')
            ->where(function ($query) {
                $query->whereIn('application_status', ['pending', 'approved'])
                    ->orWhereNotNull('locked_at');
            })
            ->count();
    }

    $academicScholarshipIdMap = Scholarship::where('type', 'academic')->pluck('id', 'name')->toArray();
    $nonAcademicScholarshipIdMap = Scholarship::where('type', 'non-academic')->pluck('id', 'name')->toArray();
    $dynamicScholarships = Scholarship::whereIn('status', ['active', 'inactive'])
        ->whereNotNull('short_information')
        ->where('type', $section === 'non' ? 'non-academic' : 'academic')
        ->orderBy('name')
        ->get();

    return view('student.dashboard', compact('appliedScholarships', 'activeAcademicApplications', 'activeNonAcademicApplications', 'academicScholarshipIdMap', 'nonAcademicScholarshipIdMap', 'dynamicScholarships'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/application-status', [StudentProfileController::class, 'applicationStatus'])
    ->middleware(['auth', 'verified'])
    ->name('application-status');

Route::get('/student-application-prefill', [StudentProfileController::class, 'applicationPrefill'])
    ->middleware(['auth', 'verified', 'web'])
    ->name('student.application-prefill');

//ADMIN DASHBOARD ROUTE 
// Admin dashboard - shows recent applications
Route::get('/admin', [AdminController::class, 'dashboard'])->middleware(['auth', 'verified', 'admin'])->name('admin.dashboard');
Route::get('/admin/reports/download', [AdminController::class, 'exportApprovedGrantees'])->middleware(['auth', 'verified', 'admin'])->name('admin.dashboard.export');

// Admin dashboard filtered by academic grants
Route::get('/admin/academic', [AdminController::class, 'academicDashboard'])->middleware(['auth', 'verified', 'admin'])->name('admin.academic');

// Admin dashboard filtered by non-academic grants
Route::get('/admin/non-academic', [AdminController::class, 'nonAcademicDashboard'])->middleware(['auth', 'verified', 'admin'])->name('admin.non_academic');

// Admin scholarship creation
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/scholarships/create', [ScholarshipController::class, 'create'])->name('admin.scholarships.create');
    Route::post('/admin/scholarships', [ScholarshipController::class, 'store'])->name('admin.scholarships.store');
    Route::get('/admin/scholarships/{scholarship}/edit', [ScholarshipController::class, 'edit'])->name('admin.scholarships.edit');
    Route::put('/admin/scholarships/{scholarship}', [ScholarshipController::class, 'update'])->name('admin.scholarships.update');
    Route::patch('/admin/scholarships/{scholarship}/status', [ScholarshipController::class, 'updateStatus'])->name('admin.scholarships.status');
    Route::delete('/admin/scholarships/{scholarship}', [ScholarshipController::class, 'destroy'])->name('admin.scholarships.destroy');
});

// Old Grants routes
Route::get('/admin/old-academic', [OldGrantController::class, 'academicGrants'])->middleware(['auth', 'verified', 'admin'])->name('admin.old_academic');
Route::get('/admin/old-non-academic', [OldGrantController::class, 'nonAcademicGrants'])->middleware(['auth', 'verified', 'admin'])->name('admin.old_non_academic');
Route::post('/admin/old-grants/import', [OldGrantController::class, 'import'])->middleware(['auth', 'verified', 'admin']);

// Admin users list route
Route::get('/admin/users', [AdminController::class, 'users'])->middleware(['auth', 'verified', 'admin'])->name('admin.users');
Route::get('/admin/users/{id}/id-document', [AdminController::class, 'viewIdDocument'])->middleware(['auth', 'verified', 'admin'])->name('admin.users.id_document');
Route::get('/admin/applications/{id}/document/{group}/{index}', [AdminController::class, 'viewApplicationDocument'])->whereNumber('id')->whereNumber('index')->middleware(['auth', 'verified', 'admin'])->name('admin.applications.document');
Route::post('/admin/users/{id}/approve', [AdminController::class, 'approveUser'])->middleware(['auth', 'verified', 'admin']);
Route::post('/admin/users/{id}/reject', [AdminController::class, 'rejectUser'])->middleware(['auth', 'verified', 'admin']);
Route::post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser'])->middleware(['auth', 'verified', 'admin']);

// ADMIN APPLICATION MANAGEMENT ROUTES
// Fetch single application details as JSON
// From: app/Http/Controllers/AdminController - application detail view
Route::get('/admin/applications/{id}', function($id){
    $p = StudentProfile::with('familyBackground')->find($id);
    if (!$p) return response()->json(['error'=>'not_found'], 404);
    return response()->json($p);
})->middleware(['auth', 'admin']);

// Approve application endpoint
Route::post('/admin/applications/{id}/approve', [AdminController::class, 'approve'])->middleware(['auth', 'admin']);

// Reject application endpoint - uses AdminController
Route::post('/admin/applications/{id}/reject', [AdminController::class, 'reject'])->middleware(['auth', 'admin']);
Route::post('/admin/applications/{id}/sections/{section}/approve', [AdminController::class, 'approveSection'])->middleware(['auth', 'admin']);
Route::post('/admin/applications/{id}/sections/{section}/reject', [AdminController::class, 'rejectSection'])->middleware(['auth', 'admin']);
Route::post('/admin/applications/{id}/documents/{group}/{index}/approve', [AdminController::class, 'approveDocument'])->whereNumber('id')->whereNumber('index')->middleware(['auth', 'admin']);
Route::post('/admin/applications/{id}/documents/{group}/{index}/reject', [AdminController::class, 'rejectDocument'])->whereNumber('id')->whereNumber('index')->middleware(['auth', 'admin']);

// Update application endpoint - uses AdminController
Route::post('/admin/applications/{id}/update', [AdminController::class, 'update'])->middleware(['auth', 'admin']);
Route::post('/admin/applications/update-scholarship-amounts', [AdminController::class, 'updateScholarshipAmounts'])->middleware(['auth', 'admin']);
Route::post('/admin/manual-applicants', [AdminController::class, 'storeManualApplicant'])->middleware(['auth', 'admin']);
Route::put('/admin/manual-applicants/{id}', [AdminController::class, 'updateManualApplicant'])->whereNumber('id')->middleware(['auth', 'admin']);
Route::delete('/admin/manual-applicants/{id}', [AdminController::class, 'destroyManualApplicant'])->whereNumber('id')->middleware(['auth', 'admin']);
Route::get('/admin/academic/manual-applicants/college-department', [AdminController::class, 'academicManualApplicantCollegeDepartment'])->middleware(['auth', 'admin'])->name('admin.academic.manual_applicants.college_department');
Route::post('/admin/academic/manual-applicants', [AdminController::class, 'storeAcademicManualApplicant'])->middleware(['auth', 'admin'])->name('admin.academic.manual_applicants.store');
Route::put('/admin/academic/manual-applicants/{id}', [AdminController::class, 'updateAcademicManualApplicant'])->whereNumber('id')->middleware(['auth', 'admin'])->name('admin.academic.manual_applicants.update');
Route::post('/admin/non-academic/manual-applicants', [AdminController::class, 'storeNonAcademicManualApplicant'])->middleware(['auth', 'admin'])->name('admin.non_academic.manual_applicants.store');
Route::put('/admin/non-academic/manual-applicants/{id}', [AdminController::class, 'updateNonAcademicManualApplicant'])->whereNumber('id')->middleware(['auth', 'admin'])->name('admin.non_academic.manual_applicants.update');

// Delete application endpoint - uses AdminController with DELETE method
Route::delete('/admin/applications/{id}', [AdminController::class, 'destroy'])->middleware(['auth', 'admin']);

// Delete application endpoint - POST alias for compatibility
Route::post('/admin/applications/{id}/delete', [AdminController::class, 'destroy'])->middleware(['auth', 'admin']);

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
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/settings', [BackupSettingsController::class, 'index'])->name('backup.index');
    Route::post('/admin/backup/generate', [BackupSettingsController::class, 'generateManualBackup'])->name('backup.generate');
    Route::post('/admin/backup/update-settings', [BackupSettingsController::class, 'updateAutoBackupSettings'])->name('backup.update-auto-settings');
    Route::post('/admin/backup/restore/{id}', [BackupSettingsController::class, 'restoreApplicant'])->name('backup.restore-applicant');
    Route::get('/admin/backup/applicant-info/{id}', [BackupSettingsController::class, 'getApplicantInfo'])->name('backup.applicant-info');
});

// BACKUP API ROUTES
Route::middleware(['auth', 'verified', 'admin'])->prefix('api/backup')->group(function () {
    Route::post('/create', [BackupController::class, 'create']);
    Route::get('/list', [BackupController::class, 'list']);
    Route::get('/{id}/download', [BackupController::class, 'download']);
    Route::post('/{id}/restore', [BackupController::class, 'restore']);
    Route::delete('/{id}', [BackupController::class, 'delete']);
    Route::get('/schedule/get', [BackupController::class, 'getSchedule']);
    Route::post('/schedule/update', [BackupController::class, 'updateSchedule']);
});