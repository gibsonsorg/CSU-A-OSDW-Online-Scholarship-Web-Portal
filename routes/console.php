<?php

use App\Models\BackupSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

return function (Schedule $schedule): void {
    try {
        if (! Schema::hasTable('backup_schedules')) {
            return;
        }

        $backupSchedule = BackupSchedule::first();

        if (! $backupSchedule || ! $backupSchedule->enabled) {
            return;
        }

        $scheduledTime = $backupSchedule->time ?? '03:00';

        if (($backupSchedule->frequency ?? 'daily') === 'daily') {
            $schedule->command('backup:run --type=automatic')
                ->dailyAt($scheduledTime)
                ->timezone('Asia/Manila')
                ->name('database-backup-daily')
                ->withoutOverlapping();
        } elseif (($backupSchedule->frequency ?? 'daily') === 'weekly') {
            $dayOfWeek = $backupSchedule->day_of_week ?? 'monday';
            $schedule->command('backup:run --type=automatic')
                ->weekly()
                ->{$dayOfWeek}()
                ->at($scheduledTime)
                ->timezone('Asia/Manila')
                ->name('database-backup-weekly')
                ->withoutOverlapping();
        } elseif (($backupSchedule->frequency ?? 'daily') === 'monthly') {
            $schedule->command('backup:run --type=automatic')
                ->monthlyOn(1, $scheduledTime)
                ->timezone('Asia/Manila')
                ->name('database-backup-monthly')
                ->withoutOverlapping();
        }
    } catch (\Throwable $e) {
        // Ignore missing tables or rows during fresh setups.
    }
};
