<?php

namespace App\Services;

use App\Models\BackupHistory;
use App\Models\BackupLog;
use Exception;
use FilesystemIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
        $sqlPath = null;
        $zipFilename = null;
        $zipPath = null;
        $zipComplete = false;
        $success = false;

        try {
            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                throw new Exception('Unable to create the private backup directory.');
            }
            if (!is_writable($backupDir)) {
                throw new Exception('The private backup directory is not writable.');
            }
            if (!class_exists(ZipArchive::class)) {
                throw new Exception('The PHP ZIP extension is required to create backups. Enable ZipArchive in the web PHP configuration.');
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
                throw new Exception('Unable to create the backup ZIP file. Check storage permissions and available disk space.');
            }

            try {
                if (!$zip->addFile($sqlPath, $sqlFilename)) {
                    throw new Exception('Unable to add the database dump to the backup ZIP.');
                }
                $this->addUploadedFilesToArchive($zip);
            } catch (Throwable $e) {
                $zip->close();
                throw $e;
            }

            if (!$zip->close()) {
                throw new Exception('Unable to finalize the backup ZIP file.');
            }
            $zipComplete = true;

            if (!unlink($sqlPath)) {
                throw new Exception('The ZIP was created, but its temporary SQL file could not be removed.');
            }

            $finalSize = filesize($zipPath);
            if ($finalSize === false || $finalSize < 1) {
                throw new Exception('The generated backup ZIP is empty or unreadable.');
            }

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
            $success = true;

            return [
                'status' => 'success',
                'message' => 'Backup created successfully',
                'filename' => $zipFilename,
                'filepath' => $zipPath,
                'backup' => $backup,
            ];
        } catch (Throwable $e) {
            Log::error('Backup creation failed', ['type' => $type, 'exception' => $e]);

            $friendlyMessage = $this->formatGoogleDriveErrorMessage($e, $type);
            $filename = $zipFilename ?? ($sqlFilename ?? 'unknown');
            $size = $zipPath && file_exists($zipPath) ? filesize($zipPath) : 0;

            try {
                BackupHistory::create([
                    'filename' => $filename,
                    'file_path' => 'backups/' . $filename,
                    'file_size' => $size ?: 0,
                    'backup_type' => $type,
                    'status' => 'failed',
                    'error_message' => $friendlyMessage,
                ]);

                $this->logBackupAttempt($type, $filename, $size ?: 0, 'failed', $type === 'automatic' ? 'google' : 'local', $friendlyMessage);
            } catch (Throwable $recordingException) {
                Log::error('Failed to record backup failure', ['type' => $type, 'exception' => $recordingException]);
            }

            return [
                'status' => 'error',
                'message' => $friendlyMessage,
            ];
        } finally {
            if ($sqlPath && is_file($sqlPath)) {
                @unlink($sqlPath);
            }
            if (!$success && !$zipComplete && $zipPath && is_file($zipPath)) {
                @unlink($zipPath);
            }
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

    private function formatGoogleDriveErrorMessage(Throwable $e, string $type): string
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

        if ($type === 'automatic') {
            return 'Google Drive upload failed.';
        }

        if (str_contains($message, 'zip') || str_contains($message, 'writable') || str_contains($message, 'unsupported') || str_contains($message, 'unable to create')) {
            return $e->getMessage();
        }

        return 'Backup operation failed. Please check the application logs for details.';
    }

    /**
     * Create an SQL dump of the database
     */
    protected function createSqlDump($filePath): void
    {
        $stream = @fopen($filePath, 'wb');
        if ($stream === false) {
            throw new Exception('Unable to write the temporary database dump. Check storage permissions.');
        }

        try {
            app(PhpDatabaseDumper::class)->dumpToStream(DB::connection(), $stream);
        } finally {
            fclose($stream);
        }

        if (!is_file($filePath) || filesize($filePath) === 0) {
            throw new Exception('The database dump is empty or could not be completed.');
        }
    }

    private function addUploadedFilesToArchive(ZipArchive $zip): void
    {
        foreach (['local' => 'private', 'public' => 'public'] as $diskName => $archiveDisk) {
            $root = realpath(Storage::disk($diskName)->path(''));
            if ($root === false || !is_dir($root)) {
                continue;
            }

            $root = rtrim($root, DIRECTORY_SEPARATOR);
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->isLink()) {
                    continue;
                }

                $relativePath = substr($file->getPathname(), strlen($root) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                if (!$this->isSafeArchiveRelativePath($relativePath)) {
                    throw new Exception('An uploaded file has an invalid storage path; backup was aborted.');
                }

                if (!$zip->addFile($file->getPathname(), 'storage/' . $archiveDisk . '/' . $relativePath)) {
                    throw new Exception('Unable to add an uploaded file to the backup ZIP.');
                }
            }
        }
    }

    private function isSafeArchiveRelativePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '' || str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:/', $normalized) || str_contains($normalized, "\0")) {
            return false;
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
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
            if (!class_exists(ZipArchive::class)) {
                throw new Exception('The PHP ZIP extension is required to restore backups. Enable ZipArchive in the web PHP configuration.');
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
                $matches = [];
                $isSqlEntry = preg_match('/^backup_[0-9_-]+\.sql$/', $normalizedEntry) === 1;
                $isUploadEntry = preg_match('~^storage/(private|public)/(.+)$~', $normalizedEntry, $matches) === 1;

                if ($entryName === false || str_ends_with($normalizedEntry, '/') || $isSymlink ||
                    (!$isSqlEntry && !$isUploadEntry) ||
                    ($isSqlEntry && dirname($normalizedEntry) !== '.') ||
                    ($isUploadEntry && !$this->isSafeArchiveRelativePath($matches[2] ?? ''))) {
                    $zip->close();
                    throw new Exception('Invalid backup archive');
                }
                if ($isSqlEntry) {
                    $sqlEntries++;
                }
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
            $this->restoreUploadedFiles($tempDir);

            return [
                'status' => 'success',
                'message' => 'Database and uploaded files restored successfully from ' . $backup->filename,
            ];
        } catch (Throwable $e) {
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
    private function restoreUploadedFiles(string $extractedRoot): void
    {
        foreach (['private' => 'local', 'public' => 'public'] as $archiveDisk => $diskName) {
            $stagedRoot = $extractedRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $archiveDisk;
            if (!is_dir($stagedRoot)) {
                continue;
            }

            $destinationRoot = Storage::disk($diskName)->path('');
            if (!is_dir($destinationRoot) && !mkdir($destinationRoot, 0755, true) && !is_dir($destinationRoot)) {
                throw new Exception('Unable to access the configured upload storage directory.');
            }
            $destinationRoot = realpath($destinationRoot);
            if ($destinationRoot === false) {
                throw new Exception('Unable to access the configured upload storage directory.');
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($stagedRoot, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->isLink()) {
                    continue;
                }

                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($stagedRoot) + 1));
                if (!$this->isSafeArchiveRelativePath($relativePath)) {
                    throw new Exception('Invalid uploaded-file path in backup archive.');
                }

                $segments = explode('/', $relativePath);
                $filename = array_pop($segments);
                $targetDirectory = $destinationRoot;
                foreach ($segments as $segment) {
                    $targetDirectory .= DIRECTORY_SEPARATOR . $segment;
                    if (is_link($targetDirectory)) {
                        throw new Exception('A symbolic link in upload storage prevents safe restore.');
                    }
                    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                        throw new Exception('Unable to create an upload directory during restore.');
                    }
                }

                $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
                if (is_link($targetPath) || !copy($file->getPathname(), $targetPath)) {
                    throw new Exception('Unable to restore an uploaded file. Check storage permissions.');
                }
            }
        }
    }

    private function restoreFromSql($sqlFilePath): void
    {
        $connection = DB::connection();
        if (!in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new Exception('SQL restore is supported only for a MySQL or MariaDB connection.');
        }
        if (!function_exists('exec')) {
            throw new Exception('The PHP exec function is disabled; the mysql command-line client is required to restore database backups.');
        }

        $config = $connection->getConfig();
        $database = (string) $connection->getDatabaseName();
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $socket = (string) ($config['unix_socket'] ?? '');

        $command = 'mysql';
        if ($socket !== '') {
            $command .= ' --socket=' . escapeshellarg($socket);
        } else {
            $command .= ' --host=' . escapeshellarg((string) ($config['host'] ?? '127.0.0.1'));
            if (!empty($config['port'])) {
                $command .= ' --port=' . escapeshellarg((string) $config['port']);
            }
        }
        $command .= ' --user=' . escapeshellarg($username);

        if ($password !== '') {
            $command .= ' --password=' . escapeshellarg($password);
        }

        $command .= ' ' . escapeshellarg($database) . ' < ' . escapeshellarg($sqlFilePath) . ' 2>&1';

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
