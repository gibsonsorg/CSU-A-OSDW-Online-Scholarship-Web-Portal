<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'suffix')) {
                $table->string('suffix')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('student_profiles', 'college_department')) {
                $table->string('college_department')->nullable()->after('course');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'suffix')) {
                $table->dropColumn('suffix');
            }

            if (Schema::hasColumn('student_profiles', 'college_department')) {
                $table->dropColumn('college_department');
            }
        });
    }
};
