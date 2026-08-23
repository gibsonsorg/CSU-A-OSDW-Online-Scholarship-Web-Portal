<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationStatusMail;
use App\Mail\ApprovalMail;
use App\Mail\RejectionMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Display main admin dashboard with all applications
     */
    public function dashboard()
    {
        $recentApplications = StudentProfile::whereNull('deleted_at')
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id')
            ->orderBy('applications.created_at')
            ->orderBy('student_profiles.created_at')
            ->select('student_profiles.*')
            ->take(10)
            ->get();
        $filterType = 'all';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    /**
     * Display admin dashboard filtered by academic grants
     */
    public function academicDashboard(Request $request)
    {
        $recentApplications = $this->filteredApplications($request, 'academic');
        $filterType = 'academic';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    /**
     * Display admin dashboard filtered by non-academic grants
     */
    public function nonAcademicDashboard(Request $request)
    {
        $recentApplications = $this->filteredApplications($request, 'non-academic');
        $filterType = 'non-academic';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    private function filteredApplications(Request $request, string $grantType)
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'approved', 'pending', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = StudentProfile::where('grant_type', $grantType)
            ->whereNull('deleted_at')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($query) use ($like) {
                    $query->where('last_name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('course', 'like', $like)
                        ->orWhere('scholarship_type', 'like', $like)
                        ->orWhere('scholarship_name', 'like', $like);
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('application_status', $status);
            })
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id')
            ->select('student_profiles.*')
            ->orderBy('applications.created_at')
            ->orderBy('student_profiles.created_at');

        return $query->paginate(10)->withQueryString();
    }

    /**
     * List registered users for admin.
     */
    public function users(\Illuminate\Http\Request $request)
    {
        $users = \App\Models\User::orderBy('created_at', 'desc')->get();
        $recentApplications = collect();
        $filterType = 'users';

        if ($request->wantsJson()) {
            return response()->json(['users' => $users]);
        }

        if ($request->ajax()) {
            return view('admin.partials.users-table', compact('users'));
        }

        return view('admin.welcome', compact('users', 'recentApplications', 'filterType'));
    }

    /**
     * Approve an application
     */
    public function approve($id)
    {
        $application = StudentProfile::find($id);
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $application->application_status = 'approved';
        $application->rejection_reason = null;
        $application->save();

        $this->syncApplicationRecord($application, 'approved');

        try {
            $user = $application->user()->first();
            if ($user) {
                Mail::to($user->email)->send(new ApplicationStatusMail(
                    $user->name ?? $application->first_name . ' ' . $application->last_name,
                    $application->scholarship_name ?? 'Scholarship',
                    'approved'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Application approval notification failed', ['application_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'ok',
                'message' => 'Application approved, but the notification could not be queued.',
            ]);
        }

        return response()->json(['status' => 'ok', 'message' => 'Application approved']);
    }

    /**
     * Approve a pending user account so they can log in.
     */
    public function approveUser($id)
    {
        $user = \App\Models\User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $user->forceFill([
            'account_status' => 'approved',
            'rejection_reason' => null,
        ])->save();

        try {
            Mail::to($user->email)->send(new ApprovalMail($user));
        } catch (\Throwable $e) {
            Log::error('User approval notification failed', ['user_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'User approved, but the notification could not be queued.',
            ], 500);
        }

        return response()->json(['status' => 'ok', 'message' => 'User approved']);
    }

    /**
     * Reject an application
     */
    public function reject(Request $request, $id)
    {
        $application = StudentProfile::find($id);
        if ($application) {
            $application->application_status = 'rejected';
            $application->rejection_reason = $request->input('reason', '');
            $application->save();

            $this->syncApplicationRecord($application, 'rejected');

            try {
                $user = $application->user()->first();
                if ($user) {
                    Mail::to($user->email)->send(new ApplicationStatusMail(
                        $user->name ?? $application->first_name . ' ' . $application->last_name,
                        $application->scholarship_name ?? 'Scholarship',
                        'rejected'
                    ));
                }
            } catch (\Throwable $e) {
                Log::error('Application rejection notification failed', ['application_id' => $id, 'exception' => $e]);
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Application rejected, but the notification could not be queued.',
                ]);
            }

            return response()->json(['status' => 'ok', 'message' => 'Application rejected']);
        }
        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
    }

    /**
     * Reject a user account and remove it from the system.
     */
    public function rejectUser(Request $request, $id)
    {
        $user = \App\Models\User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $message = (string) $request->input('reason', $request->input('message', ''));

        $user->forceFill([
            'account_status' => 'rejected',
            'rejection_reason' => $message,
        ])->save();

        try {
            Mail::to($user->email)->send(new RejectionMail($user, $message));
        } catch (\Throwable $e) {
            Log::error('User rejection notification failed', ['user_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'User rejected, but the notification could not be queued.',
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'User account rejected',
        ]);
    }

    /**
     * Download or view the uploaded ID document for a user.
     */
    public function viewIdDocument($id)
    {
        $user = \App\Models\User::find($id);

        if (! $user || ! $user->id_document) {
            abort(404);
        }

        [$disk, $path] = $this->findDocument($user->id_document);

        if (!$path) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * View an uploaded scholarship document through an authorized admin route.
     */
    public function viewApplicationDocument($id, string $group, int $index)
    {
        $application = StudentProfile::findOrFail($id);
        $uploads = $application->uploads;

        if (!is_array($uploads) || $index < 0) {
            abort(404);
        }

        $files = $uploads[$group] ?? ($group === 'uploads' && array_is_list($uploads) ? $uploads : null);
        if (!is_array($files) || !isset($files[$index]) || !is_string($files[$index])) {
            abort(404);
        }

        [$disk, $path] = $this->findDocument($files[$index]);
        if (!$path) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    private function findDocument(string $storedPath): array
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $storedPath), '/');
        if ($normalizedPath === '' || str_contains($normalizedPath, '../') || str_contains($normalizedPath, '..\\')) {
            return [null, null];
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($normalizedPath)) {
                return [$disk, $normalizedPath];
            }
        }

        return [null, null];
    }

    /**
     * Keep the applications table in sync with student profile status.
     */
    private function syncApplicationRecord(StudentProfile $profile, string $status): void
    {
        if (!$profile->user_id || !$profile->scholarship_name) {
            return;
        }

        $scholarship = Scholarship::where('name', $profile->scholarship_name)->first();

        if (!$scholarship) {
            return;
        }

        Application::whereKey($profile->application_id)
            ->update(['status' => $status]);
    }

    /**
     * Update student profile
     */
    public function update(Request $request, $id)
    {
        try {
            $application = StudentProfile::findOrFail($id);
            
            $updateData = $request->only([
                'first_name', 'middle_name', 'last_name', 'sex', 'status', 'email',
                'contact_number', 'course', 'year_level', 'scholarship_name',
                'scholarship_type', 'home_address', 'program', 'id_number',
                'birthdate', 'birthplace', 'religion'
            ]);
            
            $application->update($updateData);
            
            return response()->json(['status' => 'ok', 'message' => 'Profile updated successfully', 'data' => $application]);
        } catch (\Exception $e) {
            Log::error('Failed to update application profile', ['application_id' => $id, 'exception' => $e]);
            return response()->json(['status' => 'error', 'message' => 'Unable to update application.'], 500);
        }
    }

    /**
     * Delete an application
     */
    public function destroy($id)
    {
        try {
            $application = StudentProfile::findOrFail($id);
            $application->delete();
            return response()->json(['status' => 'ok', 'message' => 'Application deleted']);
        } catch (\Exception $e) {
            Log::error('Failed to delete application', ['application_id' => $id, 'exception' => $e]);
            return response()->json(['status' => 'error', 'message' => 'Unable to delete application.'], 500);
        }
    }
}
