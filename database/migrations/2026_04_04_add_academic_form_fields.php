<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'program')) {
                $table->string('program')->nullable()->after('year_level');
            }
            if (!Schema::hasColumn('student_profiles', 'id_number')) {
                $table->string('id_number')->nullable()->after('program');
            }
            if (!Schema::hasColumn('student_profiles', 'birthdate')) {
                $table->date('birthdate')->nullable()->after('id_number');
            }
            if (!Schema::hasColumn('student_profiles', 'birthplace')) {
                $table->string('birthplace')->nullable()->after('birthdate');
            }
            if (!Schema::hasColumn('student_profiles', 'religion')) {
                $table->string('religion')->nullable()->after('birthplace');
            }
        });
    }

    public function down()
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'program')) {
                $table->dropColumn('program');
            }
            if (Schema::hasColumn('student_profiles', 'id_number')) {
                $table->dropColumn('id_number');
            }
            if (Schema::hasColumn('student_profiles', 'birthdate')) {
                $table->dropColumn('birthdate');
            }
            if (Schema::hasColumn('student_profiles', 'birthplace')) {
                $table->dropColumn('birthplace');
            }
            if (Schema::hasColumn('student_profiles', 'religion')) {
                $table->dropColumn('religion');
            }
        });
    }
};
