<?php

namespace App\Http\Controllers;

use App\Models\BackupSchedule;
use App\Models\BackupHistory;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

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
                $backupId = $result['backup']->id ?? null;
                if (!$backupId) {
                    throw new \RuntimeException('Backup was created without a history record.');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Backup created successfully',
                    'filename' => $result['filename'],
                    'backup_id' => $backupId,
                    'download_url' => route('backup.api.download', ['id' => $backupId]),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 500);
        } catch (Throwable $e) {
            Log::error('Manual backup API request failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create backup. Check the application log for details.',
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
            $localFilePath = storage_path('app/' . ltrim($backup->file_path, '/\\'));

            if (file_exists($localFilePath)) {
                return response()->download($localFilePath, $backup->filename, ['Content-Type' => 'application/zip']);
            }

            if ($backup->backup_type === 'automatic' && $this->backupService->hasGoogleBackup($backup->filename)) {
                $contents = $this->backupService->getGoogleBackupContents($backup->filename);

                return response()->streamDownload(static function () use ($contents): void {
                    echo $contents;
                }, $backup->filename, ['Content-Type' => 'application/zip']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Backup file not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Backup download API request failed', ['backup_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Download failed. Check the application log for details.',
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
        } catch (Throwable $e) {
            Log::error('Backup history API request failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to load backups.',
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
        } catch (Throwable $e) {
            Log::error('Backup restore API request failed', ['backup_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Restore failed. Check the application log for details.',
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
        } catch (Throwable $e) {
            Log::error('Backup deletion API request failed', ['backup_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Delete failed. Check the application log for details.',
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
        } catch (Throwable $e) {
            Log::error('Backup schedule API request failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to load backup schedule.',
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
        } catch (Throwable $e) {
            Log::warning('Backup schedule update API request failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update backup schedule.',
            ], 422);
        }
    }
}
