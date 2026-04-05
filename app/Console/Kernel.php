<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Load backup schedule from database
        try {
            $backupSchedule = \App\Models\BackupSchedule::first();
            
            if ($backupSchedule && $backupSchedule->enabled) {
                $scheduledTime = $backupSchedule->time;

                if ($backupSchedule->frequency === 'daily') {
                    $schedule->command('backup:run --type=automatic')
                        ->dailyAt($scheduledTime)
                        ->name('database-backup-daily')
                        ->withoutOverlapping();
                } elseif ($backupSchedule->frequency === 'weekly') {
                    $dayOfWeek = $backupSchedule->day_of_week ?? 'monday';
                    $schedule->command('backup:run --type=automatic')
                        ->weekly()
                        ->{$dayOfWeek}()
                        ->at($scheduledTime)
                        ->name('database-backup-weekly')
                        ->withoutOverlapping();
                } elseif ($backupSchedule->frequency === 'monthly') {
                    $schedule->command('backup:run --type=automatic')
                        ->monthlyOn(1, $scheduledTime)
                        ->name('database-backup-monthly')
                        ->withoutOverlapping();
                }
            }
        } catch (\Exception $e) {
            // Database not ready or table doesn't exist yet, skip scheduling
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
