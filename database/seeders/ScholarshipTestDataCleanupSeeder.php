<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScholarshipTestDataCleanupSeeder extends Seeder
{
    private const EMAIL_PATTERN = 'test-scholarship.%@example.com';

    public function run(): void
    {
        $this->ensureLocalDatabase();

        $users = User::withTrashed()
            ->where('email', 'like', self::EMAIL_PATTERN)
            ->get();
        $userIds = $users->modelKeys();
        $deletedProfiles = 0;

        DB::transaction(function () use ($userIds, &$deletedProfiles): void {
            $profiles = StudentProfile::withTrashed()
                ->whereIn('user_id', $userIds)
                ->get();

            foreach ($profiles as $profile) {
                $profile->forceDelete();
                $deletedProfiles++;
            }

            foreach (User::withTrashed()->whereIn('id', $userIds)->get() as $user) {
                $user->forceDelete();
            }
        });

        $this->command?->info(sprintf(
            'Removed %d test profiles and %d test users. Scholarship definitions were not changed.',
            $deletedProfiles,
            $users->count()
        ));
    }

    private function ensureLocalDatabase(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Scholarship test data may only be cleaned up in local or testing environments.');
        }

        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        $config = $connection->getConfig();
        $host = strtolower((string) ($config['host'] ?? ''));
        $urlHost = strtolower((string) parse_url((string) ($config['url'] ?? ''), PHP_URL_HOST));
        $effectiveHost = $urlHost !== '' ? $urlHost : $host;

        if (!in_array($effectiveHost, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Scholarship test data cleanup requires a local database host.');
        }
    }
}