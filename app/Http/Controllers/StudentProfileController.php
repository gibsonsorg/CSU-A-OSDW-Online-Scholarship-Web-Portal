<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentProfile;
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
     * Delete old files from storage
     */
    private function deleteOldFiles($uploads)
    {
        if (!$uploads || !is_array($uploads)) {
            return;
        }

        foreach ($uploads as $filePath) {
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
            // Basic validation for non-file fields
            $data = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'sex' => 'required|in:Male,Female',
                'status' => 'required|in:Single,Married,In a Relationship,Divorced',
                'email' => 'required|email|max:255',
                'home_address' => 'nullable|string',
                'contact_number' => 'nullable|string|max:50',
                'course' => 'required|string',
                'year_level' => 'required|in:1st Year,2nd Year,3rd Year,4th Year',
                'scholarship_name' => 'required|string',
                'scholarship_type' => 'required|string',
            ]);

            // Check if user has already applied for this scholarship
            $existingApplication = StudentProfile::where('email', $data['email'])
                ->where('scholarship_name', $data['scholarship_name'])
                ->first();

            if ($existingApplication) {
                return redirect()->route('dashboard', ['section' => 'non'])
                    ->with('error', 'You have already applied for the ' . $data['scholarship_name'] . ' scholarship. Only one application per scholarship is allowed.');
            }

            // Handle file uploads with custom validation
            $uploaded = [];
            $uploadErrors = [];

            if ($request->hasFile('uploads')) {
                $files = is_array($request->file('uploads')) ? $request->file('uploads') : [$request->file('uploads')];

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
                        $uploaded[] = $filePath;
                        Log::info("File uploaded successfully: {$filePath}");
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to store file '{$file->getClientOriginalName()}': " . $e->getMessage();
                        Log::error("File storage error: " . $e->getMessage());
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
            }

            $data['uploads'] = !empty($uploaded) ? $uploaded : null;

            $profile = StudentProfile::create($data);

            Log::info('Student profile created successfully', [
                'profile_id' => $profile->id,
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

            if ($request->hasFile('uploads')) {
                $files = is_array($request->file('uploads')) ? $request->file('uploads') : [$request->file('uploads')];

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
                        $newUploads[] = $filePath;
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to store file: " . $e->getMessage();
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
}
