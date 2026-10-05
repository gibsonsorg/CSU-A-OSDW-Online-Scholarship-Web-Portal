<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarships', 'max_slots')) {
                $table->integer('max_slots')->nullable()->after('status');
            }
            if (!Schema::hasColumn('scholarships', 'accepting_applications')) {
                $table->boolean('accepting_applications')->default(true)->after('max_slots');
            }
            if (!Schema::hasColumn('scholarships', 'time_restricted')) {
                $table->boolean('time_restricted')->default(false)->after('accepting_applications');
            }
            if (!Schema::hasColumn('scholarships', 'application_start_time')) {
                $table->time('application_start_time')->nullable()->after('time_restricted');
            }
            if (!Schema::hasColumn('scholarships', 'application_end_time')) {
                $table->time('application_end_time')->nullable()->after('application_start_time');
            }
        });

    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'application_end_time',
            'application_start_time',
            'time_restricted',
            'accepting_applications',
            'max_slots',
        ], fn ($column) => Schema::hasColumn('scholarships', $column)));

        if ($columns !== []) {
            Schema::table('scholarships', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};