<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarships', 'application_start_date')) {
                $table->date('application_start_date')->nullable();
            }
            if (!Schema::hasColumn('scholarships', 'application_end_date')) {
                $table->date('application_end_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'application_start_date',
            'application_end_date',
        ], fn ($column) => Schema::hasColumn('scholarships', $column)));

        if ($columns !== []) {
            Schema::table('scholarships', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
