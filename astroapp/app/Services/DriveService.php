<?php

namespace App\Services;

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class DriveService {
    public function __construct(private readonly Drive $drive) {}

    public static function make(): self {
        $client = GoogleClientFactory::make();
        return new self(new Drive($client));
    }

    public function copyToDbFolder(string $templateId, string $newName): string {
        $file = new DriveFile([
            'name' => $newName,
            'parents' => [config('services.google.db_folder_id')],
        ]);
        $copied = $this->drive->files->copy($templateId, $file, ['fields' => 'id']);
        return $copied->id;
    }

    public function findByNameInFolder(string $folderId, string $name): ?string {
        $q = sprintf("name = '%s' and '%s' in parents and trashed = false", addslashes($name), $folderId);
        $res = $this->drive->files->listFiles(['q' => $q, 'fields' => 'files(id,name)', 'pageSize' => 1]);
        return count($res->files) ? $res->files[0]->id : null;
    }

    public function deleteByNameInFolder(string $folderId, string $name): void {
        $id = $this->findByNameInFolder($folderId, $name);
        if ($id) $this->drive->files->delete($id);
    }

    public function uploadPdfToFiles(string $localPath, string $destName): string {
        $fileMeta = new DriveFile([
            'name' => $destName,
            'parents' => [config('services.google.files_folder_id')]
        ]);
        $file = $this->drive->files->create(
            $fileMeta,
            [
                'data' => file_get_contents($localPath),
                'mimeType' => 'application/pdf',
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]
        );
        return $file->id;
    }

    public function makeAnyoneReader(string $fileId): void {
        $this->drive->permissions->create($fileId, new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]));
    }

    public function getShareLink(string $fileId): string {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }
}
