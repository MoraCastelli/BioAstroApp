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
    public array $imagenesExistentes = [];


    public $fotoUpload; // (si después lo usás para carta/foto)
    public string $imgUltimaUrl = '';

    public array $calc = [
        'edad' => '',
        'fase' => ['nombre'=>'','planeta'=>'','signo'=>'','texto'=>'','imagen'=>''],
        'sabiano' => ['titulo'=>'','imagen'=>'','texto'=>''],
    ];

    public bool $verMasCalc = false;

    // Para renombre
    private string $nombreOriginal = '';

    // Imagenes (pestaña "Imagenes")
    public $imgUpload;
    public string $imgNombre = '';
    public string $imgDescripcion = '';

    // (si todavía no está implementado en SheetsService, lo dejamos sin romper)
    public array $sabianos = [];
    public array $nuevoSabiano = ['SIGNO'=>'','GRADO'=>''];

    // Encuentros (lo vas a usar en otra pantalla, lo dejamos acá por compatibilidad)
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
        'perfil.RESUMEN_PARA_PSICOLOGA_URL_AUDIO' => 'nullable|url',

        // carta/foto (si la seguís usando)
        'fotoUpload' => 'nullable|image|max:5120',

        // subir imagen a hoja Imagenes
        'imgUpload' => 'nullable|image|max:5120',
        'imgNombre' => 'required_with:imgUpload|string|max:80',
        'imgDescripcion' => 'nullable|string|max:400',

        'nuevoEncuentro.FECHA' => 'nullable|regex:/^\d{2}\/\d{2}\/\d{4}$/',
    ];

    public function mount($id): void
    {
        

        $this->id = (string)$id;

        $svc = SheetsService::make();
        $perfil = $svc->getPerfil($this->id);

        $this->nombreOriginal = (string)($perfil['NOMBRE_Y_APELLIDO'] ?? '');

        // Defaults
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
            'FILTRO_SALUD' => '',
            'FILTRO_TEA' => '',
            'FILTRO_HISTORICOS' => '',
            'FILTRO_FILOSOFOS' => '',
            'FILTRO_PAISES' => '',
            'FILTRO_ECLIPSES' => '',
            'FILTRO_ANUALES' => '',
            'FILTRO_MOMENTOS_CRITICOS' => '',
            'FILTRO_INICIO_CICLOS' => '',

            'SIGNO_SOL' => '',
            'GRADO_SOL' => '',
            'SIGNO_LUNA' => '',
            'GRADO_LUNA' => '',

            'SIGNO_SOLAR' => '',

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
        foreach ([
        'FILTRO_MELLIZOS',
        'FILTRO_ADOPTADO',
        'FILTRO_ABUSOS',
        'FILTRO_SUICIDIO',
        'FILTRO_SALUD',
        'FILTRO_TEA',
        'FILTRO_HISTORICOS',
        'FILTRO_FILOSOFOS',
        'FILTRO_PAISES',
        'FILTRO_ECLIPSES',
        'FILTRO_ANUALES',
        'FILTRO_MOMENTOS_CRITICOS',
        'FILTRO_INICIO_CICLOS',
        ] as $k) {

        $v = $this->perfil[$k] ?? '';
        $v = is_string($v) ? strtoupper(trim($v)) : $v;

        $this->perfil[$k] = in_array($v, ['SI','TRUE','1','ON','X'], true);
        }

        // Calcs iniciales
        $this->calc['edad'] = $this->calcularEdadHoy($this->perfil['FECHA_NAC'] ?? null);

        $this->sabianos = method_exists(SheetsService::make(), 'readSabianos')
            ? SheetsService::make()->readSabianos($this->id)
            : [];

        // Sabiano panel inicial
        if (!empty($this->perfil['SIGNO_SABIANO']) && !empty($this->perfil['GRADO_SABIANO'])) {
            $sab = SabianoService::get($this->perfil['SIGNO_SABIANO'], (int)$this->perfil['GRADO_SABIANO']);
            if ($sab) {
                $this->calc['sabiano'] = [
                    'titulo' => $sab['titulo'] ?? '',
                    'imagen' => $sab['imagen'] ?? '',
                    'texto'  => $sab['texto'] ?? '',
                ];
            }
        }

        // Fase panel inicial
        $solSigno  = $this->perfil['SIGNO_SOL'] ?? '';
        $solGrado  = (int)($this->perfil['GRADO_SOL'] ?? 0);
        $lunaSigno = $this->perfil['SIGNO_LUNA'] ?? '';
        $lunaGrado = (int)($this->perfil['GRADO_LUNA'] ?? 0);

        if ($solSigno && $solGrado && $lunaSigno && $lunaGrado) {
            $fase = $this->calcularFaseLunacionInterno($solSigno, $solGrado, $lunaSigno, $lunaGrado);
            if ($fase) {
                $this->calc['fase'] = [
                    'nombre' => $fase['nombre'] ?? '',
                    'planeta'=> $fase['planeta'] ?? '',
                    'signo'  => $fase['signo'] ?? '',
                    'texto'  => $fase['texto'] ?? '',
                    'imagen' => $fase['imagen'] ?? '',
                ];
            }
        }

        // Sabianos extra (si existe el método)
        try {
            if (method_exists($svc, 'readSabianos')) {
                $this->sabianos = $svc->readSabianos($this->id);
            }
        } catch (\Throwable $e) {
            $this->sabianos = [];
        }

        try {
            $this->imagenesExistentes = $svc->readImagenesWithRows($this->id);
        } catch (\Throwable $e) {
            $this->imagenesExistentes = [];
        }
    }

    public function guardarDescripcionImagen(int $i): void
    {
        $img = $this->imagenesExistentes[$i] ?? null;
        if (!$img) return;

        $row = (int)($img['row'] ?? 0);
        if ($row <= 0) return;

        SheetsService::make()->updateImagenDescripcion(
            $this->id,
            $row,
            (string)($img['DESCRIPCION'] ?? '')
        );

        $this->mensaje = 'Descripción guardada ✔';
    }

    public function guardarDescripcionesImagenes(): void
    {
        $svc = SheetsService::make();

        foreach ($this->imagenesExistentes as $img) {
            $row = (int)($img['row'] ?? 0);
            if ($row <= 0) continue;

            $svc->updateImagenDescripcion(
                $this->id,
                $row,
                (string)($img['DESCRIPCION'] ?? '')
            );
        }

        $this->mensaje = 'Descripciones guardadas ✔';
    }

    public function eliminarImagen(int $i): void
    {
        $img = $this->imagenesExistentes[$i] ?? null;
        if (!$img) return;

        // 1) borrar de la hoja (fila exacta)
        $row = (int)($img['row'] ?? 0);
        if ($row > 0) {
            SheetsService::make()->deleteImagenRow($this->id, $row);
        }

        // 2) opcional: borrar el archivo de Drive si querés (si podés extraer el fileId desde la URL)
        // $url = (string)($img['URL'] ?? '');
        // $fileId = $this->extractDriveFileId($url);
        // if ($fileId) DriveService::make()->deleteFile($fileId);

        // 3) recargar lista (importante porque cambian los row numbers)
        $this->imagenesExistentes = SheetsService::make()->readImagenesWithRows($this->id);

        $this->mensaje = 'Imagen eliminada ✔';
    }



    public function agregarSabiano(): void
    {
        $signo = trim((string)($this->nuevoSabiano['SIGNO'] ?? ''));
        $grado = (int)($this->nuevoSabiano['GRADO'] ?? 0);

        if ($signo === '' || $grado < 1 || $grado > 30) {
            $this->addError('nuevoSabiano', 'Signo y grado (1 a 30) requeridos.');
            return;
        }

        $sab = SabianoService::get($signo, $grado);
        if (!$sab) {
            $this->addError('nuevoSabiano', 'No encontré ese sabiano.');
            return;
        }

        SheetsService::make()->appendSabiano($this->id, [
            'FECHA'  => now()->format('d/m/Y'),
            'SIGNO'  => $signo,
            'GRADO'  => $grado,
            'TITULO' => $sab['titulo'] ?? '',
            'TEXTO'  => $sab['texto'] ?? '',
            'IMAGEN' => $sab['imagen'] ?? '',
        ]);

        // refrescar lista
        $this->sabianos = SheetsService::make()->readSabianos($this->id);
        $this->nuevoSabiano = ['SIGNO'=>'','GRADO'=>''];
        $this->dispatch('sabiano-added');
        $this->mensaje = 'Sabiano agregado ✔';
        // limpiar form + errores
        $this->reset('nuevoSabiano');
        $this->resetErrorBag('nuevoSabiano');

        // opcional: mensaje
        $this->mensaje = 'Sabiano agregado ✔';

        // cerrar modal desde JS
        $this->dispatch('sabiano-added');
    }


    public function updatedPerfil($value, $key): void
    {
        // Edad actual
        if ($key === 'FECHA_NAC') {
            $this->calc['edad'] = $this->calcularEdadHoy($this->perfil['FECHA_NAC'] ?? null);
        }

        // Sabiano
        if (in_array($key, ['SIGNO_SABIANO', 'GRADO_SABIANO'], true)) {
            $signo = $this->perfil['SIGNO_SABIANO'] ?? '';
            $grado = (int)($this->perfil['GRADO_SABIANO'] ?? 0);

            if ($signo && $grado) {
                $sab = SabianoService::get($signo, $grado);
                if ($sab) {
                    $this->perfil['TITULO_SABIANO'] = $sab['titulo'] ?? '';
                    $this->perfil['IMAGEN_SABIANO'] = $sab['imagen'] ?? '';
                    $this->perfil['TEXTO_SABIANO']  = $sab['texto'] ?? '';

                    $this->calc['sabiano'] = [
                        'titulo' => $this->perfil['TITULO_SABIANO'],
                        'imagen' => $this->perfil['IMAGEN_SABIANO'],
                        'texto'  => $this->perfil['TEXTO_SABIANO'],
                    ];
                }
            }
        }

        // Lunación
        if (in_array($key, ['SIGNO_SOL','GRADO_SOL','SIGNO_LUNA','GRADO_LUNA'], true)) {
            $solSigno  = $this->perfil['SIGNO_SOL'] ?? '';
            $solGrado  = (int)($this->perfil['GRADO_SOL'] ?? 0);
            $lunaSigno = $this->perfil['SIGNO_LUNA'] ?? '';
            $lunaGrado = (int)($this->perfil['GRADO_LUNA'] ?? 0);

            if ($solSigno && $solGrado && $lunaSigno && $lunaGrado) {
                $fase = $this->calcularFaseLunacionInterno($solSigno, $solGrado, $lunaSigno, $lunaGrado);
                if ($fase) {
                    $this->perfil['FASE_LUNACION_NATAL']       = $fase['nombre'];
                    $this->perfil['PLANETA_ASOCIADO_LUNACION'] = $fase['planeta'];
                    $this->perfil['SIGNO_ASOCIADO_LUNACION']   = $fase['signo'];
                    $this->perfil['IMAGEN_FASE_LUNACION']      = $fase['imagen'];
                    $this->perfil['TEXTO_FASE_LUNACION']       = $fase['texto'];

                    $this->calc['fase'] = [
                        'nombre' => $this->perfil['FASE_LUNACION_NATAL'],
                        'planeta'=> $this->perfil['PLANETA_ASOCIADO_LUNACION'],
                        'signo'  => $this->perfil['SIGNO_ASOCIADO_LUNACION'],
                        'texto'  => $this->perfil['TEXTO_FASE_LUNACION'],
                        'imagen' => $this->perfil['IMAGEN_FASE_LUNACION'],
                    ];
                }
            }
        }
    }

    public function subirImagenPaciente(): void
    {
        $this->validateImagenForm();

        $folderImgs = $this->getPacienteImagesFolderId();

        $ext = $this->imgUpload->getClientOriginalExtension() ?: 'jpg';

        $safeTitle = (string) Str::of($this->imgNombre)
            ->trim()
            ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
            ->limit(60, '');

        Storage::disk('local')->makeDirectory('tmp');

        $tmpName = 'up_' . Str::random(20) . '.' . $ext;

        $tmpPath = $this->imgUpload->storeAs('tmp', $tmpName, 'local');
        if (!$tmpPath) {
            throw new \RuntimeException('No se pudo guardar el archivo temporal (storeAs devolvió null/false).');
        }

        $absPath = Storage::disk('local')->path($tmpPath);
        if (!is_file($absPath)) {
            throw new \RuntimeException("Archivo temporal no existe: {$absPath}");
        }

        $drive = DriveService::make();

        $fileName = $safeTitle . ' - ' . now()->format('Ymd_His') . '.' . $ext;

        $fileId = $drive->uploadImageToFolder($absPath, $fileName, $folderImgs);
        $drive->makeAnyoneReader($fileId);
        $url = $drive->getPublicContentUrl($fileId);

        SheetsService::make()->appendImagen($this->id, [
            'NOMBRE_IMAGEN' => (string) $this->imgNombre,
            'URL'           => $url,
            'DESCRIPCION'   => (string) $this->imgDescripcion,
        ]);

        Storage::disk('local')->delete($tmpPath);

        $this->imgUltimaUrl = $url;
        $this->reset(['imgUpload', 'imgNombre', 'imgDescripcion']);
        $url = $drive->getPublicContentUrl($fileId);
        $this->mensaje = 'Imagen cargada y registrada ✔';
    }

    private function validateImagenForm(): void
    {
        $this->validate([
            'imgUpload'      => 'required|image|max:5120',
            'imgNombre'      => 'required|string|max:80',
            'imgDescripcion' => 'nullable|string|max:400',
        ]);
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

    private function getPacienteImagesFolderId(): string
    {
        $drive = DriveService::make();
        $parents = $drive->getParents($this->id);
        $folderPaciente = $parents[0] ?? null;

        if (!$folderPaciente) {
            throw new \RuntimeException('No pude detectar la carpeta padre del Spreadsheet del paciente.');
        }

        return $drive->ensureChildFolder($folderPaciente, 'Imagenes');
    }

    public function guardar()
    {
        // corregido: antes tenías $this->validate();q
        $this->validate();

        $nuevoNombre = trim((string)($this->perfil['NOMBRE_Y_APELLIDO'] ?? ''));
        if ($nuevoNombre === '') $nuevoNombre = 'Paciente';

        // Si cambió el nombre, renombramos spreadsheet y carpeta padre
        if ($this->nombreOriginal !== $nuevoNombre) {
            SheetsService::make()->renameSpreadsheet($this->id, $nuevoNombre);

            $drive = DriveService::make();
            $parents = $drive->getParents($this->id);
            if (!empty($parents[0])) {
                // la carpeta padre del sheet es la carpeta del paciente
                $drive->renameFile($parents[0], $nuevoNombre);
            }

            $this->nombreOriginal = $nuevoNombre;
        }

        foreach ([
        'FILTRO_MELLIZOS',
        'FILTRO_ADOPTADO',
        'FILTRO_ABUSOS',
        'FILTRO_SUICIDIO',
        'FILTRO_SALUD',
        'FILTRO_TEA',
        'FILTRO_HISTORICOS',
        'FILTRO_FILOSOFOS',
        'FILTRO_PAISES',
        'FILTRO_ECLIPSES',
        'FILTRO_ANUALES',
        'FILTRO_MOMENTOS_CRITICOS',
        'FILTRO_INICIO_CICLOS',
        ] as $k) {
        $this->perfil[$k] = !empty($this->perfil[$k]) ? 'SI' : '';
        }


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

        if (!array_key_exists($signo, $offsets)) return null;
        return $offsets[$signo] + $grado;
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
