<?php

namespace Tests\Feature;

use App\Mail\IdentityVerificationApprovedMail;
use App\Models\IdentityVerification;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DiditVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_submit_application_without_approved_identity_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $scholarship = Scholarship::create([
            'name' => 'University Based Scholarship',
            'type' => 'academic',
        ]);

        $response = $this->actingAs($user)->post(route('student-profiles.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '1st Year',
            'program' => 'BSIT',
            'id_number' => '2024001',
            'birthdate' => '2002-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'Roman Catholic',
            'scholarship_name' => 'University Based Scholarship',
            'scholarship_type' => 'Academic Excellence – In-House',
            'scholarship_id' => $scholarship->id,
            'email' => $user->email,
            'contact_number' => '09123456789',
            'home_address' => 'Aparri',
        ]);

        $response->assertRedirect(route('didit.start', $scholarship));
    }

    public function test_valid_didit_webhook_is_not_rejected_by_csrf_and_duplicate_approved_webhooks_queue_only_one_approval_email(): void
    {
        Mail::fake();
        config(['services.didit.webhook_secret' => 'test-webhook-secret']);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $scholarship = Scholarship::create([
            'name' => 'University Based Scholarship',
            'type' => 'academic',
        ]);

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'scholarship_id' => $scholarship->id,
            'provider' => 'didit',
            'provider_session_id' => 'didit-session-1',
            'status' => 'In Review',
        ]);

        $payload = [
            'session_id' => $verification->provider_session_id,
            'status' => 'Approved',
        ];
        $content = json_encode($payload);
        $signature = hash_hmac('sha256', $content, 'test-webhook-secret');

        $this->postJson(route('didit.webhook'), $payload, ['X-Signature-V2' => $signature])
            ->assertOk();
        $this->postJson(route('didit.webhook'), $payload, ['X-Signature-V2' => $signature])
            ->assertOk();

        $this->assertDatabaseHas('identity_verifications', [
            'id' => $verification->id,
            'status' => 'Approved',
        ]);
        Mail::assertQueued(IdentityVerificationApprovedMail::class, 1);
    }

    public function test_generic_didit_test_webhook_with_unknown_vendor_data_is_acknowledged_without_creating_a_record(): void
    {
        config(['services.didit.webhook_secret' => 'test-webhook-secret']);

        $payload = [
            'session_id' => 'didit-test-session',
            'status' => 'Approved',
            'vendor_data' => 'test-vendor-data-123',
        ];
        $signature = hash_hmac('sha256', json_encode($payload), 'test-webhook-secret');

        $this->postJson(route('didit.webhook'), $payload, ['X-Signature-V2' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseMissing('identity_verifications', [
            'provider_session_id' => 'didit-test-session',
        ]);
    }

    public function test_valid_webhook_with_existing_user_creates_verification_when_session_record_is_missing(): void
    {
        config(['services.didit.webhook_secret' => 'test-webhook-secret']);

        $user = User::factory()->create();
        $payload = [
            'session_id' => 'didit-real-session',
            'status' => 'In Review',
            'vendor_data' => (string) $user->id,
        ];
        $signature = hash_hmac('sha256', json_encode($payload), 'test-webhook-secret');

        $this->postJson(route('didit.webhook'), $payload, ['X-Signature-V2' => $signature])
            ->assertOk();

        $this->assertDatabaseHas('identity_verifications', [
            'user_id' => $user->id,
            'provider_session_id' => 'didit-real-session',
            'status' => 'In Review',
        ]);
    }

    public function test_invalid_didit_webhook_signature_is_rejected_without_creating_a_record(): void
    {
        config(['services.didit.webhook_secret' => 'test-webhook-secret']);

        $payload = [
            'session_id' => 'didit-invalid-signature-session',
            'status' => 'Approved',
            'vendor_data' => 'test-vendor-data-123',
        ];

        $this->postJson(route('didit.webhook'), $payload, ['X-Signature-V2' => 'invalid-signature'])
            ->assertUnauthorized();

        $this->assertDatabaseMissing('identity_verifications', [
            'provider_session_id' => 'didit-invalid-signature-session',
        ]);
    }
}
