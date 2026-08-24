<?php

namespace App\Imports;

use App\Models\OldGrantRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OldGrantImport
{
    private int $successCount = 0;
    private int $academicCount = 0;
    private int $nonAcademicCount = 0;
    private int $errorCount = 0;
    private int $duplicateCount = 0;
    private array $errors = [];

    public function __construct(private ?string $ignoredGrantType = null) {}

    public function import(string $path): void
    {
        $workbook = IOFactory::load($path);
        foreach ($workbook->getWorksheetIterator() as $sheet) {
            $this->importSheet($sheet);
        }
    }

    private function importSheet(Worksheet $sheet): void
    {
        $header = $this->findHeader($sheet);
        if ($header === null) {
            $this->addError($sheet->getTitle(), 'Unable to find a valid header row.');
            return;
        }

        $lastRow = $sheet->getHighestDataRow();
        $lastColumn = $sheet->getHighestDataColumn();
        for ($rowNumber = $header['row'] + 1; $rowNumber <= $lastRow; $rowNumber++) {
            $values = $sheet->rangeToArray("A{$rowNumber}:{$lastColumn}{$rowNumber}", null, true, false)[0] ?? [];
            $values = array_map(fn ($value) => $this->text($value), $values);
            if (count(array_filter($values, fn ($value) => $value !== '')) === 0) continue;
            $this->importRow($sheet->getTitle(), $rowNumber, $values, $header['map']);
        }
    }

    private function findHeader(Worksheet $sheet): ?array
    {
        $required = ['sequence_no', 'last_name', 'first_name', 'program', 'year', 'scholarship_name', 'scholarship_type', 'funding', 'amount'];
        $lastRow = $sheet->getHighestDataRow();
        $lastColumn = $sheet->getHighestDataColumn();
        for ($row = 1; $row <= $lastRow; $row++) {
            $values = $sheet->rangeToArray("A{$row}:{$lastColumn}{$row}", null, true, false)[0] ?? [];
            $map = [];
            foreach ($required as $field) {
                foreach ($values as $index => $value) {
                    if ($this->matches($this->text($value), $field)) { $map[$field] = $index; break; }
                }
            }
            if (count($map) === count($required)) {
                foreach (['middle_name', 'sex'] as $field) {
                    foreach ($values as $index => $value) {
                        if ($this->matches($this->text($value), $field)) { $map[$field] = $index; break; }
                    }
                }
                return ['row' => $row, 'map' => $map];
            }
        }
        return null;
    }

    private function matches(string $value, string $field): bool
    {
        $value = $this->header($value);
        $aliases = [
            'sequence_no' => ['sequence no', 'sequence'], 'last_name' => ['last name', 'surname'],
            'first_name' => ['first name', 'given name'], 'middle_name' => ['middle name'], 'sex' => ['sex', 'gender'],
            'program' => ['program', 'course'], 'year' => ['year'],
            'scholarship_name' => ['name of scholarship financial assistance', 'scholarship name', 'financial assistance'],
            'scholarship_type' => ['type of scholarship'], 'funding' => ['funding'],
            'amount' => ['amount of financial assistance', 'amount'],
        ];
        foreach ($aliases[$field] as $alias) {
            $alias = $this->header($alias);
            if ($value === $alias || str_contains($value, $alias)) return true;
        }
        return false;
    }

    private function importRow(string $sheet, int $rowNumber, array $values, array $map): void
    {
        $data = [];
        foreach (['sequence_no', 'last_name', 'first_name', 'middle_name', 'sex', 'program', 'year', 'scholarship_name', 'scholarship_type', 'funding', 'amount'] as $field) {
            $data[$field] = isset($map[$field], $values[$map[$field]]) && $values[$map[$field]] !== '' ? $values[$map[$field]] : null;
        }
        $data['course'] = $data['program']; unset($data['program']);
        $data['funding_source'] = $data['funding']; unset($data['funding']);
        $type = strtolower(trim((string) $data['scholarship_type']));
        $data['scholarship_type'] = str_contains($type, 'non') && str_contains($type, 'academic') ? 'non_academic' : (str_contains($type, 'academic') ? 'academic' : null);
        foreach (['sequence_no', 'last_name', 'first_name', 'scholarship_name', 'scholarship_type', 'funding_source', 'amount'] as $field) {
            if ($data[$field] === null) { $this->addError($sheet, "Excel row {$rowNumber}: Missing {$field}."); return; }
        }
        if (OldGrantRecord::where('sequence_no', $data['sequence_no'])->where('last_name', $data['last_name'])->where('first_name', $data['first_name'])->where('scholarship_name', $data['scholarship_name'])->exists()) { $this->duplicateCount++; return; }
        OldGrantRecord::create($data);
        $this->successCount++;
        $data['scholarship_type'] === 'academic' ? $this->academicCount++ : $this->nonAcademicCount++;
    }

    private function header(string $value): string { return preg_replace('/\s+/', ' ', str_replace(["\r", "\n", "\t", "\u{00A0}", '.', '/', '(', ')', ','], ' ', strtolower(trim($value)))) ?? ''; }
    private function text(mixed $value): string { return is_array($value) ? implode(' ', array_map(fn ($item) => $this->text($item), $value)) : trim((string) $value); }
    private function addError(string $sheet, string $message): void { $this->errorCount++; $this->errors[] = "Sheet {$sheet}: {$message}"; }
    public function getSuccessCount(): int { return $this->successCount; }
    public function getAcademicCount(): int { return $this->academicCount; }
    public function getNonAcademicCount(): int { return $this->nonAcademicCount; }
    public function getErrorCount(): int { return $this->errorCount; }
    public function getDuplicateCount(): int { return $this->duplicateCount; }
    public function getErrors(): array { return $this->errors; }
}
