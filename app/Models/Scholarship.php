<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'type_label',
        'funding_type',
        'scholarship_classification',
        'short_information',
        'description',
        'requirements',
        'required_documents',
        'logo_path',
        'status',
        'is_admin_created',
    ];

    protected $casts = [
        'requirements' => 'array',
        'required_documents' => 'array',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
