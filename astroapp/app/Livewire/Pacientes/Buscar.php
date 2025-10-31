<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;

class Buscar extends Component
{
    public string $q = '';
    public array $items = [];
    public ?string $error = null;

    public function mount() { $this->cargar(); }
    public function updatedQ() { $this->cargar(); }

    private function cargar(): void
    {
        $this->error = null;
        $ssid = env('GOOGLE_SHEETS_INDEX_ID');
        if (!$ssid) { $this->items = []; return; }

        try {

            $s = SheetsService::make();

            // aseguramos la pestaña y leemos
            $ref = 'IndicePacientes!A2:C10000';
            $s->ensureIndiceSheet($ssid); // <-- agrega este método como public en tu SheetsService si no lo está
            $values = $s->sheets->spreadsheets_values->get($ssid, $ref)->getValues() ?? [];

            $rows = array_map(fn($r) => [
                'nombre' => $r[0] ?? '',
                'id'     => $r[1] ?? '',
                'ts'     => $r[2] ?? '',
            ], $values);

            $q = mb_strtolower(trim($this->q));
            $this->items = array_values(array_filter($rows, function($row) use ($q){
                if ($q === '') return true;
                return str_starts_with(mb_strtolower($row['nombre']), $q);
            }));
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            $this->items = [];
        }
    }

    public function render() {
        return view('livewire.pacientes.buscar')->layout('components.layouts.app');
    }
}