<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\BackupSchedule;

class BackupRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--type=automatic : The type of backup (manual or automatic)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Run a backup of the database';

    protected $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'automatic';
        
        $this->info('Starting backup...');

        $result = $this->backupService->createBackup($type);

        if ($result['status'] === 'success') {
            // Update last run info
            $schedule = BackupSchedule::first();
            if ($schedule && $type === 'automatic') {
                $schedule->update([
                    'last_run' => now(),
                    'last_status' => 'success',
                ]);
            }

            $this->info('✓ Backup completed successfully!');
            $this->info('File: ' . $result['filename']);
            return Command::SUCCESS;
        } else {
            // Update last run info
            $schedule = BackupSchedule::first();
            if ($schedule && $type === 'automatic') {
                $schedule->update([
                    'last_run' => now(),
                    'last_status' => 'failed',
                ]);
            }

            $this->error('✗ Backup failed: ' . $result['message']);
            return Command::FAILURE;
        }
    }
}
