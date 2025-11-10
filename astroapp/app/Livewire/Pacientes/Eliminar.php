<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Repositories\PacienteRepository;
use App\Services\SheetsService;

class Eliminar extends Component
{
    public string $id;
    public string $nombre = '';
    public bool $eliminando = false;

    public function mount($id)
    {
        $this->id = $id;
        $this->nombre = '(registro no disponible)';

        $indiceId = env('GOOGLE_SHEETS_INDEX_ID');

        // 1) Intentar obtener nombre desde índice
        if ($indiceId) {
            try {
                $filas = \App\Services\SheetsService::make()->readIndice($indiceId);
                foreach ($filas as $row) {
                    if (($row['id'] ?? '') === $id) {
                        $this->nombre = $row['nombre'] ?? $this->nombre;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning("No se pudo leer índice en Eliminar::mount: ".$e->getMessage());
            }
        }

        // 2) Si no lo encontramos, intentamos perfil (por si el Sheet sigue vivo)
        if ($this->nombre === '(registro no disponible)') {
            try {
                $perfil = \App\Services\SheetsService::make()->getPerfil($id);
                $this->nombre = $perfil['NOMBRE_Y_APELLIDO'] ?? $this->nombre;
            } catch (\Throwable $e) {
                \Log::warning("No se pudo leer perfil de {$id}: ".$e->getMessage());
            }
        }
    }



    public function eliminar()
    {
        $this->eliminando = true;

        $repo = PacienteRepository::make();
        $repo->eliminarPaciente($this->id, $this->nombre);

        session()->flash('ok', "Paciente {$this->nombre} eliminado correctamente ✔");
        return redirect()->route('buscar');
    }

    public function render()
    {
        return view('livewire.pacientes.eliminar')->layout('components.layouts.app');
    }
}
