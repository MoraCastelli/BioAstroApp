<?php

namespace App\Livewire\Pacientes;

use Carbon\Carbon;
use Livewire\Component;

class Editar extends Component
{
    public string $id;      // spreadsheetId del paciente
    public array $perfil = [];
    public string $mensaje = '';

    public function mount($id)
    {
        $this->id = $id;
        // Más adelante: cargar $this->perfil desde SheetsService
    }

    public function guardar()
    {
        // --- calcular edad automática ---
        $fn = $this->perfil['FECHA_NAC'] ?? null;
        $fe = $this->perfil['FECHA_ENCUENTRO_INICIAL'] ?? null;

        if ($fn && $fe) {
            try {
                $nac = Carbon::createFromFormat('d/m/Y', $fn);
                $enc = Carbon::createFromFormat('d/m/Y', $fe);
                $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = $nac->diffInYears($enc);
            } catch (\Exception $e) {
                // si el formato está mal, no rompe la app
                $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = '';
            }
        }

        // --- actualizar timestamp ---
        $this->perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();

        // --- guardar en Google Sheets ---
        \App\Services\SheetsService::make()->setPerfil($this->id, $this->perfil);

        // --- regenerar PDF (ya explicado en paso 10) ---
        $tmp = storage_path('app/tmp/paciente.pdf');
        @mkdir(dirname($tmp), 0777, true);
        (new \App\Services\PdfService())->generarPdfLocal($this->perfil, $tmp);

        $nombre = ($this->perfil['NOMBRE_Y_APELLIDO'] ?? 'Paciente').'.pdf';
        $drive = \App\Services\DriveService::make();
        $drive->deleteByNameInFolder(config('services.google.files_folder_id'), $nombre);
        $fid = $drive->uploadPdfToFiles($tmp, $nombre);
        $drive->makeAnyoneReader($fid);

        $this->mensaje = 'Guardado y PDF actualizado ✔';
    }

    public function render()
    {
        return view('livewire.pacientes.editar');
    }
}
