<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('sex', ['Male','Female']);
            $table->enum('status', ['Single','Married','In a Relationship','Divorced']);
            $table->string('email')->unique();
            $table->text('home_address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('course');
            $table->string('scholarship_name');
            $table->string('scholarship_type');
            $table->json('uploads')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_profiles');
    }
}
