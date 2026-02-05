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
    public array $matchMap = [];
    public bool $ocultarNombres = false;
    public ?string $error = null;

    public array $filtrosDisponibles = [
        'FILTRO_MELLIZOS'          => 'Mellizos',
        'FILTRO_ADOPTADO'          => 'Adoptado',
        'FILTRO_ABUSOS'            => 'Abusos',
        'FILTRO_SUICIDIO'          => 'Suicidio',
        'FILTRO_SALUD'             => 'Salud',
        'FILTRO_TEA'               => 'TEA',
        'FILTRO_HISTORICOS'        => 'Históricos',
        'FILTRO_FILOSOFOS'         => 'Filósofos',
        'FILTRO_PAISES'            => 'Países',
        'FILTRO_ECLIPSES'          => 'Eclipses',
        'FILTRO_ANUALES'           => 'Anuales',
        'FILTRO_MOMENTOS_CRITICOS' => 'Momentos críticos',
        'FILTRO_INICIO_CICLOS'     => 'Inicio de ciclos',
    ];


    public function toggleNombres(): void
    {
        $this->ocultarNombres = !$this->ocultarNombres;
        session(['pacientes.ocultar_nombres' => $this->ocultarNombres]);
    }


    public function mount(): void
    {
        $this->ocultarNombres = (bool) session('pacientes.ocultar_nombres', false);
        $this->cargarPacientes();
    }

    public bool $showFiltros = false;

    public function toggleFiltros(): void
    {
        $this->showFiltros = !$this->showFiltros;
    }

    public function closeFiltros(): void
    {
        $this->showFiltros = false;
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

            $indice = array_values(array_filter(
                $svc->readIndice($ssid),
                fn ($r) => !empty(trim((string)($r['id'] ?? '')))
            ));

            $uniq = []; // clave = spreadsheetId

            foreach ($indice as $r) {
                $id = trim((string)$r['id']);
                if ($id === '') {
                    continue;
                }

                // Si ya procesamos este ID, lo salteamos (evita duplicados)
                if (isset($uniq[$id])) {
                    continue;
                }

                $fila = [
                    'nombre'  => $r['nombre'] ?? 'Paciente',
                    'id'      => $id,
                    'ts'      => $r['ts'] ?? '',
                    'signo'   => '',
                    'edad'    => '',
                    'filtros' => [],
                ];

                try {
                    $perfil = $svc->getPerfil($id);

                    $fila['signo'] = $perfil['SIGNO_SOLAR'] ?? '';
                    $fila['edad']  = $this->edadDesdeFn($perfil['FECHA_NAC'] ?? null);

                    // Filtros marcados como "SI"
                    foreach ($this->filtrosDisponibles as $campo => $label) {
                        if ($this->truthy($perfil[$campo] ?? null)) {
                            $fila['filtros'][] = $campo;
                        }
                    }

                    // Si en el perfil ya tiene nombre real, pisa al del índice
                    if (!empty($perfil['NOMBRE_Y_APELLIDO'])) {
                        $fila['nombre'] = $perfil['NOMBRE_Y_APELLIDO'];
                    }
                } catch (\Throwable $e) {
                    // ignoramos errores de perfiles individuales
                }

                // Guardamos deduplicado
                $uniq[$id] = $fila;
            }

            $this->todos = array_values($uniq);
            $this->items = $this->todos;

        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    private function truthy($v): bool
    {
        if (is_bool($v)) return $v;
        if (is_int($v)) return $v === 1;

        $s = mb_strtolower(trim((string)$v));

        return in_array($s, [
            '1', 'true', 'si', 'sí', 'verdadero', 'x', 'ok', 'yes'
        ], true);
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
        $sel = $this->filtrosSeleccionados;

        $this->matchMap = [];

        $this->items = array_values(array_filter($this->todos, function ($r) use ($q, $sel) {
            if (empty($r['id'])) return false;

            $nombre = $this->normalize($r['nombre'] ?? '');
            if ($q !== '' && !str_contains($nombre, $q)) return false;

            // si no hay filtros seleccionados -> pasa y no marca match
            if (empty($sel)) {
                $this->matchMap[$r['id']] = [];
                return true;
            }

            $tiene = $r['filtros'] ?? [];
            $match = array_values(array_intersect($sel, $tiene));

            $this->matchMap[$r['id']] = $match;

            // OR (como hoy): con que coincida uno, entra
            return count($match) > 0;
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

