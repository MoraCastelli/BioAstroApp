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

    /**
     * Crea un Google Spreadsheet dentro de una carpeta y devuelve el ID.
     * Si ya existe un archivo con el mismo nombre en esa carpeta, reutiliza ese ID (idempotencia).
     */
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

        $res = $this->drive->files->listFiles([
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        return (count($res->files) > 0) ? $res->files[0]->id : null;
    }


    public function getShareLink(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }
}
