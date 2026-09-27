<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('father_status')->nullable();
            $table->string('father_name')->nullable();
            $table->unsignedSmallInteger('father_age')->nullable();
            $table->date('father_birthdate')->nullable();
            $table->text('father_address')->nullable();
            $table->string('father_occupation')->nullable();
            $table->text('father_office_address')->nullable();
            $table->string('father_education')->nullable();
            $table->string('mother_status')->nullable();
            $table->string('mother_name')->nullable();
            $table->unsignedSmallInteger('mother_age')->nullable();
            $table->date('mother_birthdate')->nullable();
            $table->text('mother_address')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->text('mother_office_address')->nullable();
            $table->string('mother_education')->nullable();
            $table->string('parent_contact_number')->nullable();
            $table->string('parent_email')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->boolean('has_differently_abled_member')->nullable();
            $table->text('disability_details')->nullable();
            $table->json('siblings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_backgrounds');
    }
};