<?php

namespace Tests\Feature;

use App\Models\BackupHistory;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class BackupPortableCreationTest extends TestCase
{
    use RefreshDatabase;

    private string $originalStoragePath;
    private string $temporaryStoragePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalStoragePath = $this->app->storagePath();
        $this->temporaryStoragePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scholarship-backup-test-' . bin2hex(random_bytes(8));
        if (!mkdir($this->temporaryStoragePath, 0755, true)) {
            throw new \RuntimeException('Unable to create temporary test storage.');
        }

        $this->app->useStoragePath($this->temporaryStoragePath);
        config([
            'filesystems.disks.local.root' => $this->temporaryStoragePath . DIRECTORY_SEPARATOR . 'private',
            'filesystems.disks.public.root' => $this->temporaryStoragePath . DIRECTORY_SEPARATOR . 'public',
        ]);
        foreach (['app/backups', 'private', 'public'] as $directory) {
            $path = $this->temporaryStoragePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
            if (!is_dir($path) && !mkdir($path, 0755, true)) {
                throw new \RuntimeException('Unable to create a temporary test directory.');
            }
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->temporaryStoragePath) && is_dir($this->temporaryStoragePath)) {
            File::deleteDirectory($this->temporaryStoragePath);
        }
        if (isset($this->originalStoragePath)) {
            $this->app->useStoragePath($this->originalStoragePath);
        }

        parent::tearDown();
    }

    public function test_manual_backup_contains_database_and_private_and_public_uploads(): void
    {
        DB::statement('CREATE TABLE backup_probe (id INTEGER PRIMARY KEY, label TEXT NOT NULL, payload BLOB)');
        DB::insert('INSERT INTO backup_probe (id, label, payload) VALUES (?, ?, ?)', [1, "quoted ' value", "\x00\xff"]);
        Storage::disk('local')->put('ids/tagged-applicant.txt', 'private upload fixture');
        Storage::disk('public')->put('scholarship-logos/tagged-scholarship.txt', 'public upload fixture');

        $admin = User::factory()->approved()->create(['role' => 0]);
        $response = $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('backup.api.create'));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('backup_id', $response->json('backup_id'))
            ->assertJsonPath('download_url', route('backup.api.download', ['id' => $response->json('backup_id')]));

        $backup = BackupHistory::findOrFail($response->json('backup_id'));
        $zipPath = $this->temporaryStoragePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $backup->file_path);
        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($zipPath));
        $sqlEntry = null;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if (is_string($entry) && str_ends_with($entry, '.sql')) {
                $sqlEntry = $entry;
                break;
            }
        }

        $this->assertNotNull($sqlEntry);
        $sql = $zip->getFromName($sqlEntry);
        $this->assertStringContainsString('CREATE TABLE backup_probe', $sql);
        $this->assertStringContainsString("quoted '' value", $sql);
        $this->assertSame('private upload fixture', $zip->getFromName('storage/private/ids/tagged-applicant.txt'));
        $this->assertSame('public upload fixture', $zip->getFromName('storage/public/scholarship-logos/tagged-scholarship.txt'));
        $zip->close();

        $this->actingAs($admin)
            ->get($response->json('download_url'))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_existing_history_download_route_still_serves_zip(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $backup = BackupHistory::create([
            'filename' => 'backup_history_fixture.zip',
            'file_path' => 'backups/backup_history_fixture.zip',
            'file_size' => 0,
            'backup_type' => 'manual',
            'status' => 'success',
        ]);

        $zipPath = $this->temporaryStoragePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $backup->filename;
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('fixture.txt', 'archive fixture');
        $zip->close();

        $response = $this->actingAs($admin)->get(route('backup.api.download', ['id' => $backup->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_settings_page_renders_generated_backup_routes(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $escapeJsonUrl = static fn (string $url): string => str_replace('/', '\\/', $url);

        $response = $this->actingAs($admin)->get(route('backup.index'));

        $response->assertOk()
            ->assertSee($escapeJsonUrl(route('backup.api.create')), false)
            ->assertSee($escapeJsonUrl(route('backup.api.list')), false)
            ->assertSee($escapeJsonUrl(route('backup.api.schedule.get')), false)
            ->assertSee($escapeJsonUrl(route('backup.api.schedule.update')), false)
            ->assertSee($escapeJsonUrl(route('backup.restore-applicant', ['id' => '__BACKUP_ID__'])), false)
            ->assertSee($escapeJsonUrl(route('backup.applicant-info', ['id' => '__BACKUP_ID__'])), false);
    }

    public function test_manual_backup_php_errors_are_returned_as_json(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $service = Mockery::mock(BackupService::class);
        $service->shouldReceive('createBackup')->with('manual')->andThrow(new \Error('synthetic extension error'));
        $this->app->instance(BackupService::class, $service);

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('backup.api.create'))
            ->assertStatus(500)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Failed to create backup. Check the application log for details.');
    }
}
