<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('old_grant_records', function (Blueprint $table) {
            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('old_grant_records', function (Blueprint $table) {
            $table->dropIndex(['last_name']);
        });
    }
};