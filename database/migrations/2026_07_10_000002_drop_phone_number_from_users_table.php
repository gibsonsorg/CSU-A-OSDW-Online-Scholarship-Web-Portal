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
        if (Schema::hasColumn('users', 'contact_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('contact_number');
            });
        }

        if (Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('phone_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'contact_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('contact_number')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone_number')->nullable();
            });
        }
    }
};
