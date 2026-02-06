
<div class="max-w-6xl mx-auto p-6 space-y-8"
    x-data="{
      imgOpen:false,
      imgSrc:'',
      imgAlt:'',
      imgIndex: 0,
      gallery: [],

      setGallery(list){ this.gallery = list || []; },

      openImg(src, alt='Imagen'){
        // abre el modal con una imagen suelta (fase/sabiano/etc)
        this.imgIndex = -1; // “no pertenece a la galería”
        this.imgSrc = src || '';
        this.imgAlt = alt || 'Imagen';
        this.imgOpen = true;
      },

      openAt(i){
        this.imgIndex = i;
        const it = this.gallery[i] || {};
        this.imgSrc = it.url || '';
        this.imgAlt = it.title || 'Imagen';
        this.imgOpen = true;
      },

    }">

{{-- MODAL IMAGEN --}}
<div
  x-show="imgOpen"
  x-cloak
  x-transition.opacity
  class="fixed inset-0 z-[9999] backdrop-blur-2xl bg-black/10 bg-black/20"
  @keydown.escape.window="imgOpen=false"
  @click.self="imgOpen=false"
>
  <div class="absolute inset-0 flex items-center justify-center p-4">

    {{-- MARCO BLANCO --}}
    <div
      class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden"
      style="max-height: 85vh;"
    >

      {{-- HEADER FIJO --}}
      <div class="sticky top-0 z-20 flex items-center justify-between px-4 py-3 border-b bg-white">
        <div class="text-sm font-medium text-gray-700 truncate"
             x-text="imgAlt || 'Imagen'"></div>

        <button
          type="button"
          class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition"
          @click="imgOpen=false"
          aria-label="Cerrar"
        >
          ✕
        </button>
      </div>

      {{-- CONTENIDO --}}
      <div class="relative bg-black" style="max-height: calc(85vh - 56px);">

        {{-- Imagen --}}
        <div class="grid place-items-center p-4" style="max-height: calc(85vh - 56px);">
          <img
            :src="imgSrc"
            :alt="imgAlt"
            class="max-w-full object-contain"
            style="max-height: 70vh;"
          >
        </div>

      </div>

    </div>
  </div>
</div>


  {{-- HEADER --}}
  <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

    {{-- IZQUIERDA: nombre + ocultar --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
      @php
        $nombreReal = $perfil['NOMBRE_Y_APELLIDO'] ?? 'Paciente';
        $nombreOculto = str_repeat('*', max(10, mb_strlen($nombreReal)));
      @endphp

      <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
        <h1 class="text-3xl font-black tracking-tight">
          {{ $ocultarNombres ? $nombreOculto : $nombreReal }}
        </h1>

        {{-- botón chico --}}
        <button
          wire:click="toggleNombre"
          type="button"
          class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-semibold
                border border-gray-300 text-gray-600 bg-white
                hover:bg-gray-100 transition whitespace-nowrap">
          {{ $ocultarNombres ? 'Ver nombre' : 'Ocultar nombre' }}
        </button>
      </div>


    {{-- DERECHA: acciones --}}
    <div class="flex flex-wrap gap-2 justify-end">
      <a href="{{ route('paciente.editar', $id) }}"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
        Editar
      </a>

      <a href="{{ route('paciente.nuevo-encuentro', $id) }}"
        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition">
        + Agregar encuentro
      </a>

      <a href="{{ route('buscar') }}"
        class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 transition">
        Volver
      </a>
    </div>
  </header>



  @php
    $rawFotoUrl = $perfil['FOTO_URL'] ?? '';
    $driveFileId = null;

    if ($rawFotoUrl && preg_match('~/file/d/([a-zA-Z0-9_-]+)~', $rawFotoUrl, $m)) $driveFileId = $m[1];
    if (!$driveFileId && $rawFotoUrl && preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $rawFotoUrl, $m)) $driveFileId = $m[1];

    $foto = $driveFileId ? route('drive.image', ['fileId' => $driveFileId]) : '';

  @endphp

  {{-- TOP GRID: FOTO + DATOS --}}
  <div class="grid lg:grid-cols-3 gap-6">

   @if(!empty($imagenes))
  @php
    $gallery = [];
    foreach ($imagenes as $img) {
      $raw = (string)($img['URL'] ?? '');
      $driveId = null;

      if ($raw && preg_match('~/file/d/([a-zA-Z0-9_-]+)~', $raw, $m)) $driveId = $m[1];
      if (!$driveId && $raw && preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $raw, $m)) $driveId = $m[1];

      $url = $driveId ? route('drive.image', ['fileId' => $driveId]) : $raw;

      $gallery[] = [
        'url'   => $url,
        'title' => $img['NOMBRE_IMAGEN'] ?? 'Imagen',
        'desc'  => $img['DESCRIPCION'] ?? '',
      ];
    }
    $totalImgs = count($gallery);
  @endphp

  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 lg:col-span-1"
          x-init="setGallery(@js($gallery))">

      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-lg">Imágenes del paciente</h2>
        <div class="text-xs text-gray-500">
          Total: <span class="font-medium text-gray-800">{{ $totalImgs }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

      {{-- GRILLA MINIATURAS --}}
      <div class="grid grid-cols-4 gap-2 content-start">
        <template x-for="(g, i) in gallery" :key="i">
          <button type="button"
            @click.stop="openAt(i)"
            class="group rounded-lg border overflow-hidden bg-gray-50 hover:bg-gray-100 transition"
            :class="imgIndex===i ? 'ring-2 ring-emerald-500/60 border-emerald-200' : 'border-gray-200'"
            :title="g.title"
          >
            <img :src="g.url"
                :alt="g.title"
                class="w-full aspect-square object-cover block"
                loading="lazy">
          </button>
        </template>
      </div>

      {{-- DETALLE (del seleccionado) --}}
      <div class="space-y-3">
        <div class="text-sm">
          <div class="text-gray-500">Título</div>
          <div class="font-semibold text-gray-800" x-text="(gallery[imgIndex]?.title) || 'Imagen'"></div>
        </div>

        <div>
          <div class="text-gray-500 text-sm mb-1">Descripción</div>

          <template x-if="((gallery[imgIndex]?.desc)||'').trim() !== ''">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-gray-700 leading-relaxed text-sm whitespace-pre-line"
                x-text="gallery[imgIndex].desc"></div>
          </template>

          <template x-if="((gallery[imgIndex]?.desc)||'').trim() === ''">
            <div class="text-sm text-gray-400 italic">Sin descripción.</div>
          </template>
        </div>

        <div class="text-xs text-gray-400">
          Click en una miniatura para ver grande.
        </div>
      </div>

    </div>

  </section>
@endif

{{-- FILTROS (solo lectura) --}}
@php
  $filtrosMap = [
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

  $activos = [];
  foreach ($filtrosMap as $k => $label) {
    $v = $perfil[$k] ?? '';
    $v = is_string($v) ? strtoupper(trim($v)) : $v;

    // considera “verdadero” si viene como SI / TRUE / 1 / ON / X o boolean true
    $isTrue = ($v === true) || in_array($v, ['SI','TRUE','1','ON','X'], true);
    if ($isTrue) $activos[] = $label;
  }
@endphp

<section class="bg-white p-5 rounded-xl bg-gray-100 shadow space-y-3">
  <div class="flex items-center justify-between">
    <h2 class="font-semibold text-lg">Filtros</h2>
  </div>

  @if(!empty($activos))
    <div class="flex flex-wrap gap-2">
      @foreach($activos as $tag)
        <span class="inline-flex items-center px-2.5 py-1 text-[10px]
                     bg-slate-900 bg-gray-100 text-black">
          {{ $tag }}
        </span>
      @endforeach
    </div>
  @else
    <div class="text-sm text-gray-400 italic">
      Sin filtros seleccionados.
    </div>
  @endif
</section>


    {{-- DATOS BÁSICOS --}}
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100 lg:col-span-2 space-y-3">
      <h2 class="font-semibold text-lg">Datos básicos</h2>

      <div class="grid md:grid-cols-2 gap-3 text-sm">
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Fecha de Nacimiento</div>
          <div class="font-medium">{{ $perfil['FECHA_NAC'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Hora de Nacimiento</div>
          <div class="font-medium">{{ $perfil['HORA_NAC'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Ciudad de Nacimiento</div>
          <div class="font-medium">{{ $perfil['CIUDAD_NAC'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Edad</div>
          <div class="font-medium">{{ $calc['edad'] !== '' ? intval($calc['edad']) : '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Signo Solar</div>
          <div class="font-medium">{{ $perfil['SIGNO_SOLAR'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Contacto</div>
          <div class="font-medium">{{ $perfil['CONTACTO'] ?? '—' }}</div>
        </div>
      </div>
    </section>
  </div>

  {{-- LECTURA --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Lectura Astrológica</h2>

    <div class="grid lg:grid-cols-2 gap-4 text-sm">
      <div class="p-4 rounded-lg bg-gray-50 border">
        <div class="font-semibold">Signo Subyacente</div>
        <p class="whitespace-pre-line text-gray-700 mt-2">{{ $perfil['SIGNO_SUBYACENTE'] ?? '—' }}</p>
      </div>
      <div class="p-4 rounded-lg bg-gray-50 border">
        <div class="font-semibold">Balance Energético</div>
        <p class="whitespace-pre-line text-gray-700 mt-2">{{ $perfil['BALANCE_ENERGETICO'] ?? '—' }}</p>
      </div>
      <div class="p-4 rounded-lg bg-gray-50 border">
        <div class="font-semibold">Dispositores</div>
        <p class="whitespace-pre-line text-gray-700 mt-2">{{ $perfil['DISPOSITORES'] ?? '—' }}</p>
      </div>
      <div class="p-4 rounded-lg bg-gray-50 border">
        <div class="font-semibold">Progresiones y Retornos</div>
        <p class="whitespace-pre-line text-gray-700 mt-2">{{ $perfil['PROGRESIONES_RETORNOS'] ?? '—' }}</p>
      </div>
    </div>
  </section>

    {{-- FASE LUNACIÓN + SABIANO --}}
  <div class="grid lg:grid-cols-2 gap-6">

    {{-- FASE --}}
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100">
      <h2 class="font-semibold text-lg mb-3">Fase de Lunación Natal</h2>

      <div class="grid md:grid-cols-3 gap-4 items-start">
        {{-- IZQ: texto --}}
        <div class="md:col-span-2 space-y-3">
          <div class="text-sm space-y-1">
            <div><span class="text-gray-500">Fase:</span> <span class="font-medium">{{ $perfil['FASE_LUNACION_NATAL'] ?? '—' }}</span></div>
            <div><span class="text-gray-500">Planeta:</span> <span class="font-medium">{{ $perfil['PLANETA_ASOCIADO_LUNACION'] ?? '—' }}</span></div>
            <div><span class="text-gray-500">Signo asociado:</span> <span class="font-medium">{{ $perfil['SIGNO_ASOCIADO_LUNACION'] ?? '—' }}</span></div>
          </div>

          @if(!empty($perfil['TEXTO_FASE_LUNACION']))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 leading-relaxed text-sm">
              {{ $perfil['TEXTO_FASE_LUNACION'] }}
            </div>
          @endif
        </div>

        {{-- DER: imagen chica --}}
        <div class="md:col-span-1">
          @if(!empty($perfil['IMAGEN_FASE_LUNACION']))
            @php $imgFase = asset('images/fases/' . $perfil['IMAGEN_FASE_LUNACION']); @endphp

            <button type="button" class="w-full text-left"
                    @click="openImg('{{ $imgFase }}', 'Fase de Lunación')">
              <div class="border rounded-xl p-2 bg-gray-50 hover:bg-gray-100 transition">
                <img src="{{ $imgFase }}"
                    class="w-full h-32 object-cover rounded-lg"
                    alt="Fase de Lunación">
                <div class="text-xs text-gray-500 mt-2">Click para ampliar</div>
              </div>
            </button>
          @else
            <div class="text-sm text-gray-400 italic">Sin imagen</div>
          @endif
        </div>
      </div>
    </section>

    {{-- SABIANO (PRINCIPAL) --}}
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100">
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-lg mb-3">Grado Sabiano (principal)</h2>

        <a href="{{ route('paciente.editar', $id) }}"
          class="text-sm text-emerald-700 hover:underline">
          Ir a editar y agregar sabiano extra →
        </a>
      </div>

      <div class="grid md:grid-cols-3 gap-4 items-start">
        {{-- IZQ: texto --}}
        <div class="md:col-span-2 space-y-3">
          <div class="text-sm space-y-1">
            <div><span class="text-gray-500">Signo:</span> <span class="font-medium">{{ $perfil['SIGNO_SABIANO'] ?? '—' }}</span></div>
            <div><span class="text-gray-500">Grado:</span> <span class="font-medium">{{ $perfil['GRADO_SABIANO'] ?? '—' }}</span></div>
            <div><span class="text-gray-500">Título:</span> <span class="font-medium">{{ $perfil['TITULO_SABIANO'] ?? '—' }}</span></div>
          </div>

          @if(!empty($perfil['TEXTO_SABIANO']))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 leading-relaxed text-sm">
              {{ $perfil['TEXTO_SABIANO'] }}
            </div>
          @endif
        </div>

        {{-- DER: imagen chica --}}
        <div class="md:col-span-1">
          @if(!empty($perfil['IMAGEN_SABIANO']))
            @php $imgSab = asset($perfil['IMAGEN_SABIANO']); @endphp
            <button type="button" class="w-full text-left"
                    @click="openImg('{{ $imgSab }}', 'Imagen Sabiano')">
              <div class="border rounded-xl p-2 bg-gray-50 hover:bg-gray-100 transition">
                <img src="{{ $imgSab }}"
                    class="w-full h-32 object-cover rounded-lg"
                    alt="Imagen Sabiano"
                    loading="lazy">
                <div class="text-xs text-gray-500 mt-2">Click para ampliar</div>
              </div>
            </button>
          @else
            <div class="text-sm text-gray-400 italic">Sin imagen</div>
          @endif
        </div>
      </div>
    </section>

    {{-- SABIANOS EXTRA (MISMO ESTILO QUE PRINCIPAL) --}}
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-lg">Sabianos extra</h2>
      </div>

      @if(!empty($sabianos))
        <div class="space-y-6">
          @foreach($sabianos as $idx => $s)
            <div class="pt-6 first:border-t-0 first:pt-0">
              <div class="grid md:grid-cols-3 gap-4 items-start">

                {{-- IZQ: texto (igual al principal) --}}
                <div class="md:col-span-2 space-y-3">
                  <div class="text-sm space-y-1">
                    <div>
                      <span class="text-gray-500">Signo:</span>
                      <span class="font-medium">{{ $s['SIGNO'] ?? '—' }}</span>
                    </div>
                    <div>
                      <span class="text-gray-500">Grado:</span>
                      <span class="font-medium">{{ $s['GRADO'] ?? '—' }}</span>
                    </div>
                    <div>
                      <span class="text-gray-500">Título:</span>
                      <span class="font-medium">{{ $s['TITULO'] ?? '—' }}</span>
                    </div>
                  </div>

                  @if(!empty($s['TEXTO']))
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 leading-relaxed text-sm">
                      {{ $s['TEXTO'] }}
                    </div>
                  @endif
                </div>

                {{-- DER: imagen (igual al principal) --}}
                <div class="md:col-span-1">
                  @if(!empty($s['IMAGEN']))
                    @php $sabImg = asset($s['IMAGEN']); @endphp
                    <button type="button" class="w-full text-left"
                            @click="openImg('{{ $sabImg }}', 'Sabiano extra')">
                      <div class="border rounded-xl p-2 bg-gray-50 hover:bg-gray-100 transition">
                        <img src="{{ $sabImg }}"
                            class="w-full h-32 object-cover rounded-lg"
                            alt="Sabiano extra"
                            loading="lazy">
                        <div class="text-xs text-gray-500 mt-2">Click para ampliar</div>
                      </div>
                    </button>
                  @else
                    <div class="text-sm text-gray-400 italic">Sin imagen</div>
                  @endif
                </div>

              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-sm text-gray-500 italic">
          Todavía no hay sabianos extra agregados.
        </div>
      @endif
    </section>
  </div>

  {{-- RESUMEN / DETALLE --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Resumen y Detalle</h2>

    <div class="grid lg:grid-cols-2 gap-4 text-sm">
      <div class="p-4 rounded-lg bg-gray-50 border">
        <div class="font-semibold">Motivo de Consulta</div>
        <p class="whitespace-pre-line text-gray-700 mt-2">{{ $perfil['MOTIVO_CONSULTA'] ?? '—' }}</p>
      </div>

      <div class="p-4 rounded-lg bg-gray-50 border">
        <div class="font-semibold">Resumen para Psicóloga</div>

        <p class="whitespace-pre-line text-gray-700 mt-2">
          {{ $perfil['RESUMEN_PARA_PSICOLOGA_TEXTO'] ?? '—' }}
        </p>

        {{-- Audios --}}
        @if(!empty($audios))
          <div class="mt-4 space-y-3">
            @foreach($audios as $a)
              @php
                $fileId = trim((string)($a['FILE_ID'] ?? ''));
                $src = $fileId ? route('drive.audio', ['fileId' => $fileId]) : '';

                $titulo = $a['TITULO'] ?? 'Audio';
                $desc   = $a['DESCRIPCION'] ?? '';
              @endphp

              @if($src)
                <div class="bg-white border rounded-lg p-3">
                  <div class="text-sm font-medium">{{ $titulo }}</div>

                  @if(trim($desc) !== '')
                    <div class="text-xs text-gray-600 mt-1 whitespace-pre-line">{{ $desc }}</div>
                  @endif

                  <audio class="w-full mt-2" controls preload="metadata">
                    <source src="{{ $src }}">
                    Tu navegador no soporta audio HTML5.
                  </audio>

                </div>
              @endif
            @endforeach
          </div>
        @else
          <div class="text-xs text-gray-400 mt-2">Sin audios cargados.</div>
        @endif
      </div>
    </div>
  </section>


  {{-- ENCUENTROS (ACORDEÓN) --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4"
          x-data="{ open: null }">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-lg">Encuentros</h2>
      <a href="{{ route('paciente.nuevo-encuentro', $id) }}"
        class="text-sm text-emerald-700 hover:underline">
        + Agregar encuentro
      </a>
    </div>

    @php $encs = $encuentros ?? []; @endphp

    @if(empty($encs))
      <div class="text-sm text-gray-500 italic">No hay encuentros cargados todavía.</div>
    @else
      <div class="divide-y border rounded-xl overflow-hidden">
        @foreach($encs as $idx => $e)
          @php
            $n = $idx + 1;
            $titulo = $n === 1 ? '1er Encuentro' : ($n === 2 ? '2do Encuentro' : ($n === 3 ? '3er Encuentro' : $n.'º Encuentro'));
            $fecha = $e['FECHA'] ?? '—';
          @endphp

          <button type="button"
                  class="w-full text-left p-4 bg-white hover:bg-gray-50 transition flex items-center justify-between"
                  @click="open === {{ $idx }} ? open = null : open = {{ $idx }}">
            <div>
              <div class="font-medium">{{ $titulo }}</div>
              <div class="text-xs text-gray-500">Fecha: {{ $fecha }}</div>
            </div>
            <div class="text-gray-400">
              <svg class="w-5 h-5 transition" :class="open === {{ $idx }} ? 'rotate-180' : ''"
                   viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                      d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z"
                      clip-rule="evenodd" />
              </svg>
            </div>
          </button>

          <div x-show="open === {{ $idx }}" x-cloak class="bg-gray-50 p-4">
            <div class="grid lg:grid-cols-3 gap-3 text-sm">
              <div class="p-3 bg-white border rounded-lg">
                <div class="text-xs text-gray-500">Fecha</div>
                <div class="font-medium">{{ $e['FECHA'] ?? '—' }}</div>
              </div>
              <div class="p-3 bg-white border rounded-lg">
                <div class="text-xs text-gray-500">Ciudad últ. cumple</div>
                <div class="font-medium">{{ $e['CIUDAD_ULT_CUMPLE'] ?? '—' }}</div>
              </div>
              <div class="p-3 bg-white border rounded-lg">
                <div class="text-xs text-gray-500">Edad</div>
                <div class="font-medium">{{ $e['EDAD_EN_ESE_ENCUENTRO'] ?? '—' }}</div>
              </div>

              <div class="lg:col-span-3 p-3 bg-white border rounded-lg">
                <div class="text-xs text-gray-500">Temas tratados</div>
                <div class="whitespace-pre-line text-gray-800 mt-1">{{ $e['TEMAS_TRATADOS'] ?? '—' }}</div>
              </div>

              <div class="lg:col-span-3 p-3 bg-white border rounded-lg">
                <div class="text-xs text-gray-500">Resumen</div>
                <div class="whitespace-pre-line text-gray-800 mt-1">{{ $e['RESUMEN'] ?? '—' }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </section>
</div>
