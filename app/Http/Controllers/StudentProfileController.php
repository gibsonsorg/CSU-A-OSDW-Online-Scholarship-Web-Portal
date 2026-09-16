<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request)
    {
        try {
            // Detect if this is an academic or non-academic scholarship form
            $isAcademic = $request->has('program') || $request->has('id_number') || $request->has('birthdate');
            
            // Build validation rules dynamically based on form type
            $validationRules = [
                'first_name' => 'required_without:last_name|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required_without:first_name|string|max:255',
                'sex' => 'required|in:Male,Female',
                'status' => 'required|in:Single,Married,In a Relationship,Divorced',
                'email' => $isAcademic ? 'nullable|email|max:255' : 'required|email|max:255',
                'student_id_number' => 'nullable|string|max:255',
                'home_address' => 'nullable|string',
                'contact_number' => 'nullable|string|max:50',
                'course' => 'required|string',
                'year_level' => 'required|in:1st Year,2nd Year,3rd Year,4th Year',
                'scholarship_id' => 'nullable|integer|min:1',
                'scholarship_name' => 'required|string',
                'scholarship_type' => 'required|string',
            ];
            
            // Add academic-specific fields if this is an academic form
            if ($isAcademic) {
                $validationRules['program'] = 'required|string|max:255';
                $validationRules['id_number'] = 'required|string|max:255';
                $validationRules['birthdate'] = 'required|date';
                $validationRules['birthplace'] = 'required|string|max:255';
                $validationRules['religion'] = 'required|string|max:255';
            }

            // Basic validation for non-file fields
            $data = $request->validate($validationRules);
            
            $user = Auth::user();
            $data['user_id'] = $user->id;
            $data['email'] = $user->email;
            $data['student_id_number'] = $request->input('student_id_number') ?? $request->input('id_number') ?? $user->student_id ?? null;

            $selectedScholarshipId = $request->input('scholarship_id');
            $selectedScholarship = !empty($selectedScholarshipId)
                ? Scholarship::find($selectedScholarshipId)
                : Scholarship::where('name', trim((string) $data['scholarship_name']))->first();

            if ($selectedScholarship) {
                [$resolvedName, $resolvedType] = $this->resolveScholarshipMetadata($selectedScholarship);
                $data['scholarship_name'] = $resolvedName;
                $data['scholarship_type'] = $resolvedType;
            }

            $requestProfileId = $request->input('student_profile_id') ?: $request->input('profile_id');
            $requestApplicationId = $request->input('application_id');

            if ($requestProfileId) {
                $previousProfile = StudentProfile::whereKey($requestProfileId)
                    ->where('user_id', $user->id)
                    ->first();
            } elseif ($requestApplicationId) {
                $previousProfile = StudentProfile::where('user_id', $user->id)
                    ->where('application_id', $requestApplicationId)
                    ->latest('id')
                    ->first();
            } else {
                $previousProfile = StudentProfile::where('user_id', $user->id)
                    ->where('scholarship_name', $data['scholarship_name'])
                    ->latest('id')
                    ->first();
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

            if ($request->input('scholarship_name') === 'Athletic Scholarship') {
                $uploadGroups['certificate_of_adviser'] = $this->collectFilesFromRequest($request, 'certificate_of_adviser');
            }

            if ($previousProfile) {
                $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
                $previousReviews = is_array($previousProfile->{$reviewColumn}) ? $previousProfile->{$reviewColumn} : [];
                $rejectedGroups = collect($previousReviews)
                    ->filter(fn ($review) => ($review['status'] ?? null) === 'rejected')
                    ->keys()
                    ->map(fn ($key) => explode('.', $key)[0])
                    ->unique();

                foreach ($rejectedGroups as $group) {
                    $requestGroup = in_array($group, ['uploads', 'grades'], true)
                        ? 'certificate_of_grades'
                        : $group;
                    if (empty($uploadGroups[$requestGroup])) {
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

            if ($previousProfile) {
                $studentStatus = $previousProfile->student_information_status ?? 'pending';
                $personalStatus = $previousProfile->personal_information_status ?? 'pending';
                $requirementsStatus = $previousProfile->scholarship_requirements_status ?? 'pending';
                $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
                $previousReviews = is_array($previousProfile->{$reviewColumn}) ? $previousProfile->{$reviewColumn} : [];
                $hasRejectedDocument = collect($previousReviews)
                    ->contains(fn ($review) => ($review['status'] ?? null) === 'rejected');
                $requirementsRejected = $requirementsStatus === 'rejected' || $hasRejectedDocument;

                $data['student_information_status'] = $studentStatus === 'approved' ? 'approved' : 'pending';
                $data['personal_information_status'] = $personalStatus === 'approved' ? 'approved' : 'pending';
                $data['scholarship_requirements_status'] = $requirementsStatus === 'approved' ? 'approved' : 'pending';

                if ($studentStatus !== 'rejected') {
                    foreach (['first_name', 'middle_name', 'last_name', 'email', 'student_id_number', 'course', 'year_level', 'program', 'id_number'] as $field) {
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

            // Wrap profile + scholarship + application creation in a DB transaction
            // Also check for an existing Application for this user + scholarship_id
            try {
                DB::beginTransaction();

                $scholarship = !empty($selectedScholarshipId)
                    ? Scholarship::find($selectedScholarshipId)
                    : Scholarship::where('name', trim((string) $data['scholarship_name']))->first();

                if (!$scholarship && !empty($data['scholarship_name'])) {
                    $scholarship = Scholarship::firstOrCreate(
                        ['name' => $data['scholarship_name']],
                        ['type' => $data['grant_type']]
                    );
                }

                if (!$scholarship) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'The selected scholarship could not be found. Please choose a valid scholarship.')->withInput();
                }

                [$resolvedName, $resolvedType] = $this->resolveScholarshipMetadata($scholarship);
                $data['scholarship_name'] = $resolvedName;
                $data['scholarship_type'] = $resolvedType;

                // Serialize application checks per user so concurrent submissions cannot bypass limits.
                User::whereKey($user->id)->lockForUpdate()->first();

                $isAcademicScholarship = $scholarship->type === 'academic';

                if ($isAcademicScholarship !== $isAcademic) {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'The selected scholarship does not match the application form category.');
                }

                $existingApp = null;

                if (!empty($requestApplicationId)) {
                    $existingApp = Application::whereKey($requestApplicationId)
                        ->where('user_id', $user->id)
                        ->first();
                }

                if (!$existingApp) {
                    $existingApp = Application::where('user_id', $user->id)
                        ->where('scholarship_id', $scholarship->id)
                        ->where(function ($query) use ($previousProfile) {
                            $query->whereIn('status', ['pending', 'approved']);
                            if ($previousProfile?->application_id) {
                                $query->orWhere('applications.id', $previousProfile->application_id);
                            }
                        })
                        ->first();
                }

                $reviewColumn = Schema::hasColumn('student_profiles', 'document_review') ? 'document_review' : 'document_reviews';
                $previousReviews = is_array($previousProfile?->{$reviewColumn}) ? $previousProfile->{$reviewColumn} : [];
                $hasRejectedDocument = collect($previousReviews)->contains(fn ($review) => ($review['status'] ?? null) === 'rejected');
                $hasRejectedSection = in_array('rejected', [
                    $previousProfile?->student_information_status,
                    $previousProfile?->personal_information_status,
                    $previousProfile?->scholarship_requirements_status,
                ], true);
                $canReapply = $previousProfile && ($hasRejectedSection || $hasRejectedDocument);

                if ($existingApp && !$canReapply) {
                    // Rollback and return the same friendly error message used earlier
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'You have already applied for the ' . $data['scholarship_name'] . ' scholarship. Only one application per scholarship is allowed.');
                }

                if ($isAcademicScholarship) {
                    $activeCategoryApplications = Application::where('applications.user_id', $user->id)
                        ->join('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
                        ->where('scholarships.type', 'academic')
                        ->whereIn('applications.status', ['pending', 'approved'])
                        ->when($existingApp && $canReapply, fn ($query) => $query->where('applications.id', '!=', $existingApp->id))
                        ->lockForUpdate()
                        ->count();
                } else {
                    $activeCategoryApplications = StudentProfile::where('user_id', $user->id)
                        ->where('grant_type', $data['grant_type'])
                        ->whereIn('application_status', ['pending', 'approved'])
                        ->when($previousProfile && $canReapply, fn ($query) => $query->where('id', '!=', $previousProfile->id))
                        ->lockForUpdate()
                        ->count();
                }

                if ($activeCategoryApplications >= 2) {
                    DB::rollBack();
                    return redirect()->route('dashboard', ['section' => $isAcademic ? 'acad' : 'non'])
                        ->with('error', 'You already have two active applications in the ' . $data['grant_type'] . ' category. Only two open applications per category are allowed.');
                }

                if ($existingApp && $canReapply && $previousProfile) {
                    $application = $existingApp;
                    $application->status = 'pending';
                    $application->save();
                    $data['application_id'] = $application->id;
                    $previousProfile->fill($data);
                    $previousProfile->application_status = 'pending';
                    $previousProfile->rejection_reason = null;
                    $previousProfile->save();
                    $profile = $previousProfile;
                } else {
                    $application = Application::create([
                        'user_id' => $user->id,
                        'scholarship_id' => $scholarship->id,
                        'status' => 'pending',
                    ]);

                    $data['application_id'] = $application->id;
                    $profile = StudentProfile::create($data);
                }

                DB::commit();

                foreach ($oldRejectedFiles as $oldFile) {
                    if (Storage::disk('local')->exists($oldFile)) {
                        Storage::disk('local')->delete($oldFile);
                    }
                }

                Log::info('Student profile created successfully', [
                    'profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'scholarship_id' => $scholarship->id,
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
