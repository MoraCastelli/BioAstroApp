<?php

namespace App\Livewire\Pacientes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\SheetsService;
use App\Services\DriveService;
use Carbon\Carbon;
use App\Services\SabianoService;

class Editar extends Component
{
    use WithFileUploads;

    public string $id;
    public array $perfil = [];
    public string $mensaje = '';
    public $fotoUpload;

    public array $nuevoEncuentro = [
        'FECHA' => '',
        'CIUDAD_ULT_CUMPLE' => '',
        'TEMAS_TRATADOS' => '',
        'RESUMEN' => '',
        'EDAD_EN_ESE_ENCUENTRO' => '',
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
        'perfil.RESUMEN_PARA_PSICOLOGA_URL_AUDIO' => 'nullable|url',

        'fotoUpload' => 'nullable|image|max:5120',
        'nuevoEncuentro.FECHA' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
    ];

    public function mount($id)
    {
        $this->id = $id;
        $perfil = SheetsService::make()->getPerfil($id);

        // Claves EXACTAS según SheetsService::setPerfil()
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

            'SIGNO_SOL' => '',
            'GRADO_SOL' => '',
            'SIGNO_LUNA' => '',
            'GRADO_LUNA' => '',

            'SIGNO_SOLAR' => '', // aunque no lo uses lo mantenemos porque existe en Sheets

            'FECHA_ENCUENTRO_INICIAL' => '',
            'HORA_ENCUENTRO_INICIAL' => '',
            'EDAD_EN_ENCUENTRO_INICIAL' => '',

            'SIGNO_SUBYACENTE' => '',
            'BALANCE_ENERGETICO' => '',
            'DISPOSITORES' => '',
            'PROGRESIONES_RETORNOS' => '',

            'FASE_LUNACION_NATAL' => '',
            'PLANETA_ASOCIADO_LUNACION' => '',
            'SIGNO_ASOCIADO_LUNACION' => '',
            'IMAGEN_FASE_LUNACION' => '',
            'TEXTO_FASE_LUNACION' => '',

            'SIGNO_SABIANO' => '',
            'GRADO_SABIANO' => '',
            'TITULO_SABIANO' => '',
            'IMAGEN_SABIANO' => '',
            'TEXTO_SABIANO' => '',

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

        $this->perfil = array_merge($defaults, $perfil);
    }

    public function updatedPerfil($value, $key)
    {
        // EDAD
        if (in_array($key, ['FECHA_NAC', 'FECHA_ENCUENTRO_INICIAL'])) {
            $this->perfil['EDAD_EN_ENCUENTRO_INICIAL'] = $this->calcularEdad(
                $this->perfil['FECHA_NAC'] ?? null,
                $this->perfil['FECHA_ENCUENTRO_INICIAL'] ?? null
            );
        }

        // SABIANO
        if (in_array($key, ['SIGNO_SABIANO', 'GRADO_SABIANO'])) {
            $signo = $this->perfil['SIGNO_SABIANO'];
            $grado = intval($this->perfil['GRADO_SABIANO']);

            if ($signo && $grado) {
                $sab = SabianoService::get($signo, $grado);
                if ($sab) {
                    $this->perfil['TITULO_SABIANO'] = $sab['titulo'] ?? '';
                    $this->perfil['IMAGEN_SABIANO'] = $sab['imagen'] ?? '';
                    $this->perfil['TEXTO_SABIANO'] = $sab['texto'] ?? '';
                }
            }
        }

        // LUNACIÓN
        if (in_array($key, ['SIGNO_SOL','GRADO_SOL','SIGNO_LUNA','GRADO_LUNA'])) {

            $solSigno  = $this->perfil['SIGNO_SOL'];
            $solGrado  = intval($this->perfil['GRADO_SOL']);
            $lunaSigno = $this->perfil['SIGNO_LUNA'];
            $lunaGrado = intval($this->perfil['GRADO_LUNA']);

            if ($solSigno && $solGrado && $lunaSigno && $lunaGrado) {

                $fase = $this->calcularFaseLunacionInterno($solSigno, $solGrado, $lunaSigno, $lunaGrado);

                if ($fase) {
                    $this->perfil['FASE_LUNACION_NATAL']       = $fase['nombre'];
                    $this->perfil['PLANETA_ASOCIADO_LUNACION'] = $fase['planeta'];
                    $this->perfil['SIGNO_ASOCIADO_LUNACION']   = $fase['signo'];
                    $this->perfil['IMAGEN_FASE_LUNACION']      = $fase['imagen'];
                    $this->perfil['TEXTO_FASE_LUNACION']       = $fase['texto'];
                }
            }
        }
    }

    private function calcularEdad(?string $nac, ?string $ref): string
    {
        try {
            if (!$nac || !$ref) return '';
            return Carbon::createFromFormat('d/m/Y', $nac)
                ->diffInYears(Carbon::createFromFormat('d/m/Y', $ref));
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function guardar()
    {
        $this->validate();
        $this->perfil['ULTIMA_ACTUALIZACION'] = Carbon::now()->toIso8601String();

        SheetsService::make()->setPerfil($this->id, $this->perfil);

        return redirect()->route('paciente.ver', ['id' => $this->id]);
    }

    private function zodiacToDegrees(string $signo, int $grado): ?float
    {
        $signo = strtolower(trim($signo));
        $offsets = [
            'aries' => 0,
            'tauro' => 30,
            'geminis' => 60, 'géminis' => 60,
            'cancer' => 90, 'cáncer' => 90,
            'leo' => 120,
            'virgo' => 150,
            'libra' => 180,
            'escorpio' => 210,
            'sagitario' => 240,
            'capricornio' => 270,
            'acuario' => 300,
            'piscis' => 330,
        ];
        return $offsets[$signo] ?? null
            ? $offsets[$signo] + $grado
            : null;
    }

    private function calcularFaseLunacionInterno(string $signoSol, int $gradoSol, string $signoLuna, int $gradoLuna): ?array
    {
        $sol  = $this->zodiacToDegrees($signoSol, $gradoSol);
        $luna = $this->zodiacToDegrees($signoLuna, $gradoLuna);

        if ($sol === null || $luna === null) return null;

        $angulo = fmod(($luna - $sol + 360), 360);
        $fases  = config('fases_lunacion');

        if ($angulo < 45)  return ['nombre'=>'Luna Nueva'] + $fases['Luna Nueva'];
        if ($angulo < 90)  return ['nombre'=>'Luna Creciente'] + $fases['Luna Creciente'];
        if ($angulo <135)  return ['nombre'=>'Luna Cuarto Creciente'] + $fases['Luna Cuarto Creciente'];
        if ($angulo <180)  return ['nombre'=>'Luna Gibosa'] + $fases['Luna Gibosa'];
        if ($angulo <225)  return ['nombre'=>'Luna Llena'] + $fases['Luna Llena'];
        if ($angulo <270)  return ['nombre'=>'Luna Menguante'] + $fases['Luna Menguante'];
        if ($angulo <315)  return ['nombre'=>'Luna Cuarto Menguante'] + $fases['Luna Cuarto Menguante'];

        return ['nombre'=>'Luna Balsamica'] + $fases['Luna Balsamica'];
    }

    public function render()
    {
        return view('livewire.pacientes.editar')
            ->layout('components.layouts.app');
    }
}
