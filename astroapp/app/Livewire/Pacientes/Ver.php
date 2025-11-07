<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;

class Ver extends Component
{
    public string $id;
    public array $perfil = [];

    public function mount($id)
    {
        $this->id = $id;
        $this->perfil = SheetsService::make()->getPerfil($id);
    }

    public function render()
    {
        return view('livewire.pacientes.ver')
            ->layout('components.layouts.app');
    }
}
