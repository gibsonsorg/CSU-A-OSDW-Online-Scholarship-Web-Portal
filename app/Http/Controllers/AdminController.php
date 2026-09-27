<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationStatusMail;
use App\Mail\ApplicationSectionStatusMail;
use App\Mail\ApprovalMail;
use App\Mail\RejectionMail;
use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Display main admin dashboard with all applications
     */
    public function dashboard(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'approved', 'pending', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = StudentProfile::whereNull('deleted_at')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($query) use ($like) {
                    $query->where('last_name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('course', 'like', $like)
                        ->orWhere('scholarship_name', 'like', $like);
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('application_status', $status);
            })
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id')
            ->leftJoin('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
            ->orderByDesc('applications.created_at')
            ->orderByDesc('applications.id')
            ->orderByDesc('student_profiles.id')
            ->select('student_profiles.*', 'scholarships.type as scholarship_category_type', 'scholarships.funding_type as scholarship_funding_type');

        $recentApplications = $query->take(10)->get();
        $filterType = 'all';
        $stats = $this->buildDashboardStats();

        return view('admin.welcome', compact('recentApplications', 'filterType', 'stats'));
    }

    /**
     * Display admin dashboard filtered by academic grants
     */
    public function academicDashboard(Request $request)
    {
        $recentApplications = $this->filteredApplications($request, 'academic');
        $scholarshipNames = $this->scholarshipNames('academic');
        $filterType = 'academic';
        $stats = $this->buildDashboardStats();

        return view('admin.welcome', compact('recentApplications', 'scholarshipNames', 'filterType', 'stats'));
    }

    /**
     * Display admin dashboard filtered by non-academic grants
     */
    public function nonAcademicDashboard(Request $request)
    {
        $recentApplications = $this->filteredApplications($request, 'non-academic');
        $scholarshipNames = $this->scholarshipNames('non-academic');
        $filterType = 'non-academic';
        $stats = $this->buildDashboardStats();

        return view('admin.welcome', compact('recentApplications', 'scholarshipNames', 'filterType', 'stats'));
    }

    private function buildDashboardStats(): array
    {
        $now = now();

        $totalGrants = Scholarship::count();
        $grantsAddedThisMonth = Scholarship::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $applicationsBaseQuery = StudentProfile::whereNull('deleted_at');
        $totalApplications = $applicationsBaseQuery->count();
        $applicationsThisWeek = $applicationsBaseQuery
            ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->count();

        $approved = $applicationsBaseQuery->where('application_status', 'approved')->count();
        $approvalRate = $totalApplications > 0 ? (int) round(($approved / $totalApplications) * 100) : 0;

        $registeredUsersQuery = User::where('role', '!=', 0)->whereNull('deleted_at');
        $registeredUsers = $registeredUsersQuery->count();
        $newRegisteredThisMonth = $registeredUsersQuery
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        return [
            'total_grants' => $totalGrants,
            'grants_added_this_month' => $grantsAddedThisMonth,
            'total_applications' => $totalApplications,
            'applications_this_week' => $applicationsThisWeek,
            'approved' => $approved,
            'approval_rate' => $approvalRate,
            'registered_users' => $registeredUsers,
            'new_registered_this_month' => $newRegisteredThisMonth,
        ];
    }

    private function filteredApplications(Request $request, string $grantType)
    {
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $scholarshipName = trim((string) $request->query('scholarship_name', ''));
        // CHANGE: Filter applicants by the minimum admin-entered amount.
        $status = strtolower((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'approved', 'pending', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = StudentProfile::where('grant_type', $grantType)
            ->whereNull('deleted_at')
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '<=', $dateTo);
            })
            ->when($scholarshipName !== '', function ($query) use ($scholarshipName) {
                $query->where('student_profiles.scholarship_name', $scholarshipName);
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('student_profiles.application_status', $status);
            })
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id')
            ->leftJoin('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
            ->select('student_profiles.*', 'scholarships.type as scholarship_category_type', 'scholarships.funding_type as scholarship_funding_type');

        $createdAtDirection = $scholarshipName !== '' ? 'ASC' : 'DESC';
        $query->orderByRaw('COALESCE(applications.created_at, student_profiles.created_at) ' . $createdAtDirection)
            ->orderBy('applications.id', $createdAtDirection)
            ->orderBy('student_profiles.id', $createdAtDirection);

        return $query->paginate(10)->withQueryString();
    }

    private function scholarshipNames(string $grantType)
    {
        $names = Scholarship::where('type', $grantType)
            ->orderBy('name')
            ->pluck('name');

        if ($names->isNotEmpty()) {
            return $names;
        }

        if ($grantType !== 'academic') {
            return collect();
        }

        return collect([
            'Cagayan Valley Assoc. of Hawaii',
            'University Based Scholarship',
            'ACEF – GIAHEP',
            'DOST SEI Project Strand',
            'Tulong Dunong Program',
            'LGU Aparri',
            'Natalged A Lasam',
            'Andres Tamayo Scholarship',
            'Gokongwei Brothers Foundation',
        ]);
    }

    private function canonicalScholarshipNames(string $grantType): array
    {
        return Scholarship::where('type', $grantType)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function scholarshipNamesLegacyFallback(string $grantType)
    {
        $names = Scholarship::where('type', $grantType)
            ->orderBy('name')
            ->pluck('name');

        if ($names->isNotEmpty()) {
            return $names;
        }

        if ($grantType !== 'academic') {
            return collect();
        }

        return collect([
            'Cagayan Valley Assoc. of Hawaii',
            'University Based Scholarship',
            'ACEF – GIAHEP',
            'DOST SEI Project Strand',
            'Tulong Dunong Program',
            'LGU Aparri',
            'Natalged A Lasam',
            'Andres Tamayo Scholarship',
            'Gokongwei Brothers Foundation',
        ]);
    }

    /**
     * List registered users for admin with optional status and search filtering.
     */
    public function users(\Illuminate\Http\Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'pending', 'approved', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = \App\Models\User::where('role', '!=', 0)
            ->whereNull('deleted_at')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('student_id', 'like', $like);
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('account_status', $status);
            })
            ->orderBy('created_at', 'desc');

        $users = $query->get();
        $recentApplications = collect();
        $filterType = 'users';

        if ($request->wantsJson()) {
            return response()->json(['users' => $users]);
        }

        if ($request->ajax()) {
            return view('admin.partials.users-table', compact('users'));
        }

        return view('admin.welcome', compact('users', 'recentApplications', 'filterType'));
    }

    /**
     * Approve an application
     */
    public function approve($id)
    {
        $application = StudentProfile::find($id);
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $this->syncOverallStatus($application);
        if ($application->application_status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'All application sections must be approved first.',
            ], 422);
        }

        try {
            $user = $application->user()->first();
            if ($user) {
                Mail::to($user->email)->send(new ApplicationStatusMail(
                    $user->name ?? $application->first_name . ' ' . $application->last_name,
                    $application->scholarship_name ?? 'Scholarship',
                    'approved'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Application approval notification failed', ['application_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'ok',
                'message' => 'Application approved, but the notification could not be queued.',
            ]);
        }

        return response()->json(['status' => 'ok', 'message' => 'Application approved']);
    }

    /**
     * Approve a pending user account so they can log in.
     */
    public function approveUser($id)
    {
        $user = \App\Models\User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $user->forceFill([
            'account_status' => 'approved',
            'rejection_reason' => null,
        ])->save();

        try {
            Mail::to($user->email)->send(new ApprovalMail($user));
        } catch (\Throwable $e) {
            Log::error('User approval notification failed', ['user_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'User approved, but the notification could not be queued.',
            ], 500);
        }

        return response()->json(['status' => 'ok', 'message' => 'User approved']);
    }

    /**
     * Reject an application
     */
    public function reject(Request $request, $id)
    {
        $application = StudentProfile::find($id);
        if ($application) {
            $application->application_status = 'rejected';
            $application->rejection_reason = $request->input('reason', '');
            $application->save();

            $this->syncApplicationRecord($application, 'rejected');

            try {
                $user = $application->user()->first();
                if ($user) {
                    Mail::to($user->email)->send(new ApplicationStatusMail(
                        $user->name ?? $application->first_name . ' ' . $application->last_name,
                        $application->scholarship_name ?? 'Scholarship',
                        'rejected'
                    ));
                }
            } catch (\Throwable $e) {
                Log::error('Application rejection notification failed', ['application_id' => $id, 'exception' => $e]);
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Application rejected, but the notification could not be queued.',
                ]);
            }

            return response()->json(['status' => 'ok', 'message' => 'Application rejected']);
        }
        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
    }

    public function approveSection($id, string $section)
    {
        $application = StudentProfile::findOrFail($id);
        $fields = $this->sectionFields($section);
        if ($section === 'family-background' && $application->grant_type !== 'non-academic') {
            abort(404);
        }

        $application->forceFill([
            $fields['status'] => 'approved',
            $fields['reason'] => null,
        ])->save();
        $this->syncOverallStatus($application);

        return response()->json(['status' => 'ok', 'message' => $fields['label'] . ' approved']);
    }

    public function rejectSection(Request $request, $id, string $section)
    {
        $application = StudentProfile::with('user')->findOrFail($id);
        $fields = $this->sectionFields($section);
        if ($section === 'family-background' && $application->grant_type !== 'non-academic') {
            abort(404);
        }
        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') {
            return response()->json(['status' => 'error', 'message' => 'A rejection reason is required.'], 422);
        }

        $application->forceFill([
            $fields['status'] => 'rejected',
            $fields['reason'] => $reason,
        ])->save();
        $this->syncOverallStatus($application);

        if ($application->user?->email) {
            Mail::to($application->user->email)->queue(new ApplicationSectionStatusMail(
                $application->user->name ?: trim($application->first_name . ' ' . $application->last_name),
                $fields['label'],
                $reason,
            ));
        }

        return response()->json(['status' => 'ok', 'message' => $fields['label'] . ' rejected']);
    }

    public function approveDocument($id, string $group, int $index)
    {
        return $this->reviewDocument($id, $group, $index, 'approved');
    }

    public function rejectDocument(Request $request, $id, string $group, int $index)
    {
        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') {
            return response()->json(['status' => 'error', 'message' => 'A rejection reason is required.'], 422);
        }

        return $this->reviewDocument($id, $group, $index, 'rejected', $reason);
    }

    private function sectionFields(string $section): array
    {
        return match ($section) {
            'student-information' => [
                'status' => 'student_information_status',
                'reason' => 'student_information_reason',
                'label' => 'Student Information',
            ],
            'personal-information' => [
                'status' => 'personal_information_status',
                'reason' => 'personal_information_reason',
                'label' => 'Personal Information',
            ],
            'scholarship-requirements' => [
                'status' => 'scholarship_requirements_status',
                'reason' => 'scholarship_requirements_reason',
                'label' => 'Scholarship & Requirements',
            ],
            'family-background' => [
                'status' => 'family_background_status',
                'reason' => 'family_background_reason',
                'label' => 'Family Background',
            ],
            default => abort(404),
        };
    }

    private function reviewDocument($id, string $group, int $index, string $status, ?string $reason = null)
    {
        $application = StudentProfile::with('user')->findOrFail($id);
        $uploads = $application->uploads;
        $files = is_array($uploads) && isset($uploads[$group])
            ? $uploads[$group]
            : (is_array($uploads) && $group === 'uploads' && array_is_list($uploads) ? $uploads : null);

        if (!is_array($files) || !isset($files[$index]) || !is_string($files[$index])) {
            abort(404);
        }

        $reviewColumn = $this->documentReviewColumn();
        $reviews = $application->{$reviewColumn} ?? [];
        $reviewKey = $group . '.' . $index;
        $reviews[$reviewKey] = [
            'status' => $status,
            'reason' => $reason,
        ];
        $application->{$reviewColumn} = $reviews;
        $application->save();

        if ($status === 'approved' && $this->allDocumentsApproved($application->uploads, $reviews)) {
            $application->forceFill([
                'scholarship_requirements_status' => 'approved',
                'scholarship_requirements_reason' => null,
            ])->save();
        } elseif ($status === 'rejected') {
            $application->forceFill([
                'scholarship_requirements_status' => 'rejected',
                'scholarship_requirements_reason' => $reason,
            ])->save();
        }

        $this->syncOverallStatus($application);

        if ($status === 'rejected' && $application->user?->email) {
            Mail::to($application->user->email)->queue(new ApplicationSectionStatusMail(
                $application->user->name ?: trim($application->first_name . ' ' . $application->last_name),
                'Scholarship & Requirements',
                $reason ?? '',
                ucwords(str_replace(['_', '-'], ' ', $group)),
            ));
        }

        return response()->json(['status' => 'ok', 'message' => 'Document ' . $status]);
    }

    private function documentReviewColumn(): string
    {
        return Schema::hasColumn('student_profiles', 'document_review')
            ? 'document_review'
            : 'document_reviews';
    }

    private function allDocumentsApproved($uploads, array $reviews): bool
    {
        $documentCount = 0;
        foreach ((array) $uploads as $files) {
            foreach ((array) $files as $file) {
                if (is_string($file)) {
                    $documentCount++;
                }
            }
        }

        if ($documentCount === 0) {
            return false;
        }

        return collect($reviews)
            ->filter(fn ($review) => ($review['status'] ?? null) === 'approved')
            ->count() === $documentCount;
    }

    private function syncOverallStatus(StudentProfile $application): void
    {
        $statuses = [
            $application->student_information_status,
            $application->personal_information_status,
            $application->scholarship_requirements_status,
        ];
        if ($application->grant_type === 'non-academic') {
            $statuses[] = $application->family_background_status;
        }

        $overallStatus = in_array('rejected', $statuses, true)
            ? 'rejected'
            : (count(array_filter($statuses, fn ($status) => $status === 'approved')) === count($statuses) ? 'approved' : 'pending');

        if ($application->application_status !== $overallStatus) {
            $application->application_status = $overallStatus;
            if ($overallStatus !== 'rejected') {
                $application->rejection_reason = null;
            }
            $application->save();
        }

        $this->syncApplicationRecord($application, $overallStatus);
    }

    /**
     * Reject a user account and remove it from the system.
     */
    public function rejectUser(Request $request, $id)
    {
        $user = \App\Models\User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $message = (string) $request->input('reason', $request->input('message', ''));

        $user->forceFill([
            'account_status' => 'rejected',
            'rejection_reason' => $message,
        ])->save();

        try {
            // Get the attempt number for the email
            $attempt = $user->registered_attempt ?? 1;
            Mail::to($user->email)->send(new RejectionMail($user, $message, $attempt));
        } catch (\Throwable $e) {
            Log::error('User rejection notification failed', ['user_id' => $id, 'exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'User rejected, but the notification could not be queued.',
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'User account rejected',
        ]);
    }

    /**
     * Delete a rejected user registration after the final rejection.
     * Kept separate from rejectUser so second rejection does not remove the record automatically.
     */
    public function deleteUser($id)
    {
        $user = \App\Models\User::withTrashed()->find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($user->account_status !== 'rejected' && ! $user->trashed()) {
            return response()->json(['status' => 'error', 'message' => 'Only rejected users can be deleted'], 422);
        }

        // Preserve the user's attempt history by storing the record in a deleted state instead of deleting the history fields.
        // This ensures the user remains permanently blocked from a third registration attempt after final rejection.
        $attempt = (int) ($user->registered_attempt ?? 2);
        $user->forceFill([
            'registered_attempt' => $attempt,
        ]); 

        $user->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'User registration deleted',
        ]);
    }

    /**
     * Download or view the uploaded ID document for a user.
     */
    public function viewIdDocument($id)
    {
        $user = \App\Models\User::find($id);

        if (! $user || ! $user->id_document) {
            abort(404);
        }

        [$disk, $path] = $this->findDocument($user->id_document);

        if (!$path) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * View an uploaded scholarship document through an authorized admin route.
     */
    public function viewApplicationDocument($id, string $group, int $index)
    {
        $application = StudentProfile::findOrFail($id);
        $uploads = $application->uploads;

        if (!is_array($uploads) || $index < 0) {
            abort(404);
        }

        $files = $uploads[$group] ?? ($group === 'uploads' && array_is_list($uploads) ? $uploads : null);
        if (!is_array($files) || !isset($files[$index]) || !is_string($files[$index])) {
            abort(404);
        }

        [$disk, $path] = $this->findDocument($files[$index]);
        if (!$path) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    private function findDocument(string $storedPath): array
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $storedPath), '/');
        if ($normalizedPath === '' || str_contains($normalizedPath, '../') || str_contains($normalizedPath, '..\\')) {
            return [null, null];
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($normalizedPath)) {
                return [$disk, $normalizedPath];
            }
        }

        return [null, null];
    }

    /**
     * Keep the applications table in sync with student profile status.
     */
    private function syncApplicationRecord(StudentProfile $profile, string $status): void
    {
        if (!$profile->user_id || !$profile->scholarship_name) {
            return;
        }

        Application::whereKey($profile->application_id)
            ->update(['status' => $status]);
    }

    /**
     * Update student profile
     */
    public function update(Request $request, $id)
    {
        try {
            $application = StudentProfile::findOrFail($id);
            
            $updateData = $request->only([
                'first_name', 'middle_name', 'last_name', 'sex', 'status', 'email',
                'contact_number', 'course', 'year_level', 'scholarship_name',
                'scholarship_type', 'home_address', 'program', 'id_number',
                'birthdate', 'birthplace', 'religion'
            ]);
            
            $application->update($updateData);
            
            return response()->json(['status' => 'ok', 'message' => 'Profile updated successfully', 'data' => $application]);
        } catch (\Exception $e) {
            Log::error('Failed to update application profile', ['application_id' => $id, 'exception' => $e]);
            return response()->json(['status' => 'error', 'message' => 'Unable to update application.'], 500);
        }
    }

    // CHANGE: Create a manually logged applicant without an applications record.
    public function storeManualApplicant(Request $request)
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:academic,non-academic'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', 'in:Male,Female'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'in:1st Year,2nd Year,3rd Year,4th Year'],
            'scholarship_name' => [
                'required',
                'string',
                'max:255',
                Rule::in($this->canonicalScholarshipNames($request->input('grant_type', 'academic'))),
            ],
            'date' => ['required', 'date'],
            'application_status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $profile = StudentProfile::create([
            'source' => 'manual',
            'grant_type' => $validated['grant_type'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'sex' => $validated['sex'],
            'status' => 'Single',
            'email' => 'manual-' . Str::uuid() . '@local.invalid',
            'course' => $validated['course'],
            'campus' => 'Aparri',
            'year_level' => $validated['year_level'],
            'scholarship_name' => $validated['scholarship_name'],
            'scholarship_type' => $validated['grant_type'] === 'academic' ? 'Academic' : 'Non-Academic',
            'application_status' => $validated['application_status'],
        ]);
        $profile->forceFill(['created_at' => $validated['date'] . ' 00:00:00'])->save();

        return response()->json(['status' => 'ok', 'data' => $profile->fresh()]);
    }

    // CHANGE: Update a manually logged applicant without opening Applicant Info.
    public function updateManualApplicant(Request $request, $id)
    {
        $profile = StudentProfile::where('source', 'manual')->findOrFail($id);
        $validated = $request->validate([
            'grant_type' => ['required', 'in:academic,non-academic'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', 'in:Male,Female'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'in:1st Year,2nd Year,3rd Year,4th Year'],
            'scholarship_name' => [
                'required',
                'string',
                'max:255',
                Rule::in($this->canonicalScholarshipNames($request->input('grant_type', $profile->grant_type ?: 'academic'))),
            ],
            'date' => ['required', 'date'],
            'application_status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $profile->fill([
            'grant_type' => $validated['grant_type'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'sex' => $validated['sex'],
            'course' => $validated['course'],
            'campus' => 'Aparri',
            'year_level' => $validated['year_level'],
            'scholarship_name' => $validated['scholarship_name'],
            'scholarship_type' => $validated['grant_type'] === 'academic' ? 'Academic' : 'Non-Academic',
            'application_status' => $validated['application_status'],
        ]);
        $profile->forceFill(['created_at' => $validated['date'] . ' 00:00:00'])->save();

        return response()->json(['status' => 'ok', 'data' => $profile->fresh()]);
    }

    // CHANGE: Delete only manually logged applicants.
    public function destroyManualApplicant($id)
    {
        $profile = StudentProfile::where('source', 'manual')->findOrFail($id);
        $profile->delete();

        return response()->json(['status' => 'ok']);
    }

    // CHANGE: Save an amount for all filtered approved applicants for either grant page in a transaction.
    public function updateScholarshipAmounts(Request $request)
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:academic,non-academic'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'scholarship_name' => ['nullable', 'string'],
            'status' => ['required', 'in:approved'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $updated = DB::transaction(function () use ($validated) {
                $query = StudentProfile::query()
                    ->where('student_profiles.grant_type', $validated['grant_type'])
                    ->whereNull('student_profiles.deleted_at')
                    ->where('student_profiles.application_status', 'approved')
                    ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id');

                $query->when(!empty($validated['date_from']), function ($query) use ($validated) {
                    $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '>=', $validated['date_from']);
                });
                $query->when(!empty($validated['date_to']), function ($query) use ($validated) {
                    $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '<=', $validated['date_to']);
                });
                $query->when(!empty($validated['scholarship_name']), function ($query) use ($validated) {
                    $query->where('student_profiles.scholarship_name', $validated['scholarship_name']);
                });

                $profileIds = $query->pluck('student_profiles.id');

                return StudentProfile::whereIn('id', $profileIds)
                    ->update(['amount' => $validated['amount']]);
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Amount saved for all applicants under this scholarship.',
                'updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update scholarship amounts', [
                'scholarship_name' => $validated['scholarship_name'],
                'exception' => $e,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Unable to save amounts.'], 500);
        }
    }

    /**
     * Delete an application
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $profile = StudentProfile::findOrFail($id);

                if (!is_null($profile->application_id)) {
                    Application::whereKey($profile->application_id)->delete();
                }

                $profile->delete();
            });

            return response()->json(['status' => 'ok', 'message' => 'Application deleted']);
        } catch (\Exception $e) {
            Log::error('Failed to delete application', ['application_id' => $id, 'exception' => $e]);
            return response()->json(['status' => 'error', 'message' => 'Unable to delete application.'], 500);
        }
    }
}
