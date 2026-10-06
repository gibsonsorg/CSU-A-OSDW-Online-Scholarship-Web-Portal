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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['manual', 'automatic'])->default('manual');
            $table->string('filename');
            $table->unsignedBigInteger('size')->default(0);
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->enum('storage', ['local', 'google'])->default('local');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
