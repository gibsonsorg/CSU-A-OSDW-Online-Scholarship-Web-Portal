<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('student_profiles', 'application_id')) {
    Schema::table('student_profiles', function (Blueprint $table) {
        $table->foreignId('application_id')
            ->nullable()
            ->after('user_id')
            ->constrained()
            ->nullOnDelete();
    });
}

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $profiles = DB::table('student_profiles')
            ->whereNull('application_id')
            ->orderBy('id')
            ->get();
        $usedApplications = [];

        foreach ($profiles as $profile) {
            $application = DB::table('applications as a')
                ->join('scholarships as s', 's.id', '=', 'a.scholarship_id')
                ->where('a.user_id', $profile->user_id)
                ->where('s.name', $profile->scholarship_name)
                ->whereNotIn('a.id', $usedApplications)
                ->orderBy('a.created_at')
                ->select('a.id')
                ->first();

            if ($application) {
                DB::table('student_profiles')
                    ->where('id', $profile->id)
                    ->whereNull('application_id')
                    ->update(['application_id' => $application->id]);
                $usedApplications[] = $application->id;
            }
        }

       if (Schema::hasIndex('applications', ['user_id', 'scholarship_id'], 'unique')) {
    Schema::table('applications', function (Blueprint $table) {
        $table->dropUnique(['user_id', 'scholarship_id']);
    });
}

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        Schema::table('applications', function (Blueprint $table) {
            $table->unique(['user_id', 'scholarship_id']);
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('application_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};