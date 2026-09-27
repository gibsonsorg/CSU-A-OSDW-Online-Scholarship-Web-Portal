<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyBackground extends Model
{
    protected $fillable = [
        'student_profile_id',
        'father_status',
        'father_name',
        'father_age',
        'father_birthdate',
        'father_address',
        'father_occupation',
        'father_office_address',
        'father_education',
        'mother_status',
        'mother_name',
        'mother_age',
        'mother_birthdate',
        'mother_address',
        'mother_occupation',
        'mother_office_address',
        'mother_education',
        'parent_contact_number',
        'parent_email',
        'monthly_income',
        'has_differently_abled_member',
        'disability_details',
        'siblings',
    ];

    protected $casts = [
        'father_birthdate' => 'date:Y-m-d',
        'mother_birthdate' => 'date:Y-m-d',
        'monthly_income' => 'decimal:2',
        'has_differently_abled_member' => 'boolean',
        'siblings' => 'array',
    ];

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}