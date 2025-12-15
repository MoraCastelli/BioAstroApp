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

        $rootPacientes = (string) config('services.google.db_folder_id'); // si querés renombrarlo a pacientes_folder_id mejor
        if ($rootPacientes === '') {
            throw new \RuntimeException('Falta services.google.db_folder_id');
        }

        $templateId = (string) config('services.google.template_paciente_spreadsheet_id');
        if ($templateId === '') {
            throw new \RuntimeException('Falta services.google.template_paciente_spreadsheet_id');
        }

        $nombre = $this->fileName($nombreApellido);

        // 1) carpeta del paciente (dentro del root)
        $folderPaciente = $drive->ensureFolderByName($nombre, $rootPacientes);

        // 2) subcarpeta Imagenes
        $folderImgs = $drive->ensureFolderByName('Imagenes', $folderPaciente);

        // 3) Libro (Spreadsheet) dentro de la carpeta del paciente:
        //    idempotencia: si existe uno con ese nombre, reusarlo
        $spreadsheetId = $drive->findByNameInFolder($folderPaciente, $nombre);
        if (!$spreadsheetId) {
            $spreadsheetId = $drive->copyFileToFolder($templateId, $nombre, $folderPaciente);
        }

        // 4) Asegurar estructura (por si cambió template)
        $sheets->seedPerfil($spreadsheetId);
        $sheets->ensureEncuentrosSheet($spreadsheetId);
        // si tenés ensureImagenesSheet, llamalo (o appendImagen ya lo hace)

        // 5) Valores iniciales
        $this->guardarPerfil($spreadsheetId, [
            'NOMBRE_Y_APELLIDO'    => $nombreApellido,
            'ULTIMA_ACTUALIZACION' => Carbon::now()->toIso8601String(),
        ]);

        // 6) Índice: ideal guardar folder IDs también
        $indiceId = (string) config('services.google.index_id');
        if ($indiceId) {
            $sheets->upsertIndice($indiceId, [
                $nombreApellido,
                $spreadsheetId,
                Carbon::now()->toIso8601String(),
            ]);

        }

        return $spreadsheetId;
    }


    public function eliminarPaciente(string $spreadsheetId, ?string $nombreUi = null): void
    {
        $drive  = $this->drive;
        $sheets = $this->sheets;

        // Vamos a intentar leer el índice una sola vez
        $indiceId = env('GOOGLE_SHEETS_INDEX_ID');
        $filasIndice = [];
        $filaPaciente = null;

        if ($indiceId) {
            try {
                $filasIndice = $sheets->readIndice($indiceId); // [['nombre'=>..., 'id'=>..., 'ts'=>...], ...]
                foreach ($filasIndice as $row) {
                    if (($row['id'] ?? '') === $spreadsheetId) {
                        $filaPaciente = $row;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning("⚠️ No se pudo leer índice para eliminar paciente: ".$e->getMessage());
            }
        }

        // Nombre definitivo del paciente (para logs y PDF)
        $nombre = trim(
            ($filaPaciente['nombre'] ?? '') !== ''
                ? $filaPaciente['nombre']
                : ($nombreUi ?: 'Paciente')
        );

        /* 1️⃣ Eliminar spreadsheet (hoja del paciente) */
        try {
            $drive->deleteFileById($spreadsheetId);
            \Log::info("✅ Spreadsheet eliminado: {$spreadsheetId}");
        } catch (\Google\Service\Exception $e) {
            if (str_contains($e->getMessage(), 'notFound')) {
                \Log::info("🟢 El spreadsheet ya no existía: {$spreadsheetId}");
            } else {
                \Log::warning("⚠️ Error al eliminar spreadsheet {$spreadsheetId}: ".$e->getMessage());
            }
        } catch (\Throwable $e) {
            \Log::warning("⚠️ Error genérico al eliminar spreadsheet {$spreadsheetId}: ".$e->getMessage());
        }

        /* 2️⃣ Eliminar PDF del paciente en carpeta "Archivos" */
        try {
            $folderId = config('services.google.files_folder_id');
            if ($folderId) {
                $nombreArchivo = ($nombre !== '' ? $nombre : 'Paciente') . '.pdf';
                $pdfId = $drive->findByNameInFolder($folderId, $nombreArchivo);
                if ($pdfId) {
                    $drive->deleteFileById($pdfId);
                    \Log::info("✅ PDF eliminado: {$nombreArchivo}");
                } else {
                    \Log::info("📄 No se encontró PDF llamado {$nombreArchivo} en la carpeta.");
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("⚠️ No se pudo eliminar el PDF de {$nombre}: ".$e->getMessage());
        }

        /* 3️⃣ Actualizar índice (IndicePacientes) */
        try {
            if ($indiceId && $filasIndice) {
                // Mantener solo los que NO son este paciente
                $restantes = array_filter(
                    $filasIndice,
                    fn($f) => ($f['id'] ?? '') !== $spreadsheetId
                );

                // Limpiar el rango de datos (dejamos headers en A1:C1)
                $sheets->clearRange($indiceId, 'IndicePacientes!A2:C10000');

                if (count($restantes)) {
                    // Convertir a matriz "plana" de filas [nombre, id, ts] y reindexar
                    $values = array_values(array_map(
                        fn($r) => [
                            $r['nombre'] ?? '',
                            $r['id'] ?? '',
                            $r['ts'] ?? '',
                        ],
                        $restantes
                    ));

                    $sheets->writeRange($indiceId, 'IndicePacientes!A2', $values);
                }

                \Log::info("✅ Índice actualizado sin {$nombre}");
            }
        } catch (\Throwable $e) {
            \Log::warning("⚠️ No se pudo actualizar índice al eliminar {$nombre}: ".$e->getMessage());
        }
    }

    public function guardarPerfil(string $spreadsheetId, array $perfil): void
    {
        $antes = $this->sheets->getPerfil($spreadsheetId);
        $nombreAntes = trim((string)($antes['NOMBRE_Y_APELLIDO'] ?? ''));
        $nombreNuevo = trim((string)($perfil['NOMBRE_Y_APELLIDO'] ?? ''));

        $perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();
        $this->sheets->setPerfil($spreadsheetId, $perfil);

        if ($nombreNuevo !== '' && $nombreNuevo !== $nombreAntes) {
            // renombrar spreadsheet (Drive)
            $this->drive->renameFile($spreadsheetId, $nombreNuevo);

            // actualizar índice (upsert)
            $indiceId = config('services.google.index_id');
            if ($indiceId) {
                $this->sheets->upsertIndice($indiceId, [
                    $nombreNuevo,
                    $spreadsheetId,
                    Carbon::now()->toIso8601String(),
                ]);
            }
        }

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
