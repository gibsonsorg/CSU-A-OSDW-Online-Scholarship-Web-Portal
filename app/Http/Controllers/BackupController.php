<?php

namespace App\Http\Controllers;

use App\Models\BackupSchedule;
use App\Models\BackupHistory;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Create a manual backup and initiate download
     */
    public function create(): JsonResponse
    {
        try {
            $result = $this->backupService->createBackup('manual');
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Backup created successfully',
                    'filename' => $result['filename'],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($id)
    {
        try {
            $backup = BackupHistory::findOrFail($id);
            $filePath = storage_path('app/' . $backup->file_path);

            if (!file_exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Backup file not found',
                ], 404);
            }

            return response()->download($filePath, $backup->filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Download failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all backups
     */
    public function list(): JsonResponse
    {
        try {
            $backups = $this->backupService->getAllBackups();
            $formatted = $backups->map(function($backup) {
                return [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'size' => BackupService::formatFileSize($backup->file_size),
                    'size_bytes' => $backup->file_size,
                    'type' => ucfirst($backup->backup_type),
                    'status' => ucfirst($backup->status),
                    'created_at' => $backup->created_at->format('Y-m-d H:i:s'),
                    'created_at_short' => $backup->created_at->format('M d, Y g:i A'),
                    'error_message' => $backup->error_message,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restore($id): JsonResponse
    {
        try {
            $result = $this->backupService->restore($id);
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function delete($id): JsonResponse
    {
        try {
            $result = $this->backupService->deleteBackup($id);
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup schedule settings
     */
    public function getSchedule(): JsonResponse
    {
        try {
            $schedule = BackupSchedule::first() ?? BackupSchedule::create([
                'frequency' => 'daily',
                'time' => '03:00',
                'enabled' => true,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $schedule->id,
                    'frequency' => $schedule->frequency,
                    'time' => $schedule->time,
                    'day_of_week' => $schedule->day_of_week,
                    'enabled' => $schedule->enabled,
                    'last_run' => $schedule->last_run ? $schedule->last_run->format('Y-m-d H:i:s') : null,
                    'last_status' => $schedule->last_status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update backup schedule settings
     */
    public function updateSchedule(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'enabled' => 'required|boolean',
                'frequency' => 'required|in:daily,weekly,monthly',
                'time' => 'required|date_format:H:i',
                'day_of_week' => 'nullable|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            ]);

            $schedule = BackupSchedule::first() ?? BackupSchedule::create([
                'frequency' => 'daily',
                'time' => '03:00',
                'enabled' => true,
            ]);

            $schedule->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule updated successfully',
                'data' => [
                    'id' => $schedule->id,
                    'frequency' => $schedule->frequency,
                    'time' => $schedule->time,
                    'day_of_week' => $schedule->day_of_week,
                    'enabled' => $schedule->enabled,
                    'last_run' => $schedule->last_run ? $schedule->last_run->format('Y-m-d H:i:s') : null,
                    'last_status' => $schedule->last_status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
