<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_uses_the_shared_sidebar_toggle_and_marks_settings_active(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('backup.index'))->assertOk();
        $html = $response->getContent();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query("//button[@id='sidebarToggle' and @aria-label='Toggle sidebar']")->length);
        $this->assertSame(1, $xpath->query("//div[@id='adminSidebar']")->length);
        $this->assertSame(1, $xpath->query("//div[@id='adminMain']")->length);
        $this->assertSame(1, $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ') and contains(concat(' ', normalize-space(@class), ' '), ' active ') and normalize-space(.)='Settings']")->length);
        $this->assertSame(1, $xpath->query("//script[contains(@src, '/js/admin-sidebar.js')]")->length);
        $this->assertStringContainsString('Backup & Recovery', $html);
        $this->assertStringContainsString('Old Academic Grants', $html);
        $this->assertStringContainsString('Old Non-Academic Grants', $html);
        $this->assertStringContainsString('Add Scholarship', $html);
        $this->assertStringContainsString('Logout', $html);
    }

    public function test_dashboard_and_add_scholarship_retain_their_sidebar_toggle(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        $dashboard = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('id="sidebarToggle"', $dashboard);
        $this->assertStringContainsString('/js/admin-sidebar.js', $dashboard);

        $scholarships = $this->get(route('admin.scholarships.create'))->assertOk()->getContent();
        $this->assertStringContainsString('id="sidebarToggle"', $scholarships);
        $this->assertStringContainsString('toggle.addEventListener', $scholarships);
    }
}
