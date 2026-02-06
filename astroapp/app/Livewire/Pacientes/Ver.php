<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use App\Services\SabianoService; 
use Carbon\Carbon;


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
    public bool $ocultarNombres = false;
    public array $audios = [];


    
    public array $calc = [
        'edad' => ''
    ];


    public function mount($id): void
    {
        $this->id = (string) $id;
        $this->ocultarNombres = (bool) session('pacientes.ocultar_nombres', false);

        try {
            $svc = SheetsService::make();

            $this->perfil     = $svc->getPerfil($this->id);
            $this->encuentros = $svc->readEncuentros($this->id);
            $this->sabianos   = $svc->readSabianos($this->id);
            $this->imagenes   = method_exists($svc, 'readImagenes') ? $svc->readImagenes($this->id) : [];
            $this->audios     = method_exists($svc, 'readAudios')   ? $svc->readAudios($this->id)   : [];

            $this->calc['edad'] = $this->calcularEdadHoy($this->perfil['FECHA_NAC'] ?? null);

        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }



    public function toggleNombre(): void
    {
        $this->ocultarNombres = !$this->ocultarNombres;

        session(['pacientes.ocultar_nombres' => $this->ocultarNombres]);
    }

    private function calcularEdadHoy(?string $nac): string
    {
        try {
            $nac = trim((string)$nac);
            if ($nac === '') return '';

            // normalizar separadores
            $nac = str_replace(['-', '.', ' '], '/', $nac);

            // intentar DD/MM/AAAA (con o sin ceros)
            if (preg_match('~^(\d{1,2})/(\d{1,2})/(\d{4})$~', $nac, $m)) {
                $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mth = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $y = $m[3];
                $nac = "{$d}/{$mth}/{$y}";
                $dt = Carbon::createFromFormat('d/m/Y', $nac);
                return (string) $dt->diffInYears(Carbon::today());
            }

            // intentar AAAA/MM/DD (por si te llega desde algún lado así)
            if (preg_match('~^(\d{4})/(\d{1,2})/(\d{1,2})$~', $nac, $m)) {
                $y = $m[1];
                $mth = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $d = str_pad($m[3], 2, '0', STR_PAD_LEFT);
                $dt = Carbon::createFromFormat('Y/m/d', "{$y}/{$mth}/{$d}");
                return (string) $dt->age;
            }

            return '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function render()
    {
        return view('livewire.pacientes.ver', [
            'perfil' => $this->perfil,
            'encuentros' => $this->encuentros,
            'imagenes' => $this->imagenes,
            'audios' => $this->audios,
            'calc' => $this->calc,
            'error' => $this->error,
            'id' => $this->id,
            'sabianos' => $this->sabianos,
            'ocultarNombres' => $this->ocultarNombres,
        ])->layout('components.layouts.app');
    }


}
