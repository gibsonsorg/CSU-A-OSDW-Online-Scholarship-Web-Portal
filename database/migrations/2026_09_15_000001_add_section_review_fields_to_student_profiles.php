<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->enum('student_information_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('student_information_reason')->nullable();
            $table->enum('personal_information_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('personal_information_reason')->nullable();
            $table->enum('scholarship_requirements_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('scholarship_requirements_reason')->nullable();
            $table->json('document_reviews')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'student_information_status',
                'student_information_reason',
                'personal_information_status',
                'personal_information_reason',
                'scholarship_requirements_status',
                'scholarship_requirements_reason',
                'document_reviews',
            ]);
        });
    }
};