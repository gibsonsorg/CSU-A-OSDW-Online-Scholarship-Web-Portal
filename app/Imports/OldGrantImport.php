<?php

namespace App\Imports;

use App\Models\OldGrantRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;

class OldGrantImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    private $scholarshipType;
    private $successCount = 0;
    private $errorCount = 0;
    private $errors = [];

    public function __construct($scholarshipType = 'academic')
    {
        $this->scholarshipType = $scholarshipType;
    }

    /**
     * Map Excel row to OldGrantRecord
     */
    public function model(array $row)
    {
        try {
            // Map column names from Excel to model fields
            // Excel columns should have these headers (case-insensitive):
            // First Name, Middle Name, Sex, Course, Year, 
            // Name of Scholarship / Financial Assistance, Type of Scholarship, Funding, Campus

            $data = [];

            // Extract and trim values from row using flexible column name mapping
            foreach ($row as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                
                $normalizedKey = strtolower(trim($key));

                // Map Excel column names to database fields
                if (strpos($normalizedKey, 'first') !== false && strpos($normalizedKey, 'name') !== false) {
                    $data['first_name'] = trim($value);
                } elseif (strpos($normalizedKey, 'middle') !== false) {
                    $data['middle_name'] = trim($value);
                } elseif ($normalizedKey === 'sex') {
                    $data['sex'] = trim($value);
                } elseif ($normalizedKey === 'course') {
                    $data['course'] = trim($value);
                } elseif ($normalizedKey === 'year') {
                    $data['year'] = trim($value);
                } elseif (strpos($normalizedKey, 'scholarship') !== false && (strpos($normalizedKey, 'name') !== false || strpos($normalizedKey, 'assistance') !== false)) {
                    $data['scholarship_name'] = trim($value);
                } elseif (strpos($normalizedKey, 'type') !== false && strpos($normalizedKey, 'scholarship') !== false) {
                    $data['scholarship_type'] = trim($value);
                } elseif (strpos($normalizedKey, 'funding') !== false || strpos($normalizedKey, 'source') !== false) {
                    $data['funding_source'] = trim($value);
                } elseif ($normalizedKey === 'campus') {
                    $data['campus'] = trim($value);
                }
            }

            // Validate that we have at least first_name
            if (empty($data['first_name'])) {
                $this->errorCount++;
                $this->errors[] = 'Row skipped: Missing first name';
                return null;
            }

            // Set scholarship_type based on constructor parameter
            $data['scholarship_type'] = $this->scholarshipType;

            $this->successCount++;
            return new OldGrantRecord($data);
        } catch (\Exception $e) {
            $this->errorCount++;
            $this->errors[] = 'Error: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get success count
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * Get error count
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * Get errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
