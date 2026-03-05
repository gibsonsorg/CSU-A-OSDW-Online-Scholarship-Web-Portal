<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name','middle_name','last_name','sex','status','email','home_address','contact_number','course','scholarship_name','scholarship_type','uploads','application_status'
    ];

    protected $casts = [
        'uploads' => 'array',
    ];
}
