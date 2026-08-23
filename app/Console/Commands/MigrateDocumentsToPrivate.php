<?php

namespace App\Console\Commands;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigrateDocumentsToPrivate extends Command
{
    protected $signature = 'documents:migrate-to-private';
    protected $description = 'Move existing public student documents to private storage';

    public function handle(): int
    {
        $summary = [
            'migrated' => 0,
            'already_private' => 0,
            'public_duplicates_deleted' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $this->migratePaths(User::query(), 'id_document', $summary);

        foreach (StudentProfile::query()->whereNotNull('uploads')->cursor() as $profile) {
            $uploads = $profile->uploads;
            if (!is_array($uploads)) {
                $summary['skipped']++;
                Log::warning('Skipped malformed student document metadata', ['profile_id' => $profile->id]);
                continue;
            }

            foreach ($uploads as $group => $files) {
                if (is_string($files)) {
                    $this->migratePath($files, $summary);
                } elseif (is_array($files)) {
                    foreach ($files as $index => $file) {
                        if (is_string($file)) {
                            $this->migratePath($file, $summary);
                        }
                    }
                }
            }
        }

        $this->table(['Result', 'Count'], [
            ['Migrated', $summary['migrated']],
            ['Already private', $summary['already_private']],
            ['Public duplicates deleted', $summary['public_duplicates_deleted']],
            ['Skipped', $summary['skipped']],
            ['Failed', $summary['failed']],
        ]);

        return self::SUCCESS;
    }

    private function migratePaths($query, string $column, array &$summary): void
    {
        foreach ($query->whereNotNull($column)->cursor() as $model) {
            $this->migratePath($model->{$column}, $summary);
        }
    }

    private function migratePath(string $path, array &$summary): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $publicExists = $public->exists($path);
        $privateExists = $private->exists($path);

        if (!$publicExists && !$privateExists) {
            $summary['skipped']++;
            Log::info('Skipped missing student document', ['path' => $path]);
            return;
        }

        $privateValid = false;
        if ($privateExists) {
            try {
                $privateValid = $private->get($path) !== false;
            } catch (\Throwable $exception) {
                Log::error('Private student document verification failed', ['path' => $path, 'exception' => $exception]);
            }
        }

        if ($privateValid) {
            if ($publicExists) {
                try {
                    if ($public->delete($path)) {
                        $summary['public_duplicates_deleted']++;
                        Log::info('Deleted duplicate public student document', ['path' => $path]);
                    } else {
                        $summary['failed']++;
                        Log::error('Failed to delete duplicate public student document', ['path' => $path]);
                    }
                } catch (\Throwable $exception) {
                    $summary['failed']++;
                    Log::error('Failed to delete duplicate public student document', ['path' => $path, 'exception' => $exception]);
                }
            } else {
                $summary['already_private']++;
                Log::info('Student document already private', ['path' => $path]);
            }
            return;
        }

        if (!$publicExists) {
            $summary['failed']++;
            Log::error('Private student document could not be verified and no public copy exists', ['path' => $path]);
            return;
        }

        try {
            $contents = $public->get($path);
            if ($contents === false || !$private->put($path, $contents) || $private->get($path) === false) {
                throw new \RuntimeException('Private copy verification failed');
            }

            if (!$public->delete($path)) {
                throw new \RuntimeException('Public source deletion failed');
            }

            $summary['migrated']++;
            Log::info('Migrated student document to private storage', ['path' => $path]);
        } catch (\Throwable $exception) {
            $summary['failed']++;
            Log::error('Student document migration failed; public source was preserved', [
                'path' => $path,
                'exception' => $exception,
            ]);
        }
    }
}