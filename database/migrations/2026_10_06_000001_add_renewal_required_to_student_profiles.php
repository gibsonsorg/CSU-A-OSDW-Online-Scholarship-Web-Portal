<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'renewal_required')) {
                $table->boolean('renewal_required')->default(false)->after('rejection_remark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'renewal_required')) {
                $table->dropColumn('renewal_required');
            }
        });
    }
};
