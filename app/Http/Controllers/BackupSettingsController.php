<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            $command = 'mysqldump --host=' . escapeshellarg($dbHost) . ' --user=' . escapeshellarg($dbUser);
            if ($dbPassword) {
                $command .= ' --password=' . escapeshellarg($dbPassword);
            }
            $command .= ' ' . escapeshellarg($dbName) . ' > ' . escapeshellarg($backupPath) . ' 2>&1';
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                return response()->download($backupPath, $backupFilename)->deleteFileAfterSend(false);
            } else {
                return response()->json(['error' => 'Backup creation failed'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Manual backup generation failed', ['exception' => $e]);
            return response()->json(['error' => 'Backup creation failed.'], 500);
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
            Log::warning('Automatic backup settings update failed', ['exception' => $e]);
            return response()->json(['error' => 'Unable to update backup settings.'], 422);
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
            Log::error('Applicant restore failed', ['applicant_id' => $id, 'exception' => $e]);
            return response()->json(['error' => 'Applicant restore failed.'], 500);
        }
    }

    public function getApplicantInfo($id)
    {
        try {
            // Retrieve the applicant, including soft-deleted records
            $applicant = StudentProfile::withTrashed()->findOrFail($id);
            return response()->json(['status' => 'ok', 'data' => $applicant]);
        } catch (\Exception $e) {
            Log::error('Applicant information lookup failed', ['applicant_id' => $id, 'exception' => $e]);
            return response()->json(['error' => 'Unable to load applicant information.'], 500);
        }
    }
}
