<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use Google\Service\Sheets;

class Buscar extends Component
{
    public string $q = '';
    public array $items = [];
    public string $indiceId = ''; // ponelo en mount()

    public function mount() {
        $this->indiceId = env('GOOGLE_SHEETS_INDEX_ID', '');
    }

    public function updatedQ() {
        $this->buscar();
    }

    public function buscar() {
        if (!$this->indiceId) return;
        $sheets = SheetsService::make();
        $values = (new Sheets($sheets::make()->sheets->getClient()))->spreadsheets_values
            ->get($this->indiceId, 'IndicePacientes!A2:C')->getValues() ?? [];

        $pref = mb_strtoupper(trim($this->q));
        $this->items = array_values(array_filter(array_map(function($r){
            return ['nombre'=>$r[0]??'', 'id'=>$r[1]??'', 'ts'=>$r[2]??''];
        }, $values), function($row) use ($pref){
            return $pref === '' || str_starts_with(mb_strtoupper($row['nombre']), $pref);
        }));
    }

    public function render() { return view('livewire.pacientes.buscar'); }
}
