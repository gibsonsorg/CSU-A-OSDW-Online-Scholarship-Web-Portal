<?php

namespace Tests\Feature;

use App\Mail\ApplicationStatusMail;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminApplicationStatusMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_sends_application_status_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $application = StudentProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'status' => 'active',
            'email' => $user->email,
            'contact_number' => '09170000000',
            'course' => 'BSIT',
            'year_level' => '2',
            'program' => 'Information Technology',
            'scholarship_type' => 'academic',
            'scholarship_name' => 'Merit Scholarship',
            'application_status' => 'pending',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin);
        $this->withoutMiddleware();

        $response = $this->postJson('/admin/applications/' . $application->id . '/approve');

        $response->assertOk();
        $this->assertSame('approved', $application->fresh()->application_status);

        Mail::assertQueued(ApplicationStatusMail::class, function (ApplicationStatusMail $mail) use ($user): bool {
            $this->assertSame('Jane Doe', $mail->userName);
            $this->assertSame('Merit Scholarship', $mail->scholarshipName);
            $this->assertSame('approved', $mail->status);

            return true;
        });
    }
}
