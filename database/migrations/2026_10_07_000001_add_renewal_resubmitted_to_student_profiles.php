<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'renewal_resubmitted')) {
                $table->boolean('renewal_resubmitted')->default(false)->after('renewal_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'renewal_resubmitted')) {
                $table->dropColumn('renewal_resubmitted');
            }
        });
    }
};
