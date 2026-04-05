<?php

namespace App\Services;

use App\Models\BackupHistory;
use Exception;
use ZipArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    /**
     * Create a backup of the database
     */
    public function createBackup($type = 'manual'): array
    {
        try {
            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Create SQL dump
            $timestamp = date('Y-m-d_H-i-s');
            $sqlFilename = "backup_$timestamp.sql";
            $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlFilename;

            $this->createSqlDump($sqlPath);

            // Verify the dump was created
            if (!file_exists($sqlPath)) {
                throw new Exception('Failed to create database dump');
            }

            // Get file size
            $fileSize = filesize($sqlPath);

            // Create ZIP file
            $zipFilename = "backup_$timestamp.zip";
            $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Failed to create ZIP file');
            }

            $zip->addFile($sqlPath, $sqlFilename);
            $zip->close();

            // Delete the SQL file (keep only ZIP)
            unlink($sqlPath);

            // Get the final ZIP file size
            $finalSize = filesize($zipPath);

            // Record in database
            $backup = BackupHistory::create([
                'filename' => $zipFilename,
                'file_path' => 'backups/' . $zipFilename,
                'file_size' => $finalSize,
                'backup_type' => $type,
                'status' => 'success',
            ]);

            return [
                'status' => 'success',
                'message' => 'Backup created successfully',
                'filename' => $zipFilename,
                'filepath' => $zipPath,
                'backup' => $backup,
            ];
        } catch (Exception $e) {
            // Record failed backup
            BackupHistory::create([
                'filename' => $sqlFilename ?? 'unknown',
                'file_path' => 'backups/' . ($sqlFilename ?? 'unknown'),
                'file_size' => 0,
                'backup_type' => $type,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Backup failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create an SQL dump of the database
     */
    private function createSqlDump($filePath): void
    {
        $dbHost = env('DB_HOST', 'localhost');
        $dbName = env('DB_DATABASE', 'scholarship_portal');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPassword = env('DB_PASSWORD', '');

        $command = "mysqldump --host=$dbHost --user=$dbUser";
        
        if (!empty($dbPassword)) {
            $command .= " --password={$dbPassword}";
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
        try {
            $backup = BackupHistory::findOrFail($backupId);

            if (!file_exists(storage_path('app/' . $backup->file_path))) {
                throw new Exception('Backup file not found');
            }

            $backupPath = storage_path('app/' . $backup->file_path);
            $tempDir = storage_path('app/backups/temp_' . time());

            // Extract ZIP
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new Exception('Failed to open backup ZIP file');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // Find SQL file
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

            // Restore database
            $this->restoreFromSql($sqlFile);

            // Cleanup
            exec('rm -rf ' . escapeshellarg($tempDir) . ' 2>/dev/null || rmdir /s /q ' . escapeshellarg($tempDir));

            return [
                'status' => 'success',
                'message' => 'Database restored successfully from ' . $backup->filename,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Restore failed: ' . $e->getMessage(),
            ];
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

        $command = "mysql --host=$dbHost --user=$dbUser";
        
        if (!empty($dbPassword)) {
            $command .= " --password={$dbPassword}";
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
            $filePath = storage_path('app/' . $backup->file_path);

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $backup->delete();

            return [
                'status' => 'success',
                'message' => 'Backup deleted successfully',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Delete failed: ' . $e->getMessage(),
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
