<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Add application_id to student_profiles if it does not already exist.
         */
        if (!Schema::hasColumn('student_profiles', 'application_id')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->foreignId('application_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        /*
         * Match existing student profiles to their applications.
         */
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
                    ->update([
                        'application_id' => $application->id,
                    ]);

                $usedApplications[] = $application->id;
            }
        }

        /*
         * The old unique index:
         *
         * UNIQUE(user_id, scholarship_id)
         *
         * may currently be supporting the foreign keys on applications.
         * Create separate indexes first so MySQL no longer needs the
         * unique index for those foreign keys.
         */
        if (!Schema::hasIndex('applications', ['user_id'])) {
            Schema::table('applications', function (Blueprint $table) {
                $table->index('user_id');
            });
        }

        if (!Schema::hasIndex('applications', ['scholarship_id'])) {
            Schema::table('applications', function (Blueprint $table) {
                $table->index('scholarship_id');
            });
        }

        /*
         * Remove the old unique constraint.
         *
         * This allows a student to submit another application
         * for the same scholarship.
         */
        if (Schema::hasIndex(
            'applications',
            ['user_id', 'scholarship_id'],
            'unique'
        )) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropUnique([
                    'user_id',
                    'scholarship_id',
                ]);
            });
        }
    }

    public function down(): void
    {
        /*
         * Restore the unique constraint.
         */
        if (!Schema::hasIndex(
            'applications',
            ['user_id', 'scholarship_id'],
            'unique'
        )) {
            Schema::table('applications', function (Blueprint $table) {
                $table->unique([
                    'user_id',
                    'scholarship_id',
                ]);
            });
        }

        /*
         * Remove application_id from student_profiles.
         */
        if (Schema::hasColumn('student_profiles', 'application_id')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('application_id');
            });
        }
    }
};