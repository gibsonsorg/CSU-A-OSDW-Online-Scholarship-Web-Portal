<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManualApplicantValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);
    }

    protected function makeScholarship(string $name, string $type): void
    {
        Scholarship::create([
            'name' => $name,
            'type' => $type,
            'status' => 'active',
        ]);
    }

    public function test_manual_applicant_rejects_non_canonical_scholarship_name(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('Socio-Cultural Scholarship', 'non-academic');

        $response = $this->actingAs($admin)->postJson('/admin/manual-applicants', [
            'grant_type' => 'non-academic',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'sex' => 'Female',
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => 'Socio cultural',
            'date' => '2026-09-22',
            'application_status' => 'pending',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scholarship_name']);
    }

    public function test_manual_applicant_accepts_canonical_scholarship_name(): void
    {
        $admin = $this->makeAdmin();
        $this->makeScholarship('Socio-Cultural Scholarship', 'non-academic');

        $response = $this->actingAs($admin)->postJson('/admin/manual-applicants', [
            'grant_type' => 'non-academic',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'sex' => 'Female',
            'course' => 'BSIT',
            'year_level' => '2nd Year',
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'date' => '2026-09-22',
            'application_status' => 'pending',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('student_profiles', [
            'source' => 'manual',
            'grant_type' => 'non-academic',
            'scholarship_name' => 'Socio-Cultural Scholarship',
            'application_status' => 'pending',
        ]);
    }
}
