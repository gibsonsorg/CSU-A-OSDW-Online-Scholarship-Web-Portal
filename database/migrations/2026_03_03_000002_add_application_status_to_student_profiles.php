<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicationStatusToStudentProfiles extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('student_profiles', 'application_status')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->enum('application_status', ['pending','approved','rejected'])->default('pending')->after('uploads');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('student_profiles', 'application_status')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('application_status');
            });
        }
    }
}
