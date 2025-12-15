<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use App\Services\SabianoService; 

class Ver extends Component
{
    public string $id;
    public array $perfil = [];
    public array $encuentros = [];
    public array $imagenes = [];
    public ?string $error = null;
    public array $sabianos = [];
    public array $nuevoSabiano = ['SIGNO'=>'','GRADO'=>''];
    public string $mensaje = '';

    public function mount($id): void
    {
        $this->id = (string) $id;

        try {
            $svc = SheetsService::make();

            $this->perfil = $svc->getPerfil($this->id);
            $this->encuentros = $svc->readEncuentros($this->id);

            $this->sabianos = $svc->readSabianos($this->id);

            $this->imagenes = method_exists($svc, 'readImagenes')
                ? $svc->readImagenes($this->id)
                : [];
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.pacientes.ver', [
            'perfil' => $this->perfil,
            'encuentros' => $this->encuentros,
            'imagenes' => $this->imagenes,
            'error' => $this->error,
            'id' => $this->id,
            'sabianos' => $this->sabianos,
        ])->layout('components.layouts.app');
    }
}
