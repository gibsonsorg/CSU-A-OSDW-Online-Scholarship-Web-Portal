<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['scholarship_id']);
            $table->unsignedBigInteger('scholarship_id')->nullable()->change();
            $table->foreign('scholarship_id')->references('id')->on('scholarships')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['scholarship_id']);
            $table->unsignedBigInteger('scholarship_id')->nullable(false)->change();
            $table->foreign('scholarship_id')->references('id')->on('scholarships')->cascadeOnDelete();
        });
    }
};