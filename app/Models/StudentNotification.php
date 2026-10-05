<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'application_id',
        'scholarship_name',
        'type',
        'message',
        'decision_reason',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): bool
    {
        if (! is_null($this->read_at)) {
            return true;
        }

        $this->read_at = now();

        return (bool) $this->save();
    }

    public static function createForApplication(User $user, ?int $applicationId, string $scholarshipName, string $type, string $message, ?string $decisionReason = null): ?self
    {
        $normalizedType = strtolower($type);

        $existing = static::query()
            ->where('user_id', $user->id)
            ->where('application_id', $applicationId)
            ->where('scholarship_name', $scholarshipName)
            ->where('type', $normalizedType)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'user_id' => $user->id,
            'application_id' => $applicationId,
            'scholarship_name' => $scholarshipName,
            'type' => $normalizedType,
            'message' => $message,
            'decision_reason' => $decisionReason,
        ]);
    }

    public function getTitleAttribute(): string
    {
        if ($this->type === 'approved') {
            return 'Scholarship Application Approved';
        }

        if ($this->type === 'rejected' && $this->decision_reason && str_contains(strtolower($this->decision_reason), 'document')) {
            return 'Scholarship Application Requires Correction';
        }

        return $this->type === 'rejected'
            ? 'Scholarship Application Rejected'
            : 'Scholarship Application Update';
    }
}
