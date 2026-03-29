<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'year_level')) {
                $table->enum('year_level', ['1st Year', '2nd Year', '3rd Year', '4th Year'])->nullable()->after('course');
            }
        });
    }

    public function down()
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'year_level')) {
                $table->dropColumn('year_level');
            }
        });
    }
};
