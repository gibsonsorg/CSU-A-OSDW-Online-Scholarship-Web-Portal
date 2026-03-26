<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Log;

class StudentProfileController extends Controller
{
    public function store(Request $request)
    {
        try {
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
                'scholarship_name' => 'required|string',
                'scholarship_type' => 'required|string',
                'uploads.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            ]);

            // Check if user has already applied for this scholarship
            $existingApplication = StudentProfile::where('email', $data['email'])
                ->where('scholarship_name', $data['scholarship_name'])
                ->first();

            if ($existingApplication) {
                return redirect()->route('dashboard', ['section' => 'non'])
                    ->with('error', 'You have already applied for the ' . $data['scholarship_name'] . ' scholarship. Only one application per scholarship is allowed.');
            }

            $uploaded = [];
            if ($request->hasFile('uploads')) {
                foreach ($request->file('uploads') as $file) {
                    $uploaded[] = $file->store('student_profiles', 'public');
                }
            }

            $data['uploads'] = $uploaded ?: null;

            $profile = StudentProfile::create($data);
            
            Log::info('Student profile created successfully', ['profile_id' => $profile->id, 'email' => $profile->email]);

            return redirect()->route('dashboard', ['section' => 'non'])->with('success', 'Application submitted successfully for ' . $profile->scholarship_name);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in student profile store', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving student profile', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
            return redirect()->route('dashboard', ['section' => 'non'])->with('error', 'Failed to submit application: ' . $e->getMessage());
        }
    }
}
