<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('student_profiles', 'application_status')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->enum('application_status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('uploads');
            });
        }

        if (!Schema::hasColumn('student_profiles', 'rejection_reason')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('application_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_profiles', 'rejection_reason')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }

        if (Schema::hasColumn('student_profiles', 'application_status')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('application_status');
            });
        }
    }
};
