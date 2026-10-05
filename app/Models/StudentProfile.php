<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id','application_id','source','student_id_number','first_name','middle_name','last_name','suffix','sex','status','email','home_address','contact_number','course','college_department','campus','year_level','program','id_number','birthdate','birthplace','religion','scholarship_name','scholarship_type','amount','uploads','application_status','grant_type','rejection_reason','rejection_remark','resubmission_deadline','student_information_status','student_information_reason','personal_information_status','personal_information_reason','scholarship_requirements_status','scholarship_requirements_reason','family_background_status','family_background_reason','document_review','document_reviews','reapply_count','locked_at','last_notification_subject','last_notification_body'
    ];

    protected $casts = [
        'uploads' => 'array',
        'document_review' => 'array',
        'document_reviews' => 'array',
        'birthdate' => 'date',
        'amount' => 'decimal:2',
        'locked_at' => 'datetime',
        'resubmission_deadline' => 'datetime',
    ];

    public function expireResubmissionIfDue(): bool
    {
        if (
            $this->application_status !== 'rejected'
            || !$this->resubmission_deadline
            || $this->locked_at
            || $this->resubmission_deadline->isFuture()
        ) {
            return false;
        }

        $this->forceFill(['locked_at' => now()])->save();

        return true;
    }

    public function isResubmissionExpired(): bool
    {
        return $this->application_status === 'rejected'
            && $this->resubmission_deadline !== null
            && $this->locked_at !== null
            && $this->resubmission_deadline->lessThanOrEqualTo(now());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function recordNotification(string $subject, string $body): void
    {
        $this->forceFill([
            'last_notification_subject' => $subject,
            'last_notification_body' => $body,
        ])->save();
    }

    public function familyBackground()
    {
        return $this->hasOne(FamilyBackground::class);
    }

    public static function scholarshipAttemptSummaryForUser(int $userId, string $scholarshipName): array
    {
        $records = static::where('user_id', $userId)
            ->where('scholarship_name', $scholarshipName)
            ->orderByDesc('created_at')
            ->get(['application_status', 'created_at']);

        $latestStatus = $records->first()->application_status ?? null;

        return [
            'total_attempts' => $records->count(),
            'rejected_attempts' => $records->where('application_status', 'rejected')->count(),
            'latest_status' => $latestStatus,
        ];
    }

    public static function hasReachedScholarshipAttemptLimit(int $userId, string $scholarshipName): bool
    {
        $summary = static::scholarshipAttemptSummaryForUser($userId, $scholarshipName);

        return $summary['rejected_attempts'] >= 2;
    }
}
