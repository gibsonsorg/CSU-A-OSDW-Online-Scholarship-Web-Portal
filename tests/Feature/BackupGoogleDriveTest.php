<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BackupGoogleDriveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('google');
    }

    public function test_successful_upload_deletes_the_local_file_and_logs_success(): void
    {
        $service = app(BackupService::class);
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_2026-10-05_09-15-00.zip';
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($zipPath, 'zip-content');

        $result = $service->uploadAutomaticBackup($zipPath, $filename);

        $this->assertSame('success', $result['status']);
        $this->assertFalse(file_exists($zipPath));
        $this->assertTrue(Storage::disk('google')->exists($filename));

        $log = BackupLog::latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('automatic', $log->type);
        $this->assertSame('success', $log->status);
        $this->assertSame('google', $log->storage);
    }

    public function test_failed_upload_keeps_the_local_file_and_logs_failed(): void
    {
        $service = app(BackupService::class);
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_2026-10-05_09-15-01.zip';
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($zipPath, 'zip-content');

        $result = $service->handleGoogleDriveUploadException($zipPath, $filename, new RuntimeException('invalid_grant'));

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Google Drive token expired', $result['message']);
        $this->assertTrue(file_exists($zipPath));

        $log = BackupLog::latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('failed', $log->status);
        $this->assertSame('google', $log->storage);
        $this->assertStringContainsString('Google Drive token expired', $log->error_message);
    }

    public function test_invalid_grant_produces_the_friendly_error_message(): void
    {
        $service = app(BackupService::class);

        $message = $service->friendlyGoogleDriveErrorMessage('invalid_grant');

        $this->assertSame('Google Drive token expired. Please generate a new refresh token.', $message);
    }

    public function test_retention_keeps_only_the_latest_10_backups(): void
    {
        $service = app(BackupService::class);

        for ($i = 1; $i <= 15; $i++) {
            $filename = 'backup_2026-10-05_09-15-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '.zip';
            Storage::disk('google')->put($filename, 'content-' . $i);
        }

        $service->pruneGoogleBackups();

        $files = Storage::disk('google')->files();
        $this->assertCount(10, $files);

        $latestTen = array_map('basename', $files);
        $this->assertContains('backup_2026-10-05_09-15-06.zip', $latestTen);
        $this->assertContains('backup_2026-10-05_09-15-15.zip', $latestTen);
        $this->assertNotContains('backup_2026-10-05_09-15-00.zip', $latestTen);
    }
}
