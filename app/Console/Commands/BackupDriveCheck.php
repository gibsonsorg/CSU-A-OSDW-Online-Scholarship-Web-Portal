<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveBackupStorage;
use Illuminate\Console\Command;

class BackupDriveCheck extends Command
{
    protected $signature = 'backup:drive-check';

    protected $description = 'Check Google Drive backup folder connectivity';

    public function handle(GoogleDriveBackupStorage $googleDrive): int
    {
        $status = $googleDrive->checkFolder();

        $this->line('Google Drive folder ID configured: ' . ($status['configured'] ? 'Yes' : 'No'));
        $this->line('Google Drive folder reachable: ' . ($status['reachable'] ? 'Yes' : 'No'));
        $this->line('Backup files in folder: ' . ($status['backup_count'] ?? 'Unavailable'));

        return $status['configured'] && $status['reachable']
            ? self::SUCCESS
            : self::FAILURE;
    }
}
