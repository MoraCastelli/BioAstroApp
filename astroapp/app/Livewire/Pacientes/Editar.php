<?php

namespace App\Livewire\Pacientes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\SheetsService;
use App\Services\DriveService;
use Carbon\Carbon;

class Editar extends Component
{
    use WithFileUploads;

    public string $id;
    public array $perfil = [];
    public string $mensaje = '';

    // Upload local temporal (drag & drop o selector)
    public $fotoUpload;

    public array $nuevoEncuentro = [
        'FECHA' => '',
        'CIUDAD_ULT_CUMPLE' => '',
        'TEMAS_TRATADOS' => '',
        'RESUMEN' => '',
        'EDAD_EN_ESE_ENCUENTRO' => '',
    ];

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
        'perfil.ANIO_NAC'  => 'nullable|string|max:4',
        'perfil.CIUDAD_NAC' => 'nullable|string|max:80',
        'perfil.PROVINCIA_NAC' => 'nullable|string|max:80',
        'perfil.PAIS_NAC' => 'nullable|string|max:80',
        'perfil.FECHA_ENCUENTRO_INICIAL' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
        'perfil.HORA_ENCUENTRO_INICIAL'  => 'nullable|string|max:10',
        'perfil.SIGNO_SOLAR' => 'nullable|string|max:30',
        'perfil.FASE_LUNACION_NATAL' => 'nullable|string',
        'perfil.RESUMEN_PARA_PSICOLOGA_URL_AUDIO' => 'nullable|url',

        // Upload imagen (drag & drop)
        'fotoUpload' => 'nullable|image|max:5120', // 5MB
        'nuevoEncuentro.FECHA' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
    ];

    public function mount($id)
    {
        $this->id = $id;
        $this->perfil = SheetsService::make()->getPerfil($id);

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
        if (in_array($key, ['FECHA_NAC', 'FECHA_ENCUENTRO_INICIAL'])) {
            $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = $this->calcularEdad(
                $this->perfil['FECHA_NAC'] ?? null,
                $this->perfil['FECHA_ENCUENTRO_INICIAL'] ?? null
            );
        }

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
        $this->dispatch('ui-loading', true);
        $this->validate();

        $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = $this->calcularEdad(
            $this->perfil['FECHA_NAC'] ?? null,
            $this->perfil['FECHA_ENCUENTRO_INICIAL'] ?? null
        );
        $this->perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();

        SheetsService::make()->setPerfil($this->id, $this->perfil);

        // PDF (si ya lo tenías armado, acá iría regeneración + subida)
        $this->mensaje = 'Perfil guardado correctamente ✔';

        $this->dispatch('guardado-ok');         // toast si querés
        $this->dispatch('scroll-top');          // scrollear arriba
        $this->dispatch('ui-loading', false);   // apagar loader
    }

    public function subirFoto()
    {
        // 1) Validar que haya archivo
        $this->validateOnly('fotoUpload'); // 'image|max:5120' ya lo tenías
        if (!$this->fotoUpload) {
            $this->mensaje = 'No hay archivo para subir.';
            return;
        }

        // 2) Guardar el tmp local de forma segura
        //    (livewire-tmp a veces se limpia; movelo a 'tmp' con nombre único)
        $ext = strtolower($this->fotoUpload->getClientOriginalExtension() ?: 'jpg');
        $tmpRelPath = $this->fotoUpload->storeAs('tmp', Str::uuid().'.'.$ext, 'local');
        $abs = Storage::disk('local')->path($tmpRelPath);

        if (!is_file($abs)) {
            $this->mensaje = 'No se pudo guardar el archivo temporal.';
            return;
        }

        // 3) Preparar nombre en Drive
        $nombreBase = trim($this->perfil['NOMBRE_Y_APELLIDO'] ?: 'Paciente');
        $nombre = $nombreBase.' - '.date('Ymd-His').'.'.$ext;

        // 4) Asegurar carpeta y subir
        $drive = \App\Services\DriveService::make();
        $folderId = $drive->ensureFolderByName('Cartas Astrales'); // crea si no existe

        try {
            $fileId = $drive->uploadImageToFolder($abs, $nombre, $folderId);
            $drive->makeAnyoneReader($fileId);

            // probá primero con el thumbnail (suele cargar siempre) o la vista
            //$publicUrl = $drive->getPublicContentUrl($fileId);
            $publicUrl = $drive->getThumbnailUrl($fileId, 1000); // si preferís

            $this->perfil['FOTO_URL'] = $publicUrl;
            $this->mensaje = 'Imagen subida ✔';
        } finally {
            // 5) Limpiar el tmp local pase lo que pase
            @Storage::disk('local')->delete($tmpRelPath);
        }

        // 6) Limpiar el input para que puedas subir otra
        $this->fotoUpload = null;
    }


    public function agregarEncuentro()
    {
        $this->dispatch('ui-loading', true);

        $this->validateOnly('nuevoEncuentro.FECHA');
        $fn = $this->perfil['FECHA_NAC'] ?? null;
        $fe = $this->nuevoEncuentro['FECHA'] ?? null;
        $this->nuevoEncuentro['EDAD_EN_ESE_ENCUENTRO'] = $this->calcularEdad($fn, $fe);

        SheetsService::make()->appendEncuentro($this->id, $this->nuevoEncuentro);

        $this->nuevoEncuentro = [
            'FECHA' => '',
            'CIUDAD_ULT_CUMPLE' => '',
            'TEMAS_TRATADOS' => '',
            'RESUMEN' => '',
            'EDAD_EN_ESE_ENCUENTRO' => '',
        ];

        $this->mensaje = 'Encuentro agregado ✔';
        $this->dispatch('encuentro-ok');
        $this->dispatch('ui-loading', false);
    }

    public function render()
    {
        return view('livewire.pacientes.editar')->layout('components.layouts.app');
    }
}
