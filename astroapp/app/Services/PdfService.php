<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Genera el PDF local en $destino a partir de $perfil y $encuentros.
     * La vista pdf.paciente YA puede usar $encuentros (1 página por encuentro).
     */
    public function generarPdfLocal(array $perfil, array $encuentros, string $destino): void
    {
        // Render del Blade con ambas variables
        $html = view('pdf.paciente', [
            'perfil'      => $perfil,
            'encuentros'  => $encuentros,
        ])->render();

        // Importante: para imágenes remotas (Drive u otros), activá enable_remote=true en config/dompdf.php
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');

        @mkdir(dirname($destino), 0777, true);
        file_put_contents($destino, $pdf->output());
    }

    /**
     * Atajo: Genera el PDF local y lo sube a la carpeta "Archivos" (files_folder_id),
     * reemplazando el PDF anterior del paciente si existe (por nombre).
     *
     * @param array  $perfil         Perfil (con NOMBRE_Y_APELLIDO)
     * @param array  $encuentros     Lista de encuentros (cada item: FECHA, RESUMEN, etc.)
     * @param string $nombreArchivo  Nombre del PDF (ej: "Mora Castelli.pdf")
     * @return string                fileId del PDF subido en Drive
     */
    public function generarYSubirPdf(array $perfil, array $encuentros, string $nombreArchivo): string
    {
        // 1) Generar local
        $tmp = storage_path('app/tmp/pdf_' . uniqid() . '.pdf');
        $this->generarPdfLocal($perfil, $encuentros, $tmp);

        // 2) Subir a Drive en carpeta "Archivos", reemplazando si ya existe
        $drive = DriveService::make();
        $filesFolderId = config('services.google.files_folder_id');
        if (!$filesFolderId) {
            throw new \RuntimeException('Falta services.google.files_folder_id en config/services.php');
        }

        // Borrar anterior (si existe con el mismo nombre) para “recrear” siempre
        $drive->deleteByNameInFolder($filesFolderId, $nombreArchivo);

        // Subir
        $fileId = $drive->uploadPdfToFiles($tmp, $nombreArchivo);

        // Hacerlo público (opcional, si lo vas a compartir por link)
        try {
            $drive->makeAnyoneReader($fileId);
        } catch (\Throwable $e) {
            // no interrumpe si falla; podés loguear si querés
        }

        // Limpiar tmp
        @unlink($tmp);

        return $fileId;
    }
}

