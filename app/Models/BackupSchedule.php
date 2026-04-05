<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    protected $fillable = [
        'frequency',
        'time',
        'day_of_week',
        'enabled',
        'last_run',
        'last_status',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_run' => 'datetime',
    ];
}
