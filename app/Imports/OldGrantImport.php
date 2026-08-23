<?php

namespace App\Imports;

use App\Models\OldGrantRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

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

            $requiredFields = ['first_name', 'course', 'year', 'scholarship_name', 'funding_source', 'campus'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->errorCount++;
                    $this->errors[] = 'Row skipped: Missing ' . str_replace('_', ' ', $field);
                    return null;
                }
            }

            $maxLengths = [
                'first_name' => 255, 'middle_name' => 255, 'sex' => 255,
                'course' => 255, 'year' => 255, 'scholarship_name' => 255,
                'funding_source' => 255, 'campus' => 255,
            ];
            foreach ($maxLengths as $field => $maxLength) {
                if (isset($data[$field]) && strlen($data[$field]) > $maxLength) {
                    $this->errorCount++;
                    $this->errors[] = 'Row skipped: ' . str_replace('_', ' ', $field) . ' is too long';
                    return null;
                }
            }

            if (!in_array(strtolower($data['sex'] ?? ''), ['male', 'female'], true)) {
                $this->errorCount++;
                $this->errors[] = 'Row skipped: Invalid sex value';
                return null;
            }

            // Set scholarship_type based on constructor parameter
            $data['scholarship_type'] = $this->scholarshipType;

            $this->successCount++;
            return new OldGrantRecord($data);
        } catch (\Exception $e) {
            $this->errorCount++;
            Log::error('Old grant row import failed', ['exception' => $e]);
            $this->errors[] = 'Row skipped: malformed data';
            return null;
        }
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'course' => 'required|string|max:255',
            'year' => 'required|string|max:255',
            'scholarship_name' => 'required|string|max:255',
            'funding_source' => 'required|string|max:255',
            'campus' => 'required|string|max:255',
        ];
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
