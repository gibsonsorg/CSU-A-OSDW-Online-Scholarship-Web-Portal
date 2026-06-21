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
        Schema::table('users', function (Blueprint $table) {
            // Add account_status column with default 'pending'
            $table->enum('account_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('role');
            
            // Add rejection_reason for rejected accounts
            $table->text('rejection_reason')->nullable()->after('account_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_status', 'rejection_reason']);
        });
    }
};
