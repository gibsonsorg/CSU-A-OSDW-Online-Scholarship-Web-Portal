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
        $recentApplications = StudentProfile::orderBy('created_at', 'desc')->take(10)->get();
        $filterType = 'all';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
    }

    /**
     * Display admin dashboard filtered by academic grants
     */
    public function academicDashboard()
    {
        $recentApplications = StudentProfile::where('grant_type', 'academic')
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
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $filterType = 'non-academic';
        return view('admin.welcome', compact('recentApplications', 'filterType'));
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
