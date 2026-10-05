<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScholarshipAvailabilityManagementTest extends TestCase
{
	use RefreshDatabase;

	public function test_new_scholarships_have_no_slot_limit_and_count_only_active_applications(): void
	{
		$scholarship = Scholarship::create([
			'name' => 'Availability Test Scholarship',
			'type' => 'academic',
			'status' => 'active',
		]);

		$scholarship->refresh();
		$this->assertNull($scholarship->max_slots);
		$this->assertNull($scholarship->availableSlotsCount());
		$this->assertNull($scholarship->availabilityMessage());

		foreach (['pending', 'approved', 'rejected'] as $status) {
			$user = User::factory()->create();
			$application = Application::create([
				'user_id' => $user->id,
				'scholarship_id' => $scholarship->id,
				'status' => $status,
			]);
			StudentProfile::create([
				'user_id' => $user->id,
				'application_id' => $application->id,
				'first_name' => 'Test',
				'last_name' => ucfirst($status),
				'sex' => 'Female',
				'status' => 'Single',
				'email' => $user->email,
				'course' => 'Test Course',
				'scholarship_name' => $scholarship->name,
				'scholarship_type' => 'Academic',
				'grant_type' => 'academic',
				'application_status' => $status,
			]);
		}

		$orphanUser = User::factory()->create();
		Application::create([
			'user_id' => $orphanUser->id,
			'scholarship_id' => $scholarship->id,
			'status' => 'pending',
		]);

		$lockedUser = User::factory()->create();
		$lockedApplication = Application::create([
			'user_id' => $lockedUser->id,
			'scholarship_id' => $scholarship->id,
			'status' => 'approved',
		]);
		StudentProfile::create([
			'user_id' => $lockedUser->id,
			'application_id' => $lockedApplication->id,
			'first_name' => 'Locked',
			'last_name' => 'Applicant',
			'sex' => 'Female',
			'status' => 'Single',
			'email' => $lockedUser->email,
			'course' => 'Test Course',
			'scholarship_name' => $scholarship->name,
			'scholarship_type' => 'Academic',
			'grant_type' => 'academic',
			'application_status' => 'approved',
			'locked_at' => now(),
		]);

		$this->assertSame(2, $scholarship->activeApplicationsCount());
		$scholarship->update(['max_slots' => 10]);
		$this->assertSame(8, $scholarship->availableSlotsCount());

		$unlimitedStudent = User::factory()->approved()->create(['student_id' => '22-10004']);
		$scholarship->update(['max_slots' => null]);
		$this->submit($unlimitedStudent, $scholarship->fresh())->assertSessionDoesntHaveErrors();
	}

	public function test_availability_messages_follow_master_switch_slots_then_daily_window_priority(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00'));

		$scholarship = Scholarship::create([
			'name' => 'Priority Test Scholarship',
			'type' => 'academic',
			'status' => 'active',
			'max_slots' => 0,
			'accepting_applications' => true,
			'time_restricted' => true,
			'application_start_time' => '13:00',
			'application_end_time' => '14:00',
		]);

		$this->assertSame('No slots available', $scholarship->availabilityMessage());

		$scholarship->update(['accepting_applications' => false]);
		$this->assertSame('This scholarship is currently not accepting applications.', $scholarship->availabilityMessage());

		$scholarship->update(['accepting_applications' => true, 'max_slots' => 1]);
		$this->assertSame('Applications are only accepted from 01:00 PM to 02:00 PM.', $scholarship->availabilityMessage());

		$scholarship->update(['application_start_time' => '11:00', 'application_end_time' => '13:00']);
		$this->assertNull($scholarship->availabilityMessage());

		Carbon::setTestNow();
	}

	public function test_application_date_range_messages_enforcement_and_priority(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-10-03 12:00:00', config('app.timezone')));

		try {
			$scholarship = Scholarship::create([
				'name' => 'Date Range Test Scholarship',
				'type' => 'academic',
				'status' => 'active',
				'max_slots' => 10,
				'accepting_applications' => true,
			]);

			$this->assertTrue($scholarship->isWithinApplicationDateRange());
			$this->assertNull($scholarship->applicationDateMessage());
			$this->assertNull($scholarship->availabilityMessage());

			$scholarship->update([
				'application_start_date' => '2026-10-04',
				'application_end_date' => null,
			]);
			$this->assertFalse($scholarship->isWithinApplicationDateRange());
			$this->assertSame('Applications accepted starting October 4, 2026.', $scholarship->availabilityMessage());

			$scholarship->update([
				'application_start_date' => null,
				'application_end_date' => '2026-10-02',
			]);
			$this->assertFalse($scholarship->isWithinApplicationDateRange());
			$this->assertSame('Applications accepted until October 2, 2026.', $scholarship->availabilityMessage());

			$scholarship->update([
				'application_start_date' => '2026-10-01',
				'application_end_date' => '2026-10-03',
			]);
			$this->assertTrue($scholarship->isWithinApplicationDateRange());
			$this->assertSame('Applications accepted from October 1, 2026 to October 3, 2026.', $scholarship->applicationDateMessage());
			$this->assertNull($scholarship->availabilityMessage());
			$dashboardStudent = User::factory()->approved()->create();
			$this->actingAs($dashboardStudent)
				->get('/dashboard?section=acad')
				->assertOk()
				->assertSee('Applications accepted from October 1, 2026 to October 3, 2026.', false);

			$scholarship->update([
				'application_start_date' => '2026-10-04',
				'application_end_date' => '2026-10-05',
			]);
			$this->assertSame('Applications accepted from October 4, 2026 to October 5, 2026.', $scholarship->availabilityMessage());

			$scholarship->update(['accepting_applications' => false]);
			$this->assertSame('This scholarship is currently not accepting applications.', $scholarship->availabilityMessage());

			$scholarship->update(['accepting_applications' => true, 'max_slots' => 0]);
			$this->assertSame('No slots available', $scholarship->availabilityMessage());

			$scholarship->update([
				'max_slots' => 10,
				'application_start_date' => '2026-10-01',
				'application_end_date' => '2026-10-05',
				'time_restricted' => true,
				'application_start_time' => '13:00',
				'application_end_time' => '14:00',
			]);
			$this->assertSame(
				'Applications are only accepted from 01:00 PM to 02:00 PM, between October 1, 2026 and October 5, 2026.',
				$scholarship->availabilityMessage()
			);
			$timeBlockedStudent = User::factory()->approved()->create(['student_id' => '22-10979']);
			$this->actingAs($timeBlockedStudent)
				->get('/dashboard?section=acad')
				->assertOk()
				->assertSee('Applications are only accepted from 01:00 PM to 02:00 PM, between October 1, 2026 and October 5, 2026.', false);
			$this->submit($timeBlockedStudent, $scholarship)
				->assertSessionHas('error', 'Applications are only accepted from 01:00 PM to 02:00 PM, between October 1, 2026 and October 5, 2026.');

			$scholarship->update([
				'application_start_time' => '11:00',
				'application_end_time' => '13:00',
			]);
			$this->assertNull($scholarship->availabilityMessage());
			$student = User::factory()->approved()->create(['student_id' => '22-10980']);
			$this->submit($student, $scholarship)->assertSessionDoesntHaveErrors();

			$outsideRangeStudent = User::factory()->approved()->create(['student_id' => '22-10981']);
			$scholarship->update([
				'application_start_date' => '2026-10-04',
				'application_end_date' => null,
				'application_start_time' => '07:00',
				'application_end_time' => '21:00',
			]);
			$this->assertSame('Applications accepted starting October 4, 2026.', $scholarship->availabilityMessage());
			$this->submit($outsideRangeStudent, $scholarship)
				->assertSessionHas('error', 'Applications accepted starting October 4, 2026.');

			$timeOnlyScholarship = Scholarship::create([
				'name' => 'Time Only Test Scholarship',
				'type' => 'academic',
				'status' => 'active',
				'accepting_applications' => true,
				'time_restricted' => true,
				'application_start_time' => '13:00',
				'application_end_time' => '14:00',
			]);
			$this->assertSame(
				'Applications are only accepted from 01:00 PM to 02:00 PM.',
				$timeOnlyScholarship->availabilityMessage()
			);

			$dateOnlyScholarship = Scholarship::create([
				'name' => 'Date Only Test Scholarship',
				'type' => 'academic',
				'status' => 'active',
				'accepting_applications' => true,
				'application_start_date' => '2026-10-04',
			]);
			$this->assertSame('Applications accepted starting October 4, 2026.', $dateOnlyScholarship->availabilityMessage());

			$priorityScholarship = Scholarship::create([
				'name' => 'Status Priority Date Scholarship',
				'type' => 'academic',
				'status' => 'active',
				'accepting_applications' => false,
				'application_start_date' => '2026-10-04',
			]);
			$this->assertSame(
				'This scholarship is currently not accepting applications.',
				$priorityScholarship->availabilityMessage()
			);
		} finally {
			Carbon::setTestNow();
		}
	}

	public function test_admin_can_filter_and_save_availability_settings_for_a_scholarship(): void
	{
		$admin = User::factory()->approved()->create(['role' => 0]);
		$academic = Scholarship::create([
			'name' => 'Academic Availability Row',
			'type' => 'academic',
			'status' => 'active',
		]);
		Scholarship::create([
			'name' => 'Non Academic Availability Row',
			'type' => 'non-academic',
			'status' => 'active',
		]);

		$this->actingAs($admin)
			->get('/admin/scholarships/availability?type=academic&name=Academic%20Availability%20Row')
			->assertOk()
			->assertSee('Academic Availability Row')
			->assertSee('Start Date')
			->assertSee('End Date')
			->assertDontSee('Non Academic Availability Row');

		$this->actingAs($admin)
			->from('/admin/scholarships/availability')
			->patch(route('admin.scholarships.availability.update', $academic), [
				'max_slots' => 2,
				'accepting_applications' => '1',
				'time_restricted' => '1',
				'application_start_time' => '09:00',
				'application_end_time' => '17:00',
				'application_start_date' => '2026-10-03',
				'application_end_date' => '2026-10-31',
			])
			->assertRedirect(route('admin.scholarships.availability', [
				'type' => 'academic',
				'name' => 'Academic Availability Row',
			]));

		$academic->refresh();
		$this->assertSame(2, $academic->max_slots);
		$this->assertTrue($academic->accepting_applications);
		$this->assertTrue($academic->time_restricted);
		$this->assertSame('09:00', $academic->application_start_time);
		$this->assertSame('17:00', $academic->application_end_time);
		$this->assertSame('2026-10-03', $academic->application_start_date->toDateString());
		$this->assertSame('2026-10-31', $academic->application_end_date->toDateString());

		$this->actingAs($admin)
			->patch(route('admin.scholarships.availability.update', $academic), [
				'application_start_date' => '',
				'application_end_date' => '2026-10-31',
			])
			->assertRedirect(route('admin.scholarships.availability', [
				'type' => 'academic',
				'name' => 'Academic Availability Row',
			]));
		$this->assertNull($academic->fresh()->application_start_date);
		$this->assertSame('2026-10-31', $academic->fresh()->application_end_date->toDateString());

		$this->actingAs($admin)
			->patch(route('admin.scholarships.availability.update', $academic), [
				'max_slots' => '',
				'accepting_applications' => '1',
				'application_start_date' => '',
				'application_end_date' => '',
			])
			->assertRedirect(route('admin.scholarships.availability', [
				'type' => 'academic',
				'name' => 'Academic Availability Row',
			]));
		$this->assertNull($academic->fresh()->max_slots);
		$this->assertNull($academic->fresh()->application_start_date);
		$this->assertNull($academic->fresh()->application_end_date);
	}

	public function test_student_submission_respects_capacity_and_rejection_frees_a_slot(): void
	{
		$students = collect(range(1, 3))->map(fn ($number) => User::factory()->approved()->create([
			'student_id' => '22-10' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
		]));
		$scholarship = Scholarship::create([
			'name' => 'Two Slot Test Scholarship',
			'type' => 'academic',
			'status' => 'active',
			'max_slots' => 2,
		]);

		$this->submit($students[0], $scholarship)->assertSessionDoesntHaveErrors();
		$this->submit($students[1], $scholarship)->assertSessionDoesntHaveErrors();
		$this->submit($students[2], $scholarship)
			->assertSessionHas('error', 'No slots available');

		$firstApplication = Application::where('user_id', $students[0]->id)
			->where('scholarship_id', $scholarship->id)
			->firstOrFail();
		$firstApplication->update(['status' => 'rejected']);
		$firstApplication->studentProfile()->update(['application_status' => 'rejected']);

		$this->submit($students[2], $scholarship)->assertSessionDoesntHaveErrors();
		$this->assertSame(2, $scholarship->activeApplicationsCount());
	}

	public function test_student_submission_enforces_time_and_master_switch(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00'));
		$student = User::factory()->approved()->create(['student_id' => '22-10991']);
		$scholarship = Scholarship::create([
			'name' => 'Window Test Scholarship',
			'type' => 'academic',
			'status' => 'active',
			'max_slots' => 2,
			'time_restricted' => true,
			'application_start_time' => '13:00',
			'application_end_time' => '14:00',
		]);

		$this->submit($student, $scholarship)
			->assertSessionHas('error', 'Applications are only accepted from 01:00 PM to 02:00 PM.');

		$scholarship->update([
			'application_start_time' => '11:00',
			'application_end_time' => '13:00',
		]);
		$this->submit($student, $scholarship)->assertSessionDoesntHaveErrors();

		$closedScholarship = Scholarship::create([
			'name' => 'Disabled Test Scholarship',
			'type' => 'academic',
			'status' => 'active',
			'max_slots' => 0,
			'accepting_applications' => false,
			'time_restricted' => true,
			'application_start_time' => '13:00',
			'application_end_time' => '14:00',
		]);
		$anotherStudent = User::factory()->approved()->create(['student_id' => '22-10992']);

		$this->submit($anotherStudent, $closedScholarship)
			->assertSessionHas('error', 'This scholarship is currently not accepting applications.');

		$nonAcademicScholarship = Scholarship::create([
			'name' => 'Socio-Cultural Scholarship',
			'type' => 'non-academic',
			'status' => 'active',
			'max_slots' => 0,
		]);
		$nonAcademicStudent = User::factory()->approved()->create(['student_id' => '22-10994']);
		$this->submit($nonAcademicStudent, $nonAcademicScholarship)
			->assertSessionHas('error', 'No slots available');

		Carbon::setTestNow();
	}

	public function test_dashboard_shows_availability_for_legacy_cards_in_both_categories(): void
	{
		$student = User::factory()->approved()->create(['student_id' => '22-10993']);
		Scholarship::create([
			'name' => 'Cagayan Valley Assoc. of Hawaii',
			'type' => 'academic',
			'status' => 'active',
			'max_slots' => 0,
		]);
		Scholarship::create([
			'name' => 'ATHLETIC SCHOLARSHIP',
			'type' => 'non-academic',
			'status' => 'active',
			'max_slots' => 0,
		]);

		$this->actingAs($student)
			->get('/dashboard?section=acad')
			->assertOk()
			->assertSee('No slots available')
			->assertSee('"cagayan valley assoc. of hawaii":0', false)
			->assertSee('window.scholarshipAvailabilityMessages', false);

		$this->actingAs($student)
			->get('/dashboard?section=non')
			->assertOk()
			->assertSee('No slots available')
			->assertSee('"athletic scholarship":0', false)
			->assertSee('"athletic scholarship":"No slots available"', false);
	}

	public function test_dashboard_omits_slot_count_map_for_scholarships_without_a_limit(): void
	{
		$student = User::factory()->approved()->create(['student_id' => '22-10995']);
		Scholarship::create([
			'name' => 'Unlimited Scholarship',
			'type' => 'academic',
			'status' => 'active',
			'max_slots' => null,
		]);

		$this->actingAs($student)
			->get('/dashboard?section=acad')
			->assertOk()
			->assertSee('window.scholarshipSlotCounts = {"id":[],"name":[]};', false);
	}

	public function test_dashboard_slot_counts_ignore_pre_cutoff_applications_and_keep_the_student_limit(): void
	{
		config(['scholarships.slot_counting_starts_at' => '2026-10-03T05:32:34+08:00']);
		Carbon::setTestNow(Carbon::parse('2026-10-02 21:32:33', 'UTC'));

		try {
			$scholarship = Scholarship::create([
				'name' => 'Cutoff Test Scholarship',
				'type' => 'academic',
				'status' => 'active',
				'max_slots' => 100,
			]);
			$otherScholarship = Scholarship::create([
				'name' => 'Other Cutoff Test Scholarship',
				'type' => 'academic',
				'status' => 'active',
			]);
			$limitedStudent = User::factory()->approved()->create(['student_id' => '22-10996']);
			$existingApplications = [];

			foreach ([$scholarship, $otherScholarship] as $index => $existingScholarship) {
				$application = Application::create([
					'user_id' => $limitedStudent->id,
					'scholarship_id' => $existingScholarship->id,
					'status' => 'pending',
				]);
				StudentProfile::create([
					'user_id' => $limitedStudent->id,
					'application_id' => $application->id,
					'first_name' => 'Existing',
					'last_name' => 'Applicant ' . $index,
					'sex' => 'Female',
					'status' => 'Single',
					'email' => 'existing-' . $index . '-' . $limitedStudent->email,
					'course' => 'Test Course',
					'scholarship_name' => $existingScholarship->name,
					'scholarship_type' => 'Academic',
					'grant_type' => 'academic',
					'application_status' => 'pending',
				]);
				$existingApplications[] = $application;
			}

			$existingCreatedAts = array_map(
				fn (Application $application) => $application->created_at->toDateTimeString(),
				$existingApplications
			);
			$this->assertSame(0, $scholarship->activeApplicationsCount());
			$this->assertSame(100, $scholarship->availableSlotsCount());

			$dashboardStudent = User::factory()->approved()->create(['student_id' => '22-10997']);
			$this->actingAs($dashboardStudent)
				->get('/dashboard?section=acad')
				->assertOk()
				->assertSee('"cutoff test scholarship":100', false);

			Carbon::setTestNow(Carbon::parse('2026-10-02 21:32:35', 'UTC'));
			$newStudent = User::factory()->approved()->create(['student_id' => '22-10998']);
			$this->submit($newStudent, $scholarship)->assertSessionDoesntHaveErrors();

			$scholarship->refresh();
			$this->assertSame(1, $scholarship->activeApplicationsCount());
			$this->assertSame(99, $scholarship->availableSlotsCount());

			$this->actingAs($newStudent)
				->get('/dashboard?section=acad')
				->assertOk()
				->assertSee('"cutoff test scholarship":99', false);

			$thirdScholarship = Scholarship::create([
				'name' => 'Third Cutoff Test Scholarship',
				'type' => 'academic',
				'status' => 'active',
				'max_slots' => 100,
			]);
			$this->submit($limitedStudent, $thirdScholarship)
				->assertSessionHas('error', 'You already have two active applications, so this scholarship is not available.');

			foreach ($existingApplications as $index => $application) {
				$freshApplication = $application->fresh();
				$this->assertSame($existingCreatedAts[$index], $freshApplication->created_at->toDateTimeString());
				$this->assertSame('pending', $freshApplication->status);
			}
			$this->assertSame(2, StudentProfile::where('user_id', $limitedStudent->id)
				->whereIn('application_status', ['pending', 'approved'])
				->whereNull('locked_at')
				->count());
		} finally {
			Carbon::setTestNow();
		}
	}

	private function submit(User $student, Scholarship $scholarship): \Illuminate\Testing\TestResponse
	{
		return $this->actingAs($student)
			->from('/dashboard?section=' . ($scholarship->type === 'academic' ? 'acad' : 'non'))
			->post('/student-profiles', [
				'first_name' => 'Test',
				'last_name' => 'Student',
				'sex' => 'Female',
				'status' => 'Single',
				'email' => $student->email,
				'student_id_number' => $student->student_id,
				'home_address' => 'Aparri',
				'contact_number' => '123456789',
				'birthdate' => '2000-01-01',
				'birthplace' => 'Aparri',
				'course' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
				'year_level' => '1st Year',
				'scholarship_id' => $scholarship->id,
				'scholarship_name' => $scholarship->name,
				'scholarship_type' => $scholarship->type === 'academic' ? 'Academic' : 'Non-Academic',
				'program' => $scholarship->type === 'academic' ? 'Information Technology' : null,
				'religion' => $scholarship->type === 'academic' ? 'Christian' : null,
				'uploads' => [UploadedFile::fake()->create('grades.pdf', 10, 'application/pdf')],
				'certificate_of_enrollment' => [UploadedFile::fake()->create('enrollment.pdf', 10, 'application/pdf')],
			]);
	}
}
