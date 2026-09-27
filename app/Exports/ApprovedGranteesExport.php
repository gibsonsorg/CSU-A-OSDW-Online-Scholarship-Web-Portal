<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class ApprovedGranteesExport implements FromQuery, WithHeadings, WithMapping, WithCustomStartCell, WithColumnWidths, WithEvents
{
    private int $sequence = 0;

    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
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
        ];
    }

    public function map($grantee): array
    {
        return [
            ++$this->sequence,
            $grantee->last_name,
            $grantee->first_name,
            $grantee->middle_name,
            $grantee->suffix,
            $grantee->sex,
            $grantee->course,
            $grantee->year_level,
            $grantee->college_department,
            match ($grantee->scholarship_category_type) {
                'academic' => 'Academic',
                'non-academic' => 'Non-Academic',
                default => 'Unknown',
            },
            $grantee->scholarship_name,
            $grantee->scholarship_funding_type,
            $grantee->campus ?: 'Aparri',
            optional($grantee->created_at)->format('Y-m-d'),
            $grantee->amount,
        ];
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 13,
            'B' => 18,
            'C' => 18,
            'D' => 18,
            'E' => 12,
            'F' => 12,
            'G' => 18,
            'H' => 15,
            'I' => 32,
            'J' => 22,
            'K' => 32,
            'L' => 18,
            'M' => 16,
            'N' => 14,
            'O' => 17,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(1, $sheet->getHighestRow());

                $sheet->getRowDimension(1)->setRowHeight(34);
                $sheet->getStyle('A1:O1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '3D2A10']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3E3B3']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                    'borders' => ['bottom' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'B99B5B']]],
                ]);

                if ($lastRow > 1) {
                    $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("I2:I{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("K2:K{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("O2:O{$lastRow}")->getNumberFormat()->setFormatCode('"₱"#,##0.00;[Red]-"₱"#,##0.00');
                    $sheet->getStyle("O2:O{$lastRow}")->getAlignment()->setHorizontal('right');
                    $sheet->getStyle("A2:O{$lastRow}")->getAlignment()->setVertical('top');
                }

                $sheet->setAutoFilter("A1:O{$lastRow}");
                $sheet->freezePane('A2');
            },
        ];
    }
}