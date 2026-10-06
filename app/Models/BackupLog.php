<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $table = 'backup_logs';

    protected $fillable = [
        'type',
        'filename',
        'size',
        'status',
        'storage',
        'error_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
