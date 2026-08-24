<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldGrantRecord extends Model
{
    use HasFactory;

    protected $table = 'old_grant_records';

    protected $fillable = [
        'sequence_no',
        'last_name',
        'first_name',
        'middle_name',
        'sex',
        'course',
        'year',
        'scholarship_name',
        'scholarship_type',
        'funding_source',
        'campus',
        'amount',
    ];
}
