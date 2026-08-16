<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('old_grant_records', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('sex')->nullable();
            $table->string('course')->nullable();
            $table->string('year')->nullable();
            $table->string('scholarship_name')->nullable();
            $table->string('scholarship_type')->nullable(); // 'academic' or 'non_academic'
            $table->string('funding_source')->nullable();
            $table->string('campus')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_grant_records');
    }
};
