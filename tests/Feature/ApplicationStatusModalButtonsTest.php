<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationStatusModalButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_status_email_modal_no_longer_renders_footer_buttons_but_keeps_close_controls(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => '1',
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'source' => 'application',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'sex' => 'Male',
            'status' => 'Single',
            'email' => 'student@example.com',
            'course' => 'BSIT',
            'scholarship_name' => 'Sample Scholarship',
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'last_notification_subject' => 'Your Scholarship Application Has Been Received',
            'last_notification_body' => 'Email notification body text.',
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/application-status');

        $response->assertOk();
        $response->assertDontSee('Browse Scholarships');
        $response->assertDontSee('rejectionDetailCloseBtn');
        $response->assertSee('rejectionDetailClose', false);
        $response->assertSee('Latest email notification', false);
    }
}
