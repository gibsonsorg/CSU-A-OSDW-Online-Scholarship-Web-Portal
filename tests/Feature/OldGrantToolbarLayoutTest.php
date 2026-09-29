<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OldGrantToolbarLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_grant_pages_keep_upload_and_filters_without_a_page_heading_in_the_toolbar(): void
    {
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        foreach ([
            ['route' => 'admin.old_academic', 'heading' => 'Old Academic Grants'],
            ['route' => 'admin.old_non_academic', 'heading' => 'Old Non-Academic Grants'],
        ] as $page) {
            $response = $this->actingAs($admin)->get(route($page['route']))->assertOk();
            $html = $response->getContent();
            $document = new \DOMDocument();
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $toolbars = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' old-grants-toolbar ')]");

            $this->assertSame(1, $toolbars->length);
            $toolbar = $toolbars->item(0);
            $toolbarText = $toolbar->textContent;
            $this->assertStringNotContainsString($page['heading'], $toolbarText);
            $this->assertSame(1, $xpath->query(".//button[contains(normalize-space(.), 'Upload Old Data')]", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//form[contains(concat(' ', normalize-space(@class), ' '), ' grant-filters ')]", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//form[contains(concat(' ', normalize-space(@class), ' '), ' grant-filters ')]/following-sibling::button[contains(normalize-space(.), 'Upload Old Data')]", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//*[@name='date_from']", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//*[@name='date_to']", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//*[@name='scholarship_name']", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//*[@name='status']", $toolbar)->length);
            $this->assertSame(1, $xpath->query(".//button[normalize-space(.)='Filter']", $toolbar)->length);
            $this->assertSame(1, $xpath->query("//div[@id='adminSidebar']//a[contains(normalize-space(.), 'Old Academic Grants')]")->length);
            $this->assertSame(1, $xpath->query("//div[@id='adminSidebar']//a[contains(normalize-space(.), 'Old Non-Academic Grants')]")->length);
            $this->assertStringContainsString('.old-grants-toolbar > .grant-filters', $html);
            $this->assertStringContainsString('@media (max-width: 900px)', $html);
            $this->assertStringContainsString($page['heading'], $html);
        }

        foreach (['admin.academic', 'admin.non_academic'] as $routeName) {
            $html = $this->get(route($routeName))->assertOk()->getContent();
            $document = new \DOMDocument();
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' old-grants-toolbar ')]")->length);
        }
    }
}