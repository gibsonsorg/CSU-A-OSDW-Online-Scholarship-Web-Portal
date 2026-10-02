<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicScholarshipUploadRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public static function academicScholarshipNames(): array
    {
        return [
            'Cagayan Valley Assoc. of Hawaii' => ['Cagayan Valley Assoc. of Hawaii'],
            'DOST SEI Project Strand' => ['DOST SEI Project Strand'],
            'ACEF – GIAHEP' => ['ACEF – GIAHEP'],
            'LGU Aparri' => ['LGU Aparri'],
            'Tulong Dunong Program' => ['Tulong Dunong Program'],
        ];
    }

    #[DataProvider('academicScholarshipNames')]
    public function test_named_academic_grants_accept_pdf_and_docx_with_both_required_documents(string $name): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => $name, 'type' => 'academic']);

        $response = $this->actingAs($student)->post('/student-profiles', $this->applicationPayload($student, $scholarship, [
            'uploads' => [UploadedFile::fake()->create('grades.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
            'certificate_of_enrollment' => UploadedFile::fake()->create('enrollment.pdf', 100, 'application/pdf'),
        ]));

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $profile = StudentProfile::where('user_id', $student->id)->firstOrFail();
        $this->assertCount(1, $profile->uploads['certificate_of_grades']);
        $this->assertCount(1, $profile->uploads['certificate_of_enrollment']);
        Storage::disk('local')->assertExists($profile->uploads['certificate_of_grades'][0]);
        Storage::disk('local')->assertExists($profile->uploads['certificate_of_enrollment'][0]);
    }

    public function test_academic_grants_reject_images_and_files_over_two_megabytes(): void
    {
        foreach ([
            ['grades.jpg', 100, 'image/jpeg'],
            ['grades.jpeg', 100, 'image/jpeg'],
            ['grades.png', 100, 'image/png'],
            ['grades.pdf', 2049, 'application/pdf'],
        ] as [$fileName, $size, $mime]) {
            $student = User::factory()->approved()->create();
            $scholarship = Scholarship::create(['name' => 'Document Only ' . $fileName, 'type' => 'academic']);

            $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $this->applicationPayload($student, $scholarship, [
                'uploads' => [UploadedFile::fake()->create($fileName, $size, $mime)],
                'certificate_of_enrollment' => UploadedFile::fake()->create('enrollment.pdf', 100, 'application/pdf'),
            ]))->assertSessionHasErrors('uploads.0');

            $this->assertDatabaseMissing('student_profiles', ['user_id' => $student->id]);
        }
    }

    public function test_certificate_of_enrollment_is_required_for_non_university_academic_grants(): void
    {
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Enrollment Required Award', 'type' => 'academic']);

        $this->actingAs($student)->from('/dashboard')->post('/student-profiles', $this->applicationPayload($student, $scholarship, [
            'uploads' => [UploadedFile::fake()->create('grades.pdf', 100, 'application/pdf')],
        ]))->assertSessionHas('error', fn (string $message) => str_contains($message, 'Certificate of Enrollment'));

        $this->assertDatabaseMissing('student_profiles', ['user_id' => $student->id]);
    }

    public function test_rejected_academic_application_can_reapply_with_new_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('student_profiles/old-grades.pdf', 'old grades');
        Storage::disk('local')->put('student_profiles/old-enrollment.pdf', 'old enrollment');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'Reapply Document Award', 'type' => 'academic']);
        $application = \App\Models\Application::create([
            'user_id' => $student->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'rejected',
        ]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'application_id' => $application->id,
            'first_name' => 'Academic',
            'last_name' => 'Applicant',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'program' => 'Information Technology',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'religion' => 'None',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
            'grant_type' => 'academic',
            'application_status' => 'rejected',
            'uploads' => [
                'certificate_of_grades' => ['student_profiles/old-grades.pdf'],
                'certificate_of_enrollment' => ['student_profiles/old-enrollment.pdf'],
            ],
        ]);

        $response = $this->actingAs($student)->post('/student-profiles', $this->applicationPayload($student, $scholarship, [
            'student_profile_id' => $profile->id,
            'application_id' => $application->id,
            'uploads' => [UploadedFile::fake()->create('replacement-grades.pdf', 100, 'application/pdf')],
            'certificate_of_enrollment' => UploadedFile::fake()->create('replacement-enrollment.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]));

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(1, (int) $profile->fresh()->reapply_count);
        $this->assertSame('pending', $application->fresh()->status);
        $this->assertNotSame('student_profiles/old-grades.pdf', $profile->fresh()->uploads['certificate_of_grades'][0]);
        $this->assertNotSame('student_profiles/old-enrollment.pdf', $profile->fresh()->uploads['certificate_of_enrollment'][0]);
    }

    public function test_university_based_keeps_legacy_image_upload_validation(): void
    {
        Storage::fake('local');
        $student = User::factory()->approved()->create();
        $scholarship = Scholarship::create(['name' => 'University Based Scholarship', 'type' => 'academic']);

        $response = $this->actingAs($student)->post('/student-profiles', $this->applicationPayload($student, $scholarship, [
            'uploads' => [UploadedFile::fake()->create('grades.jpg', 900, 'image/jpeg')],
            'certificate_of_enrollment' => UploadedFile::fake()->create('enrollment.pdf', 100, 'application/pdf'),
        ]));

        $response->assertRedirect(route('dashboard', ['section' => 'acad'], absolute: false))
            ->assertSessionDoesntHaveErrors();
        $profile = StudentProfile::where('user_id', $student->id)->firstOrFail();
        $this->assertCount(1, $profile->uploads['certificate_of_grades']);
    }

    private function applicationPayload(User $student, Scholarship $scholarship, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Academic',
            'last_name' => 'Applicant',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'student_id_number' => $student->student_id ?: '23-11941',
            'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'year_level' => '2nd Year',
            'program' => 'Information Technology',
            'religion' => 'None',
            'birthdate' => '2000-01-01',
            'birthplace' => 'Aparri',
            'scholarship_id' => $scholarship->id,
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => 'Academic',
        ], $overrides);
    }
}