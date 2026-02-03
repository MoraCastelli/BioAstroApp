
<div class="max-w-6xl mx-auto p-6 space-y-8"
     x-data="{
        imgOpen:false,
        imgSrc:'',
        imgAlt:'',
        openImg(src, alt='Imagen'){ this.imgSrc=src; this.imgAlt=alt; this.imgOpen=true; },
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
      <div
        class="overflow-auto bg-black"
        style="max-height: calc(85vh - 56px);"
      >
        <img
          :src="imgSrc"
          :alt="imgAlt"
          class="block mx-auto object-contain"
          style="max-width: 100%; max-height: 70vh;"
        >
      </div>

    </div>
  </div>
</div>



  {{-- HEADER --}}
  <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
      <h1 class="text-3xl font-semibold tracking-tight">
        {{ $perfil['NOMBRE_Y_APELLIDO'] ?? 'Paciente' }}
      </h1>
      <p class="text-sm text-gray-600">Vista del perfil astrológico</p>
    </div>

    <div class="flex flex-wrap gap-2">
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
      {{-- IMÁGENES DEL PACIENTE --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 lg:col-span-1">
        <h2 class="font-semibold text-lg mb-3">Imágenes del paciente</h2>

        @php
          $totalImgs = is_countable($imagenes ?? null) ? count($imagenes) : 0;

          // elegimos la "principal": la primera imagen
          $first = ($totalImgs > 0) ? $imagenes[0] : null;

          $raw = $first ? (string)($first['URL'] ?? '') : '';
          $driveId = null;

          if ($raw && preg_match('~/file/d/([a-zA-Z0-9_-]+)~', $raw, $m)) $driveId = $m[1];
          if (!$driveId && $raw && preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $raw, $m)) $driveId = $m[1];

          $url = $driveId ? route('drive.image', ['fileId' => $driveId]) : $raw;

          $nombre = $first['NOMBRE_IMAGEN'] ?? 'Imagen';
          $desc = $first['DESCRIPCION'] ?? '';
        @endphp

        <div class="grid md:grid-cols-3 gap-4 items-start">
          {{-- IZQ: texto --}}
          <div class="md:col-span-2 space-y-3">
            <div class="text-sm space-y-1">
              <div>
                <span class="text-gray-500">Total:</span>
                <span class="font-medium">{{ $totalImgs }}</span>
              </div>

              @if($first)
                <div>
                  <span class="text-gray-500">Principal:</span>
                  <span class="font-medium">{{ $nombre }}</span>
                </div>
              @endif
            </div>

            @if(!empty($desc))
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 leading-relaxed text-sm whitespace-pre-line">
                {{ $desc }}
              </div>
            @else
              <div class="text-sm text-gray-400 italic">Sin descripción.</div>
            @endif
          </div>

          {{-- DER: imagen chica --}}
          <div class="md:col-span-1">
            @if(!empty($url))
              <button type="button" class="w-full text-left"
                      @click="openImg(@js($url), @js($nombre))">
                <div class="border rounded-xl p-2 bg-gray-50 hover:bg-gray-100 transition">
                  <img src="{{ $url }}"
                      class="w-full h-32 object-cover rounded-lg"
                      alt="{{ $nombre }}"
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



    @endif


    {{-- DATOS BÁSICOS --}}
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100 lg:col-span-2 space-y-3">
      <h2 class="font-semibold text-lg">Datos básicos</h2>

      <div class="grid md:grid-cols-2 gap-3 text-sm">
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Fecha de Nacimiento</div>
          <div class="font-medium">{{ $perfil['FECHA_NAC'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Hora</div>
          <div class="font-medium">{{ $perfil['HORA_NAC'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">Ciudad</div>
          <div class="font-medium">{{ $perfil['CIUDAD_NAC'] ?? '—' }}</div>
        </div>
        <div class="p-3 rounded-lg bg-gray-50 border">
          <div class="text-gray-500 text-xs">País</div>
          <div class="font-medium">{{ $perfil['PAIS_NAC'] ?? '—' }}</div>
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
        <p class="whitespace-pre-line text-gray-700 mt-2">{{ $perfil['RESUMEN_PARA_PSICOLOGA_TEXTO'] ?? '—' }}</p>
        @if(!empty($perfil['RESUMEN_PARA_PSICOLOGA_URL_AUDIO']))
          <a href="{{ $perfil['RESUMEN_PARA_PSICOLOGA_URL_AUDIO'] }}"
             class="text-blue-600 underline text-sm mt-2 inline-block" target="_blank">
            Escuchar audio
          </a>
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
