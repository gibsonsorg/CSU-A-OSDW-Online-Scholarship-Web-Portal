<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use Illuminate\Http\Request;

class BackupSettingsController extends Controller
{
    public function index()
    {
        // Get all soft-deleted applicants
        $deletedApplicants = StudentProfile::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();
        
        return view('admin.settings', compact('deletedApplicants'));
    }

    public function generateManualBackup()
    {
        try {
            $backupDir = storage_path('backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $backupFilename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backupPath = $backupDir . '/' . $backupFilename;

            $dbHost = env('DB_HOST', 'localhost');
            $dbName = env('DB_DATABASE', 'scholarship_portal');
            $dbUser = env('DB_USERNAME', 'root');
            $dbPassword = env('DB_PASSWORD', '');

            $command = "mysqldump --host=$dbHost --user=$dbUser" . ($dbPassword ? " --password=$dbPassword" : "") . " $dbName > $backupPath 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                return response()->download($backupPath, $backupFilename)->deleteFileAfterSend(false);
            } else {
                return response()->json(['error' => 'Backup creation failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateAutoBackupSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'auto_backup_enabled' => 'required|boolean',
                'backup_frequency' => 'required|in:daily,weekly,monthly',
                'backup_time' => 'required|date_format:H:i',
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function restoreApplicant($id)
    {
        try {
            // Find the soft-deleted applicant
            $applicant = StudentProfile::withTrashed()->findOrFail($id);

            if ($applicant->trashed()) {
                // Restore the applicant
                $applicant->restore();
                
                return response()->json([
                    'status' => 'ok', 
                    'message' => 'Applicant "' . $applicant->first_name . ' ' . $applicant->last_name . '" has been restored successfully',
                    'applicant' => $applicant
                ]);
            }

            return response()->json(['error' => 'Applicant is not deleted'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getApplicantInfo($id)
    {
        try {
            // Retrieve the applicant, including soft-deleted records
            $applicant = StudentProfile::withTrashed()->findOrFail($id);
            return response()->json(['status' => 'ok', 'data' => $applicant]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
