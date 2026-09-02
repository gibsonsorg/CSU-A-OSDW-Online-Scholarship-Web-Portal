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
            // Add registered_attempt column with default 1 for new registrations
            if (!Schema::hasColumn('users', 'registered_attempt')) {
                $table->integer('registered_attempt')
                    ->default(1)
                    ->after('account_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('registered_attempt');
        });
    }
};
