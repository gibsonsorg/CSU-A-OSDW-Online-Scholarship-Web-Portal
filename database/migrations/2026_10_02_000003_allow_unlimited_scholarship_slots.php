<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('scholarships', 'max_slots')) {
            Schema::table('scholarships', function (Blueprint $table) {
                $table->integer('max_slots')->nullable()->default(null)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('scholarships', 'max_slots')) {
            Schema::table('scholarships', function (Blueprint $table) {
                $table->integer('max_slots')->nullable()->default(null)->change();
            });
        }
    }
};