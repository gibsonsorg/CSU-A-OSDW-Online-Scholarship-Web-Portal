<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scholarship_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('didit');
            $table->string('provider_session_id')->nullable()->index();
            $table->enum('status', ['Approved', 'Declined', 'In Review', 'Expired', 'Not Finished'])->default('Not Finished');
            $table->timestamp('verified_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('session_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scholarship_id']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};
