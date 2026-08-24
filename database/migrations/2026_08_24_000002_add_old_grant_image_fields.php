<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('old_grant_records', function (Blueprint $table) {
            if (!Schema::hasColumn('old_grant_records', 'sequence_no')) $table->string('sequence_no')->nullable()->after('id');
            if (!Schema::hasColumn('old_grant_records', 'last_name')) $table->string('last_name')->nullable()->after('first_name');
            if (!Schema::hasColumn('old_grant_records', 'amount')) $table->string('amount')->nullable()->after('campus');
        });
    }

    public function down(): void
    {
        Schema::table('old_grant_records', function (Blueprint $table) {
            foreach (['sequence_no', 'last_name', 'amount'] as $column) {
                if (Schema::hasColumn('old_grant_records', $column)) $table->dropColumn($column);
            }
        });
    }
};
