<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use App\Mail\ApplicationReceivedMail;
use App\Support\CourseCollegeDepartment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

class StudentProfileController extends Controller
{
    /**
     * Define file upload rules based on file type
     * Returns an array with allowed extensions and max size in KB
     */
    private function getFileValidationRules()
    {
        return [
            'document' => [
                'extensions' => ['pdf', 'docx', 'doc'],
                'mimes' => ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'],
                'maxSize' => 2048, // 2MB in KB
                'displaySize' => '2MB'
            ],
            'image' => [
                'extensions' => ['jpg', 'jpeg', 'png'],
                'mimes' => ['image/jpeg', 'image/png'],
                'maxSize' => 1024, // 1MB in KB
                'displaySize' => '1MB'
            ]
        ];
    }

    /**
     * Determine file type category
     */
    private function getFileTypeCategory($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $rules = $this->getFileValidationRules();

        if (in_array($extension, $rules['document']['extensions'], true) &&
            in_array($mimeType, $rules['document']['mimes'], true)) {
            return 'document';
        }

        if (in_array($extension, $rules['image']['extensions'], true) &&
            in_array($mimeType, $rules['image']['mimes'], true)) {
            return 'image';
        }

        return null;
    }

    /**
     * Validate individual file based on type
     */
    private function validateFile($file)
    {
        $category = $this->getFileTypeCategory($file);
        $rules = $this->getFileValidationRules();

        if (!$category) {
            return [
                'valid' => false,
                'error' => "File '{$file->getClientOriginalName()}' has an unsupported format. Allowed formats: PDF, DOCX, JPG, PNG"
            ];
        }

        // Check file size (filezise returns bytes)
        $fileSizeKB = $file->getSize() / 1024;
        $maxSizeKB = $rules[$category]['maxSize'];

        if ($fileSizeKB > $maxSizeKB) {
            return [
                'valid' => false,
                'error' => "File '{$file->getClientOriginalName()}' exceeds the maximum size limit of {$rules[$category]['displaySize']} for {$category} files. Current size: " . round($fileSizeKB, 2) . "KB"
            ];
        }

        return [
            'valid' => true,
            'category' => $category
        ];
    }

    /**
     * Collect uploaded files from a request field.
     */
    private function collectFilesFromRequest(Request $request, string $fieldName)
    {
        if (!$request->hasFile($fieldName)) {
            return [];
        }

        $files = $request->file($fieldName);

        if ($files instanceof \Illuminate\Http\UploadedFile) {
            return $files->isValid() ? [$files] : [];
        }

        if (!is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, function ($file) {
            return $file instanceof \Illuminate\Http\UploadedFile && $file->isValid();
        }));
    }

    /**
     * Delete old files from storage
     */
    private function deleteOldFiles($uploads)
    {
        if (!$uploads || !is_array($uploads)) {
            return;
        }

        $flattenedFiles = [];

        foreach ($uploads as $key => $value) {
            if (is_string($value)) {
                $flattenedFiles[] = $value;
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $nestedValue) {
                    if (is_string($nestedValue)) {
                        $flattenedFiles[] = $nestedValue;
                    }
                }
            }
        }

        foreach ($flattenedFiles as $filePath) {
            try {
                if (Storage::disk('local')->exists($filePath)) {
                    Storage::disk('local')->delete($filePath);
                } elseif (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("Deleted old file: {$filePath}");
                }
            } catch (\Exception $e) {
                Log::warning("Failed to delete file {$filePath}: " . $e->getMessage());
            }
        }
    }

    private function resolveScholarshipMetadata(Scholarship $scholarship): array
    {
        $name = trim((string) $scholarship->name);

        if (trim((string) $scholarship->type_label) !== '') {
            return [$name, trim((string) $scholarship->type_label)];
        }

        $academicMap = [
            'Cagayan Valley Assoc. of Hawaii' => 'International Educational Grant',
            'University Based Scholarship' => 'Academic Excellence – In-House',
            'ACEF – GIAHEP' => 'Agricultural Credit & Education Fund',
            'DOST SEI Project Strand' => 'Graduate School Scholarship',
            'Tulong Dunong Program' => 'Kuya Win Scholarship Program',
            'LGU Aparri' => 'Local Government Unit Scholarship',
            'Natalged A Lasam' => 'Educational Assistance Grant',
            'Andres Tamayo Scholarship' => 'Private Foundation Grant',
            'Gokongwei Brothers Foundation' => 'Foundation Scholarship Program',
        ];

        $nonAcademicMap = [
            'Athletic Scholarship' => 'Sports Excellence Program In-House',
            "Employees' Grant" => "Employees' Grant In-House",
            'Socio-Cultural Scholarship' => 'Arts & Culture Program In-House',
            'Campus Student Publication' => 'University Based Scholarship In-House',
            'Differently Abled Persons' => 'University Based Scholarship In-House',
            'Student Council Federation Grant' => 'University Based Scholarship In-House',
            'CSU Aparri Alumni Association' => 'Alumni Grant Program In-House',
            'Tertiary Education Subsidy' => 'UniFAST – TES Program Government Based',
            'Agkaykaysa' => 'Regional Solidarity Grant Government Based',
            'Open Heart Scholarship' => 'Private Foundation Grant Private Based',
            'Talamayan Scholarship' => 'Community Support Grant Private Based',
        ];

        if ($scholarship->type === 'academic') {
            return [
                $name,
                $academicMap[$name] ?? 'Academic Excellence – In-House',
            ];
        }

        return [
            $name,
            $nonAcademicMap[$name] ?? 'University Based Scholarship In-House',
        ];
    }

    private function resolveLegacyScholarshipType(string $name): ?string
    {
        return [
            'Athletic Scholarship' => 'Sports Excellence Program In-House',
            'Tulong Dunong Program' => 'Kuya Win Scholarship Program',
            "Employees' Grant" => "Employees' Grant In-House",
            'Socio-Cultural Scholarship' => 'Arts & Culture Program In-House',
            'Campus Student Publication' => 'University Based Scholarship In-House',
            'Differently Abled Persons' => 'University Based Scholarship In-House',
            'Student Council Federation Grant' => 'University Based Scholarship In-House',
            'CSU Aparri Alumni Association' => 'Alumni Grant Program In-House',
            'Tertiary Education Subsidy' => 'UniFAST – TES Program Government Based',
            'Agkaykaysa' => 'Regional Solidarity Grant Government Based',
            'Open Heart Scholarship' => 'Private Foundation Grant Private Based',
            'Talamayan Scholarship' => 'Community Support Grant Private Based',
        ][$name] ?? null;
    }

    private function resolveCollegeDepartment(?string $course): ?string
    {
        return CourseCollegeDepartment::forCourse($course);
    }

    private function findSelectedScholarship(?string $scholarshipId, ?string $scholarshipName): ?Scholarship
    {
        if (!empty($scholarshipId)) {
            return Scholarship::find($scholarshipId);
        }

        $name = trim((string) ($scholarshipName ?? ''));
        if ($name === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', $name);
        $candidates = [$normalized];

        if (stripos($normalized, 'Tulong Dunong') !== false) {
            $candidates[] = 'Tulong Dunong Program';
        }

        foreach ($candidates as $candidate) {
            $scholarship = Scholarship::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $candidate))])->first();
            if ($scholarship) {
                return $scholarship;
            }
        }

        return Scholarship::where(function ($query) use ($normalized) {
            $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($normalized)])
                ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', [mb_strtolower($normalized) . '%'])
                ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%' . mb_strtolower($normalized)]);
        })->first();
    }

    public function applicationPrefill(Request $request)
    {
        $user = Auth::user();
        $grantType = $request->query('grant_type', 'academic');

        if (!$user || !in_array($grantType, ['academic', 'non-academic'], true)) {
            return response()->json(['data' => null]);
        }

        $requestedScholarshipId = trim((string) $request->query('scholarship_id', ''));
        $requestedScholarshipName = trim((string) $request->query('scholarship_name', ''));

        $query = StudentProfile::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->with('application:id,scholarship_id');

        $profile = $query->first();

        if (!$profile) {
            return response()->json(['data' => null]);
        }

        if ($requestedScholarshipId !== '' || $requestedScholarshipName !== '') {
            $previousScholarshipId = (string) ($profile->application?->scholarship_id ?? '');
            $sameScholarship = $requestedScholarshipId !== '' && $previousScholarshipId !== ''
                ? $requestedScholarshipId === $previousScholarshipId
                : ($requestedScholarshipName !== ''
                    && mb_strtolower($requestedScholarshipName) === mb_strtolower(trim((string) $profile->scholarship_name)));

            if ($sameScholarship) {
                return response()->json(['data' => null]);
            }
        }

        $academicFields = [
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'student_id_number',
            'course',
            'college_department',
            'year_level',
            'program',
            'birthdate',
            'birthplace',
            'sex',
            'status',
            'religion',
            'contact_number',
            'home_address',
        ];

        $nonAcademicFields = [
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'student_id_number',
            'course',
            'college_department',
            'year_level',
            'birthdate',
            'birthplace',
            'sex',
            'status',
            'contact_number',
            'home_address',
        ];

        $allowedFields = $grantType === 'academic' ? $academicFields : $nonAcademicFields;
        $data = [];

        foreach ($allowedFields as $field) {
            if ($profile->getAttribute($field) === null || $profile->getAttribute($field) === '') {
                continue;
            }

            $value = $profile->getAttribute($field);
            if ($field === 'birthdate' && $value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            $data[$field] = $value;
        }

        return response()->json([
            'data' => $data,
            'grant_type' => $grantType,
        ]);
    }

    private function countUsableDocuments($uploads, array $reviews = []): int
    {
        $count = 0;

        foreach ((array) $uploads as $group => $files) {
            $legacyAliases = [$group];
            if (in_array($group, ['uploads', 'grades'], true)) {
                $legacyAliases[] = 'certificate_of_grades';
            }
            if ($group === 'certificate_of_grades') {
                $legacyAliases[] = 'uploads';
                $legacyAliases[] = 'grades';
            }
            $legacyAliases = array_values(array_unique($legacyAliases));

            foreach ((array) $files as $index => $file) {
                $reviewStatus = null;
                foreach ($legacyAliases as $alias) {
                    if (isset($reviews[$alias . '.' . $index]['status'])) {
                        $reviewStatus = $reviews[$alias . '.' . $index]['status'];
                        break;
                    }
                }

                if (is_string($file) && $reviewStatus !== 'rejected') {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function requiredDocumentRules(?Scholarship $scholarship, bool $isAcademic, string $scholarshipName): array
    {
        if ($isAcademic && $scholarshipName !== 'University Based Scholarship') {
            return [
                'groups' => ['certificate_of_grades', 'certificate_of_enrollment'],
                'count' => 0,
                'labels' => [],
            ];
        }

        $configuredDocuments = $scholarship?->required_documents ?? [];
        if (is_array($configuredDocuments) && count(array_filter($configuredDocuments)) > 0) {
            return ['groups' => [], 'count' => count(array_filter($configuredDocuments)), 'labels' => array_values(array_filter($configuredDocuments))];
        }

        if ($isAcademic) {
            return [
                'groups' => $scholarshipName === 'University Based Scholarship'
                    ? ['certificate_of_grades', 'certificate_of_enrollment']
                    : ['certificate_of_grades'],
                'count' => 0,
                'labels' => [],
            ];
        }

        $groups = match ($scholarshipName) {
            'Athletic Scholarship' => ['certificate_of_sports_coordinator', 'certificate_of_adviser'],
            'Socio-Cultural Scholarship' => ['uploads'],
            'Differently Abled Persons', 'Open Heart Scholarship', 'Talamayan Scholarship' => ['uploads'],
            'Campus Student Publication' => ['uploads'],
            'CSU Aparri Alumni Association' => ['uploads'],
            default => [],
        };

        return ['groups' => $groups, 'count' => 0, 'labels' => []];
    }

    public function store(Request $request)
    {
        try {
            // Detect if this is an academic or non-academic scholarship form
            $scholarshipIdInput = $request->input('scholarship_id');
            $scholarshipNameInput = $request->input('scholarship_name');
            $selectedScholarship = $this->findSelectedScholarship(
                is_scalar($scholarshipIdInput) ? (string) $scholarshipIdInput : null,
                is_scalar($scholarshipNameInput) ? (string) $scholarshipNameInput : null
            );
            $isAcademic = $selectedScholarship
                ? $selectedScholarship->type === 'academic'
                : $request->has('program');
            $request->merge([
                'student_id_number' => $request->input('student_id_number') ?? $request->input('id_number') ?? Auth::user()?->student_id,
            ]);

            // Build validation rules dynamically based on form type
            $validationRules = [
                'first_name' => 'required_without:last_name|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required_without:first_name|string|max:255',
                'suffix' => 'nullable|string|max:255',
                'sex' => 'required|in:Male,Female',
                'status' => 'required|in:Single,Married,In a Relationship,Divorced',
                'email' => $isAcademic ? 'nullable|email|max:255' : 'required|email|max:255',
                'student_id_number' => ['required', 'string', 'regex:/^\d{2}-\d{5}$/'],
                'birthdate' => ['required', 'date', 'before_or_equal:2010-12-31'],
                'birthplace' => 'required|string|max:255',
                'home_address' => $isAcademic ? 'nullable|string' : 'required|string',
                'contact_number' => $isAcademic
                    ? ['nullable', 'string', 'regex:/^\d{9}$/']
                    : ['required', 'string', 'regex:/^\d{9}$/'],
                'course' => 'required|string',
                'college_department' => 'nullable|string|max:255',
                'year_level' => 'required|in:1st Year,2nd Year,3rd Year,4th Year',
                'scholarship_id' => 'nullable|integer|min:1',
                'scholarship_name' => 'required|string',
                'scholarship_type' => 'required|string',
                'family_background' => 'nullable|array',
                'family_background.father_status' => 'nullable|in:Living,Deceased',
                'family_background.father_name' => 'nullable|string|max:255',
                'family_background.father_age' => 'nullable|integer|min:0|max:150',
                'family_background.father_birthdate' => 'nullable|date',
                'family_background.father_address' => 'nullable|string|max:2000',
                'family_background.father_occupation' => 'nullable|string|max:255',
                'family_background.father_office_address' => 'nullable|string|max:2000',
                'family_background.father_education' => 'nullable|string|max:255',
                'family_background.mother_status' => 'nullable|in:Living,Deceased',
                'family_background.mother_name' => 'nullable|string|max:255',
                'family_background.mother_age' => 'nullable|integer|min:0|max:150',
                'family_background.mother_birthdate' => 'nullable|date',
                'family_background.mother_address' => 'nullable|string|max:2000',
                'family_background.mother_occupation' => 'nullable|string|max:255',
                'family_background.mother_office_address' => 'nullable|string|max:2000',
                'family_background.mother_education' => 'nullable|string|max:255',
                'family_background.parent_contact_number' => 'nullable|string|max:50',
                'family_background.parent_email' => 'nullable|email|max:255',
                'family_background.monthly_income' => 'nullable|numeric|min:0',
                'family_background.has_differently_abled_member' => 'nullable|in:yes,no',
                'family_background.disability_details' => 'nullable|string|max:2000',
                'family_background.siblings' => 'nullable|array|max:10',
                'family_background.siblings.*' => 'array',
                'family_background.siblings.*.name' => 'nullable|string|max:255',
                'family_background.siblings.*.age' => 'nullable|integer|min:0|max:150',
                'family_background.siblings.*.marital_status' => 'nullable|in:Single,Married,Widowed,Divorced',
                'family_background.siblings.*.occupation' => 'nullable|string|max:255',
            ];

            // Add academic-specific fields if this is an academic form
            if ($isAcademic) {
                $validationRules['program'] = 'required|string|max:255';
                $validationRules['religion'] = 'required|string|max:255';
            }

            // Basic validation for non-file fields
            $data = $request->validate($validationRules);
            $familyBackgroundInput = $data['family_background'] ?? [];
            unset($data['family_background']);
            
            $user = Auth::user();
            $requestProfileId = $request->input('student_profile_id') ?: $request->input('profile_id');
            $requestApplicationId = $request->input('application_id');

            $data['user_id'] = $user->id;
            $data['email'] = $user->email;
            $data['suffix'] = trim((string) ($request->input('suffix') ?? '')) ?: null;
            $data['college_department'] = $this->resolveCollegeDepartment($data['course'] ?? null) ?? trim((string) ($request->input('college_department') ?? '')) ?: null;
            $data['campus'] = 'Aparri';
            $data['student_id_number'] = $request->input('student_id_number') ?? $request->input('id_number') ?? $user->student_id ?? null;
            if (isset($data['contact_number']) && $data['contact_number'] !== '') {
                $data['contact_number'] = '+63 9' . $data['contact_number'];
            }

            $selectedScholarshipId = $request->input('scholarship_id');

            if ($selectedScholarship) {
                [$resolvedName, $resolvedType] = $this->resolveScholarshipMetadata($selectedScholarship);
                $data['scholarship_name'] = $resolvedName;
                $data['scholarship_type'] = $resolvedType;
            } elseif (!$isAcademic) {
                $legacyType = $this->resolveLegacyScholarshipType(trim((string) $data['scholarship_name']));
                if ($legacyType === null) {
                    return redirect()->back()->with('error', 'The selected scholarship could not be found. Please choose a valid scholarship.')->withInput();
                }
                $data['scholarship_name'] = trim((string) $data['scholarship_name']);
                $data['scholarship_type'] = $legacyType;
            }

            $grantType = $isAcademic ? 'academic' : 'non-academic';
            $scholarshipName = mb_strtolower(trim((string) $data['scholarship_name']));
            $previousProfileQuery = StudentProfile::where('user_id', $user->id)
                ->where('grant_type', $grantType)
                ->where(function ($query) use ($selectedScholarship, $scholarshipName, $user) {
                    if ($selectedScholarship) {
                        $query->whereHas('application', fn ($applicationQuery) => $applicationQuery
                            ->where('user_id', $user->id)
                            ->where('scholarship_id', $selectedScholarship->id))
                            ->orWhere(function ($legacyQuery) use ($scholarshipName, $user) {
                                $legacyQuery->whereRaw('LOWER(TRIM(scholarship_name)) = ?', [$scholarshipName])
                                    ->where(function ($applicationQuery) use ($user) {
                                        $applicationQuery->whereNull('application_id')
                                            ->orWhereHas('application', fn ($query) => $query
                                                ->where('user_id', $user->id)
                                                ->whereNull('scholarship_id'));
                                    });
                            });
                    } else {
                        $query->whereRaw('LOWER(TRIM(scholarship_name)) = ?', [$scholarshipName])
                            ->where(function ($applicationQuery) use ($user) {
                                $applicationQuery->whereNull('application_id')
                                    ->orWhereHas('application', fn ($query) => $query
                                        ->where('user_id', $user->id)
                                        ->whereNull('scholarship_id'));
                            });
                    }
                })
                ->with('application')
                ->orderByDesc('id')
                ->get();

            $previousProfile = null;
            if (is_scalar($requestProfileId)) {
                $previousProfile = $previousProfileQuery->firstWhere('id', (int) $requestProfileId);
            }
            if (!$previousProfile && is_scalar($requestApplicationId)) {
                $previousProfile = $previousProfileQuery->firstWhere('application_id', (int) $requestApplicationId);
            }
            $previousProfile ??= $previousProfileQuery->first();
            $applicationWasRejected = ($previousProfile?->application_status ?? null) === 'rejected';

            $lockedProfileQuery = StudentProfile::where('user_id', $user->id)
                ->where('grant_type', $isAcademic ? 'academic' : 'non-academic')
                ->whereNotNull('locked_at');
            if ($selectedScholarship) {
                $lockedProfileQuery->whereHas('application', fn ($query) => $query->where('scholarship_id', $selectedScholarship->id));
            } elseif ($previousProfile) {
                $lockedProfileQuery->whereKey($previousProfile->id);
            } else {
                $lockedProfileQuery->whereRaw('1 = 0');
            }
            if ($lockedProfileQuery->exists()) {
                return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                    ->with('error', "You've reached the maximum number of applications for this scholarship.");
            }

            // Fill missing name parts from authenticated user name when available
            if (!empty($user->name)) {
                $nameParts = preg_split('/\s+/', trim($user->name));

                if (empty($data['first_name'])) {
                    $data['first_name'] = $nameParts[0] ?? null;
                }

                if (empty($data['last_name'])) {
                    $data['last_name'] = $nameParts[count($nameParts) - 1] ?? null;
                }

                if (empty($data['middle_name']) && count($nameParts) > 2) {
                    $data['middle_name'] = $nameParts[1];
                }
            }

            // Assign grant type
            $data['grant_type'] = $isAcademic ? 'academic' : 'non-academic';

            // Handle file uploads with custom validation
            $uploaded = [];
            $uploadErrors = [];

            $uploadGroups = [
                'certificate_of_grades' => $this->collectFilesFromRequest($request, 'uploads'),
                'certificate_of_enrollment' => $this->collectFilesFromRequest($request, 'certificate_of_enrollment'),
            ];

            if ($request->input('scholarship_name') === 'Athletic Scholarship' || $selectedScholarship?->name === 'Athletic Scholarship') {
                $allFiles = $request->allFiles();
                $uploadGroups = [
                    'certificate_of_sports_coordinator' => $this->collectFilesFromRequest($request, 'uploads') ?: ($allFiles['uploads'] ?? []),
                    'certificate_of_adviser' => $this->collectFilesFromRequest($request, 'certificate_of_adviser') ?: ($allFiles['certificate_of_adviser'] ?? []),
                ];
            }

            if ($previousProfile && !$applicationWasRejected) {
                $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
                $previousReviews = is_array($previousProfile->{$reviewColumn}) ? $previousProfile->{$reviewColumn} : [];
                $rejectedGroups = collect($previousReviews)
                    ->filter(fn ($review) => ($review['status'] ?? null) === 'rejected')
                    ->keys()
                    ->map(fn ($key) => explode('.', $key)[0])
                    ->unique();

                foreach ($rejectedGroups as $group) {
                    $candidateGroups = in_array($group, ['uploads', 'grades'], true)
                        ? ['certificate_of_grades', 'uploads', 'grades']
                        : [$group];

                    $hasReplacement = collect($candidateGroups)->contains(fn ($candidateGroup) => !empty($uploadGroups[$candidateGroup]));

                    if (!$hasReplacement) {
                        return redirect()->back()
                            ->with('error', 'Please upload the corrected rejected document before submitting.')
                            ->withInput();
                    }
                }
            }

            foreach ($uploadGroups as $groupKey => $files) {
                if (empty($files)) {
                    continue;
                }

                foreach ($files as $file) {
                    if (!$file || !$file->isValid()) {
                        $uploadErrors[] = "File upload failed. Please try again.";
                        continue;
                    }

                    // Validate file
                    $validation = $this->validateFile($file);

                    if (!$validation['valid']) {
                        $uploadErrors[] = $validation['error'];
                        continue;
                    }

                    // Store the file
                    try {
                        $filePath = $file->store('student_profiles', 'local');
                        $uploaded[$groupKey][] = $filePath;
                        Log::info("File uploaded successfully: {$filePath}");
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to store file '{$file->getClientOriginalName()}'.";
                        Log::error("File storage error: " . $e->getMessage());
                    }
                }
            }

            // If there were validation errors, return with messages
            if (!empty($uploadErrors)) {
                $errorMessage = "Some files could not be processed:\n\n" . implode("\n\n", $uploadErrors);
                
                if (empty($uploaded)) {
                    // No files were successfully uploaded
                    return redirect()->back()
                        ->with('error', $errorMessage)
                        ->withInput();
                }
                // Some files succeeded, continue with partial upload
            }

            $data['uploads'] = !empty($uploaded) ? $uploaded : null;
            $oldRejectedFiles = [];
            $familyBackgroundRejected = false;

            if ($previousProfile) {
                $studentStatus = $previousProfile->student_information_status ?? 'pending';
                $personalStatus = $previousProfile->personal_information_status ?? 'pending';
                $requirementsStatus = $previousProfile->scholarship_requirements_status ?? 'pending';
                $familyBackgroundStatus = $previousProfile->family_background_status ?? 'pending';
                $familyBackgroundRejected = !$isAcademic
                    && $previousProfile->grant_type === 'non-academic'
                    && ($applicationWasRejected || $familyBackgroundStatus === 'rejected');
                $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
                $previousReviews = is_array($previousProfile->{$reviewColumn}) ? $previousProfile->{$reviewColumn} : [];
                $hasRejectedDocument = collect($previousReviews)
                    ->contains(fn ($review) => ($review['status'] ?? null) === 'rejected');
                $requirementsRejected = $requirementsStatus === 'rejected' || $hasRejectedDocument;

                if ($applicationWasRejected) {
                    $data['student_information_status'] = 'pending';
                    $data['student_information_reason'] = null;
                    $data['personal_information_status'] = 'pending';
                    $data['personal_information_reason'] = null;
                    $data['scholarship_requirements_status'] = 'pending';
                    $data['scholarship_requirements_reason'] = null;
                    if (!$isAcademic && $previousProfile->grant_type === 'non-academic') {
                        $data['family_background_status'] = 'pending';
                        $data['family_background_reason'] = null;
                    }
                } else {
                    $data['student_information_status'] = $studentStatus === 'approved' ? 'approved' : 'pending';
                    $data['personal_information_status'] = $personalStatus === 'approved' ? 'approved' : 'pending';
                    $data['scholarship_requirements_status'] = $requirementsStatus === 'approved' ? 'approved' : 'pending';
                    if (!$isAcademic && $previousProfile->grant_type === 'non-academic') {
                        $data['family_background_status'] = $familyBackgroundRejected ? 'pending' : ($familyBackgroundStatus === 'approved' ? 'approved' : 'pending');
                        $data['family_background_reason'] = $familyBackgroundRejected ? null : $previousProfile->family_background_reason;
                    }
                }

                if ($studentStatus !== 'rejected') {
                    foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'student_id_number', 'course', 'college_department', 'year_level', 'program'] as $field) {
                        $data[$field] = $previousProfile->{$field};
                    }
                }

                if ($personalStatus !== 'rejected') {
                    foreach (['birthdate', 'birthplace', 'sex', 'status', 'religion', 'contact_number', 'home_address'] as $field) {
                        $data[$field] = $previousProfile->{$field};
                    }
                }

                if ($requirementsStatus !== 'rejected') {
                    $data['scholarship_name'] = $previousProfile->scholarship_name;
                    $data['scholarship_type'] = $previousProfile->scholarship_type;
                }

                $previousUploads = is_array($previousProfile->uploads) ? $previousProfile->uploads : [];
                if (array_is_list($previousUploads)) {
                    $previousUploads = ['uploads' => $previousUploads];
                }
                $newUploads = $requirementsRejected && is_array($data['uploads'])
                    ? $data['uploads']
                    : [];
                $mergedUploads = [];
                $mergedReviews = [];

                if ($applicationWasRejected) {
                    $oldRejectedFiles = collect($previousUploads)->flatten()->filter(fn ($path) => is_string($path))->unique()->values()->all();
                    $data[$reviewColumn] = null;
                } else {
                    foreach ($previousUploads as $group => $files) {
                        if (!is_array($files)) {
                            continue;
                        }

                        $requestGroup = in_array($group, ['uploads', 'grades'], true)
                            ? 'certificate_of_grades'
                            : $group;
                        $replacementFiles = $newUploads[$requestGroup] ?? $newUploads[$group] ?? [];
                        $replacementIndex = 0;

                        foreach ($files as $index => $path) {
                            $review = $previousReviews[$group . '.' . $index] ?? [];
                            $reviewStatus = $review['status'] ?? 'pending';

                            if ($reviewStatus === 'rejected') {
                                if (isset($replacementFiles[$replacementIndex])) {
                                    $mergedUploads[$group][] = $replacementFiles[$replacementIndex];
                                    $mergedReviews[$group . '.' . (count($mergedUploads[$group]) - 1)] = [
                                        'status' => 'pending',
                                        'reason' => null,
                                    ];
                                    if (is_string($path)) {
                                        $oldRejectedFiles[] = $path;
                                    }
                                    $replacementIndex++;
                                }
                                continue;
                            }

                            if (!is_string($path)) {
                                continue;
                            }

                            $mergedUploads[$group][] = $path;
                            $mergedReviews[$group . '.' . (count($mergedUploads[$group]) - 1)] = [
                                'status' => $reviewStatus,
                                'reason' => $reviewStatus === 'rejected' ? null : ($review['reason'] ?? null),
                            ];
                        }

                        while (isset($replacementFiles[$replacementIndex])) {
                            $mergedUploads[$group][] = $replacementFiles[$replacementIndex];
                            $mergedReviews[$group . '.' . (count($mergedUploads[$group]) - 1)] = [
                                'status' => 'pending',
                                'reason' => null,
                            ];
                            $replacementIndex++;
                        }
                    }

                    foreach ($newUploads as $group => $files) {
                        if (
                            isset($previousUploads[$group])
                            || ($group === 'certificate_of_grades' && (isset($previousUploads['grades']) || isset($previousUploads['uploads'])))
                        ) {
                            continue;
                        }
                        foreach ((array) $files as $path) {
                            if (!is_string($path)) {
                                continue;
                            }
                            $mergedUploads[$group][] = $path;
                            $mergedReviews[$group . '.' . (count($mergedUploads[$group]) - 1)] = [
                                'status' => 'pending',
                                'reason' => null,
                            ];
                        }
                    }

                    $data['uploads'] = !empty($mergedUploads) ? $mergedUploads : null;
                    $data[$reviewColumn] = !empty($mergedReviews) ? $mergedReviews : null;
                }
            }

            $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
            $requiredDocuments = $this->requiredDocumentRules($selectedScholarship, $isAcademic, $data['scholarship_name']);
            $documentReviews = is_array($data[$reviewColumn] ?? null) ? $data[$reviewColumn] : [];
            $missingGroups = collect($requiredDocuments['groups'])
                ->filter(function ($group) use ($data, $documentReviews) {
                    $storageGroups = match ($group) {
                        'uploads' => ['uploads', 'certificate_of_grades'],
                        'certificate_of_grades' => ['certificate_of_grades', 'uploads', 'grades'],
                        default => [$group],
                    };

                    $groupFiles = [];
                    foreach ($storageGroups as $storageGroup) {
                        if (!empty($data['uploads'][$storageGroup])) {
                            $groupFiles = array_merge($groupFiles, (array) $data['uploads'][$storageGroup]);
                        }
                    }

                    return $this->countUsableDocuments([
                        $group => $groupFiles,
                    ], $documentReviews) === 0;
                })
                ->values()
                ->all();
            $usableDocumentCount = $this->countUsableDocuments($data['uploads'], $documentReviews);
            $missingConfiguredDocuments = $requiredDocuments['count'] > $usableDocumentCount;
            if (!empty($missingGroups) || $missingConfiguredDocuments) {
                foreach ($uploaded as $files) {
                    foreach ((array) $files as $path) {
                        if (is_string($path) && Storage::disk('local')->exists($path)) {
                            Storage::disk('local')->delete($path);
                        }
                    }
                }

                $missingLabels = array_map(fn ($group) => match ($group) {
                    'certificate_of_grades' => 'Certificate of Grades',
                    'certificate_of_enrollment' => 'Certificate of Enrollment',
                    'certificate_of_sports_coordinator' => 'Certificate from Sports Coordinator',
                    'certificate_of_adviser' => 'Certificate of Adviser',
                    'uploads' => 'Required scholarship upload',
                    default => $group,
                }, $missingGroups);
                $message = !empty($missingLabels)
                    ? 'Required documents missing. Please upload the required documents: ' . implode(', ', $missingLabels) . '.'
                    : "Please upload all required documents before submitting. {$usableDocumentCount}/{$requiredDocuments['count']} provided.";

                return redirect()->back()
                    ->with('error', $message)
                    ->withInput();
            }

            // Wrap profile + scholarship + application creation in a DB transaction
            // Also check for an existing Application for this user + scholarship_id
            try {
                DB::beginTransaction();

                $scholarship = $this->findSelectedScholarship($selectedScholarshipId, $data['scholarship_name'] ?? null);

                $isLegacyScholarship = !$scholarship && !$isAcademic;

                if (!$scholarship && !$isLegacyScholarship) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'The selected scholarship could not be found. Please choose a valid scholarship.')->withInput();
                }

                if ($scholarship && $scholarship->status !== 'active') {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'This scholarship is currently unavailable and is not accepting new applications.');
                }

                if ($scholarship) {
                    [$resolvedName, $resolvedType] = $this->resolveScholarshipMetadata($scholarship);
                    $data['scholarship_name'] = $resolvedName;
                    $data['scholarship_type'] = $resolvedType;
                }

                // Serialize application checks per user so concurrent submissions cannot bypass limits.
                User::whereKey($user->id)->lockForUpdate()->first();

                $isAcademicScholarship = $scholarship?->type === 'academic';

                if ($isAcademicScholarship !== $isAcademic) {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'The selected scholarship does not match the application form category.');
                }

                $lockedProfileQuery = StudentProfile::where('user_id', $user->id)
                    ->where('grant_type', $isAcademic ? 'academic' : 'non-academic')
                    ->whereNotNull('locked_at');
                if ($scholarship) {
                    $lockedProfileQuery->whereHas('application', fn ($query) => $query->where('scholarship_id', $scholarship->id));
                } elseif ($previousProfile) {
                    $lockedProfileQuery->whereKey($previousProfile->id);
                } else {
                    $lockedProfileQuery->whereRaw('1 = 0');
                }
                if ($lockedProfileQuery->lockForUpdate()->exists()) {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', "You've reached the maximum number of applications for this scholarship.");
                }

                $existingApp = null;

                if (!empty($requestApplicationId)) {
                    $existingApp = Application::whereKey($requestApplicationId)
                        ->where('user_id', $user->id)
                        ->when($scholarship, fn ($query) => $query->where('scholarship_id', $scholarship->id), fn ($query) => $query->whereNull('scholarship_id'))
                        ->whereHas('studentProfile', function ($query) use ($user, $grantType, $scholarship, $scholarshipName) {
                            $query->where('user_id', $user->id)
                                ->where('grant_type', $grantType)
                                ->when(!$scholarship, fn ($profileQuery) => $profileQuery
                                    ->whereRaw('LOWER(TRIM(scholarship_name)) = ?', [$scholarshipName]));
                        })
                        ->first();
                }

                if (!$existingApp && $scholarship) {
                    $existingApp = Application::where('user_id', $user->id)
                        ->where('scholarship_id', $scholarship->id)
                        ->whereHas('studentProfile', fn ($query) => $query
                            ->where('user_id', $user->id)
                            ->where('grant_type', $grantType))
                        ->where(function ($query) use ($previousProfile) {
                            $query->whereIn('status', ['pending', 'approved']);
                            if ($previousProfile?->application_id) {
                                $query->orWhere('applications.id', $previousProfile->application_id);
                            }
                        })
                        ->first();
                }

                if (!$existingApp && $isLegacyScholarship && $previousProfile?->application_id) {
                    $existingApp = Application::whereKey($previousProfile->application_id)
                        ->where('user_id', $user->id)
                        ->whereNull('scholarship_id')
                        ->whereHas('studentProfile', fn ($query) => $query->whereKey($previousProfile->id))
                        ->first();
                }

                $canReapply = $previousProfile && $applicationWasRejected;

                if ($existingApp && !$canReapply) {
                    // Rollback and return the same friendly error message used earlier
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'You have already applied for the ' . $data['scholarship_name'] . ' scholarship. Only one application per scholarship is allowed.');
                }

                $activeApplications = StudentProfile::where('user_id', $user->id)
                    ->whereIn('application_status', ['pending', 'approved'])
                    ->whereNull('locked_at')
                    ->when($previousProfile && $canReapply, fn ($query) => $query->where('id', '!=', $previousProfile->id))
                    ->lockForUpdate()
                    ->count();

                if ($activeApplications >= 2) {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'You already have two active applications, so this scholarship is not available.');
                }

                if ($existingApp && $canReapply && $previousProfile) {
                    $application = $existingApp;
                    $application->status = 'pending';
                    $application->save();
                    $data['application_id'] = $application->id;
                    $previousProfile->fill($data);
                    $previousProfile->reapply_count = (int) $previousProfile->reapply_count + 1;
                    $previousProfile->application_status = 'pending';
                    $previousProfile->rejection_reason = null;
                    $previousProfile->save();
                    $profile = $previousProfile;
                } else {
                    $application = Application::create([
                        'user_id' => $user->id,
                        'scholarship_id' => $scholarship?->id,
                        'status' => 'pending',
                    ]);

                    $data['application_id'] = $application->id;
                    $profile = StudentProfile::create($data);
                }

                if (!$isAcademic && (!$previousProfile || $familyBackgroundRejected || $previousProfile->grant_type !== 'non-academic')) {
                    $this->saveFamilyBackground($profile, $familyBackgroundInput);
                }

                DB::commit();

                $this->queueApplicationReceivedNotification($profile, $user);

                foreach ($oldRejectedFiles as $oldFile) {
                    if (Storage::disk('local')->exists($oldFile)) {
                        Storage::disk('local')->delete($oldFile);
                    }
                }

                Log::info('Student profile created successfully', [
                    'profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'scholarship_id' => $scholarship?->id,
                    'email' => $profile->email,
                    'fileCount' => count($uploaded)
                ]);

                $successMessage = 'Application submitted successfully for ' . $profile->scholarship_name;
                if (!empty($uploadErrors)) {
                    $successMessage .= "\n\ Note: Some files could not be processed:\n" . implode("\n", $uploadErrors);
                }

                return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                    ->with('success', $successMessage);

            } catch (QueryException $e) {
                // Handle DB-level duplicate key race-condition gracefully
                Log::error('Database error while creating application', ['error' => $e->getMessage()]);

                // If it's a duplicate entry (unique constraint), return friendly message
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'duplicate') || str_contains($msg, 'unique') || $e->getCode() == '23000') {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'You have already applied for the ' . $data['scholarship_name'] . ' scholarship. Only one application per scholarship is allowed.');
                }

                DB::rollBack();
                // Do not expose SQL errors to the user — show a generic failure message
                return redirect()->back()
                    ->with('error', 'Failed to submit application.')
                    ->withInput();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error saving student profile', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
                // Do not expose exception details to the user
                return redirect()->back()
                    ->with('error', 'Failed to submit application.')
                    ->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in student profile store', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving student profile', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
                return redirect()->back()
                    ->with('error', 'Failed to submit application.')
                ->withInput();
        }
    }

    private function saveFamilyBackground(StudentProfile $profile, array $input): void
    {
        $fields = [
            'father_status', 'father_name', 'father_age', 'father_birthdate', 'father_address',
            'father_occupation', 'father_office_address', 'father_education',
            'mother_status', 'mother_name', 'mother_age', 'mother_birthdate', 'mother_address',
            'mother_occupation', 'mother_office_address', 'mother_education',
            'parent_contact_number', 'parent_email', 'monthly_income', 'disability_details',
        ];
        $attributes = [];

        foreach ($fields as $field) {
            $value = $input[$field] ?? null;
            $attributes[$field] = is_string($value) ? (trim($value) ?: null) : $value;
        }

        $disabilityResponse = $input['has_differently_abled_member'] ?? null;
        $attributes['has_differently_abled_member'] = match ($disabilityResponse) {
            'yes' => true,
            'no' => false,
            default => null,
        };

        $siblingFields = ['name', 'age', 'marital_status', 'occupation'];
        $siblings = collect($input['siblings'] ?? [])
            ->take(10)
            ->map(function ($sibling) use ($siblingFields) {
                $row = [];
                foreach ($siblingFields as $field) {
                    $value = $sibling[$field] ?? null;
                    $row[$field] = is_string($value) ? (trim($value) ?: null) : $value;
                }

                return $row;
            })
            ->filter(fn ($sibling) => collect($sibling)->contains(fn ($value) => $value !== null && $value !== ''))
            ->values()
            ->all();
        $attributes['siblings'] = $siblings ?: null;

        if (!collect($attributes)->contains(fn ($value) => $value !== null && $value !== '')) {
            return;
        }

        $profile->familyBackground()->updateOrCreate([], $attributes);
    }

    private function queueApplicationReceivedNotification(StudentProfile $profile, User $user): void
    {
        try {
            $studentName = $user->name ?: trim(implode(' ', array_filter([
                $profile->first_name,
                $profile->middle_name,
                $profile->last_name,
                $profile->suffix,
            ])));
            $mail = new ApplicationReceivedMail($studentName, $profile->scholarship_name);
            Mail::to($user->email)->queue($mail);
            $profile->recordNotification($mail->notificationSubject(), $mail->notificationBody());
        } catch (\Throwable $e) {
            Log::error('Application received notification failed', [
                'application_id' => $profile->application_id,
                'profile_id' => $profile->id,
                'user_id' => $user->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Update file upload (delete old, store new)
     * Optional method if you want to allow users to update their documents
     */
    public function updateDocuments(Request $request, $id)
    {
        try {
            $profile = StudentProfile::findOrFail($id);

            $uploadErrors = [];
            $newUploads = [];

            $uploadGroups = [
                'certificate_of_grades' => $this->collectFilesFromRequest($request, 'uploads'),
                'certificate_of_enrollment' => $this->collectFilesFromRequest($request, 'certificate_of_enrollment'),
            ];

            foreach ($uploadGroups as $groupKey => $files) {
                if (empty($files)) {
                    continue;
                }

                foreach ($files as $file) {
                    if (!$file || !$file->isValid()) {
                        $uploadErrors[] = "File upload failed. Please try again.";
                        continue;
                    }

                    $validation = $this->validateFile($file);

                    if (!$validation['valid']) {
                        $uploadErrors[] = $validation['error'];
                        continue;
                    }

                    try {
                        $filePath = $file->store('student_profiles', 'local');
                        $newUploads[$groupKey][] = $filePath;
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to store file.";
                    }
                }
            }

            if (!empty($newUploads)) {
                // Delete old files
                $this->deleteOldFiles($profile->uploads);

                // Update with new files
                $profile->uploads = $newUploads;
                $profile->save();

                Log::info("Documents updated for profile {$id}");
            }

            $message = 'Documents updated successfully.';
            if (!empty($uploadErrors)) {
                $message .= "\n\nSome files could not be processed:\n" . implode("\n", $uploadErrors);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error updating documents', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to update documents.');
        }
    }

    public function applicationStatus()
    {
        $userId = auth()->id();

        $applicationFields = [
            'id',
            'scholarship_name',
            'scholarship_type',
            'application_status',
            'rejection_reason',
            'grant_type',
            'student_information_status',
            'student_information_reason',
            'personal_information_status',
            'personal_information_reason',
            'scholarship_requirements_status',
            'scholarship_requirements_reason',
            'family_background_status',
            'family_background_reason',
            'last_notification_subject',
            'last_notification_body',
            'created_at',
            'updated_at',
        ];

        $academicApplications = StudentProfile::where('user_id', $userId)
            ->where('grant_type', 'academic')
            ->orderByDesc('created_at')
            ->get($applicationFields);

        $nonAcademicApplications = StudentProfile::where('user_id', $userId)
            ->where('grant_type', 'non-academic')
            ->orderByDesc('created_at')
            ->get($applicationFields);

        return view('student.application-status', compact(
            'academicApplications',
            'nonAcademicApplications'
        ));
    }
}
