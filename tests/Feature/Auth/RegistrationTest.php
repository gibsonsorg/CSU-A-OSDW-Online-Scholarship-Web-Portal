<?php

namespace Tests\Feature\Auth;

use App\Models\User;
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
        Storage::fake('local');

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
            'student_id' => '23-11941',
            'password' => 'password',
            'password_confirmation' => 'password',
            'id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ]);

        $this->assertDatabaseHas('users', [
            'student_id' => '23-11941',
            'email' => 'test.user@example.com',
        ]);
        $response->assertRedirect('/');
    }

    public function test_duplicate_name_is_rejected_with_a_validation_error(): void
    {
        User::factory()->create(['name' => 'Duplicate Name']);

        Storage::fake('local');

        $response = $this->from('/register')->post('/register', [
            'name' => 'Duplicate Name',
            'email' => 'new.user@example.com',
            'student_id' => '23-11942',
            'password' => 'password',
            'password_confirmation' => 'password',
            'id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertSessionHasErrors([
            'name' => 'This full name is already registered',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'new.user@example.com']);
    }
}
