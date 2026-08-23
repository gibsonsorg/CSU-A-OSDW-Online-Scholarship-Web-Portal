<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->approved()->create();

        $this->actingAs($student)->get('/admin')->assertForbidden();
    }

    public function test_student_cannot_access_backup_routes(): void
    {
        $student = User::factory()->approved()->create();

        $this->actingAs($student)->get('/api/backup/list')->assertForbidden();
    }

    public function test_student_cannot_access_protected_document_route(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create();
        $application = $this->applicationWithUpload('documents/student.pdf');

        Storage::disk('local')->put('documents/student.pdf', 'private document');

        $this->actingAs($student)
            ->get("/admin/applications/{$application->id}/document/uploads/0")
            ->assertForbidden();
    }

    public function test_admin_can_view_a_student_document(): void
    {
        Storage::fake('local');
        $admin = User::factory()->approved()->create(['role' => 0]);
        $application = $this->applicationWithUpload('documents/student.pdf');

        Storage::disk('local')->put('documents/student.pdf', 'private document');

        $response = $this->actingAs($admin)
            ->get("/admin/applications/{$application->id}/document/uploads/0");

        $response->assertOk();
        $response->assertHeader('content-disposition', 'inline; filename="student.pdf"');
        $this->assertSame('private document', Storage::disk('local')->get('documents/student.pdf'));
    }

    public function test_invalid_document_group_or_index_is_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->approved()->create(['role' => 0]);
        $application = $this->applicationWithUpload('documents/student.pdf');
        Storage::disk('local')->put('documents/student.pdf', 'private document');

        $this->actingAs($admin)
            ->get("/admin/applications/{$application->id}/document/other/0")
            ->assertNotFound();

        $this->actingAs($admin)
            ->get("/admin/applications/{$application->id}/document/uploads/1")
            ->assertNotFound();
    }

    public function test_public_only_document_is_migrated_and_public_copy_removed(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $profile = $this->applicationWithUpload('documents/public-only.pdf');
        Storage::disk('public')->put('documents/public-only.pdf', 'document');

        Artisan::call('documents:migrate-to-private');

        $this->assertTrue(Storage::disk('local')->exists('documents/public-only.pdf'));
        $this->assertFalse(Storage::disk('public')->exists('documents/public-only.pdf'));
        $this->assertSame('document', Storage::disk('local')->get('documents/public-only.pdf'));
        $this->assertNotNull($profile->fresh());
    }

    public function test_valid_private_copy_causes_public_duplicate_to_be_removed(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->applicationWithUpload('documents/duplicate.pdf');
        Storage::disk('public')->put('documents/duplicate.pdf', 'public');
        Storage::disk('local')->put('documents/duplicate.pdf', 'private');

        Artisan::call('documents:migrate-to-private');

        $this->assertFalse(Storage::disk('public')->exists('documents/duplicate.pdf'));
        $this->assertSame('private', Storage::disk('local')->get('documents/duplicate.pdf'));
    }

    public function test_failed_private_verification_leaves_public_file_untouched(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->applicationWithUpload('documents/failed.pdf');
        Storage::disk('public')->put('documents/failed.pdf', 'public');

        $publicDisk = Storage::disk('public');
        $failingLocalDisk = Mockery::mock(FilesystemAdapter::class);
        $failingLocalDisk->shouldReceive('exists')->with('documents/failed.pdf')->andReturn(false);
        $failingLocalDisk->shouldReceive('put')->with('documents/failed.pdf', 'public')->andThrow(new \RuntimeException('verification failure'));
        Storage::shouldReceive('disk')->with('public')->andReturn($publicDisk);
        Storage::shouldReceive('disk')->with('local')->andReturn($failingLocalDisk);

        Artisan::call('documents:migrate-to-private');

        $this->assertFileExists($publicDisk->path('documents/failed.pdf'));
    }

    private function applicationWithUpload(string $path): StudentProfile
    {
        $user = User::factory()->approved()->create();

        return StudentProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $user->email,
            'course' => 'BSIT',
            'scholarship_name' => 'Test Scholarship',
            'scholarship_type' => 'Academic',
            'uploads' => ['uploads' => [$path]],
            'application_status' => 'pending',
            'grant_type' => 'academic',
        ]);
    }
}
