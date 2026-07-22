<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        if (in_array($extension, $rules['document']['extensions']) || 
            in_array($mimeType, $rules['document']['mimes'])) {
            return 'document';
        }

        if (in_array($extension, $rules['image']['extensions']) || 
            in_array($mimeType, $rules['image']['mimes'])) {
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
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("Deleted old file: {$filePath}");
                }
            } catch (\Exception $e) {
                Log::warning("Failed to delete file {$filePath}: " . $e->getMessage());
            }
        }
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
            $data['student_id_number'] = $request->input('student_id_number') ?? $request->input('id_number') ?? $user->student_id ?? null;

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

            // If academic form and no email provided, use authenticated user's email
            if ($isAcademic && (empty($data['email']) || !isset($data['email']))) {
                $data['email'] = $user->email ?? 'no-email@scholarship.local';
            }

            // Always use the authenticated user's email if the field is empty for any form
            if (empty($data['email']) && $user->email) {
                $data['email'] = $user->email;
            }
            
            // Assign grant type
            $data['grant_type'] = $isAcademic ? 'academic' : 'non-academic';

            // Prevent duplicate applications for the same scholarship by the authenticated user
            $existingApplication = StudentProfile::where('user_id', $user->id)
                ->where('scholarship_name', $data['scholarship_name'])
                ->first();

            if ($existingApplication) {
                return redirect()->route('dashboard', ['section' => 'non'])
                    ->with('error', 'You have already applied for the ' . $data['scholarship_name'] . ' scholarship. Only one application per scholarship is allowed.');
            }

            // Limit two active applications per grant category for this user
            $activeCategoryApplications = StudentProfile::where('user_id', $user->id)
                ->where('grant_type', $data['grant_type'])
                ->whereIn('application_status', ['pending', 'approved'])
                ->count();

            if ($activeCategoryApplications >= 2) {
                return redirect()->route('dashboard', ['section' => 'non'])
                    ->with('error', 'You already have two active applications in the ' . $data['grant_type'] . ' category. Only two open applications per category are allowed.');
            }

            // Handle file uploads with custom validation
            $uploaded = [];
            $uploadErrors = [];

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

                    // Validate file
                    $validation = $this->validateFile($file);

                    if (!$validation['valid']) {
                        $uploadErrors[] = $validation['error'];
                        continue;
                    }

                    // Store the file
                    try {
                        $filePath = $file->store('student_profiles', 'public');
                        $uploaded[$groupKey][] = $filePath;
                        Log::info("File uploaded successfully: {$filePath}");
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to store file '{$file->getClientOriginalName()}': " . $e->getMessage();
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

            $profile = StudentProfile::create($data);

            $scholarship = Scholarship::firstOrCreate(
                ['name' => $data['scholarship_name']],
                ['type' => $data['grant_type']]
            );

            Application::create([
                'user_id' => $user->id,
                'scholarship_id' => $scholarship->id,
                'status' => $profile->application_status ?? 'pending',
            ]);

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

            return redirect()->route('dashboard', ['section' => 'non'])
                ->with('success', $successMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in student profile store', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving student profile', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
            return redirect()->back()
                ->with('error', 'Failed to submit application: ' . $e->getMessage())
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
                        $filePath = $file->store('student_profiles', 'public');
                        $newUploads[$groupKey][] = $filePath;
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to store file: " . $e->getMessage();
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
            return redirect()->back()->with('error', 'Failed to update documents: ' . $e->getMessage());
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
