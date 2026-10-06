<?php

namespace App\Services;

use App\Models\BackupHistory;
use App\Models\BackupLog;
use Exception;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Throwable;

class BackupService
{
    public function __construct(private GoogleDriveBackupStorage $googleDriveStorage)
    {
    }

    public function backupPath(string $relativePath): string
    {
        $backupRoot = realpath(storage_path('app/backups'));
        $candidate = realpath(storage_path('app/' . ltrim($relativePath, '/\\')));

        if (!$backupRoot || !$candidate || !str_starts_with($candidate, $backupRoot . DIRECTORY_SEPARATOR)) {
            throw new Exception('Invalid backup path');
        }

        return $candidate;
    }

    /**
     * Create a backup of the database
     */
    public function createBackup($type = 'manual'): array
    {
        $sqlFilename = null;
        $zipFilename = null;
        $zipPath = null;

        try {
            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $sqlFilename = "backup_$timestamp.sql";
            $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlFilename;

            $this->createSqlDump($sqlPath);

            if (!file_exists($sqlPath)) {
                throw new Exception('Failed to create database dump');
            }

            $zipFilename = "backup_$timestamp.zip";
            $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Failed to create ZIP file');
            }

            $zip->addFile($sqlPath, $sqlFilename);
            $zip->close();

            unlink($sqlPath);

            $finalSize = filesize($zipPath);

            if ($type === 'automatic') {
                $this->uploadBackupToGoogle($zipPath, $zipFilename);
                if (! unlink($zipPath)) {
                    throw new Exception('Google Drive upload succeeded, but the local backup could not be deleted.');
                }
                $storage = 'google';

                try {
                    $this->pruneGoogleDriveBackups();
                } catch (Throwable $e) {
                    Log::warning('Google Drive backup retention cleanup failed', ['exception' => $e]);
                }
            } else {
                $storage = 'local';
            }

            $backup = BackupHistory::create([
                'filename' => $zipFilename,
                'file_path' => 'backups/' . $zipFilename,
                'file_size' => $finalSize,
                'backup_type' => $type,
                'status' => 'success',
            ]);

            $this->logBackupAttempt($type, $zipFilename, $finalSize, 'success', $storage, null);

            return [
                'status' => 'success',
                'message' => 'Backup created successfully',
                'filename' => $zipFilename,
                'filepath' => $zipPath,
                'backup' => $backup,
            ];
        } catch (Exception $e) {
            Log::error('Backup creation failed', ['type' => $type, 'exception' => $e]);

            $friendlyMessage = $this->formatGoogleDriveErrorMessage($e, $type);
            $filename = $zipFilename ?? ($sqlFilename ?? 'unknown');
            $size = $zipPath && file_exists($zipPath) ? filesize($zipPath) : 0;

            BackupHistory::create([
                'filename' => $filename,
                'file_path' => 'backups/' . $filename,
                'file_size' => $size,
                'backup_type' => $type,
                'status' => 'failed',
                'error_message' => $friendlyMessage,
            ]);

            $this->logBackupAttempt($type, $filename, $size, 'failed', $type === 'automatic' ? 'google' : 'local', $friendlyMessage);

            return [
                'status' => 'error',
                'message' => $friendlyMessage,
            ];
        }
    }

    public function uploadBackupToGoogle(string $zipPath, string $filename): void
    {
        $folderId = trim((string) config('filesystems.disks.google.folder', env('GOOGLE_DRIVE_FOLDER_ID', '')));

        if ($folderId === '') {
            throw new Exception('Google Drive folder ID is missing. Please set GOOGLE_DRIVE_FOLDER_ID.');
        }

        $this->googleDriveStorage->upload($zipPath, basename($filename));
    }

    public function pruneGoogleDriveBackups(): void
    {
        $this->googleDriveStorage->pruneBackups();
    }

    public function hasGoogleBackup(string $filename): bool
    {
        return $this->googleDriveStorage->hasBackup($filename);
    }

    public function getGoogleBackupContents(string $filename): string
    {
        return $this->googleDriveStorage->getBackupContents($filename);
    }

    public function deleteGoogleBackup(string $filename): void
    {
        $this->googleDriveStorage->deleteBackup($filename);
    }

    private function logBackupAttempt(string $type, string $filename, int $size, string $status, string $storage, ?string $errorMessage): void
    {
        BackupLog::create([
            'type' => $type,
            'filename' => $filename,
            'size' => $size,
            'status' => $status,
            'storage' => $storage,
            'error_message' => $errorMessage,
        ]);
    }

    private function formatGoogleDriveErrorMessage(Exception $e, string $type): string
    {
        $message = strtolower($e->getMessage());

        if ($type === 'automatic' && str_contains($message, 'folder id is missing')) {
            return 'Google Drive folder ID is missing. Please set GOOGLE_DRIVE_FOLDER_ID.';
        }

        if ($type === 'automatic' && (
            str_contains($message, 'invalid_grant') ||
            str_contains($message, 'oauth') ||
            str_contains($message, 'unauthorized') ||
            str_contains($message, 'auth')
        )) {
            return 'Google Drive token expired. Please generate a new refresh token.';
        }

        return $type === 'automatic' ? 'Google Drive upload failed.' : 'Backup operation failed. Please check the application logs for details.';
    }

    /**
     * Create an SQL dump of the database
     */
    protected function createSqlDump($filePath): void
    {
        $dbHost = env('DB_HOST', 'localhost');
        $dbName = env('DB_DATABASE', 'scholarship_portal');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPassword = env('DB_PASSWORD', '');

        $command = 'mysqldump --host=' . escapeshellarg($dbHost) . ' --user=' . escapeshellarg($dbUser);

        if (!empty($dbPassword)) {
            $command .= ' --password=' . escapeshellarg($dbPassword);
        }

        $command .= " " . escapeshellarg($dbName) . " > " . escapeshellarg($filePath) . " 2>&1";

        $output = null;
        $returnCode = null;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('mysqldump failed: ' . implode("\n", $output));
        }
    }

    /**
     * Restore database from backup file
     */
    public function restore($backupId): array
    {
        $tempDir = null;
        $tempBackupFile = null;

        try {
            $backup = BackupHistory::findOrFail($backupId);
            $backupPath = null;

            $localBackupPath = storage_path('app/' . ltrim($backup->file_path, '/\\'));
            if (file_exists($localBackupPath)) {
                $backupPath = $localBackupPath;
            } elseif ($backup->backup_type === 'automatic' && $this->hasGoogleBackup($backup->filename)) {
                $tempBackupFile = storage_path('app/backups/' . basename($backup->filename));
                file_put_contents($tempBackupFile, $this->getGoogleBackupContents($backup->filename));
                $backupPath = $tempBackupFile;
            }

            if (!$backupPath) {
                throw new Exception('Backup file not found');
            }

            $tempDir = storage_path('app/backups/temp_' . uniqid('', true));
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new Exception('Failed to open backup ZIP file');
            }

            $sqlEntries = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = $zip->getNameIndex($index);
                $normalizedEntry = $entryName === false ? '' : str_replace('\\', '/', $entryName);
                $externalAttributes = 0;
                $externalAttributesType = 0;
                $zip->getExternalAttributesIndex($index, $externalAttributesType, $externalAttributes);
                $isSymlink = (($externalAttributes >> 16) & 0xF000) === 0xA000;

                if ($entryName === false || str_ends_with($normalizedEntry, '/') || $isSymlink ||
                    str_contains($normalizedEntry, '../') || str_starts_with($normalizedEntry, '/') ||
                    preg_match('/^[A-Za-z]:\\//', $normalizedEntry) ||
                    dirname($normalizedEntry) !== '.' || pathinfo($normalizedEntry, PATHINFO_EXTENSION) !== 'sql' ||
                    !preg_match('/^backup_[0-9_-]+\.sql$/', basename($normalizedEntry))) {
                    $zip->close();
                    throw new Exception('Invalid backup archive');
                }
                $sqlEntries++;
            }

            if ($sqlEntries !== 1) {
                $zip->close();
                throw new Exception('Invalid backup archive contents');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            $files = scandir($tempDir);
            $sqlFile = null;
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $sqlFile = $tempDir . DIRECTORY_SEPARATOR . $file;
                    break;
                }
            }

            if (!$sqlFile || !file_exists($sqlFile)) {
                throw new Exception('SQL file not found in backup');
            }

            $this->restoreFromSql($sqlFile);

            return [
                'status' => 'success',
                'message' => 'Database restored successfully from ' . $backup->filename,
            ];
        } catch (Exception $e) {
            Log::error('Backup restore failed', ['backup_id' => $backupId, 'exception' => $e]);
            return [
                'status' => 'error',
                'message' => 'Restore failed.',
            ];
        } finally {
            if ($tempBackupFile && file_exists($tempBackupFile)) {
                unlink($tempBackupFile);
            }

            if ($tempDir && is_dir($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Restore database from SQL file
     */
    private function restoreFromSql($sqlFilePath): void
    {
        $dbHost = env('DB_HOST', 'localhost');
        $dbName = env('DB_DATABASE', 'scholarship_portal');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPassword = env('DB_PASSWORD', '');

        $command = 'mysql --host=' . escapeshellarg($dbHost) . ' --user=' . escapeshellarg($dbUser);

        if (!empty($dbPassword)) {
            $command .= ' --password=' . escapeshellarg($dbPassword);
        }

        $command .= " " . escapeshellarg($dbName) . " < " . escapeshellarg($sqlFilePath) . " 2>&1";

        $output = null;
        $returnCode = null;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('mysql restore failed: ' . implode("\n", $output));
        }
    }

    /**
     * Delete a backup
     */
    public function deleteBackup($backupId): array
    {
        try {
            $backup = BackupHistory::findOrFail($backupId);
            $localFilePath = storage_path('app/' . ltrim($backup->file_path, '/\\'));

            if (file_exists($localFilePath)) {
                unlink($localFilePath);
            } elseif ($backup->backup_type === 'automatic' && $this->hasGoogleBackup($backup->filename)) {
                $this->deleteGoogleBackup($backup->filename);
            }

            $backup->delete();

            return [
                'status' => 'success',
                'message' => 'Backup deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Backup deletion failed', ['backup_id' => $backupId, 'exception' => $e]);
            return [
                'status' => 'error',
                'message' => 'Delete failed.',
            ];
        }
    }

    /**
     * Get all backups sorted by newest first
     */
    public function getAllBackups()
    {
        return BackupHistory::orderBy('created_at', 'desc')->get();
    }

    /**
     * Format file size in human readable format
     */
    public static function formatFileSize($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return number_format($bytes) . ' B';
        }
    }
}
