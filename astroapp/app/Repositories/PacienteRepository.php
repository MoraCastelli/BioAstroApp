<?php

namespace App\Repositories;

use App\Services\DriveService;
use App\Services\SheetsService;
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
        $spreadsheetId = $this->drive->copyToDbFolder(
            config('services.google.template_id'),
            $this->fileName($nombreApellido)
        );

        $this->sheets->setPerfil($spreadsheetId, [
            'NOMBRE_Y_APELLIDO'     => $nombreApellido,
            'ULTIMA_ACTUALIZACION'  => Carbon::now()->toIso8601String(),
        ]);

        return $spreadsheetId;
    }

    public function guardarPerfil(string $spreadsheetId, array $perfil): void
    {
        $perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();
        $this->sheets->setPerfil($spreadsheetId, $perfil);
    }

    public function agregarEncuentro(string $spreadsheetId, array $enc): void
    {
        $this->sheets->appendEncuentro($spreadsheetId, $enc);
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
}
