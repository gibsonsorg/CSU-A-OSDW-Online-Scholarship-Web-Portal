<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'scholarship_id',
        'provider',
        'provider_session_id',
        'status',
        'verified_at',
        'failure_reason',
        'session_url',
        'meta',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scholarship()
    {
        return $this->belongsTo(Scholarship::class);
    }

    public static function latestForUserAndScholarship(int $userId, ?int $scholarshipId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->when($scholarshipId !== null, fn ($query) => $query->where('scholarship_id', $scholarshipId))
            ->orderByDesc('updated_at')
            ->first();
    }

    public static function approvedForUserAndScholarship(int $userId, ?int $scholarshipId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->when($scholarshipId !== null, fn ($query) => $query->where('scholarship_id', $scholarshipId))
            ->where('status', 'Approved')
            ->exists();
    }
}
