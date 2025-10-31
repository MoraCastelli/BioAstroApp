<?php

namespace App\Livewire\Pacientes;

use Livewire\Component;
use App\Services\SheetsService;
use App\Repositories\PacienteRepository;
use Carbon\Carbon;

class Editar extends Component
{
    public string $id;             // spreadsheetId del paciente
    public array $perfil = [];     // Perfil!A1:B...
    public string $mensaje = '';

    // Sección 15: nuevo encuentro (se guarda en hoja Encuentros)
    public array $nuevoEncuentro = [
        'FECHA' => '',
        'CIUDAD_ULT_CUMPLE' => '',
        'TEMAS_TRATADOS' => '',
        'RESUMEN' => '',
        'EDAD_EN_ESE_ENCUENTRO' => '',
    ];

    // Opciones de la Fase de Lunación (11) y su planeta asociado
    public array $fasesLunacion = [
        'Luna Nueva' => 'Sol',
        'Creciente Iluminante' => 'Marte',
        'Cuarto Creciente' => 'Marte',
        'Gibosa Creciente' => 'Júpiter',
        'Luna Llena' => 'Luna',
        'Gibosa Menguante' => 'Saturno',
        'Cuarto Menguante' => 'Saturno',
        'Creciente Menguante (Balsámica)' => 'Neptuno',
    ];

    protected array $rules = [
        'perfil.NOMBRE_Y_APELLIDO' => 'required|min:2',
        'perfil.FECHA_NAC' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
        'perfil.HORA_NAC'  => 'nullable|string|max:10',
        'perfil.CIUDAD_NAC' => 'nullable|string|max:80',
        'perfil.PROVINCIA_NAC' => 'nullable|string|max:80',
        'perfil.PAIS_NAC' => 'nullable|string|max:80',
        'perfil.FECHA_ENCUENTRO_INICIAL' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
        'perfil.HORA_ENCUENTRO_INICIAL'  => 'nullable|string|max:10',
        'perfil.SIGNO_SOLAR' => 'nullable|string|max:30',
        'perfil.FASE_LUNACION_NATAL' => 'nullable|string',
        'perfil.RESUMEN_PARA_PSICOLOGA_URL_AUDIO' => 'nullable|url',
        // … el resto quedan como string|nullable por simplicidad
        'nuevoEncuentro.FECHA' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
        'nuevoEncuentro.CIUDAD_ULT_CUMPLE' => 'nullable|string|max:120',
        'nuevoEncuentro.TEMAS_TRATADOS' => 'nullable|string',
        'nuevoEncuentro.RESUMEN' => 'nullable|string',
    ];

    public function mount($id)
    {
        $this->id = $id;
        $this->perfil = SheetsService::make()->getPerfil($id);

        // Asegurar claves faltantes del template
        $defaults = [
            'NOMBRE_Y_APELLIDO' => '',
            'FOTO_URL' => '',
            'CONTACTO' => '',
            'FECHA_NAC' => '',
            'HORA_NAC' => '',
            'CIUDAD_NAC' => '',
            'PROVINCIA_NAC' => '',
            'PAIS_NAC' => '',
            'ANIO_NAC' => '',
            'CIUDAD_ULT_CUMPLE' => '',
            'PROV_ULT_CUMPLE' => '',
            'PAIS_ULT_CUMPLE' => '',
            'OBSERVACIONES' => '',
            'FILTRO_MELLIZOS' => '',
            'FILTRO_ADOPTADO' => '',
            'FILTRO_ABUSOS' => '',
            'FILTRO_SUICIDIO' => '',
            'FILTRO_ENFERMEDAD' => '',
            'SIGNO_SOLAR' => '',
            'FECHA_ENCUENTRO_INICIAL' => '',
            'HORA_ENCUENTRO_INICIAL' => '',
            'EDAD_EN_ENCUENTRO_INICIAL' => '',
            'SIGNO_SUBYACENTE' => '',
            'BALANCE_ENERGETICO' => '',
            'DISPOSITORES' => '',
            'PROGRESIONES_RETORNOS' => '',
            'FASE_LUNACION_NATAL' => '',
            'PLANETA_ASOCIADO_LUNACION' => '',
            'PRIMERA_VEZ_ASTROLOGIA' => '',
            'PROFESION' => '',
            'VIVO_CON' => '',
            'HOGAR_INFANCIA' => '',
            'ENF_INFANCIA' => '',
            'SINTOMAS_ACTUALES' => '',
            'MOTIVO_CONSULTA' => '',
            'DETALLE_ENCUENTRO_INICIAL' => '',
            'RESUMEN_PARA_PSICOLOGA_URL_AUDIO' => '',
            'RESUMEN_PARA_PSICOLOGA_TEXTO' => '',
            'ULTIMA_ACTUALIZACION' => '',
        ];
        $this->perfil = array_merge($defaults, $this->perfil);
    }

    public function updatedPerfil($value, $key)
    {
        // 6) Edad automática al cambiar fechas
        if (in_array($key, ['FECHA_NAC', 'FECHA_ENCUENTRO_INICIAL'])) {
            $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = $this->calcularEdad(
                $this->perfil['FECHA_NAC'] ?? null,
                $this->perfil['FECHA_ENCUENTRO_INICIAL'] ?? null
            );
        }

        // 11) Planeta asociado según fase
        if ($key === 'FASE_LUNACION_NATAL') {
            $fase = $this->perfil['FASE_LUNACION_NATAL'] ?? '';
            $this->perfil['PLANETA_ASOCIADO_LUNACION'] = $this->fasesLunacion[$fase] ?? '';
        }
    }

    private function calcularEdad(?string $fechaNac, ?string $fechaRef): string
    {
        try {
            if (!$fechaNac || !$fechaRef) return '';
            $nac = Carbon::createFromFormat('d/m/Y', $fechaNac);
            $ref = Carbon::createFromFormat('d/m/Y', $fechaRef);
            return (string) $nac->diffInYears($ref);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function guardar()
    {
        $this->validate();

        // Recalcular edad por las dudas
        $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = $this->calcularEdad(
            $this->perfil['FECHA_NAC'] ?? null,
            $this->perfil['FECHA_ENCUENTRO_INICIAL'] ?? null
        );

        // Timestamp
        $this->perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();

        // Guardar en Sheets
        SheetsService::make()->setPerfil($this->id, $this->perfil);

        // (Opcional) regenerar PDF + subir a Drive aquí si ya lo tenías armado…

        $this->mensaje = 'Perfil guardado correctamente ✔';
        $this->dispatch('guardado-ok'); // por si querés toasts
    }

    public function agregarEncuentro()
    {
        $this->validateOnly('nuevoEncuentro.FECHA');
        // Calcular edad en ese encuentro si hay FN
        $fn = $this->perfil['FECHA_NAC'] ?? null;
        $fe = $this->nuevoEncuentro['FECHA'] ?? null;
        $this->nuevoEncuentro['EDAD_EN_ESE_ENCUENTRO'] = $this->calcularEdad($fn, $fe);

        // Agregar fila a Encuentros
        SheetsService::make()->appendEncuentro($this->id, $this->nuevoEncuentro);

        // Limpiar parcial
        $this->nuevoEncuentro = [
            'FECHA' => '',
            'CIUDAD_ULT_CUMPLE' => '',
            'TEMAS_TRATADOS' => '',
            'RESUMEN' => '',
            'EDAD_EN_ESE_ENCUENTRO' => '',
        ];

        $this->mensaje = 'Encuentro agregado ✔';
        $this->dispatch('encuentro-ok');
    }

    public function render()
    {
        return view('livewire.pacientes.editar')
            ->layout('components.layouts.app');
    }
}
