<?php

namespace App\Http\Controllers;

use App\Mail\IdentityVerificationApprovedMail;
use App\Models\IdentityVerification;
use App\Models\Scholarship;
use App\Models\User;
use App\Services\DiditVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class DiditVerificationController extends Controller
{
    public function __construct(protected DiditVerificationService $diditService)
    {
    }

    public function start(Request $request, Scholarship $scholarship)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $existingVerification = IdentityVerification::query()
            ->where('user_id', $user->id)
            ->where('scholarship_id', $scholarship->id)
            ->latest('updated_at')
            ->first();

        if ($existingVerification && $existingVerification->status === 'Approved') {
            return redirect()->route('didit.continue', $scholarship);
        }

        $eligibilityError = $this->checkEligibility($user, $scholarship);
        if ($eligibilityError) {
            return redirect()->route('dashboard', ['section' => $scholarship->type === 'academic' ? 'acad' : 'non'])
                ->with('error', $eligibilityError);
        }

        try {
            $result = $this->diditService->createSession($user, $scholarship);
        } catch (\Throwable $e) {
            Log::error('Didit verification session creation failed', [
                'user_id' => $user->id,
                'scholarship_id' => $scholarship->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard', ['section' => $scholarship->type === 'academic' ? 'acad' : 'non'])
                ->with('error', 'Identity verification is temporarily unavailable. Please try again in a moment.');
        }

        $verification = IdentityVerification::updateOrCreate(
            [
                'user_id' => $user->id,
                'scholarship_id' => $scholarship->id,
            ],
            [
                'provider' => 'didit',
                'provider_session_id' => $result['session_id'],
                'status' => 'Not Finished',
                'session_url' => $result['url'],
                'failure_reason' => null,
                'verified_at' => null,
                'meta' => $result['raw'],
            ]
        );

        return redirect()->away($result['url']);
    }

    public function status(Request $request, Scholarship $scholarship)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $verification = IdentityVerification::query()
            ->where('user_id', $user->id)
            ->where('scholarship_id', $scholarship->id)
            ->latest('updated_at')
            ->first();

        return view('student.identity-verification', compact('scholarship', 'verification'));
    }

    public function continueToApplication(Request $request, Scholarship $scholarship)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $verification = IdentityVerification::query()
            ->where('user_id', $user->id)
            ->where('scholarship_id', $scholarship->id)
            ->latest('updated_at')
            ->first();

        if (! $verification || $verification->status !== 'Approved') {
            return redirect()->route('didit.status', $scholarship)
                ->with('error', 'Your identity verification is still pending or has not been approved yet.');
        }

        return redirect()->route('dashboard', [
            'section' => $scholarship->type === 'academic' ? 'acad' : 'non',
            'show_application' => 1,
            'scholarship_id' => $scholarship->id,
        ]);
    }

    public function webhook(Request $request): Response
    {
        if (! $this->diditService->verifyWebhookSignature($request)) {
            Log::warning('Didit webhook signature verification failed.', [
                'ip' => $request->ip(),
                'headers' => [
                    'x-signature-v2' => $request->header('X-Signature-V2', $request->header('x-signature-v2')),
                ],
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $sessionId = $payload['session_id'] ?? $payload['id'] ?? null;
        $status = $payload['status'] ?? null;

        if (empty($sessionId) || empty($status)) {
            return response()->json(['message' => 'Missing required Didit payload fields.'], 422);
        }

        $verification = IdentityVerification::query()->where('provider_session_id', $sessionId)->first();

        if (! $verification) {
            $vendorData = $payload['vendor_data'] ?? null;
            $userId = is_numeric((string) $vendorData) ? (int) $vendorData : null;

            if ($userId && User::query()->whereKey($userId)->exists()) {
                $verification = IdentityVerification::query()
                    ->where('user_id', $userId)
                    ->latest('updated_at')
                    ->first();
            }
        }

        if (! $verification) {
            $vendorData = $payload['vendor_data'] ?? null;
            $userId = is_numeric((string) $vendorData) ? (int) $vendorData : null;

            if (! $userId || ! User::query()->whereKey($userId)->exists()) {
                Log::info('Didit webhook received without a matching application user.', [
                    'provider_session_id' => $sessionId,
                    'status' => $status,
                ]);

                return response()->json(['status' => 'ok', 'message' => 'Didit webhook acknowledged.']);
            }

            $verification = IdentityVerification::create([
                'user_id' => $userId,
                'provider' => 'didit',
                'provider_session_id' => $sessionId,
                'status' => $status,
                'meta' => $payload,
            ]);
        }

        $previousStatus = $verification->status;
        $verification->provider_session_id = $sessionId;
        $verification->status = $status;
        $verification->failure_reason = null;
        $verification->meta = array_merge($verification->meta ?? [], $payload);

        if ($status === 'Approved') {
            $verification->verified_at = now();
            $verification->failure_reason = null;
        } elseif ($status === 'Declined' || $status === 'Expired' || $status === 'Not Finished' || $status === 'In Review') {
            $verification->verified_at = null;
            $verification->failure_reason = $payload['reason'] ?? $payload['message'] ?? $payload['failure_reason'] ?? null;
        }

        $verification->save();

        if ($verification->status === 'Approved' && $previousStatus !== 'Approved') {
            $this->sendApprovedEmail($verification);
        }

        return response()->json(['status' => 'ok', 'message' => 'Didit webhook processed.']);
    }

    protected function checkEligibility($user, Scholarship $scholarship): ?string
    {
        $scholarshipName = $scholarship->name;

        $summary = \App\Models\StudentProfile::scholarshipAttemptSummaryForUser($user->id, $scholarshipName);
        if ($summary['rejected_attempts'] >= 2) {
            return 'You have reached the maximum application attempts for the ' . $scholarshipName . ' scholarship. This scholarship is no longer available to you.';
        }

        $existingApp = \App\Models\Application::where('user_id', $user->id)
            ->where('scholarship_id', $scholarship->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingApp) {
            return 'You have already applied for the ' . $scholarshipName . ' scholarship. Only one application per scholarship is allowed.';
        }

        $isAcademicScholarship = $scholarship->type === 'academic';

        if ($isAcademicScholarship) {
            $activeCategoryApplications = \App\Models\Application::where('applications.user_id', $user->id)
                ->join('scholarships', 'scholarships.id', '=', 'applications.scholarship_id')
                ->where('scholarships.type', 'academic')
                ->whereIn('applications.status', ['pending', 'approved'])
                ->count();
        } else {
            $activeCategoryApplications = \App\Models\StudentProfile::where('user_id', $user->id)
                ->where('grant_type', 'non-academic')
                ->whereIn('application_status', ['pending', 'approved'])
                ->count();
        }

        if ($activeCategoryApplications >= 2) {
            return 'You already have two active applications in the ' . $scholarship->type . ' category. Only two open applications per category are allowed.';
        }

        return null;
    }

    protected function sendApprovedEmail(IdentityVerification $verification): void
    {
        if (! $verification->user) {
            return;
        }

        try {
            Mail::to($verification->user->email)->queue(new IdentityVerificationApprovedMail($verification->user, $verification->scholarship));
        } catch (\Throwable $e) {
            Log::error('Didit verification approval email failed', [
                'user_id' => $verification->user_id,
                'verification_id' => $verification->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
