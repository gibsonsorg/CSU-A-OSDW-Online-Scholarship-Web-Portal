<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display main admin dashboard with all applications
     */
    public function dashboard()
    {
        $recentApplications = StudentProfile::whereNull('deleted_at')->orderBy('created_at', 'desc')->take(10)->get();
        $filterType = 'all';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    /**
     * Display admin dashboard filtered by academic grants
     */
    public function academicDashboard()
    {
        $recentApplications = StudentProfile::where('grant_type', 'academic')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $filterType = 'academic';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    /**
     * Display admin dashboard filtered by non-academic grants
     */
    public function nonAcademicDashboard()
    {
        $recentApplications = StudentProfile::where('grant_type', 'non-academic')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $filterType = 'non-academic';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    /**
     * List registered users for admin.
     */
    public function users(\Illuminate\Http\Request $request)
    {
        $users = \App\Models\User::orderBy('created_at', 'desc')->get();
        $recentApplications = collect();
        $filterType = 'users';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['users' => $users]);
        }

        return view('admin.welcome', compact('users', 'recentApplications', 'filterType'));
    }

    /**
     * Approve an application
     */
    public function approve($id)
    {
        $application = StudentProfile::find($id);
        if ($application) {
            $application->application_status = 'approved';
            $application->save();
            return response()->json(['status' => 'ok', 'message' => 'Application approved']);
        }
        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
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

        $user->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'User account removed',
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

        $path = storage_path('app/public/' . $user->id_document);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
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
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
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
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
