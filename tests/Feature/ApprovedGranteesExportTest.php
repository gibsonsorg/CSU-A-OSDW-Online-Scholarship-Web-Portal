<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ApprovedGranteesExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_export_uses_approved_filters_and_is_only_shown_on_summary(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28));
        $admin = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
            'account_status' => 'approved',
        ]);

        Scholarship::create(['name' => 'Academic Award', 'type' => 'academic', 'status' => 'active']);
        Scholarship::create(['name' => 'Other Academic Award', 'type' => 'academic', 'status' => 'active']);
        Scholarship::create(['name' => 'Non-Academic Award', 'type' => 'non-academic', 'status' => 'active']);

        $this->createGrantee('Included', 'academic', 'Academic Award', 'approved', '2026-09-15');
        $this->createGrantee('Wrong name', 'academic', 'Other Academic Award', 'approved', '2026-09-15');
        $this->createGrantee('Wrong type', 'non-academic', 'Non-Academic Award', 'approved', '2026-09-15');
        $this->createGrantee('Wrong status', 'academic', 'Academic Award', 'pending', '2026-09-15');
        $this->createGrantee('Wrong date', 'academic', 'Academic Award', 'approved', '2026-08-15');

        $response = $this->actingAs($admin)->get(route('admin.dashboard.export', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'grant_type' => 'academic',
            'scholarship_name' => 'Academic Award',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('approved_grantees_2026-09-28.xlsx', $response->headers->get('content-disposition'));
        $sheet = $this->readWorkbookSheet($response);
        $filteredRows = $sheet->toArray();
        $this->assertSame([
            'Sequence No.',
            'Last Name',
            'First Name',
            'Middle Name',
            'Suffix',
            'Sex',
            'Course',
            'Year Level',
            'College or Department',
            'Type of Scholarship',
            'Name of Scholarship',
            'Funding',
            'Campus',
            'Date',
            'Amount',
        ], $filteredRows[0]);
        $this->assertCount(15, $filteredRows[1]);
        $this->assertSame('1', $filteredRows[1][0]);
        $this->assertSame('Included', $filteredRows[1][1]);
        $this->assertSame('2nd Year', $filteredRows[1][7]);
        $this->assertSame('Academic', $filteredRows[1][9]);
        $this->assertSame('Academic Award', $filteredRows[1][10]);
        $this->assertSame('2026-09-15', $filteredRows[1][13]);
        $this->assertCount(2, $filteredRows);

        $this->assertSame([], $sheet->getMergeCells());
        $this->assertSame('A1:O2', $sheet->getAutoFilter()->getRange());
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        $this->assertSame('F3E3B3', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertNotSame('4A0A12', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertGreaterThanOrEqual(30, $sheet->getColumnDimension('I')->getWidth());
        $this->assertTrue($sheet->getStyle('G2')->getAlignment()->getWrapText());
        $this->assertTrue($sheet->getStyle('I2')->getAlignment()->getWrapText());
        $this->assertTrue($sheet->getStyle('K2')->getAlignment()->getWrapText());
        $this->assertStringContainsString('₱', $sheet->getStyle('O2')->getNumberFormat()->getFormatCode());
        $this->assertSame('right', $sheet->getStyle('O2')->getAlignment()->getHorizontal());

        $combinedResponse = $this->get(route('admin.dashboard.export', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
        ]))->assertOk();
        $combinedRows = $this->readWorkbookSheet($combinedResponse)->toArray();
        $this->assertCount(4, $combinedRows);
        $this->assertEqualsCanonicalizing(['Academic', 'Academic', 'Non-Academic'], array_column(array_slice($combinedRows, 1), 9));
        $this->assertSame(['1', '2', '3'], array_column(array_slice($combinedRows, 1), 0));

        $this->get(route('admin.dashboard'))->assertSee('Download');
        $this->get(route('admin.academic'))->assertDontSee('Download');
        $this->get(route('admin.non_academic'))->assertDontSee('Download');
    }

    private function createGrantee(string $lastName, string $type, string $scholarshipName, string $status, string $createdAt): void
    {
        $grantee = StudentProfile::create([
            'first_name' => 'Test',
            'middle_name' => 'User',
            'last_name' => $lastName,
            'sex' => 'Female',
            'status' => 'Single',
            'email' => strtolower(str_replace(' ', '.', $lastName)) . '@example.test',
            'course' => 'BSIT',
            'scholarship_name' => $scholarshipName,
            'scholarship_type' => $type,
            'grant_type' => $type,
            'year_level' => '2nd Year',
            'application_status' => $status,
        ]);

        DB::table('student_profiles')->where('id', $grantee->id)->update(['created_at' => $createdAt]);
    }

    private function readWorkbookSheet($response): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        return IOFactory::load($response->baseResponse->getFile()->getPathname())
            ->getActiveSheet();
    }
}