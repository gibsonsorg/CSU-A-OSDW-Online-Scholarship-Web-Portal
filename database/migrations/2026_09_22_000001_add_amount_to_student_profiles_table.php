<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CHANGE: Store the admin-entered amount for each applicant.
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable()->default(null)->after('scholarship_type');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
