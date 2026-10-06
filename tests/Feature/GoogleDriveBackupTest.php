<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\BackupSchedule;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\FileList;
use Mockery;
use Tests\TestCase;

class GoogleDriveBackupTest extends TestCase
{
    use RefreshDatabase;

    private $googleDriveStorage;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.google.folder' => 'test-folder-id']);
        $this->googleDriveStorage = Mockery::mock(GoogleDriveBackupStorage::class);
        $this->app->instance(GoogleDriveBackupStorage::class, $this->googleDriveStorage);

        $backupDir = storage_path('app/backups');
        if (is_dir($backupDir)) {
            File::cleanDirectory($backupDir);
        }
    }

    public function test_successful_upload_deletes_local_file_and_logs_success(): void
    {
        $this->googleDriveStorage->shouldReceive('upload')->once()->with(Mockery::type('string'), Mockery::type('string'))->andReturnUsing(function ($path) {
            $this->assertFileExists($path);
        });
        $this->googleDriveStorage->shouldReceive('pruneBackups')->once();

        $service = $this->partialBackupService();
        $service->shouldReceive('createSqlDump')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) {
            file_put_contents($path, 'CREATE TABLE test;');
        });

        $result = $service->createBackup('automatic');

        $this->assertSame('success', $result['status']);
        $this->assertCount(0, glob(storage_path('app/backups/*.zip')) ?: []);
        $this->assertDatabaseHas('backup_logs', [
            'type' => 'automatic',
            'status' => 'success',
            'storage' => 'google',
        ]);
    }

    public function test_failed_upload_keeps_local_file_and_logs_failed(): void
    {
        $this->googleDriveStorage->shouldReceive('upload')->once()->andThrow(new \RuntimeException('invalid_grant'));

        $service = $this->partialBackupService();
        $service->shouldReceive('createSqlDump')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) {
            file_put_contents($path, 'CREATE TABLE test;');
        });
        $result = $service->createBackup('automatic');

        $this->assertSame('error', $result['status']);
        $this->assertSame('Google Drive token expired. Please generate a new refresh token.', $result['message']);
        $this->assertNotCount(0, glob(storage_path('app/backups/*.zip')) ?: []);
        $this->assertDatabaseHas('backup_logs', [
            'type' => 'automatic',
            'status' => 'failed',
            'storage' => 'google',
            'error_message' => 'Google Drive token expired. Please generate a new refresh token.',
        ]);
    }

    public function test_invalid_grant_produces_friendly_error_message(): void
    {
        $this->googleDriveStorage->shouldReceive('upload')->once()->andThrow(new \RuntimeException('invalid_grant'));

        $service = $this->partialBackupService();
        $service->shouldReceive('createSqlDump')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) {
            file_put_contents($path, 'CREATE TABLE test;');
        });
        $result = $service->createBackup('automatic');

        $this->assertSame('Google Drive token expired. Please generate a new refresh token.', $result['message']);
        $this->assertSame('failed', BackupLog::latest()->first()->status);
        $this->assertSame('Google Drive token expired. Please generate a new refresh token.', BackupLog::latest()->first()->error_message);
    }

    public function test_retention_keeps_only_latest_10_google_backups(): void
    {
        $files = Mockery::mock(Drive\Resource\Files::class);
        $drive = new Drive(new Client());
        $drive->files = $files;
        $this->app->instance(Drive::class, $drive);
        $listedFiles = [];
        for ($index = 1; $index <= 11; $index++) {
            $listedFiles[] = new DriveFile([
                'id' => 'file-' . $index,
                'name' => sprintf('backup_2026-10-%02d_00-00-%02d.zip', $index, $index),
            ]);
        }
        $listedFiles[] = new DriveFile(['id' => 'notes', 'name' => 'backup-notes.zip']);

        $files->shouldReceive('listFiles')->once()->with(Mockery::on(function ($options) {
            $this->assertSame("'test-folder-id' in parents and trashed = false", $options['q']);
            return true;
        }))->andReturn(new FileList(['files' => $listedFiles]));
        $files->shouldReceive('delete')->once()->with('file-1', ['supportsAllDrives' => true]);

        (new GoogleDriveBackupStorage())->pruneBackups();
    }

    public function test_schedule_registration_uses_admin_settings_from_database(): void
    {
        BackupSchedule::create([
            'frequency' => 'daily',
            'time' => '03:30',
            'day_of_week' => null,
            'enabled' => true,
        ]);

        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('backup:run --type=automatic', $output);
        $this->assertMatchesRegularExpression('/30\s+3\s+\*\s+\*\s+\*/', $output);
    }

    public function test_empty_google_folder_id_fails_fast_and_logs_the_error(): void
    {
        config(['filesystems.disks.google.folder' => '']);

        $service = $this->partialBackupService();
        $service->shouldReceive('createSqlDump')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) {
            file_put_contents($path, 'CREATE TABLE test;');
        });

        $result = $service->createBackup('automatic');

        $this->assertSame('error', $result['status']);
        $this->assertSame('Google Drive folder ID is missing. Please set GOOGLE_DRIVE_FOLDER_ID.', $result['message']);
        $this->googleDriveStorage->shouldNotHaveReceived('upload');
        $this->assertDatabaseHas('backup_logs', [
            'type' => 'automatic',
            'status' => 'failed',
            'storage' => 'google',
            'error_message' => 'Google Drive folder ID is missing. Please set GOOGLE_DRIVE_FOLDER_ID.',
        ]);
    }

    public function test_upload_sets_drive_parent_and_confirms_placement(): void
    {
        $files = Mockery::mock(Drive\Resource\Files::class);
        $drive = new Drive(new Client());
        $drive->files = $files;
        $this->app->instance(Drive::class, $drive);
        $path = tempnam(sys_get_temp_dir(), 'backup-test-');
        file_put_contents($path, 'zip-content');

        $files->shouldReceive('create')->once()->with(Mockery::on(function (DriveFile $metadata) {
            $this->assertSame('backup_2026-10-05_00-00-00.zip', $metadata->getName());
            $this->assertSame(['test-folder-id'], $metadata->getParents());
            return true;
        }), Mockery::on(function ($options) {
            $this->assertSame('application/zip', $options['mimeType']);
            $this->assertSame('multipart', $options['uploadType']);
            return true;
        }))->andReturn(new DriveFile([
            'id' => 'uploaded-file',
            'parents' => ['test-folder-id'],
        ]));

        try {
            (new GoogleDriveBackupStorage())->upload($path, 'backup_2026-10-05_00-00-00.zip');
        } finally {
            unlink($path);
        }
    }

    public function test_upload_fails_when_drive_does_not_confirm_the_parent(): void
    {
        $files = Mockery::mock(Drive\Resource\Files::class);
        $drive = new Drive(new Client());
        $drive->files = $files;
        $this->app->instance(Drive::class, $drive);
        $path = tempnam(sys_get_temp_dir(), 'backup-test-');
        file_put_contents($path, 'zip-content');

        $files->shouldReceive('create')->once()->andReturn(new DriveFile([
            'id' => 'uploaded-file',
            'parents' => ['another-folder'],
        ]));
        $files->shouldReceive('get')->once()->with('uploaded-file', [
            'fields' => 'id,parents',
            'supportsAllDrives' => true,
        ])->andReturn(new DriveFile([
            'id' => 'uploaded-file',
            'parents' => ['another-folder'],
        ]));

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Google Drive did not confirm the backup folder placement.');
            (new GoogleDriveBackupStorage())->upload($path, 'backup_2026-10-05_00-00-00.zip');
        } finally {
            unlink($path);
        }
    }

    public function test_folder_check_counts_only_backup_named_files(): void
    {
        $files = Mockery::mock(Drive\Resource\Files::class);
        $drive = new Drive(new Client());
        $drive->files = $files;
        $this->app->instance(Drive::class, $drive);

        $files->shouldReceive('get')->once()->with('test-folder-id', [
            'fields' => 'id,mimeType',
            'supportsAllDrives' => true,
        ])->andReturn(new DriveFile([
            'id' => 'test-folder-id',
            'mimeType' => 'application/vnd.google-apps.folder',
        ]));
        $files->shouldReceive('listFiles')->once()->with(Mockery::on(function ($options) {
            $this->assertSame("'test-folder-id' in parents and trashed = false", $options['q']);
            return true;
        }))->andReturn(new FileList(['files' => [
            new DriveFile(['id' => 'backup', 'name' => 'backup_2026-10-05_00-00-00.zip']),
            new DriveFile(['id' => 'other', 'name' => 'notes.txt']),
        ]]));

        $this->assertSame([
            'configured' => true,
            'reachable' => true,
            'backup_count' => 1,
        ], (new GoogleDriveBackupStorage())->checkFolder());
    }

    public function test_drive_check_reports_status_without_printing_folder_id(): void
    {
        $this->googleDriveStorage->shouldReceive('checkFolder')->once()->andReturn([
            'configured' => true,
            'reachable' => true,
            'backup_count' => 3,
        ]);

        $this->artisan('backup:drive-check')
            ->expectsOutput('Google Drive folder ID configured: Yes')
            ->expectsOutput('Google Drive folder reachable: Yes')
            ->expectsOutput('Backup files in folder: 3')
            ->doesntExpectOutput('test-folder-id')
            ->assertExitCode(0);
    }

    private function partialBackupService(): BackupService
    {
        return Mockery::mock(BackupService::class, [$this->googleDriveStorage])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
