<?php

namespace App\Services;

use App\Support\GoogleRetry;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class DriveService
{
    public function __construct(private readonly Drive $drive) {}

    public static function make(): self
    {
        $client = GoogleClientFactory::make();
        return new self(new Drive($client));
    }

    public function whoAmI(): array
    {
        $about = \App\Support\GoogleRetry::call(fn() =>
            $this->drive->about->get(['fields' => 'user(emailAddress,displayName)'])
        );

        return [
            'email' => $about->getUser()->getEmailAddress(),
            'name'  => $about->getUser()->getDisplayName(),
        ];
    }

    public function scopes(): array
    {
        // No viene de Drive API; si querés ver scopes, lo más simple es loguearlo desde el Client.
        return []; 
    }

    public function deleteByNameInFolder(string $folderId, string $name): void
    {
        $q = sprintf("name = '%s' and '%s' in parents and trashed = false", addslashes($name), $folderId);

        $res = GoogleRetry::call(fn() =>
            $this->drive->files->listFiles(['q' => $q, 'fields' => 'files(id)', 'pageSize' => 1])
        );

        if (count($res->files)) {
            GoogleRetry::call(fn() => $this->drive->files->delete($res->files[0]->id));
        }
    }

    public function getParents(string $fileId): array
    {
        $file = \App\Support\GoogleRetry::call(fn() =>
            $this->drive->files->get($fileId, ['fields' => 'parents'])
        );
        return $file->getParents() ?? [];
    }

    public function ensureChildFolder(string $parentId, string $title): string
    {
        $id = $this->findByNameInFolder($parentId, $title);
        return $id ?: $this->ensureFolderByName($title, $parentId);
    }


    /** Sube un PDF a la carpeta “Archivos” (config services.google.files_folder_id). */
    public function uploadPdfToFiles(string $localPdfPath, string $name): string
    {
        $folderId = (string) config('services.google.files_folder_id');
        if ($folderId === '') {
            throw new \RuntimeException('Falta services.google.files_folder_id');
        }

        if (!is_file($localPdfPath)) {
            throw new \RuntimeException("Archivo no existe: {$localPdfPath}");
        }

        $fileMeta = new DriveFile([
            'name'    => $name,
            'parents' => [$folderId],
            'mimeType'=> 'application/pdf',
        ]);

        $content = file_get_contents($localPdfPath);

        $file = GoogleRetry::call(fn() =>
            $this->drive->files->create(
                $fileMeta,
                [
                    'data'       => $content,
                    'mimeType'   => 'application/pdf',
                    'uploadType' => 'multipart',
                    'fields'     => 'id'
                ]
            )
        );

        return $file->id;
    }

    public function makeAnyoneReader(string $fileId): void
    {
        $perm = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
            'allowFileDiscovery' => false,
        ]);

        GoogleRetry::call(fn() =>
            $this->drive->permissions->create($fileId, $perm, [
                'fields' => 'id',
                'sendNotificationEmail' => false,
            ])
        );
    }

    /** URL para usar en <img src> (render inline). */
    public function getPublicContentUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=view&id={$fileId}";
    }

    /** Thumbnail rápido de Drive. */
    public function getThumbnailUrl(string $fileId, int $size = 1000): string
    {
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w{$size}";
    }

    /** Asegura una carpeta por nombre (opcionalmente dentro de parent). */
    public function ensureFolderByName(string $name, ?string $parentId = null): string
    {
        $q = sprintf(
            "mimeType = 'application/vnd.google-apps.folder' and name = '%s' and trashed = false",
            addslashes($name)
        );
        if ($parentId) {
            $q .= sprintf(" and '%s' in parents", $parentId);
        }

        $res = GoogleRetry::call(fn() =>
            $this->drive->files->listFiles([
                'q'        => $q,
                'fields'   => 'files(id,name)',
                'pageSize' => 1
            ])
        );

        if (count($res->files)) {
            return $res->files[0]->id;
        }

        $meta = new DriveFile([
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => $parentId ? [$parentId] : null,
        ]);

        $folder = GoogleRetry::call(fn() =>
            $this->drive->files->create($meta, ['fields' => 'id'])
        );

        return $folder->id;
    }

    /** Sube imagen a una carpeta y devuelve fileId. */
    public function uploadImageToFolder(string $localPath, string $name, string $folderId): string
    {
        if (!is_file($localPath)) {
            throw new \RuntimeException("Archivo temporal no existe: {$localPath}");
        }

        // MIME
        $mime = 'image/jpeg';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $det = finfo_file($f, $localPath) ?: null;
            finfo_close($f);
            if ($det) $mime = $det;
        }

        $meta = new DriveFile([
            'name'    => $name,
            'parents' => [$folderId],
            'mimeType'=> $mime,
        ]);

        $file = GoogleRetry::call(fn() =>
            $this->drive->files->create(
                $meta,
                [
                    'data'       => file_get_contents($localPath),
                    'mimeType'   => $mime,
                    'uploadType' => 'multipart',
                    'fields'     => 'id',
                ]
            )
        );

        return $file->id;
    }

    public function renameFile(string $fileId, string $newName): void
    {
        $file = new \Google\Service\Drive\DriveFile(['name' => $newName]);
        \App\Support\GoogleRetry::call(fn() =>
            $this->drive->files->update($fileId, $file, ['fields' => 'id,name'])
        );
    }

    public function createSpreadsheetInFolder(string $name, string $folderId): string
    {
        // Idempotencia: si ya existe un archivo con *exacto* ese nombre, lo reusamos
        if ($existente = $this->findByNameInFolder($folderId, $name)) {
            return $existente;
        }

        $meta = new DriveFile([
            'name'     => $name,
            'parents'  => [$folderId],
            'mimeType' => 'application/vnd.google-apps.spreadsheet',
        ]);

        $file = GoogleRetry::call(fn() =>
            $this->drive->files->create($meta, ['fields' => 'id'])
        );

        return $file->id;
    }

    public function findByNameInFolder(string $folderId, string $name): ?string
    {
        $q = sprintf(
            "name = '%s' and '%s' in parents and trashed = false",
            addslashes($name),
            $folderId
        );

        $res = \App\Support\GoogleRetry::call(fn() =>
            $this->drive->files->listFiles([
                'q'        => $q,
                'fields'   => 'files(id,name)',
                'pageSize' => 1,
            ])
        );

        return (count($res->files) > 0) ? $res->files[0]->id : null;
    }

    public function copyFileToFolder(string $fileId, string $newName, string $folderId): string
    {
        $copy = new \Google\Service\Drive\DriveFile([
            'name'    => $newName,
            'parents' => [$folderId],
        ]);

        $created = GoogleRetry::call(fn() =>
            $this->drive->files->copy($fileId, $copy, ['fields' => 'id'])
        );

        return $created->getId();
    }

    public function getFileName(string $fileId): string
    {
        $f = \App\Support\GoogleRetry::call(fn() =>
            $this->drive->files->get($fileId, ['fields' => 'id,name'])
        );
        return $f->name;
    }



    public function deleteFolderRecursive(string $folderId): void
    {
        // Borra hijos primero
        $pageToken = null;
        do {
            $res = GoogleRetry::call(fn() =>
                $this->drive->files->listFiles([
                    'q' => sprintf("'%s' in parents and trashed = false", $folderId),
                    'fields' => 'nextPageToken, files(id, mimeType)',
                    'pageSize' => 1000,
                    'pageToken' => $pageToken,
                ])
            );

            foreach ($res->files as $f) {
                if (($f->mimeType ?? '') === 'application/vnd.google-apps.folder') {
                    $this->deleteFolderRecursive($f->id);
                } else {
                    $this->deleteFileById($f->id);
                }
            }

            $pageToken = $res->nextPageToken ?? null;
        } while ($pageToken);

        // Borra la carpeta
        $this->deleteFileById($folderId);
    }


    public function ensurePacienteFolders(string $pacienteNombre): array
    {
        $root = (string) config('services.google.db_folder_id');
        if ($root === '') {
            throw new \RuntimeException('Falta services.google.db_folder_id');
        }

        $pacienteFolderId = $this->ensureFolderByName($pacienteNombre, $root);
        $imagenesFolderId = $this->ensureFolderByName('Imagenes', $pacienteFolderId);
        $audiosFolderId   = $this->ensureFolderByName('Audios', $pacienteFolderId);

        return [
            'pacienteFolderId' => $pacienteFolderId,
            'imagenesFolderId' => $imagenesFolderId,
            'audiosFolderId'   => $audiosFolderId,
        ];
    }

    public function moveFileToFolder(string $fileId, string $newFolderId): void
    {
        $parents = $this->getParents($fileId);
        $removeParents = implode(',', $parents);

        GoogleRetry::call(fn() =>
            $this->drive->files->update(
                $fileId,
                new DriveFile(),
                [
                    'addParents' => $newFolderId,
                    'removeParents' => $removeParents,
                    'fields' => 'id, parents',
                    'supportsAllDrives' => true,
                ]
            )
        );
    }

    public function uploadAudioToFolder(string $localPath, string $name, string $folderId): string
    {
        if (!is_file($localPath)) {
            throw new \RuntimeException("Archivo temporal no existe: {$localPath}");
        }

        $mime = 'audio/mpeg';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $det = finfo_file($f, $localPath) ?: null;
            finfo_close($f);
            if ($det) $mime = $det;
        }

        $meta = new \Google\Service\Drive\DriveFile([
            'name'    => $name,
            'parents' => [$folderId],
            'mimeType'=> $mime,
        ]);

        $file = \App\Support\GoogleRetry::call(fn() =>
            $this->drive->files->create(
                $meta,
                [
                    'data'       => file_get_contents($localPath),
                    'mimeType'   => $mime,
                    'uploadType' => 'multipart',
                    'fields'     => 'id',
                ]
            )
        );

        return $file->id;
    }

    public function getPublicDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }


    public function getShareLink(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }

    public function deleteFileById(string $fileId): void
    {
        \App\Support\GoogleRetry::call(fn() =>
            $this->drive->files->delete($fileId)
        );
    }

    public function downloadBytes(string $fileId): array
    {
        $meta = GoogleRetry::call(fn() =>
            $this->drive->files->get($fileId, [
                'fields' => 'mimeType,name',
                'supportsAllDrives' => true,
            ])
        );

        $res = GoogleRetry::call(fn() =>
            $this->drive->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ])
        );

        return [
            'mime'  => $meta->getMimeType() ?: 'application/octet-stream',
            'bytes' => $res->getBody()->getContents(),
        ];
    }

}
