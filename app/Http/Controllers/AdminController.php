<?php

namespace App\Http\Controllers;

use App\Exports\ApprovedGranteesExport;
use App\Mail\ApplicationClosureMail;
use App\Mail\ApplicationCorrectionMail;
use App\Mail\ApplicationStatusMail;
use App\Mail\ApprovalMail;
use App\Mail\RenewalRequirementMail;
use App\Mail\RejectionMail;
use App\Models\Application;
use App\Models\FamilyBackground;
use App\Models\Scholarship;
use App\Models\StudentNotification;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\CourseCollegeDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Display main admin dashboard with all applications
     */
    public function dashboard(Request $request)
    {
        $scholarshipType = $this->summaryScholarshipType($request);
        $recentApplications = $this->summaryApplicationsQuery($request)->paginate(10)->withQueryString();
        $scholarshipNames = Scholarship::whereIn('type', ['academic', 'non-academic'])
            ->orderBy('name')
            ->get(['name', 'type']);
        $filterType = $request->query('section') === 'reports' ? 'all' : 'dashboard';
        $needsAttention = $filterType === 'dashboard'
            ? $this->dashboardNeedsAttention($request->query('na_page', 1))
            : [
                'pendingApplications' => [],
                'pendingApplicationsTotal' => 0,
                'scholarshipsClosingSoon' => [],
                'scholarshipsClosingSoonTotal' => 0,
                'nearCapacityScholarships' => [],
                'nearCapacityScholarshipsTotal' => 0,
                'needsAttentionPage' => 1,
                'needsAttentionTotalPages' => 1,
            ];
        $pendingApplications = $needsAttention['pendingApplications'];
        $pendingApplicationsTotal = $needsAttention['pendingApplicationsTotal'];
        $scholarshipsClosingSoon = $needsAttention['scholarshipsClosingSoon'];
        $scholarshipsClosingSoonTotal = $needsAttention['scholarshipsClosingSoonTotal'];
        $nearCapacityScholarships = $needsAttention['nearCapacityScholarships'];
        $nearCapacityScholarshipsTotal = $needsAttention['nearCapacityScholarshipsTotal'];
        $needsAttentionPage = $needsAttention['needsAttentionPage'];
        $needsAttentionTotalPages = $needsAttention['needsAttentionTotalPages'];

        return view('admin.welcome', compact(
            'recentApplications',
            'scholarshipNames',
            'filterType',
            'scholarshipType',
            'pendingApplications',
            'pendingApplicationsTotal',
            'scholarshipsClosingSoon',
            'scholarshipsClosingSoonTotal',
            'nearCapacityScholarships',
            'nearCapacityScholarshipsTotal',
            'needsAttentionPage',
            'needsAttentionTotalPages'
        ));
    }

    public function needsAttention(Request $request)
    {
        $needsAttention = $this->dashboardNeedsAttention($request->query('na_page', 1));

        return response()->json([
            'html' => view('admin.partials.needs-attention-content', $needsAttention)->render(),
            'page' => $needsAttention['needsAttentionPage'],
            'totalPages' => $needsAttention['needsAttentionTotalPages'],
        ]);
    }

    private function dashboardNeedsAttention(mixed $requestedPage): array
    {
        $today = now()->startOfDay();
        $pendingQuery = StudentProfile::query()
            ->whereNull('deleted_at')
            ->where('application_status', 'pending')
            ->where('created_at', '<=', now()->subDays(4)->startOfDay());
        $closingSoonQuery = Scholarship::query()
            ->whereNotNull('application_end_date')
            ->whereDate('application_end_date', '>=', $today)
            ->whereDate('application_end_date', '<=', $today->copy()->addDays(7));
        $nearCapacityQuery = $this->dashboardNearCapacityQuery();

        $pendingTotal = (clone $pendingQuery)->count();
        $closingSoonTotal = (clone $closingSoonQuery)->count();
        $nearCapacityTotal = DB::query()
            ->fromSub(clone $nearCapacityQuery, 'qualifying_near_capacity')
            ->count();

        $pendingPages = (int) ceil($pendingTotal / 4);
        $closingSoonPages = (int) ceil($closingSoonTotal / 5);
        $nearCapacityPages = (int) ceil($nearCapacityTotal / 4);
        $needsAttentionTotalPages = max(1, $pendingPages, $closingSoonPages, $nearCapacityPages);
        $parsedPage = filter_var($requestedPage, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $needsAttentionPage = min($parsedPage === false ? 1 : $parsedPage, $needsAttentionTotalPages);
        $offset = ($needsAttentionPage - 1);

        $pendingApplications = (clone $pendingQuery)
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'scholarship_name', 'created_at'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->offset($offset * 4)
            ->limit(4)
            ->get()
            ->map(function (StudentProfile $application) {
                $daysPending = now()->startOfDay()->diffInDays($application->created_at->startOfDay(), true);

                return [
                    'id' => $application->id,
                    'full_name' => trim(implode(', ', array_filter([
                        $application->last_name,
                        $application->middle_name,
                        $application->first_name,
                        $application->suffix,
                    ], static fn ($value) => $value !== null && trim((string) $value) !== ''))),
                    'scholarship_name' => $application->scholarship_name,
                    'days_pending' => $daysPending,
                    'urgency' => $daysPending >= 8 ? 'urgent' : 'soon',
                ];
            })
            ->all();

        $scholarshipsClosingSoon = (clone $closingSoonQuery)
            ->select(['id', 'name', 'application_end_date'])
            ->orderBy('application_end_date', 'asc')
            ->orderBy('id', 'asc')
            ->offset($offset * 5)
            ->limit(5)
            ->get()
            ->map(function (Scholarship $scholarship) use ($today) {
                $daysRemaining = $today->diffInDays($scholarship->application_end_date->startOfDay(), false);

                return [
                    'id' => $scholarship->id,
                    'name' => $scholarship->name,
                    'days_remaining' => max(0, $daysRemaining),
                    'urgency' => $daysRemaining <= 3 ? 'urgent' : 'soon',
                ];
            })
            ->all();

        $nearCapacityScholarships = (clone $nearCapacityQuery)
            ->orderByRaw('CASE WHEN near_active_applications.active_applications * 100 >= near_scholarships.max_slots * 100 THEN 100 ELSE near_active_applications.active_applications * 100.0 / near_scholarships.max_slots END DESC')
            ->orderBy('near_scholarships.name', 'asc')
            ->offset($offset * 4)
            ->limit(4)
            ->get()
            ->map(function ($scholarship) {
                $percentage = min(100, ($scholarship->active_applications / $scholarship->max_slots) * 100);

                return [
                    'id' => $scholarship->id,
                    'name' => $scholarship->name,
                    'active_applications' => (int) $scholarship->active_applications,
                    'max_slots' => (int) $scholarship->max_slots,
                    'percentage' => $percentage,
                    'urgency' => $percentage >= 95 ? 'urgent' : 'soon',
                ];
            })
            ->all();

        return [
            'pendingApplications' => $pendingApplications,
            'pendingApplicationsTotal' => $pendingTotal,
            'scholarshipsClosingSoon' => $scholarshipsClosingSoon,
            'scholarshipsClosingSoonTotal' => $closingSoonTotal,
            'nearCapacityScholarships' => $nearCapacityScholarships,
            'nearCapacityScholarshipsTotal' => $nearCapacityTotal,
            'needsAttentionPage' => $needsAttentionPage,
            'needsAttentionTotalPages' => $needsAttentionTotalPages,
        ];
    }

    private function dashboardNearCapacityQuery()
    {
        $now = now();
        $cutoff = \Carbon\Carbon::parse(config('scholarships.slot_counting_starts_at'))
            ->setTimezone(config('app.timezone'));
        $activeApplications = DB::table('applications as eligible_applications')
            ->where('eligible_applications.created_at', '>', $cutoff)
            ->whereExists(function ($profiles) {
                $profiles->selectRaw('1')
                    ->from('student_profiles as unlocked_profiles')
                    ->whereColumn('unlocked_profiles.application_id', 'eligible_applications.id')
                    ->whereNull('unlocked_profiles.deleted_at')
                    ->whereNull('unlocked_profiles.locked_at');
            })
            ->where(function ($statuses) use ($now) {
                $statuses->whereIn('eligible_applications.status', ['pending', 'approved'])
                    ->orWhere(function ($rejected) use ($now) {
                        $rejected->where('eligible_applications.status', 'rejected')
                            ->whereExists(function ($profiles) use ($now) {
                                $profiles->selectRaw('1')
                                    ->from('student_profiles as eligible_profiles')
                                    ->whereColumn('eligible_profiles.application_id', 'eligible_applications.id')
                                    ->whereNull('eligible_profiles.deleted_at')
                                    ->whereIn('eligible_profiles.rejection_reason', ['Wrong Uploaded Documents', 'Wrong Form'])
                                    ->whereNotNull('eligible_profiles.resubmission_deadline')
                                    ->where('eligible_profiles.resubmission_deadline', '>', $now);
                            });
                    });
            })
            ->groupBy('eligible_applications.scholarship_id')
            ->select('eligible_applications.scholarship_id')
            ->selectRaw('COUNT(*) as active_applications');

        return DB::table('scholarships as near_scholarships')
            ->leftJoinSub($activeApplications, 'near_active_applications', function ($join) {
                $join->on('near_active_applications.scholarship_id', '=', 'near_scholarships.id');
            })
            ->whereNotNull('near_scholarships.max_slots')
            ->where('near_scholarships.max_slots', '>', 0)
            ->whereRaw('COALESCE(near_active_applications.active_applications, 0) * 100 >= near_scholarships.max_slots * 85')
            ->select([
                'near_scholarships.id',
                'near_scholarships.name',
                'near_scholarships.max_slots',
            ])
            ->selectRaw('COALESCE(near_active_applications.active_applications, 0) as active_applications');
    }

    public function exportApprovedGrantees(Request $request)
    {
        $filename = 'approved_grantees_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ApprovedGranteesExport($this->summaryApplicationsQuery($request)),
            $filename
        );
    }

    private function summaryScholarshipType(Request $request): string
    {
        $scholarshipType = (string) $request->query('grant_type', '');

        return in_array($scholarshipType, ['academic', 'non-academic'], true) ? $scholarshipType : '';
    }

    private function summaryApplicationsQuery(Request $request)
    {
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $scholarshipType = $this->summaryScholarshipType($request);
        $scholarshipName = trim((string) $request->query('scholarship_name', ''));

        return StudentProfile::whereNull('student_profiles.deleted_at')
            ->where(function ($query) {
                $query->where('student_profiles.application_status', 'approved')
                    ->orWhere('student_profiles.renewal_required', true)
                    ->orWhere('student_profiles.renewal_resubmitted', true);
            })
            ->when($scholarshipType !== '', function ($query) use ($scholarshipType) {
                $query->where('student_profiles.grant_type', $scholarshipType);
            })
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '<=', $dateTo);
            })
            ->when($scholarshipName !== '', function ($query) use ($scholarshipName) {
                $query->where('student_profiles.scholarship_name', $scholarshipName);
            })
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id')
            ->leftJoin('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
            ->leftJoin('scholarships as profile_scholarships', function ($join) {
                $join->on('profile_scholarships.name', '=', 'student_profiles.scholarship_name')
                    ->on('profile_scholarships.type', '=', 'student_profiles.grant_type');
            })
            ->orderByDesc(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'))
            ->orderByDesc('applications.id')
            ->orderByDesc('student_profiles.id')
            ->select(
                'student_profiles.*',
                'student_profiles.grant_type as scholarship_category_type',
                DB::raw('COALESCE(scholarships.funding_type, profile_scholarships.funding_type) as scholarship_funding_type')
            );
    }

    /**
     * Display admin dashboard filtered by academic grants
     */
    public function academicDashboard(Request $request)
    {
        $recentApplications = $this->filteredApplications($request, 'academic');
        $scholarshipNames = $this->scholarshipNames('academic');
        $academicScholarshipCatalog = Scholarship::where('type', 'academic')
            ->orderBy('name')
            ->get(['name', 'funding_type'])
            ->map(fn (Scholarship $scholarship) => [
                'name' => $scholarship->name,
                'funding_type' => $scholarship->funding_type,
            ])
            ->values()
            ->all();
        $filterType = 'academic';

        return view('admin.welcome', compact('recentApplications', 'scholarshipNames', 'academicScholarshipCatalog', 'filterType'));
    }

    /**
     * Display admin dashboard filtered by non-academic grants
     */
    public function nonAcademicDashboard(Request $request)
    {
        $recentApplications = $this->filteredApplications($request, 'non-academic');
        $scholarshipNames = $this->scholarshipNames('non-academic');
        $nonAcademicScholarshipCatalog = Scholarship::where('type', 'non-academic')
            ->orderBy('name')
            ->get(['name', 'funding_type'])
            ->map(fn (Scholarship $scholarship) => [
                'name' => $scholarship->name,
                'funding_type' => $scholarship->funding_type,
            ])
            ->values()
            ->all();
        $filterType = 'non-academic';

        return view('admin.welcome', compact('recentApplications', 'scholarshipNames', 'nonAcademicScholarshipCatalog', 'filterType'));
    }

    private function filteredApplications(Request $request, string $grantType)
    {
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $scholarshipName = trim((string) $request->query('scholarship_name', ''));
        $studentName = trim((string) $request->query('student_name', ''));
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
            ->when($studentName !== '', function ($query) use ($studentName) {
                $searchTerm = strtolower($studentName);
                $query->where(function ($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(COALESCE(student_profiles.first_name, "")) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(COALESCE(student_profiles.last_name, "")) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(COALESCE(student_profiles.middle_name, "")) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(CONCAT(COALESCE(student_profiles.first_name, ""), " ", COALESCE(student_profiles.last_name, ""))) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(CONCAT(COALESCE(student_profiles.last_name, ""), " ", COALESCE(student_profiles.first_name, ""))) LIKE ?', ['%' . $searchTerm . '%']);
                });
                if ($status === 'approved') {
                    $query->where(function ($approvedQuery) {
                        $approvedQuery->where('student_profiles.application_status', 'approved')
                            ->orWhere('student_profiles.renewal_required', true)
                            ->orWhere('student_profiles.renewal_resubmitted', true);
                    });
                } else {
                    $query->where('student_profiles.application_status', $status);
                }
            })
            ->where(function ($query) {
                $query->where('student_profiles.application_status', '!=', 'pending')
                    ->orWhere(function ($pendingQuery) {
                        $pendingQuery->where('student_profiles.renewal_required', false)
                            ->where('student_profiles.renewal_resubmitted', false);
                    });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('student_profiles.application_status', $status);
            })
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id')
            ->leftJoin('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
            ->leftJoin('scholarships as profile_scholarships', function ($join) {
                $join->on('profile_scholarships.name', '=', 'student_profiles.scholarship_name')
                    ->on('profile_scholarships.type', '=', 'student_profiles.grant_type');
            })
            ->select(
                'student_profiles.*',
                'scholarships.type as scholarship_category_type',
                DB::raw('COALESCE(scholarships.funding_type, profile_scholarships.funding_type) as scholarship_funding_type')
            );

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

        if ($message = $this->applicationDecisionUnavailableMessage($application)) {
            return response()->json(['status' => 'error', 'message' => $message], 409);
        }

        $transitionedToApproved = $application->application_status !== 'approved';
        $statuses = [
            'student_information_status' => 'approved',
            'student_information_reason' => null,
            'personal_information_status' => 'approved',
            'personal_information_reason' => null,
            'scholarship_requirements_status' => 'approved',
            'renewal_required' => false,
            'renewal_resubmitted' => false,
            'scholarship_requirements_reason' => null,
            'application_status' => 'approved',
            'rejection_reason' => null,
        ];
        if ($application->grant_type === 'non-academic') {
            $statuses['family_background_status'] = 'approved';
            $statuses['family_background_reason'] = null;
        }

        $application->forceFill($statuses)->save();
        $this->syncApplicationRecord($application, 'approved');

        if ($transitionedToApproved) {
            $this->queueApplicationApprovalNotification($application);
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
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if ($message = $this->applicationDecisionUnavailableMessage($application)) {
            return response()->json(['status' => 'error', 'message' => $message], 409);
        }

        $correctionReasons = $application->renewal_required || $application->renewal_resubmitted
            ? ['Wrong Uploaded Documents']
            : ['Wrong Uploaded Documents', 'Wrong Form'];
        $validated = $request->validate([
            'reason' => ['required', Rule::in([
                'Does not meet the eligibility requirements',
                ...$correctionReasons,
            ])],
            'rejection_remark' => ['nullable', 'string', 'max:2000'],
        ]);
        $reason = $validated['reason'];
        $isCorrectionRejection = in_array($reason, $correctionReasons, true);
        $rejectionRemark = trim((string) ($validated['rejection_remark'] ?? ''));
        if ($isCorrectionRejection && $rejectionRemark === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Enter a remark describing what needs to be corrected.',
            ], 422);
        }
        $eligibilityRejection = $reason === 'Does not meet the eligibility requirements';
        $shouldLock = $application->source !== 'manual'
            && ($eligibilityRejection || (int) $application->reapply_count >= 1);
        $hasResubmissionWindow = $isCorrectionRejection && !$shouldLock && $application->source !== 'manual';
        $resubmissionDeadline = $hasResubmissionWindow
            ? now()->addDay()
            : null;
        $transitionedToRejected = $application->application_status !== 'rejected';
        $statuses = [
            'student_information_status' => 'rejected',
            'student_information_reason' => null,
            'personal_information_status' => 'rejected',
            'personal_information_reason' => null,
            'scholarship_requirements_status' => 'rejected',
            'scholarship_requirements_reason' => $reason,
            'application_status' => 'rejected',
            'rejection_reason' => $reason,
            'rejection_remark' => $isCorrectionRejection ? $rejectionRemark : null,
            'resubmission_deadline' => $resubmissionDeadline,
        ];
        if ($application->grant_type === 'non-academic') {
            $statuses['family_background_status'] = 'rejected';
            $statuses['family_background_reason'] = null;
        }
        if ($shouldLock && !$application->locked_at) {
            $statuses['locked_at'] = now();
        }

        if ($application->renewal_required || $application->renewal_resubmitted) {
            $statuses['renewal_required'] = false;
            $statuses['renewal_resubmitted'] = false;
        }

        $application->forceFill($statuses)->save();
        $this->syncApplicationRecord($application, 'rejected');

        if ($application->user_id && $application->source !== 'manual') {
            try {
                $user = $application->user;
                if ($user?->email) {
                    $studentName = $user->name ?? trim($application->first_name . ' ' . $application->last_name);
                    $scholarshipName = $application->scholarship_name ?? 'Scholarship';
                    $mail = $isCorrectionRejection && $shouldLock
                        ? new ApplicationClosureMail($studentName, $scholarshipName, $rejectionRemark)
                        : ($hasResubmissionWindow
                            ? new ApplicationCorrectionMail($studentName, $scholarshipName, $rejectionRemark, $resubmissionDeadline)
                            : new ApplicationStatusMail(
                            $studentName,
                            $scholarshipName,
                            'rejected',
                            $reason,
                            $isCorrectionRejection ? $rejectionRemark : null,
                            ));
                    Mail::to($user->email)->queue($mail);
                    $application->recordNotification($mail->notificationSubject(), $mail->notificationBody());

                    if ($transitionedToRejected) {
                        $this->createStudentDecisionNotification($user, $application, 'rejected', $reason, $rejectionRemark);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Application rejection notification failed', [
                    'application_id' => $application->id,
                    'exception' => $e,
                ]);
            }
        }

        return response()->json([
            'status' => 'ok',
            'message' => $shouldLock && !$eligibilityRejection
                ? 'Application rejected and scholarship locked'
                : 'Application rejected',
            'locked' => $shouldLock,
            'transitioned' => $transitionedToRejected,
        ]);
    }

    private function queueApplicationApprovalNotification(StudentProfile $application): void
    {
        if (!$application->user_id || $application->source === 'manual') {
            return;
        }

        try {
            $user = $application->user()->first();
            if (!$user?->email) {
                return;
            }

            $mail = new ApplicationStatusMail(
                $user->name ?? trim($application->first_name . ' ' . $application->last_name),
                $application->scholarship_name ?? 'Scholarship',
                'approved'
            );
            Mail::to($user->email)->queue($mail);
            $application->recordNotification($mail->notificationSubject(), $mail->notificationBody());

            $this->createStudentDecisionNotification($user, $application, 'approved');
        } catch (\Throwable $e) {
            Log::error('Application approval notification failed', [
                'application_id' => $application->id,
                'exception' => $e,
            ]);
        }
    }

    private function createStudentDecisionNotification(User $user, StudentProfile $application, string $decision, ?string $reason = null, ?string $remark = null): void
    {
        $scholarshipName = $application->scholarship_name ?? 'Scholarship';

        if ($decision === 'approved') {
            $title = 'Scholarship Application Approved';
            $message = 'Congratulations! Your application for ' . $scholarshipName . ' has been approved by the Scholarship Administrator. Please check your Application Status page for further information.';
        } else {
            $isCorrection = in_array($reason, ['Wrong Uploaded Documents', 'Wrong Form'], true);
            $title = $isCorrection
                ? 'Scholarship Application Requires Correction'
                : 'Scholarship Application Rejected';
            $message = $isCorrection
                ? ($application->resubmission_deadline
                    ? 'Your application for ' . $scholarshipName . ' needs corrections. What to fix: ' . $remark
                        . ' Deadline: ' . $application->resubmission_deadline->format('F j, Y g:i A') . '.'
                    : 'Your application for ' . $scholarshipName . ' has been closed after its final resubmission. Reason for closure: ' . $remark)
                : 'Your application for ' . $scholarshipName . ' has been rejected by the Scholarship Administrator. Please review the rejection reason and any instructions provided in your Application Status page.';
        }

        StudentNotification::createForApplication(
            $user,
            (int) $application->id,
            $scholarshipName,
            $decision,
            $message,
            $reason
        );
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

    private function applicationDecisionUnavailableMessage(StudentProfile $application): ?string
    {
        if ($application->locked_at) {
            return 'This application is locked and can no longer be approved or rejected.';
        }

        if ($application->application_status === 'approved') {
            return 'This application has already been approved.';
        }

        if ($application->application_status === 'rejected') {
            return 'This application is awaiting student resubmission before another decision can be made.';
        }

        if ($application->application_status !== 'pending') {
            return 'This application is not awaiting a decision.';
        }

        return null;
    }

    public function removeRestrictions(Request $request)
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:academic,non-academic,all'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'scholarship_name' => ['nullable', 'string'],
            'preview' => ['nullable', 'boolean'],
        ]);

        $query = StudentProfile::query()
            ->whereNull('student_profiles.deleted_at')
            ->where('student_profiles.application_status', 'approved')
            ->when($validated['grant_type'] !== 'all', fn ($query) => $query->where('student_profiles.grant_type', $validated['grant_type']))
            ->when(!empty($validated['date_from']), fn ($query) => $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '>=', $validated['date_from']))
            ->when(!empty($validated['date_to']), fn ($query) => $query->whereDate(DB::raw('COALESCE(applications.created_at, student_profiles.created_at)'), '<=', $validated['date_to']))
            ->when(!empty($validated['scholarship_name']), fn ($query) => $query->where('student_profiles.scholarship_name', $validated['scholarship_name']))
            ->leftJoin('applications', 'applications.id', '=', 'student_profiles.application_id');

        $profileIds = $query->pluck('student_profiles.id');
        if (!empty($validated['preview'])) {
            return response()->json([
                'status' => 'ok',
                'updated' => $profileIds->count(),
            ]);
        }

        $profiles = StudentProfile::whereIn('id', $profileIds)
            ->with('user')
            ->get();

        $updated = 0;
        $emailFailures = [];

        foreach ($profiles as $profile) {
            $profile->forceFill([
                'application_status' => 'pending',
                'student_information_status' => $profile->student_information_status === 'approved' ? 'approved' : 'pending',
                'personal_information_status' => $profile->personal_information_status === 'approved' ? 'approved' : 'pending',
                'scholarship_requirements_status' => 'pending',
                'scholarship_requirements_reason' => null,
                'family_background_status' => $profile->family_background_status ?? 'pending',
                'family_background_reason' => $profile->family_background_reason,
                'rejection_reason' => null,
                'rejection_remark' => null,
                'resubmission_deadline' => null,
                'renewal_required' => true,
                'renewal_resubmitted' => false,
            ])->save();

            if ($profile->application_id) {
                Application::whereKey($profile->application_id)->update(['status' => 'pending']);
            }

            $updated++;

            if (!$profile->user || !$profile->user->email || $profile->source === 'manual') {
                continue;
            }

            try {
                $mail = new RenewalRequirementMail(
                    $profile->user->name ?: trim($profile->first_name . ' ' . $profile->last_name),
                    $profile->scholarship_name ?? 'Scholarship',
                );
                Mail::to($profile->user->email)->queue($mail);
                $profile->recordNotification($mail->notificationSubject(), $mail->notificationBody());
            } catch (\Throwable $e) {
                Log::error('Bulk renewal notification failed', [
                    'profile_id' => $profile->id,
                    'user_id' => $profile->user->id,
                    'exception' => $e,
                ]);
                $emailFailures[] = [
                    'application_id' => $profile->id,
                    'student' => $profile->user->name ?: trim($profile->first_name . ' ' . $profile->last_name),
                    'email' => $profile->user->email,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'ok',
            'updated' => $updated,
            'email_failures' => $emailFailures,
        ]);
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
            'suffix' => ['nullable', 'string', 'max:50'],
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
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'],
            'status' => 'Single',
            'email' => 'manual-' . Str::uuid() . '@local.invalid',
            'course' => $validated['course'],
            'college_department' => CourseCollegeDepartment::forCourse($validated['course']),
            'campus' => 'Aparri',
            'year_level' => $validated['year_level'],
            'scholarship_name' => $validated['scholarship_name'],
            'scholarship_type' => $validated['grant_type'] === 'academic' ? 'Academic' : 'Non-Academic',
            'application_status' => $validated['application_status'],
        ]);
        $profile->forceFill(['created_at' => $validated['date'] . ' 00:00:00'])->save();

        return response()->json(['status' => 'ok', 'data' => $profile->fresh()]);
    }

    public function academicManualApplicantCollegeDepartment(Request $request)
    {
        $validated = $request->validate([
            'course' => ['required', Rule::in(CourseCollegeDepartment::courses())],
        ]);

        return response()->json([
            'college_department' => CourseCollegeDepartment::forCourse($validated['course']),
        ]);
    }

    public function storeAcademicManualApplicant(Request $request)
    {
        return $this->storeStructuredManualApplicant($request, 'academic');
    }

    public function storeNonAcademicManualApplicant(Request $request)
    {
        return $this->storeStructuredManualApplicant($request, 'non-academic');
    }

    private function storeStructuredManualApplicant(Request $request, string $grantType)
    {
        $validated = $request->validate($this->structuredManualApplicantRules($grantType));
        $profile = StudentProfile::create([
            'source' => 'manual',
            'grant_type' => $grantType,
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'],
            'status' => 'Single',
            'email' => 'manual-' . Str::uuid() . '@local.invalid',
            'course' => $validated['course'],
            'college_department' => CourseCollegeDepartment::forCourse($validated['course']),
            'campus' => 'Aparri',
            'year_level' => $validated['year_level'],
            'scholarship_name' => $validated['scholarship_name'],
            'scholarship_type' => $grantType === 'academic' ? 'Academic' : 'Non-Academic',
            'application_status' => $validated['application_status'],
        ]);
        $profile->forceFill(['created_at' => $validated['date'] . ' 00:00:00'])->save();

        return response()->json(['status' => 'ok', 'data' => $profile->fresh()]);
    }

    private function structuredManualApplicantRules(string $grantType): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'course' => ['required', Rule::in(CourseCollegeDepartment::courses())],
            'year_level' => ['required', Rule::in(['1st Year', '2nd Year', '3rd Year', '4th Year'])],
            'scholarship_name' => ['required', 'string', Rule::in($this->canonicalScholarshipNames($grantType))],
            'date' => ['required', 'date'],
            'application_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    public function updateAcademicManualApplicant(Request $request, $id)
    {
        return $this->updateStructuredManualApplicant($request, $id, 'academic');
    }

    public function updateNonAcademicManualApplicant(Request $request, $id)
    {
        return $this->updateStructuredManualApplicant($request, $id, 'non-academic');
    }

    private function updateStructuredManualApplicant(Request $request, $id, string $grantType)
    {
        $profile = StudentProfile::where('source', 'manual')
            ->where('grant_type', $grantType)
            ->findOrFail($id);
        $validated = $request->validate($this->structuredManualApplicantRules($grantType));

        $profile->fill([
            'grant_type' => $grantType,
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'],
            'course' => $validated['course'],
            'college_department' => CourseCollegeDepartment::forCourse($validated['course']),
            'campus' => 'Aparri',
            'year_level' => $validated['year_level'],
            'scholarship_name' => $validated['scholarship_name'],
            'scholarship_type' => $grantType === 'academic' ? 'Academic' : 'Non-Academic',
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
            'suffix' => ['nullable', 'string', 'max:50'],
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
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'],
            'course' => $validated['course'],
            'college_department' => CourseCollegeDepartment::forCourse($validated['course']),
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
            'grant_type' => ['required', 'in:academic,non-academic,all'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'scholarship_name' => ['nullable', 'string'],
            'status' => ['required', 'in:approved'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $updated = DB::transaction(function () use ($validated) {
                $query = StudentProfile::query()
                    ->when($validated['grant_type'] !== 'all', function ($query) use ($validated) {
                        $query->where('student_profiles.grant_type', $validated['grant_type']);
                    })
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

    public function destroyFinalizedRejected(Request $request, $id)
    {
        $profile = StudentProfile::withTrashed()->whereKey($id)->first();

        if (!$profile) {
            return response()->json(['status' => 'error', 'message' => 'Application not found.'], 404);
        }

        if (!$profile->isEligibleForPermanentDeletion()) {
            Log::warning('Blocked permanent application deletion for ineligible profile', [
                'admin_id' => $request->user()?->id,
                'student_profile_id' => $profile->id,
                'student_id' => $profile->user_id,
                'application_status' => $profile->application_status,
                'locked_at' => $profile->locked_at,
                'resubmission_deadline' => $profile->resubmission_deadline,
                'source' => $profile->source,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'This application is not eligible for permanent deletion.',
            ], 422);
        }

        $studentId = $profile->user_id;
        $scholarshipId = null;
        $scholarshipName = $profile->scholarship_name ?? 'Scholarship';
        if (!empty($profile->application_id)) {
            $application = Application::whereKey($profile->application_id)->first();
            $scholarshipId = $application?->scholarship_id;
            $scholarshipName = $application?->scholarship?->name ?? $profile->scholarship_name ?? 'Scholarship';
        }

        $filePaths = [];
        $uploads = $profile->uploads ?? [];
        if (is_array($uploads)) {
            foreach ($uploads as $groupFiles) {
                if (is_array($groupFiles)) {
                    foreach ($groupFiles as $file) {
                        if (is_string($file) && trim($file) !== '') {
                            $filePaths[] = $file;
                        }
                    }
                } elseif (is_string($groupFiles) && trim($groupFiles) !== '') {
                    $filePaths[] = $groupFiles;
                }
            }
        }

        try {
            DB::transaction(function () use ($profile, $studentId, $scholarshipId, $scholarshipName) {
                $lockedProfile = StudentProfile::withTrashed()->whereKey($profile->id)->lockForUpdate()->firstOrFail();

                if (!$lockedProfile->isEligibleForPermanentDeletion()) {
                    throw new \RuntimeException('This application is not eligible for permanent deletion.');
                }

                FamilyBackground::where('student_profile_id', $lockedProfile->id)->delete();
                StudentNotification::where(function ($query) use ($lockedProfile) {
                    $query->where('application_id', $lockedProfile->id)
                        ->orWhere('application_id', $lockedProfile->application_id);
                })->where('user_id', $lockedProfile->user_id)->delete();

                if (!is_null($lockedProfile->application_id)) {
                    Application::whereKey($lockedProfile->application_id)->delete();
                }

                $lockedProfile->forceDelete();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to permanently delete finalized application', [
                'admin_id' => $request->user()?->id,
                'student_profile_id' => $id,
                'student_id' => $studentId,
                'scholarship_id' => $scholarshipId,
                'scholarship_name' => $scholarshipName,
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Unable to permanently delete application.',
            ], 500);
        }

        foreach (array_unique($filePaths) as $filePath) {
            $deletedFile = false;

            foreach (['local', 'public'] as $disk) {
                try {
                    if (Storage::disk($disk)->exists($filePath)) {
                        Storage::disk($disk)->delete($filePath);
                        $deletedFile = true;
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Permanent deletion file removal failed', [
                        'file_path' => $filePath,
                        'disk' => $disk,
                        'student_profile_id' => $id,
                        'exception' => $e,
                    ]);
                }
            }

            if (!$deletedFile) {
                Log::warning('Permanent application deletion skipped missing file', [
                    'student_profile_id' => $id,
                    'file_path' => $filePath,
                ]);
            }
        }

        Log::info('Permanent application deletion executed', [
            'admin_id' => $request->user()?->id,
            'student_id' => $studentId,
            'scholarship_id' => $scholarshipId,
            'scholarship_name' => $scholarshipName,
            'deleted_at' => now()->toDateTimeString(),
        ]);

        return response()->json(['status' => 'ok', 'message' => 'Application permanently deleted.']);
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
