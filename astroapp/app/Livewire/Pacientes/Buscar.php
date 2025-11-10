<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use App\Repositories\PacienteRepository;
use Carbon\Carbon;

class Buscar extends Component
{
    public string $q = '';
    public array $items = [];
    public array $todos = [];
    public array $filtrosSeleccionados = [];

    public ?string $error = null;

    public array $filtrosDisponibles = [
        'FILTRO_MELLIZOS' => 'Mellizos',
        'FILTRO_ADOPTADO' => 'Adoptado',
        'FILTRO_ABUSOS' => 'Abusos',
        'FILTRO_SUICIDIO' => 'Suicidio',
        'FILTRO_ENFERMEDAD' => 'Enfermedad',
    ];

    public function mount(): void
    {
        $this->cargarPacientes();
    }

    private function cargarPacientes(): void
    {
        $this->error = null;

        $ssid = (string) env('GOOGLE_SHEETS_INDEX_ID', '');
        if ($ssid === '') {
            $this->error = 'Falta configurar GOOGLE_SHEETS_INDEX_ID en .env';
            return;
        }

        try {
            $svc = SheetsService::make();
            $this->todos = $svc->readIndice($ssid);

            // Enriquecer la lista (signo y edad)
            $base = [];
            foreach ($this->todos as $r) {
                $fila = [
                    'nombre' => $r['nombre'],
                    'id'     => $r['id'],
                    'ts'     => $r['ts'],
                    'signo'  => '',
                    'edad'   => '',
                    'filtros' => [],
                ];

                try {
                    $perfil = $svc->getPerfil($r['id']);
                    $fila['signo'] = $perfil['SIGNO_SOLAR'] ?? '';
                    $fila['edad']  = $this->edadDesdeFn($perfil['FECHA_NAC'] ?? null);

                    // guardar los filtros (marcados con "SI")
                    foreach ($this->filtrosDisponibles as $campo => $label) {
                        if (($perfil[$campo] ?? '') === 'SI') {
                            $fila['filtros'][] = $campo;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignorar fallas
                }

                $base[] = $fila;
            }

            $this->items = $base;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function updatedQ(): void
    {
        $this->filtrarPacientes();
    }

    public function updatedFiltrosSeleccionados(): void
    {
        $this->filtrarPacientes();
    }

    private function filtrarPacientes(): void
    {
        $q = $this->normalize($this->q);
        $filtros = $this->filtrosSeleccionados;

        $this->items = array_values(array_filter($this->todos, function ($r) use ($q, $filtros) {
            $nombre = $this->normalize($r['nombre'] ?? '');
            $coincideNombre = ($q === '' || str_contains($nombre, $q));

            if (!$coincideNombre) return false;

            if (empty($filtros)) return true;

            try {
                $perfil = SheetsService::make()->getPerfil($r['id']);
                foreach ($filtros as $f) {
                    if (($perfil[$f] ?? '') === 'SI') {
                        return true;
                    }
                }
                return false;
            } catch (\Throwable $e) {
                return false;
            }
        }));
    }

    public function crearPacienteVacio()
    {
        try {
            $repo = PacienteRepository::make();
            // Crear hoja con nombre temporal (se completará luego)
            $id = $repo->crearDesdeTemplate('Paciente sin nombre');
            return redirect()->route('paciente.editar', $id)
                ->with('ok', 'Nuevo paciente creado ✔');
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    private function edadDesdeFn(?string $fn): string|int
    {
        try {
            if (!$fn) return '';
            $d = Carbon::createFromFormat('d/m/Y', trim($fn));
            return $d->diffInYears(Carbon::today());
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim(preg_replace('/\s+/', ' ', $s)));
        $trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ÿ'=>'y'];
        return strtr($s, $trans);
    }

    public function render()
    {
        return view('livewire.pacientes.buscar')->layout('components.layouts.app');
    }
}

