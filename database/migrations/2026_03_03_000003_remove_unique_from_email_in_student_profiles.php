<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveUniqueFromEmailInStudentProfiles extends Migration
{
    public function up()
    {
        // Drop the unique constraint on email so multiple applications can be submitted
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }

    public function down()
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unique('email');
        });
    }
}
