<?php

namespace App\Services;

use Exception;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Throwable;

class GoogleDriveBackupStorage
{
    private const BACKUP_FILENAME_PATTERN = '/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/';

    private ?Drive $drive = null;

    public function isConfigured(): bool
    {
        return $this->folderId() !== '';
    }

    public function upload(string $path, string $filename): void
    {
        $folderId = $this->requireFolderId();
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new Exception('Unable to read backup file for upload.');
        }

        $uploaded = $this->drive()->files->create(new DriveFile([
            'name' => $filename,
            'parents' => [$folderId],
        ]), [
            'data' => $contents,
            'mimeType' => 'application/zip',
            'uploadType' => 'multipart',
            'fields' => 'id,name,parents',
            'supportsAllDrives' => true,
        ]);

        $fileId = $uploaded->getId();
        if (! $fileId) {
            throw new Exception('Google Drive did not confirm the uploaded backup.');
        }

        $parents = $uploaded->getParents() ?? [];
        if (! in_array($folderId, $parents, true)) {
            $uploaded = $this->drive()->files->get($fileId, [
                'fields' => 'id,parents',
                'supportsAllDrives' => true,
            ]);
            $parents = $uploaded->getParents() ?? [];
        }

        if (! in_array($folderId, $parents, true)) {
            throw new Exception('Google Drive did not confirm the backup folder placement.');
        }
    }

    public function pruneBackups(int $keep = 10): void
    {
        $files = $this->listBackupFiles();
        usort($files, static fn (DriveFile $left, DriveFile $right) => strcmp($right->getName(), $left->getName()));

        foreach (array_slice($files, $keep) as $file) {
            $this->drive()->files->delete($file->getId(), ['supportsAllDrives' => true]);
        }
    }

    public function hasBackup(string $filename): bool
    {
        return $this->findBackup($filename) !== null;
    }

    public function getBackupContents(string $filename): string
    {
        $file = $this->findBackup($filename);
        if (! $file) {
            throw new Exception('Google Drive backup not found.');
        }

        $response = $this->drive()->files->get($file->getId(), [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);

        if (is_string($response)) {
            return $response;
        }

        if (method_exists($response, 'getBody')) {
            return $response->getBody()->getContents();
        }

        throw new Exception('Unable to download backup from Google Drive.');
    }

    public function deleteBackup(string $filename): void
    {
        $file = $this->findBackup($filename);
        if ($file) {
            $this->drive()->files->delete($file->getId(), ['supportsAllDrives' => true]);
        }
    }

    public function checkFolder(): array
    {
        if (! $this->isConfigured()) {
            return ['configured' => false, 'reachable' => false, 'backup_count' => null];
        }

        try {
            $folder = $this->drive()->files->get($this->folderId(), [
                'fields' => 'id,mimeType',
                'supportsAllDrives' => true,
            ]);

            if ($folder->getMimeType() !== 'application/vnd.google-apps.folder') {
                return ['configured' => true, 'reachable' => false, 'backup_count' => null];
            }

            return [
                'configured' => true,
                'reachable' => true,
                'backup_count' => count($this->listBackupFiles()),
            ];
        } catch (Throwable) {
            return ['configured' => true, 'reachable' => false, 'backup_count' => null];
        }
    }

    private function findBackup(string $filename): ?DriveFile
    {
        if (preg_match(self::BACKUP_FILENAME_PATTERN, $filename) !== 1) {
            return null;
        }

        foreach ($this->listBackupFiles() as $file) {
            if ($file->getName() === $filename) {
                return $file;
            }
        }

        return null;
    }

    /** @return DriveFile[] */
    private function listBackupFiles(): array
    {
        $folderId = $this->requireFolderId();
        $files = [];
        $pageToken = null;

        do {
            $options = [
                'q' => "'{$folderId}' in parents and trashed = false",
                'pageSize' => 1000,
                'fields' => 'nextPageToken,files(id,name,parents,mimeType,size)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ];
            if ($pageToken) {
                $options['pageToken'] = $pageToken;
            }

            $page = $this->drive()->files->listFiles($options);
            foreach ($page->getFiles() ?? [] as $file) {
                if (preg_match(self::BACKUP_FILENAME_PATTERN, $file->getName() ?? '') === 1) {
                    $files[] = $file;
                }
            }

            $pageToken = $page->getNextPageToken();
        } while ($pageToken);

        return $files;
    }

    private function folderId(): string
    {
        return trim((string) config('filesystems.disks.google.folder', ''));
    }

    private function drive(): Drive
    {
        return $this->drive ??= app(Drive::class);
    }

    private function requireFolderId(): string
    {
        $folderId = $this->folderId();
        if ($folderId === '') {
            throw new Exception('Google Drive folder ID is missing. Please set GOOGLE_DRIVE_FOLDER_ID.');
        }

        return $folderId;
    }
}
