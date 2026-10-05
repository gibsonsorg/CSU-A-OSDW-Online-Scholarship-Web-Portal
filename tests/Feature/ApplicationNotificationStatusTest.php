<?php

namespace Tests\Feature;

use App\Mail\ApplicationReceivedMail;
use App\Mail\ApplicationStatusMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationNotificationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_submission_queues_and_displays_the_received_notification(): void
    {
        Mail::fake();
        Storage::fake('local');
        $student = User::factory()->approved()->create([
            'name' => 'Receipt Student',
            'student_id' => '23-13001',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Receipt Status Award',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $this->submit($student, $scholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success', 'Application submitted successfully for Receipt Status Award');

        $profile = StudentProfile::where('user_id', $student->id)->firstOrFail();
        $application = Application::where('user_id', $student->id)
            ->where('scholarship_id', $scholarship->id)
            ->sole();
        $this->assertSame('non-academic', $profile->grant_type);
        $this->assertSame('pending', $profile->application_status);
        $this->assertSame($student->id, $application->user_id);
        $this->assertSame($scholarship->id, $application->scholarship_id);
        $this->assertSame('pending', $application->status);
        $this->assertSame(1, StudentProfile::where('user_id', $student->id)->where('application_id', $application->id)->count());

        $dashboard = $this->actingAs($student)->get('/dashboard?section=non')->assertOk();
        $dashboard->assertViewHas('appliedScholarships', fn (array $items) => collect($items)->contains(fn (array $item) =>
            (int) $item['scholarship_id'] === $scholarship->id
            && $item['grant_type'] === 'non-academic'
            && $item['status'] === 'pending'
        ));

        $admin = User::factory()->approved()->create(['role' => 0]);
        $adminList = $this->actingAs($admin)->get('/admin/non-academic?status=pending')->assertOk();
        $adminList->assertViewHas('recentApplications', fn ($applications) => $applications->getCollection()->contains(fn (StudentProfile $item) =>
            $item->id === $profile->id
            && $item->grant_type === 'non-academic'
            && $item->application_status === 'pending'
        ));

        $this->submit($student, $scholarship)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionHas('error', 'You have already applied for the Receipt Status Award scholarship. Only one application per scholarship is allowed.')
            ->assertSessionMissing('success');
        $this->assertSame(1, Application::where('user_id', $student->id)->where('scholarship_id', $scholarship->id)->count());
        $this->assertSame(1, StudentProfile::where('user_id', $student->id)->where('application_id', $application->id)->count());

        $mail = Mail::queued(ApplicationReceivedMail::class)->sole();
        $this->assertSame('Your Scholarship Application Has Been Received', $mail->notificationSubject());
        $this->assertSame(
            'Dear Receipt Student, this is to confirm that your application for Receipt Status Award has been received and is currently under review. We will notify you by email once a decision has been made. If you have any questions in the meantime, feel free to visit the OSDW office in Aparri.',
            $mail->notificationBody()
        );
        $this->assertRenderedMailContainsStoredBody($mail);
        $this->assertSame($mail->notificationSubject(), $profile->last_notification_subject);
        $this->assertSame($mail->notificationBody(), $profile->last_notification_body);

        $statusPage = $this->actingAs($student)->get('/application-status')->assertOk();
        $statusPage->assertSee($mail->notificationSubject(), false)
            ->assertSee('Receipt Status Award')
            ->assertSee('status-pending')
            ->assertSee('Latest email notification', false)
            ->assertDontSee('sections reviewed', false);
        $this->assertStatusPageNotification($statusPage, $mail->notificationSubject(), $mail->notificationBody());
    }

    public function test_invalid_non_academic_submission_shows_error_feedback_without_success_or_saved_records(): void
    {
        $student = User::factory()->approved()->create([
            'student_id' => '23-13003',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Invalid Receipt Award',
            'type' => 'non-academic',
            'status' => 'active',
        ]);

        $this->actingAs($student)->from('/dashboard?section=non')->post('/student-profiles', [
            'first_name' => 'Receipt',
            'last_name' => 'Student',
            'sex' => 'Invalid',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
        ])->assertRedirect('/dashboard?section=non')
            ->assertSessionHasErrors('sex')
            ->assertSessionMissing('success');

        $dashboard = $this->actingAs($student)->get('/dashboard?section=non')->assertOk();
        $dashboard->assertSee('Application could not be submitted')
            ->assertDontSee('Application submitted successfully');

        $this->assertDatabaseMissing('applications', [
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
        ]);
        $this->assertDatabaseMissing('student_profiles', [
            'user_id' => $student->id,
            'scholarship_name' => $scholarship->name,
        ]);
    }

    public function test_approval_and_both_rejection_emails_are_saved_and_shown_verbatim(): void
    {
        Mail::fake();
        $admin = User::factory()->approved()->create(['role' => 0]);

        foreach ([
            ['status' => 'approved', 'reason' => null, 'name' => 'Approved Status Award'],
            ['status' => 'rejected', 'reason' => 'Does not meet the eligibility requirements', 'name' => 'Eligibility Status Award'],
            ['status' => 'rejected', 'reason' => 'Wrong uploaded documents or incomplete form', 'name' => 'Document Status Award'],
        ] as $case) {
            $student = User::factory()->approved()->create(['name' => 'Decision Student']);
            $scholarship = Scholarship::create([
                'name' => $case['name'],
                'type' => 'academic',
                'status' => 'active',
            ]);
            $application = Application::create([
                'user_id' => $student->id,
                'scholarship_id' => $scholarship->id,
                'status' => 'pending',
            ]);
            $profile = $this->createProfile($student, $scholarship, $application);

            $decision = $case['status'] === 'approved'
                ? $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/approve")
                : $this->actingAs($admin)->postJson("/admin/applications/{$profile->id}/reject", ['reason' => $case['reason']]);
            $decision->assertOk();

            $mail = Mail::queued(ApplicationStatusMail::class)
                ->last(fn (ApplicationStatusMail $queued) => $queued->scholarshipName === $scholarship->name);
            $this->assertNotNull($mail);
            $this->assertRenderedMailContainsStoredBody($mail);
            $profile->refresh();
            $this->assertSame($mail->notificationSubject(), $profile->last_notification_subject);
            $this->assertSame($mail->notificationBody(), $profile->last_notification_body);

            $statusPage = $this->actingAs($student)->get('/application-status')->assertOk();
            $statusPage->assertSee($mail->notificationSubject(), false);
            $this->assertStatusPageNotification($statusPage, $mail->notificationSubject(), $mail->notificationBody());
        }
    }

    public function test_document_reapplication_sends_and_replaces_the_previous_notification(): void
    {
        Mail::fake();
        Storage::fake('local');
        $student = User::factory()->approved()->create([
            'name' => 'Returning Student',
            'student_id' => '23-13002',
        ]);
        $scholarship = Scholarship::create([
            'name' => 'Returning Status Award',
            'type' => 'non-academic',
            'status' => 'active',
        ]);
        $application = Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        $profile = $this->createProfile($student, $scholarship, $application, [
            'application_status' => 'rejected',
            'rejection_reason' => 'Wrong uploaded documents or incomplete form',
            'last_notification_subject' => 'Scholarship Application Update',
            'last_notification_body' => 'Previous rejection notification.',
        ]);

        $this->submit($student, $scholarship, $profile)
            ->assertRedirect(route('dashboard', ['section' => 'non'], absolute: false))
            ->assertSessionDoesntHaveErrors();

        $mail = Mail::queued(ApplicationReceivedMail::class)->sole();
        $this->assertRenderedMailContainsStoredBody($mail);
        $profile->refresh();
        $this->assertSame('pending', $profile->application_status);
        $this->assertSame('pending', $application->fresh()->status);
        $this->assertSame($mail->notificationSubject(), $profile->last_notification_subject);
        $this->assertSame($mail->notificationBody(), $profile->last_notification_body);
        $this->assertNotSame('Previous rejection notification.', $profile->last_notification_body);

        $statusPage = $this->actingAs($student)->get('/application-status')->assertOk();
        $statusPage->assertSee($mail->notificationSubject(), false)
            ->assertDontSee('Previous rejection notification.', false);
        $this->assertStatusPageNotification($statusPage, $mail->notificationSubject(), $mail->notificationBody());
    }

    private function submit(User $student, Scholarship $scholarship, ?StudentProfile $previousProfile = null)
    {
        return $this->actingAs($student)->from('/dashboard?section=non')->post('/student-profiles', array_filter([
            'student_profile_id' => $previousProfile?->id,
            'application_id' => $previousProfile?->application_id,
            'first_name' => 'Receipt',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id,
            'home_address' => 'Aparri',
            'contact_number' => '123456789',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Non-Academic',
            'uploads' => [UploadedFile::fake()->create('supporting-document.pdf', 10, 'application/pdf')],
        ], fn ($value) => $value !== null));
    }

    private function createProfile(User $student, Scholarship $scholarship, Application $application, array $overrides = []): StudentProfile
    {
        return StudentProfile::create(array_merge([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Decision',
            'last_name' => 'Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => $scholarship->type,
            'grant_type' => $scholarship->type,
            'application_status' => 'pending',
        ], $overrides));
    }

    private function assertStatusPageNotification($response, string $subject, string $body): void
    {
        preg_match(
            '/<script id="applicationsStatusData" type="application\/json">(.*?)<\/script>/s',
            $response->getContent(),
            $matches
        );
        $this->assertNotEmpty($matches[1] ?? null);

        $applications = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $notification = collect($applications)->firstWhere('last_notification_subject', $subject);
        $this->assertNotNull($notification);
        $this->assertSame($body, $notification['last_notification_body']);
    }

    private function assertRenderedMailContainsStoredBody(Mailable $mail): void
    {
        $renderedText = html_entity_decode(strip_tags($mail->render()), ENT_QUOTES | ENT_HTML5);
        $renderedText = preg_replace('/\s+/', ' ', $renderedText);
        $storedText = preg_replace('/\s+/', ' ', $mail->notificationBody());

        $this->assertStringContainsString($storedText, $renderedText);
    }
}