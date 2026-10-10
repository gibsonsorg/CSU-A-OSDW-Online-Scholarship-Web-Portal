<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\FamilyBackground;
use App\Models\Scholarship;
use App\Models\StudentNotification;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFinalizedRejectedDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_hard_delete_finalized_rejected_application_and_rejects_ineligible_states(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => '0',
            'email_verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'role' => '1',
            'email_verified_at' => now(),
        ]);

        $scholarship = Scholarship::create([
            'name' => 'Final Rejection Cleanup Award',
            'type' => 'academic',
            'type_label' => 'Academic',
            'accepting_applications' => true,
            'status' => 'active',
            'max_slots' => 10,
        ]);

        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);

        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'source' => 'application',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => 'student@example.com',
            'course' => 'BSIT',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'rejection_reason' => 'Wrong Uploaded Documents',
            'resubmission_deadline' => now()->subDay(),
            'locked_at' => now()->subHour(),
            'uploads' => ['supporting_document' => ['student_profiles/cleanup-file.pdf']],
        ]);

        FamilyBackground::create([
            'student_profile_id' => $profile->id,
            'father_name' => 'Father Test',
        ]);

        StudentNotification::create([
            'user_id' => $student->id,
            'application_id' => $profile->id,
            'scholarship_name' => $scholarship->name,
            'type' => 'rejected',
            'message' => 'Rejected',
        ]);

        Storage::disk('local')->put('student_profiles/cleanup-file.pdf', 'placeholder');

        $this->actingAs($admin)
            ->postJson("/admin/applications/{$profile->id}/final-delete")
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseMissing('student_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
        $this->assertDatabaseMissing('family_backgrounds', ['student_profile_id' => $profile->id]);
        $this->assertDatabaseMissing('notifications', ['application_id' => $profile->id]);
        $this->assertFalse(Storage::disk('local')->exists('student_profiles/cleanup-file.pdf'));

        $pendingProfile = StudentProfile::create([
            'user_id' => $student->id,
            'source' => 'application',
            'first_name' => 'Second',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => 'secondstudent@example.com',
            'course' => 'BSIT',
            'scholarship_name' => 'Pending Application',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/applications/{$pendingProfile->id}/final-delete")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This application is not eligible for permanent deletion.');

        $this->assertDatabaseHas('student_profiles', ['id' => $pendingProfile->id]);
    }
}
