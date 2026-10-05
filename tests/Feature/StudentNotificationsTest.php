<?php

namespace Tests\Feature;

use App\Models\StudentNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_notifications_can_be_created_and_marked_read(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'name' => 'Student User',
        ]);

        $notification = StudentNotification::create([
            'user_id' => $user->id,
            'scholarship_name' => 'Academic Excellence Grant',
            'type' => 'approved',
            'message' => 'Your application has been approved.',
            'application_id' => 21,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'approved',
            'scholarship_name' => 'Academic Excellence Grant',
        ]);

        $this->assertEquals(1, $user->studentNotifications()->whereNull('read_at')->count());

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertEquals(0, $user->studentNotifications()->whereNull('read_at')->count());
    }
}
