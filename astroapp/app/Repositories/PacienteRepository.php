<?php

namespace App\Repositories;

use App\Services\DriveService;
use App\Services\SheetsService;
use App\Services\PdfService;
use Carbon\Carbon;

class PacienteRepository
{
    private DriveService $drive;
    private SheetsService $sheets;

    public function __construct(DriveService $drive, SheetsService $sheets)
    {
        $this->drive  = $drive;
        $this->sheets = $sheets;
    }

    public static function make(): self
    {
        return new self(DriveService::make(), SheetsService::make());
    }

    public function crearDesdeTemplate(string $nombreApellido): string
    {
        $drive  = $this->drive;
        $sheets = $this->sheets;

        $folderId = (string) config('services.google.db_folder_id');
        if ($folderId === '') {
            throw new \RuntimeException('Falta services.google.db_folder_id');
        }

        $fileName = $this->fileName($nombreApellido);

        // Idempotencia: si ya existe un archivo con ese nombre en la carpeta, reusarlo.
        if (method_exists($drive, 'findByNameInFolder')) {
            $existente = $drive->findByNameInFolder($folderId, $fileName);
        } else {
            // fallback simple si no tenés ese método implementado
            $existente = null;
        }

        $spreadsheetId = $existente ?: $drive->createSpreadsheetInFolder($fileName, $folderId);

        // Estructura Sheets
        $sheets->seedPerfil($spreadsheetId);
        $sheets->ensureEncuentrosSheet($spreadsheetId);

        // Valores iniciales
        $this->guardarPerfil($spreadsheetId, [
            'NOMBRE_Y_APELLIDO'    => $nombreApellido,
            'ULTIMA_ACTUALIZACION' => Carbon::now()->toIso8601String(),
        ]);

        // PDF inicial (portada + (cero) encuentros)
        $this->regenerarPdf($spreadsheetId);

        return $spreadsheetId;
    }

    public function guardarPerfil(string $spreadsheetId, array $perfil): void
    {
        $perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();
        $this->sheets->setPerfil($spreadsheetId, $perfil);

        // Cada vez que se guarda el perfil, refrescamos PDF
        $this->regenerarPdf($spreadsheetId);
    }

    public function agregarEncuentro(string $spreadsheetId, array $enc): void
    {
        $this->sheets->appendEncuentro($spreadsheetId, $enc);

        // Después de agregar encuentro, refrescamos PDF
        $this->regenerarPdf($spreadsheetId);
    }

    public function calcularEdad(string $fechaNac, ?string $fechaEncuentro = null): int
    {
        $nac = Carbon::createFromFormat('d/m/Y', $fechaNac);
        $ref = $fechaEncuentro
            ? Carbon::createFromFormat('d/m/Y', $fechaEncuentro)
            : Carbon::now();

        return $nac->diffInYears($ref);
    }

    public function fileName(string $nombreApellido): string
    {
        // Si querés estandarizar: "Apellido, Nombre"
        return $nombreApellido;
    }

    /** Regenera y sube el PDF del paciente a la carpeta "Archivos". */
    private function regenerarPdf(string $spreadsheetId): void
    {
        try {
            $perfil     = $this->sheets->getPerfil($spreadsheetId);
            $encuentros = $this->sheets->readEncuentros($spreadsheetId); // asegurate de tener este método

            $pdf = new PdfService();
            $nombre = trim((string)($perfil['NOMBRE_Y_APELLIDO'] ?? 'Paciente'));
            if ($nombre === '') { $nombre = 'Paciente'; }
            $nombreArchivo = $nombre . '.pdf';

            // Genera local y sube reemplazando el anterior
            $pdf->generarYSubirPdf($perfil, $encuentros, $nombreArchivo);
        } catch (\Throwable $e) {
            // No interrumpimos el flujo del alta/edición por fallas de PDF.
            // Podés loguearlo si querés:
            // \Log::error('Error al regenerar PDF: '.$e->getMessage());
        }
    }
}
