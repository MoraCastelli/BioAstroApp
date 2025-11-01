<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use Carbon\Carbon;

class Buscar extends Component
{
    /** Texto que escribe el usuario en el buscador de encuentro rápido */
    public string $q = '';

    /** Sugerencias del buscador (nombre + id), filtradas localmente */
    public array $sugs = [];

    /** Lista para el panel “Lista de pacientes” (se muestra completa) */
    public array $items = [];

    /** Cache local del índice completo para filtrar sin pegarle a Google */
    public array $todos = [];

    public ?string $error = null;

    // ====== Encuentro rápido ======
    public ?string $selId = null;
    public string $selNombre = '';
    public array $enc = [
        'FECHA' => '',
        'CIUDAD_ULT_CUMPLE' => '',
        'TEMAS_TRATADOS' => '',
        'RESUMEN' => '',
        'EDAD_EN_ESE_ENCUENTRO' => '',
    ];
    public string $msgEncuentro = '';

    public function mount(): void
    {
        $this->cargarTodoYLista();
    }

    private function cargarTodoYLista(): void
    {
        $this->error = null;
        $this->items = [];
        $this->todos = [];
        $this->sugs  = [];

        $ssid = (string) env('GOOGLE_SHEETS_INDEX_ID', '');
        if ($ssid === '') {
            $this->error = 'Falta configurar GOOGLE_SHEETS_INDEX_ID en .env';
            return;
        }

        try {
            $svc         = \App\Services\SheetsService::make();
            $this->todos = $svc->readIndice($ssid); // [{nombre,id,ts}, ...]

            // Lista completa (enriquecimiento best-effort)
            $base = array_map(fn($r) => [
                'nombre' => $r['nombre'],
                'id'     => $r['id'],
                'ts'     => $r['ts'],
                'signo'  => '',
                'edad'   => '',
            ], $this->todos);

            foreach ($base as $i => $it) {
                try {
                    $perfil = $svc->getPerfil($it['id']);
                    $base[$i]['signo'] = $perfil['SIGNO_SOLAR'] ?? '';
                    $base[$i]['edad']  = $this->edadDesdeFn($perfil['FECHA_NAC'] ?? null);
                } catch (\Throwable $e) {
                    // ignoramos fallas por fila
                }
            }

            $this->items = $base;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    
    /** Si querés mantener también el hook de Livewire, dejalo y delega: */
    public function updatedQ($value): void
    {
        $this->buscar((string)$value);
    }

    /** Limpia todo: buscador, sugerencias y selección actual */
    public function limpiarBusqueda(): void
    {
        $this->q = '';
        $this->sugs = [];
        $this->selId = null;
        $this->selNombre = '';
        $this->msgEncuentro = '';
        $this->enc = [
            'FECHA' => '',
            'CIUDAD_ULT_CUMPLE' => '',
            'TEMAS_TRATADOS' => '',
            'RESUMEN' => '',
            'EDAD_EN_ESE_ENCUENTRO' => '',
        ];
    }


    /** Listener explícito que filtra en memoria */
    public function buscar(string $term = ''): void
    {
        $this->q = $term;

        // si no hay texto, vaciamos sugerencias
        $needle = $this->normalize($this->q);
        if ($needle === '') {
            $this->sugs = [];
            return;
        }

        // fallback: si por algún motivo $todos está vacío, recargamos índice 1 vez
        if (empty($this->todos)) {
            try {
                $ssid = (string) env('GOOGLE_SHEETS_INDEX_ID', '');
                if ($ssid !== '') {
                    $this->todos = \App\Services\SheetsService::make()->readIndice($ssid);
                }
            } catch (\Throwable $e) {
                // si falla, seguimos con arreglo vacío (sin romper UI)
            }
        }

        // filtrar SOLO en $todos (no toca Google)
        $norm = fn(string $s) => $this->normalize($s);
        $filtrados = array_values(array_filter($this->todos, function (array $r) use ($needle, $norm) {
            $hay = $norm((string)($r['nombre'] ?? ''));
            return str_starts_with($hay, $needle);
        }));

        // sugerencias mínimas
        $this->sugs = array_map(fn($r) => [
            'nombre' => $r['nombre'],
            'id'     => $r['id'],
        ], $filtrados);
    }

    /** Click en una sugerencia → prepara el form de encuentro rápido */
    public function seleccionarPaciente(string $id): void
    {
        $this->msgEncuentro = '';
        $this->selId = $id;

        // Buscar el nombre en el cache $todos
        foreach ($this->todos as $r) {
            if (($r['id'] ?? '') === $id) {
                $this->selNombre = $r['nombre'] ?? '';
                break;
            }
        }

        $this->enc = [
            'FECHA' => '',
            'CIUDAD_ULT_CUMPLE' => '',
            'TEMAS_TRATADOS' => '',
            'RESUMEN' => '',
            'EDAD_EN_ESE_ENCUENTRO' => '',
        ];
    }

    /** Guarda el encuentro en la pestaña Encuentros del spreadsheet del paciente */
    public function agregarEncuentroRapido(): void
    {
        $this->validate([
            'enc.FECHA' => ['required','regex:/^\d{2}\/\d{2}\/\d{4}$/'],
        ],[
            'enc.FECHA.required' => 'La fecha es obligatoria.',
            'enc.FECHA.regex'    => 'Formato de fecha inválido (DD/MM/AAAA).',
        ]);

        if (!$this->selId) {
            $this->msgEncuentro = 'Primero elegí un paciente.';
            return;
        }

        $svc = SheetsService::make();

        // Edad en ese encuentro a partir de FECHA_NAC
        try {
            $perfil = $svc->getPerfil($this->selId);
            $fn = $perfil['FECHA_NAC'] ?? null;
            $fe = $this->enc['FECHA'] ?? null;
            $this->enc['EDAD_EN_ESE_ENCUENTRO'] = $this->edadDesdeFnEncuentro($fn, $fe);
        } catch (\Throwable $e) {
            $this->enc['EDAD_EN_ESE_ENCUENTRO'] = '';
        }

        // Append
        $svc->appendEncuentro($this->selId, $this->enc);

        $this->msgEncuentro = 'Encuentro agregado ✔';
    }

    /** Calcula edad (años) desde FECHA_NAC (DD/MM/AAAA) a hoy. */
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

    private function edadDesdeFnEncuentro(?string $fn, ?string $fe): string
    {
        try {
            if (!$fn || !$fe) return '';
            $nac = Carbon::createFromFormat('d/m/Y', trim($fn));
            $ref = Carbon::createFromFormat('d/m/Y', trim($fe));
            return (string) $nac->diffInYears($ref);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Normaliza: minúsculas, espacios, tildes. */
    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim(preg_replace('/\s+/', ' ', $s)));
        $trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ÿ'=>'y'];
        return strtr($s, $trans);
    }

    public function render()
    {
        return view('livewire.pacientes.buscar')
            ->layout('components.layouts.app');
    }
}
