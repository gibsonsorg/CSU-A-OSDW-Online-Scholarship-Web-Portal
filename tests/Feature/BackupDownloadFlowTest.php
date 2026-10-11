<?php

namespace Tests\Feature;

use App\Models\BackupHistory;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BackupDownloadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_backup_create_returns_backup_id_for_download(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $backup = BackupHistory::create([
            'filename' => 'backup_2026-10-11.zip',
            'file_path' => 'backups/backup_2026-10-11.zip',
            'file_size' => 2048,
            'backup_type' => 'manual',
            'status' => 'success',
        ]);

        $service = Mockery::mock(BackupService::class);
        $service->shouldReceive('createBackup')->with('manual')->andReturn([
            'status' => 'success',
            'message' => 'Backup created successfully',
            'filename' => $backup->filename,
            'backup' => $backup,
        ]);

        $this->app->instance(BackupService::class, $service);

        $response = $this->actingAs($admin)
            ->post('/api/backup/create');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('backup_id', $backup->id)
            ->assertJsonPath('filename', $backup->filename);
    }

    public function test_existing_backup_file_downloads_as_a_zip(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $backup = BackupHistory::create([
            'filename' => 'backup_2026-10-11.zip',
            'file_path' => 'backups/backup_2026-10-11.zip',
            'file_size' => 2048,
            'backup_type' => 'manual',
            'status' => 'success',
        ]);

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'backup_2026-10-11.zip', 'zip-content');

        $response = $this->actingAs($admin)
            ->get('/api/backup/' . $backup->id . '/download');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
    }
}
