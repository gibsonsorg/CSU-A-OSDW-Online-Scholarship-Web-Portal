<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('student_profiles', 'campus')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->string('campus')->nullable()->default('Aparri')->after('college_department');
            });
        }

        DB::table('student_profiles')->update(['campus' => 'Aparri']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_profiles', 'campus')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('campus');
            });
        }
    }
};