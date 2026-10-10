<?php

namespace App\Http\Controllers;

use App\Imports\OldGrantImport;
use App\Models\OldGrantRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OldGrantController extends Controller
{
    private const OFFICIAL_COURSE_OPTIONS = [
        'BACHELOR OF SCIENCE IN CRIMINOLOGY',
        'BACHELOR OF SCIENCE IN FISHERIES',
        'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
        'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT',
        'BACHELOR OF SECONDARY EDUCATION',
        'BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM',
        'BACHELOR OF ELEMENTARY EDUCATION',
    ];

    /**
     * Display old academic grants
     */
    public function academicGrants(Request $request)
    {
        $filterType = 'old-academic';
        $scholarshipNames = $this->distinctValuesForType($filterType, 'scholarship_name');
        $yearLevels = $this->distinctValuesForType($filterType, 'year');
        $courses = $this->courseOptions();
        $oldRecords = $this->filteredOldRecords($request, 'academic');

        return view('admin.welcome', compact('oldRecords', 'filterType', 'scholarshipNames', 'yearLevels', 'courses'));
    }

    /**
     * Display old non-academic grants
     */
    public function nonAcademicGrants(Request $request)
    {
        $filterType = 'old-non-academic';
        $scholarshipNames = $this->distinctValuesForType($filterType, 'scholarship_name');
        $yearLevels = $this->distinctValuesForType($filterType, 'year');
        $courses = $this->courseOptions();
        $oldRecords = $this->filteredOldRecords($request, 'non_academic');

        return view('admin.welcome', compact('oldRecords', 'filterType', 'scholarshipNames', 'yearLevels', 'courses'));
    }

    private function courseOptions(): array
    {
        return self::OFFICIAL_COURSE_OPTIONS;
    }

    private function courseMatchValues(string $selectedCourse): array
    {
        $selectedCourse = trim($selectedCourse);
        $courseAliases = [
            'BACHELOR OF SCIENCE IN CRIMINOLOGY' => ['BACHELOR OF SCIENCE IN CRIMINOLOGY', 'BS IN CRIMINOLOGY', 'BSCRIM', 'BSCrim', 'BSCrim'],
            'BACHELOR OF SCIENCE IN FISHERIES' => ['BACHELOR OF SCIENCE IN FISHERIES', 'BS IN FISHERIES', 'BSF', 'BS in Fisheries', 'Bachelor of Science in Fisheries and Aquatic Sciences'],
            'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY' => ['BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY', 'BS IN INFORMATION TECHNOLOGY', 'BSIT', 'BACHELOR OF SCIENCE IN INFORMATIO TECHNOLOGY'],
            'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT' => ['BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT', 'BS IN HOSPITALITY MANAGEMENT', 'BSHM', 'BS HOSPITALITY MANAGEMENT', 'Bachelor Of Science In Hospitality Management'],
            'BACHELOR OF SECONDARY EDUCATION' => ['BACHELOR OF SECONDARY EDUCATION', 'BACHELOR OF SCIENCE IN SECONDARY EDUCATION', 'Bachelor of Secondary Education major in English', 'Bachelor of Secondary Education major in Science', 'Bachelor of Secondary Education major in Mathematics', 'BACHELOR OF SECONDARY EDUCATION MAJOR IN ENGLISH', 'BACHELOR OF SECONDARY EDUCATION MAJOR IN SCIENCE', 'BACHELOR OF SECONDARY EDUCATION MAJOR IN MATHEMATICS', 'BSEd Major in English', 'BSEd Major in Science', 'BSEd Major in Matehematics', 'BSED-SCI', 'BSed-ENG', 'BSed-MATH', 'BSed-SCI', 'BSE-ENG', 'Bachelor of Secondary Education - English', 'Bachelor of Secondary Education - Math', 'Bachelor of Secondary Education - Major in English', 'Bachelor of Secondary Education - Major in General Science', 'Bachelor of Secondary Education - Major in Mathematics', 'Bachelor of Secondary Education - Major in Science'],
            'BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM' => ['BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM', 'BACHELOR OF SCIENCE IN ACCOUNITNG INFORMATION SYSTEM', 'BS IN ACCOUNTING INFORMATION SYSTEM', 'BS in Accounting Information System', 'BSAIS', 'Bachelor Of Science In Accounting Information System', 'Bachelor of Science in Accounting Information System', 'BS in Accounting Information'],
            'BACHELOR OF ELEMENTARY EDUCATION' => ['BACHELOR OF ELEMENTARY EDUCATION', 'BACHELOR IN ELEMENTARY EDUCATION', 'BEED', 'Bachelor Of Elementary Education', 'BACHELOR OF SCIENCE IN ELEMENTARY EDUCATION', 'BACHELOR OF SCIENCE IN ELEMENTARY EDUCATION', 'BACHELOR OF ELEMENTAY EDUCATION'],
        ];

        return $courseAliases[$selectedCourse] ?? [$selectedCourse];
    }

    private function distinctValuesForType(string $filterType, string $column): \Illuminate\Support\Collection
    {
        $grantType = $filterType === 'old-academic' ? 'academic' : 'non_academic';

        $values = OldGrantRecord::query()
            ->where('scholarship_type', $grantType)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);

        if ($column === 'year') {
            return $values
                ->map(fn ($value) => $this->normalizeYearLevelValue($value))
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                ->unique(fn ($value) => strtolower(trim((string) $value)))
                ->values();
        }

        return $values
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->values();
    }

    private function normalizeYearLevelValue(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', strtolower($raw));
        $normalized = preg_replace('/\s*year\s*$/', '', $normalized);

        if (preg_match('/^(\d+)$/', $normalized, $matches)) {
            return $this->ordinalSuffix((int) $matches[1]);
        }

        if (preg_match('/^(\d+)\s*(st|nd|rd|th)$/', $normalized, $matches)) {
            return $this->ordinalSuffix((int) $matches[1]);
        }

        return $normalized;
    }

    private function ordinalSuffix(int $number): string
    {
        $suffix = match ($number % 100) {
            11, 12, 13 => 'th',
            default => match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            },
        };

        return $number . $suffix;
    }

    private function yearLevelQueryValues(string $yearLevel): array
    {
        $normalized = strtolower(trim($yearLevel));
        if ($normalized === '') {
            return [];
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/\s*year\s*$/', '', $normalized);

        $values = [$normalized];

        if (preg_match('/^(\d+)\s*(st|nd|rd|th)?$/', $normalized, $matches)) {
            $values[] = (string) $matches[1];
            $values[] = strtoupper($normalized);
        }

        return array_values(array_unique(array_filter($values, fn ($value) => $value !== '')));
    }

    private function filteredOldRecords(Request $request, string $grantType)
    {
        $search = trim((string) $request->query('search', ''));
        $scholarshipName = trim((string) $request->query('scholarship_name', ''));
        $yearLevel = trim((string) $request->query('year_level', ''));
        $course = trim((string) $request->query('course', ''));
        $firstLastNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "LOWER(COALESCE(first_name, '') || ' ' || COALESCE(last_name, ''))"
            : 'LOWER(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")))';
        $lastFirstNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "LOWER(COALESCE(last_name, '') || ' ' || COALESCE(first_name, ''))"
            : 'LOWER(CONCAT(COALESCE(last_name, ""), " ", COALESCE(first_name, "")))';
        $amountOrderExpression = DB::connection()->getDriverName() === 'sqlite'
            ? 'CASE WHEN amount IS NULL OR TRIM(amount) = "" THEN 1 ELSE 0 END ASC'
            : 'CASE WHEN amount IS NULL OR TRIM(amount) = "" OR (amount NOT REGEXP "^[0-9]+(\\\\.[0-9]+)?$" AND UPPER(TRIM(amount)) <> "FREE TUITION") THEN 1 ELSE 0 END ASC';

        $query = OldGrantRecord::where('scholarship_type', $grantType)
            ->when($search !== '', function ($query) use ($search, $firstLastNameExpression, $lastFirstNameExpression) {
                $searchTerm = strtolower($search);
                $query->where(function ($query) use ($searchTerm, $firstLastNameExpression, $lastFirstNameExpression) {
                    $query->whereRaw('LOWER(COALESCE(first_name, "")) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(COALESCE(last_name, "")) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw($firstLastNameExpression . ' LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw($lastFirstNameExpression . ' LIKE ?', ['%' . $searchTerm . '%']);
                });
            })
            ->when($scholarshipName !== '', function ($query) use ($scholarshipName) {
                $query->where('scholarship_name', $scholarshipName);
            })
            ->when($yearLevel !== '', function ($query) use ($yearLevel) {
                $yearLevelValues = $this->yearLevelQueryValues($yearLevel);
                $query->where(function ($innerQuery) use ($yearLevelValues) {
                    foreach ($yearLevelValues as $value) {
                        $innerQuery->orWhereRaw('LOWER(TRIM(year)) = ?', [$value]);
                    }
                });
            })
            ->when($course !== '', function ($query) use ($course) {
                $matchValues = $this->courseMatchValues($course);
                $query->where(function ($innerQuery) use ($matchValues) {
                    foreach ($matchValues as $matchValue) {
                        $innerQuery->orWhereRaw('LOWER(TRIM(course)) = ?', [strtolower(trim((string) $matchValue))]);
                    }
                });
            })
            ->orderByRaw($grantType === 'academic'
                ? 'CASE WHEN year IS NULL OR TRIM(year) = "" THEN 1 ELSE 0 END ASC'
                : $amountOrderExpression)
            ->orderByRaw('CASE WHEN sequence_no IS NULL OR TRIM(sequence_no) = "" THEN 2147483647 ELSE CAST(sequence_no AS UNSIGNED) END ASC');

        return $query->paginate(10)->withQueryString();
    }

    /**
     * Show upload form (modal)
     */
    public function showUploadForm()
    {
        return view('admin.partials.old-grant-upload-modal');
    }

    /**
     * Handle Excel file upload
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('excel_file');

            // Import the Excel file
            $import = new OldGrantImport();
            $import->import($file->getRealPath());

            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();

            if ($successCount === 0) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'No records were imported. Please check your Excel file format.',
                    'academic_records' => $import->getAcademicCount(),
                    'non_academic_records' => $import->getNonAcademicCount(),
                    'details' => $import->getErrors(),
                ], 400);
            }

            $message = "$successCount record(s) imported successfully.";
            if ($errorCount > 0) {
                $message .= " ($errorCount row(s) skipped due to errors)";
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'count' => $successCount,
                'academic_records' => $import->getAcademicCount(),
                'non_academic_records' => $import->getNonAcademicCount(),
                'duplicate_rows' => $import->getDuplicateCount(),
                'errors' => $errorCount > 0 ? $import->getErrors() : [],
            ]);
        } catch (\Exception $e) {
            Log::error('Old grant import failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed.',
            ], 500);
        }
    }
}
