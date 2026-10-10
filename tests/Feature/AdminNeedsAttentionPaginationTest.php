<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNeedsAttentionPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_pages_all_sections_together_clamps_pages_and_uses_six_queries(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $pendingScholarship = Scholarship::create([
            'name' => 'Needs Attention Pending Grant',
            'type' => 'academic',
            'status' => 'active',
        ]);

        for ($index = 1; $index <= 10; $index++) {
            $this->createApplicationProfile(
                $pendingScholarship,
                sprintf('Pending %02d', $index),
                'pending',
                now()->subDays(15 - $index)
            );
        }

        foreach ([1, 6] as $daysUntilClose) {
            Scholarship::create([
                'name' => 'Closing in ' . $daysUntilClose . ' Days',
                'type' => 'non-academic',
                'status' => 'active',
                'application_end_date' => now()->addDays($daysUntilClose)->toDateString(),
            ]);
        }

        foreach ([20, 19, 18, 17, 18, 17] as $index => $activeCount) {
            $scholarship = Scholarship::create([
                'name' => sprintf('Near Capacity %02d', $index + 1),
                'type' => 'academic',
                'status' => 'active',
                'max_slots' => 20,
            ]);

            for ($studentIndex = 1; $studentIndex <= $activeCount; $studentIndex++) {
                $this->createApplicationProfile(
                    $scholarship,
                    sprintf('Capacity %02d Student %02d', $index + 1, $studentIndex),
                    'approved'
                );
            }
        }

        $sectionQueries = [];
        DB::listen(static function ($query) use (&$sectionQueries): void {
            $sql = strtolower(ltrim($query->sql));
            if (str_starts_with($sql, 'select')
                && (str_contains($sql, 'student_profiles') || str_contains($sql, 'scholarships') || str_contains($sql, 'applications'))) {
                $sectionQueries[] = $query->sql;
            }
        });

        $pageTwo = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.needs_attention', ['na_page' => 2]))
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonPath('totalPages', 3)
            ->json('html');

        $this->assertStringContainsString('Pending 05', $pageTwo);
        $this->assertStringContainsString('Pending 08', $pageTwo);
        $this->assertStringNotContainsString('Pending 04', $pageTwo);
        $this->assertStringContainsString('needs-attention-badge-urgent">9 days', $pageTwo);
        $this->assertStringContainsString('No more items.', $pageTwo);
        $this->assertStringContainsString('Near Capacity 04', $pageTwo);
        $this->assertStringContainsString('Near Capacity 06', $pageTwo);
        $this->assertSame(6, count($sectionQueries));

        $this->get('/admin?na_page=2')
            ->assertOk()
            ->assertSee('2 / 3')
            ->assertSee('Pending 05');

        $pageThree = $this->getJson(route('admin.dashboard.needs_attention', ['na_page' => 3]))
            ->assertOk()
            ->assertJsonPath('page', 3)
            ->assertJsonPath('totalPages', 3)
            ->json('html');
        $this->assertStringContainsString('Pending 09', $pageThree);
        $this->assertStringContainsString('Pending 10', $pageThree);
        $this->assertSame(2, substr_count($pageThree, 'No more items.'));

        $this->getJson(route('admin.dashboard.needs_attention', ['na_page' => 999]))
            ->assertOk()
            ->assertJsonPath('page', 3)
            ->assertJsonPath('totalPages', 3);
        $this->getJson(route('admin.dashboard.needs_attention', ['na_page' => 'invalid']))
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('totalPages', 3);
    }

    public function test_empty_sections_keep_the_empty_state_and_hide_pagination(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.needs_attention'))
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('totalPages', 1);

        $html = $response->json('html');
        $this->assertSame(3, substr_count($html, 'Nothing needs attention right now.'));
        $this->assertStringNotContainsString('No more items.', $html);
        $this->assertStringNotContainsString('needs-attention-pagination', $html);
    }

    public function test_admin_only_endpoint_hides_pagination_when_populated_sections_fit_on_one_page(): void
    {
        $admin = User::factory()->approved()->create(['role' => 0]);
        $pending = Scholarship::create([
            'name' => 'Single Page Pending Grant',
            'type' => 'academic',
            'status' => 'active',
        ]);
        $this->createApplicationProfile($pending, 'One Pending Applicant', 'pending', now()->subDays(5));
        Scholarship::create([
            'name' => 'Single Page Closing Grant',
            'type' => 'non-academic',
            'status' => 'active',
            'application_end_date' => now()->addDays(2)->toDateString(),
        ]);
        $nearCapacity = Scholarship::create([
            'name' => 'Single Page Capacity Grant',
            'type' => 'academic',
            'status' => 'active',
            'max_slots' => 10,
        ]);
        for ($index = 1; $index <= 9; $index++) {
            $this->createApplicationProfile($nearCapacity, 'Capacity Student ' . $index, 'approved');
        }

        $html = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.needs_attention'))
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('totalPages', 1)
            ->json('html');
        $this->assertStringContainsString('Single Page Pending Grant', $html);
        $this->assertStringContainsString('Single Page Closing Grant', $html);
        $this->assertStringContainsString('Single Page Capacity Grant', $html);
        $this->assertStringNotContainsString('needs-attention-pagination', $html);

        $student = User::factory()->approved()->create(['role' => 1]);
        $this->actingAs($student)
            ->getJson(route('admin.dashboard.needs_attention'))
            ->assertForbidden();
    }

    private function createApplicationProfile(
        Scholarship $scholarship,
        string $firstName,
        string $status,
        ?\Illuminate\Support\Carbon $createdAt = null
    ): StudentProfile {
        $user = User::factory()->approved()->create();
        $application = Application::create([
            'user_id' => $user->id,
            'scholarship_id' => $scholarship->id,
            'status' => $status,
        ]);
        $profile = StudentProfile::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'source' => 'application',
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => 'Test Student',
            'sex' => 'Female',
            'status' => 'Single',
            'email' => $user->email,
            'course' => 'Information Technology',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => $scholarship->type === 'academic' ? 'Academic' : 'Non-Academic',
            'grant_type' => $scholarship->type,
            'application_status' => $status,
        ]);

        if ($createdAt) {
            $profile->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        return $profile;
    }
}