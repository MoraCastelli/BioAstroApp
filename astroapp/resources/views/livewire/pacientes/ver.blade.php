<div class="max-w-6xl mx-auto p-6 space-y-8"
     x-data="{
        imgOpen:false,
        imgSrc:'',
        imgAlt:'',
        openImg(src, alt='Imagen'){ this.imgSrc=src; this.imgAlt=alt; this.imgOpen=true; },
     }">

  {{-- MODAL IMAGEN --}}
  <div x-show="imgOpen" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
       @keydown.escape.window="imgOpen=false"
       @click.self="imgOpen=false">
    <div class="max-w-5xl w-full">
      <div class="flex justify-end mb-2">
        <button class="text-white/90 hover:text-white text-sm" @click="imgOpen=false">Cerrar ✕</button>
      </div>
      <img :src="imgSrc" :alt="imgAlt"
           class="w-full max-h-[80vh] object-contain rounded-xl shadow border border-white/10">
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
    $fotoRaw = $perfil['FOTO_URL'] ?? '';

    $foto = $fotoRaw;

    // Si es link de Drive, extraer FILE_ID y convertir a thumbnail
    if (!empty($fotoRaw) && str_contains($fotoRaw, 'drive.google.com')) {
        $id = null;

        // /file/d/{id}/
        if (preg_match('~/file/d/([a-zA-Z0-9_-]+)~', $fotoRaw, $m)) $id = $m[1];

        // ?id={id}
        if (!$id && preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $fotoRaw, $m)) $id = $m[1];

        if ($id) {
            $foto = "https://drive.google.com/thumbnail?id={$id}&sz=w1200";
        }
    }

    // Si te llega "public/..." normalizalo
    if (!empty($foto) && str_starts_with($foto, 'public/')) {
        $foto = asset('storage/'.str_replace('public/', '', $foto));
    }

    // Si te llega ruta local "pacientes/..."
    if (!empty($foto) && !str_starts_with($foto, 'http') && !str_starts_with($foto, '/')) {
        $foto = asset('storage/'.$foto);
    }
  @endphp

  {{-- TOP GRID: FOTO + DATOS --}}
  <div class="grid lg:grid-cols-3 gap-6">
    {{-- FOTO / CARTA --}}
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100 lg:col-span-1 space-y-3">
      <h2 class="font-semibold text-lg">Carta / Foto</h2>

      @php $foto = $perfil['FOTO_URL'] ?? ''; @endphp
      @if(!empty($foto))
        <button type="button" class="w-full text-left"
                @click="openImg(@js($foto), 'Carta / Foto')">
          <img src="{{ $foto }}" alt="Carta / Foto"
              class="w-full h-56 rounded-lg object-cover border hover:opacity-95 transition"
              loading="lazy"
              referrerpolicy="no-referrer"
              onerror="this.style.display='none'">
          <div class="mt-2 text-xs text-gray-500">Click para ampliar</div>
        </button>
      @else
        <div class="text-sm text-gray-500 italic">Sin imagen cargada.</div>
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

  {{-- IMÁGENES DEL PACIENTE (página 3) --}}
  @if(!empty($imagenes))
    <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
      <h2 class="font-semibold text-lg">Imágenes del paciente</h2>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($imagenes as $img)
          @php
            $raw = (string)($img['URL'] ?? '');
            $url = $raw;

            if (preg_match('~drive\.google\.com/file/d/([^/]+)~', $raw, $m)) {
                $url = "https://drive.google.com/uc?export=view&id={$m[1]}";
            } elseif (preg_match('~drive\.google\.com/open\?id=([^&]+)~', $raw, $m)) {
                $url = "https://drive.google.com/uc?export=view&id={$m[1]}";
            }
          @endphp

          <div class="border rounded-xl p-3 bg-gray-50">
            <button type="button"
                    class="w-full text-left"
                    @click="openImg('{{ $url }}', '{{ $img['NOMBRE_IMAGEN'] ?? 'Imagen' }}')">
              <img src="{{ $url }}"
                  class="w-full h-36 object-cover rounded-lg border hover:opacity-95 transition"
                  alt="{{ $img['NOMBRE_IMAGEN'] ?? 'Imagen' }}"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                  onerror="this.onerror=null; this.src='{{ $url }}'.replace('export=view','export=download');">
            </button>

            <div class="mt-2">
              <div class="font-medium text-sm">{{ $img['NOMBRE_IMAGEN'] ?? '—' }}</div>
              @if(!empty($img['DESCRIPCION']))
                <div class="text-xs text-gray-600 mt-1 whitespace-pre-line">{{ $img['DESCRIPCION'] }}</div>
              @endif
              <div class="text-xs text-gray-400 mt-1">Click para ampliar</div>
            </div>
          </div>
        @endforeach
      </div>
    </section>
  @endif


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
