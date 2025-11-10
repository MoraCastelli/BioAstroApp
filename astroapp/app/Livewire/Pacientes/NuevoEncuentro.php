<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use Carbon\Carbon;

class NuevoEncuentro extends Component
{
    public string $id;           // ID del paciente (spreadsheetId)
    public array $perfil = [];   // Datos del paciente
    public array $enc = [
        'FECHA' => '',
        'CIUDAD_ULT_CUMPLE' => '',
        'TEMAS_TRATADOS' => '',
        'RESUMEN' => '',
        'EDAD_EN_ESE_ENCUENTRO' => '',
    ];

    public string $mensaje = '';

    public function mount($id)
    {
        $this->id = $id;
        $this->perfil = \App\Services\SheetsService::make()->getPerfil($id);
    }

    public function guardar()
    {
        $this->validate([
            'enc.FECHA' => 'required|regex:/^\d{2}\/\d{2}\/\d{4}$/',
        ], [
            'enc.FECHA.required' => 'La fecha es obligatoria.',
            'enc.FECHA.regex' => 'Formato inválido (DD/MM/AAAA).',
        ]);

        $svc = SheetsService::make();

        // calcular edad si se conoce la fecha de nacimiento
        $fn = $this->perfil['FECHA_NAC'] ?? null;
        $fe = $this->enc['FECHA'] ?? null;
        if ($fn && $fe) {
            try {
                $nac = Carbon::createFromFormat('d/m/Y', $fn);
                $ref = Carbon::createFromFormat('d/m/Y', $fe);
                $this->enc['EDAD_EN_ESE_ENCUENTRO'] = $nac->diffInYears($ref);
            } catch (\Throwable $e) {
                $this->enc['EDAD_EN_ESE_ENCUENTRO'] = '';
            }
        }

        $svc->appendEncuentro($this->id, $this->enc);

        $this->mensaje = 'Encuentro agregado correctamente ✔';

        // regenerar PDF
        try {
            $perfil = $svc->getPerfil($this->id);
            $encuentros = $svc->readEncuentros($this->id);
            (new \App\Services\PdfService)->generarYSubirPdf($perfil, $encuentros, ($perfil['NOMBRE_Y_APELLIDO'] ?? 'Paciente').'.pdf');
        } catch (\Throwable $e) {}

        // redirigir a ver paciente
        return redirect()->route('paciente.ver', $this->id)
                         ->with('ok', 'Encuentro agregado ✔');
    }

    public function render()
    {
        return view('livewire.pacientes.nuevo-encuentro')
            ->layout('components.layouts.app');
    }
}
