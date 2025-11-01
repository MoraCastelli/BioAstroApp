<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use Carbon\Carbon;

class Buscar extends Component
{
    public string $q = '';
    public array $items = [];
    public ?string $error = null;

    public function mount(): void
    {
        $this->cargar();
    }

    public function updatedQ(): void
    {
        $this->cargar();
    }

    private function cargar(): void
    {
        $this->error = null;
        $this->items = [];

        $ssid = (string) env('GOOGLE_SHEETS_INDEX_ID', '');
        if ($ssid === '') {
            $this->error = 'Falta configurar GOOGLE_SHEETS_INDEX_ID en .env';
            return;
        }

        try {
            $svc  = SheetsService::make();
            $rows = $svc->readIndice($ssid);  // nombre, id, ts

            // 1) Filtrar por prefijo
            $needle = mb_strtolower(trim($this->q));
            $filtrados = array_values(array_filter($rows, function (array $row) use ($needle) {
                if ($needle === '') return true;
                $hay = mb_strtolower($row['nombre'] ?? '');
                return str_starts_with($hay, $needle);
            }));

            // 2) Enriquecer: signo solar + edad (a hoy) desde Perfil
            $out = [];
            foreach ($filtrados as $r) {
                $perfil = $svc->getPerfil($r['id']); // garantiza pestaña Perfil

                $signo = $perfil['SIGNO_SOLAR'] ?? '';
                $edad  = $this->edadDesdeFn($perfil['FECHA_NAC'] ?? null);

                $out[] = [
                    'nombre' => $r['nombre'],
                    'id'     => $r['id'],
                    'ts'     => $r['ts'],
                    'signo'  => $signo,
                    'edad'   => $edad, // número entero (0..120) o '' si no se pudo
                ];
            }

            $this->items = $out;
        }
        catch (\RuntimeException $e) {
            if ($e->getMessage() === 'GOOGLE_OAUTH_NOT_AUTHENTICATED') {
                $this->error = 'No estás conectado a Google. Iniciá sesión y volvé a intentar.';
            } else {
                $this->error = $e->getMessage();
            }
        }
        catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
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

    public function render()
    {
        return view('livewire.pacientes.buscar')
            ->layout('components.layouts.app');
    }

    // ====== Encuentro rápido (debajo de "Crear paciente") ======
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

    public function seleccionarPaciente(string $id): void
    {
        $this->msgEncuentro = '';
        $this->selId = $id;

        // Buscar el nombre desde los items cargados
        $row = collect($this->items)->firstWhere('id', $id);
        $this->selNombre = $row['nombre'] ?? '';

        // limpiar formulario de encuentro
        $this->enc = [
            'FECHA' => '',
            'CIUDAD_ULT_CUMPLE' => '',
            'TEMAS_TRATADOS' => '',
            'RESUMEN' => '',
            'EDAD_EN_ESE_ENCUENTRO' => '',
        ];
    }

    public function agregarEncuentroRapido(): void
    {
        $this->validate([
            'enc.FECHA' => ['required','regex:/^\d{2}\/\d{2}\/\d{4}$/'],
        ],[
            'enc.FECHA.required' => 'La fecha es obligatoria.',
            'enc.FECHA.regex' => 'Formato de fecha inválido (DD/MM/AAAA).',
        ]);

        if (!$this->selId) {
            $this->msgEncuentro = 'Primero elegí un paciente.';
            return;
        }

        $svc = SheetsService::make();

        // calcular edad en ese encuentro usando la FECHA_NAC del perfil
        $perfil = $svc->getPerfil($this->selId);
        $fn = $perfil['FECHA_NAC'] ?? null;
        $fe = $this->enc['FECHA'] ?? null;
        $this->enc['EDAD_EN_ESE_ENCUENTRO'] = $this->edadDesdeFnEncuentro($fn, $fe);

        // guardar en pestaña Encuentros del mismo spreadsheet
        $svc->appendEncuentro($this->selId, $this->enc);

        $this->msgEncuentro = 'Encuentro agregado ✔';

        // limpiar solo texto largo si querés
        // $this->enc['RESUMEN'] = '';
    }

    private function edadDesdeFnEncuentro(?string $fn, ?string $fe): string
    {
        try {
            if (!$fn || !$fe) return '';
            $nac = \Carbon\Carbon::createFromFormat('d/m/Y', trim($fn));
            $ref = \Carbon\Carbon::createFromFormat('d/m/Y', trim($fe));
            return (string) $nac->diffInYears($ref);
        } catch (\Throwable $e) {
            return '';
        }
    }

}
