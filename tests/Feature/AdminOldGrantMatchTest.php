<?php

namespace Tests\Feature;

use App\Models\OldGrantRecord;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOldGrantMatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_profile_matches_old_records_by_normalized_first_and_last_name_in_one_query(): void
    {
        $admin = User::factory()->create(['role' => 0]);
        $profile = $this->makeProfile('application', 'mArIa.   Clara', 'LAGMAY JR.', 'academic');
        OldGrantRecord::create([
            'first_name' => ' MARIA.    Clara, ',
            'last_name' => 'Lagmay',
            'scholarship_name' => 'Legacy Assistance',
            'scholarship_type' => 'non_academic',
            'year' => '2018',
        ]);
        OldGrantRecord::create([
            'first_name' => 'Maria Clara',
            'last_name' => 'LAGMAY, JR.',
            'scholarship_name' => 'Another Legacy Award',
            'scholarship_type' => 'academic',
            'year' => '2012',
        ]);
        OldGrantRecord::create([
            'first_name' => 'maria clara',
            'last_name' => ',.Lagmay JR.',
            'scholarship_name' => 'Punctuated Legacy Award',
            'scholarship_type' => 'academic',
            'year' => '1998',
        ]);

        $oldGrantQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$oldGrantQueries): void {
            if (str_contains(strtolower($query->sql), 'old_grant_records')) {
                $oldGrantQueries[] = $query->sql;
            }
        });

        $this->actingAs($admin)
            ->getJson("/admin/applications/{$profile->id}")
            ->assertOk()
            ->assertJsonCount(3, 'old_grant_matches')
            ->assertJsonPath('old_grant_matches.0.scholarship_name', 'Legacy Assistance')
            ->assertJsonPath('old_grant_matches.0.scholarship_type', 'non_academic')
            ->assertJsonPath('old_grant_matches.1.year', '2012');

        $this->assertCount(1, $oldGrantQueries);
    }

    public function test_manual_and_unmatched_profiles_have_no_old_grant_matches(): void
    {
        $admin = User::factory()->create(['role' => 0]);
        OldGrantRecord::create([
            'first_name' => 'Alex',
            'last_name' => 'Example',
            'scholarship_name' => 'Legacy Assistance',
            'scholarship_type' => 'academic',
            'year' => '2018',
        ]);
        $manual = $this->makeProfile('manual', 'Alex', 'Example', 'academic');
        $unmatched = $this->makeProfile('application', 'Different', 'Student', 'non-academic');

        $oldGrantQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$oldGrantQueries): void {
            if (str_contains(strtolower($query->sql), 'old_grant_records')) {
                $oldGrantQueries[] = $query->sql;
            }
        });

        $this->actingAs($admin)
            ->getJson("/admin/applications/{$manual->id}")
            ->assertOk()
            ->assertJsonCount(0, 'old_grant_matches');
        $this->assertCount(0, $oldGrantQueries);

        $this->actingAs($admin)
            ->getJson("/admin/applications/{$unmatched->id}")
            ->assertOk()
            ->assertJsonCount(0, 'old_grant_matches');

        $this->assertCount(1, $oldGrantQueries);
    }

    private function makeProfile(string $source, string $firstName, string $lastName, string $grantType): StudentProfile
    {
        $student = User::factory()->create();

        return StudentProfile::create([
            'user_id' => $student->id,
            'source' => $source,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $student->email,
            'course' => 'Information Technology',
            'scholarship_name' => 'Test Scholarship',
            'scholarship_type' => ucfirst($grantType),
            'grant_type' => $grantType,
            'application_status' => 'pending',
        ]);
    }
}