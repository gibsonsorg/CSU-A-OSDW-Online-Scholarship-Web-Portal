<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Storage::fake('public');

        $response = $this->post('/register', [
            'name' => 'Test User',
            'student_id' => '23-11941',
            'contact_number' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ]);

        $this->assertDatabaseHas('users', [
            'student_id' => '23-11941',
            'contact_number' => '09171234567',
        ]);
        $response->assertRedirect(route('approval.waiting', absolute: false));
    }
}
