<div class="max-w-6xl mx-auto p-6 space-y-8"
     x-data="{
        imgOpen:false,
        imgSrc:'',
        imgAlt:'',
        openImg(src, alt='Imagen'){ this.imgSrc=src; this.imgAlt=alt; this.imgOpen=true; },
     }"
>

  {{-- MODAL IMAGEN GLOBAL --}}
  <div x-show="imgOpen" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
       @keydown.escape.window="imgOpen=false"
       @click.self="imgOpen=false">
    <div class="max-w-5xl w-full">
      <div class="flex justify-end mb-2">
        <button type="button" class="text-white/90 hover:text-white text-sm" @click="imgOpen=false">
          Cerrar ✕
        </button>
      </div>
      <img :src="imgSrc" :alt="imgAlt"
           class="w-full max-h-[80vh] object-contain rounded-xl shadow border border-white/10">
    </div>
  </div>

  {{-- OVERLAY LOADER GLOBAL --}}
  <div wire:loading.delay.class.remove="hidden"
       class="hidden fixed inset-0 z-40 grid place-items-center bg-black/10">
    <div class="bg-white border shadow rounded-xl px-4 py-3 flex items-center gap-3">
      <svg class="w-5 h-5 animate-spin text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <circle cx="12" cy="12" r="9" stroke-opacity=".25" stroke-width="4"></circle>
        <path d="M21 12a9 9 0 0 1-9 9" stroke-width="4"></path>
      </svg>
      <span class="text-sm text-gray-700">Procesando…</span>
    </div>
  </div>

  {{-- HEADER --}}
  <header class="space-y-2">
    <h1 class="text-3xl font-semibold tracking-tight">Editar paciente</h1>
    <p class="text-sm text-gray-600">Completá los datos y guardá los cambios.</p>
  </header>

  @if($mensaje)
    <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-3 rounded">
      {{ $mensaje }}
    </div>
  @endif

  {{-- GRID: contenido + sidebar --}}
  <div class="grid lg:grid-cols-3 gap-6">

    {{-- MAIN --}}
    <div class="lg:col-span-2 space-y-8">

      {{-- ACCIONES SUPERIORES --}}
      <div class="flex gap-3">
        <button type="button"
                wire:click="guardar"
                wire:loading.attr="disabled"
                wire:target="guardar,subirImagenPaciente,agregarSabiano"
                class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-2 rounded-lg">
          Guardar
          <span wire:loading wire:target="guardar" class="ml-2 text-white/80 text-xs">Guardando…</span>
        </button>

        <a href="{{ route('buscar') }}" class="px-4 py-2 rounded-lg border border-gray-300 bg-white">
          Volver
        </a>
      </div>

      {{-- 1) NOMBRE --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
        <h2 class="font-semibold text-lg">Nombre</h2>

        <div>
          <label class="block text-sm font-medium mb-1 text-gray-700">Nombre y Apellido</label>
          <input type="text" wire:model.defer="perfil.NOMBRE_Y_APELLIDO"
                 class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
                 placeholder="Ej: Ana Pérez">
          @error('perfil.NOMBRE_Y_APELLIDO') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
      </section>

      {{-- 1B) IMÁGENES DEL PACIENTE --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="font-semibold text-lg">Imágenes del paciente</h2>
            <p class="text-sm text-gray-600">
              Se guardan en Drive y se registran en la hoja “Imagenes”.
            </p>
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700">Título</label>
            <input type="text" wire:model.defer="imgNombre"
                   class="w-full border border-gray-300 rounded-lg p-2.5"
                   placeholder="Ej: Foto infancia">
            @error('imgNombre') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium mb-1 text-gray-700">Archivo</label>
            <input type="file" accept="image/*" wire:model="imgUpload"
                   class="w-full border border-gray-300 rounded-lg p-2.5 bg-white">
            <div class="text-xs text-gray-500 mt-1" wire:loading wire:target="imgUpload">Cargando archivo…</div>
            @error('imgUpload') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1 text-gray-700">Descripción (opcional)</label>
            <textarea wire:model.defer="imgDescripcion" rows="2"
                      class="w-full border border-gray-300 rounded-lg p-2.5"
                      placeholder="Breve descripción para la hoja Imagenes…"></textarea>
            @error('imgDescripcion') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button type="button"
                  wire:click="subirImagenPaciente"
                  wire:loading.attr="disabled"
                  wire:target="subirImagenPaciente,imgUpload"
                  class="bg-emerald-600 hover:bg-emerald-700 transition text-white px-4 py-2 rounded-lg">
            Subir imagen
            <span wire:loading wire:target="subirImagenPaciente" class="ml-2 text-white/80 text-xs">Subiendo…</span>
          </button>

          <button type="button"
                  wire:click="limpiarImagenForm"
                  class="px-4 py-2 rounded-lg border border-gray-300 bg-white">
            Limpiar
          </button>
        </div>
        {{-- IMÁGENES CARGADAS (EDITABLES) --}}
        <div class="pt-4 border-t space-y-3">
          <div class="flex items-center justify-between">
          </div>

          @if(!empty($imagenesExistentes))
            <div class="space-y-3">
              @foreach($imagenesExistentes as $i => $img)
                <div class="border rounded-xl p-3 bg-gray-50">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="text-sm font-semibold text-gray-900 truncate">
                        {{ $img['NOMBRE_IMAGEN'] ?? 'Sin título' }}
                      </div>
                    </div>

                    <button type="button"
                            wire:click="eliminarImagen({{ $i }})"
                            wire:loading.attr="disabled"
                            wire:target="eliminarImagen({{ $i }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-600 text-white hover:bg-red-700">
                      Eliminar
                    </button>
                  </div>

                  <label class="block text-xs text-gray-600 mt-3 mb-1">Descripción</label>
                  <textarea rows="2"
                            wire:model.defer="imagenesExistentes.{{ $i }}.DESCRIPCION"
                            class="w-full border border-gray-300 rounded-lg p-2 text-sm bg-white"
                            placeholder="Escribí una descripción…"></textarea>

                  <div class="mt-2 flex justify-end">
                    <button type="button"
                            wire:click="guardarDescripcionImagen({{ $i }})"
                            wire:loading.attr="disabled"
                            wire:target="guardarDescripcionImagen({{ $i }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700">
                      Guardar
                    </button>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-sm text-gray-500">
              No hay imágenes cargadas todavía.
            </div>
          @endif
        </div>

      </section>

      {{-- 2) CONTACTO --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-3">
        <h2 class="font-semibold text-lg">Contacto</h2>
        <input type="text" wire:model.defer="perfil.CONTACTO"
               class="w-full border border-gray-300 rounded-lg p-2.5"
               placeholder="Nombre y apellido de quien lo deriva / recomienda">
      </section>

      {{-- 3) DATOS NATALES --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
        <h2 class="font-semibold text-lg">Datos natales</h2>

        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm text-gray-700">Fecha de Nacimiento (DD/MM/AAAA)</label>
            <input type="text" wire:model.lazy="perfil.FECHA_NAC"
                   class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
                   placeholder="01/02/1990">
            @error('perfil.FECHA_NAC') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm text-gray-700">Hora (NN:NN AM/PM)</label>
            <input type="text" wire:model.defer="perfil.HORA_NAC"
                   class="w-full border border-gray-300 rounded-lg p-2.5"
                   placeholder="03:15 PM">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Año (AAAA)</label>
            <input type="text" wire:model.defer="perfil.ANIO_NAC"
                   class="w-full border border-gray-300 rounded-lg p-2.5"
                   placeholder="1990">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Ciudad</label>
            <input type="text" wire:model.defer="perfil.CIUDAD_NAC"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Provincia</label>
            <input type="text" wire:model.defer="perfil.PROVINCIA_NAC"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>

          <div>
            <label class="block text-sm text-gray-700">País</label>
            <input type="text" wire:model.defer="perfil.PAIS_NAC"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Ciudad último cumpleaños</label>
            <input type="text" wire:model.defer="perfil.CIUDAD_ULT_CUMPLE"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Provincia último cumpleaños</label>
            <input type="text" wire:model.defer="perfil.PROV_ULT_CUMPLE"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>

          <div>
            <label class="block text-sm text-gray-700">País último cumpleaños</label>
            <input type="text" wire:model.defer="perfil.PAIS_ULT_CUMPLE"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Signo Solar</label>
            <input type="text" wire:model.defer="perfil.SIGNO_SOLAR"
                   class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
                   placeholder="Ej: Aries">
          </div>

          <div>
            <label class="block text-sm text-gray-700">Edad (automática)</label>
            <input type="text" class="w-full border rounded-lg p-2.5 bg-gray-100"
                   value="{{ $calc['edad'] !== '' ? $calc['edad'] : '' }}" readonly>
          </div>

          <div>
            <label class="block text-sm text-gray-700">Observaciones</label>
            <textarea wire:model.defer="perfil.OBSERVACIONES" rows="1"
                      class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
          </div>
        </div>
      </section>

      {{-- 4) FILTROS --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
        <h2 class="font-semibold text-lg">Filtros</h2>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
          <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_MELLIZOS"> Mellizos</label>
          <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_ADOPTADO"> Adoptado</label>
          <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_ABUSOS"> Abusos</label>
          <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_SUICIDIO"> Suicidio</label>
          <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_ENFERMEDAD"> Enfermedad</label>
        </div>
      </section>

      {{-- 5) LECTURA --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
        <h2 class="font-semibold text-lg">Lectura</h2>
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-700">Signo Subyacente</label>
            <textarea wire:model.defer="perfil.SIGNO_SUBYACENTE" rows="2"
                      class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
          </div>
          <div>
            <label class="block text-sm text-gray-700">Balance Energético</label>
            <textarea wire:model.defer="perfil.BALANCE_ENERGETICO" rows="2"
                      class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
          </div>
          <div>
            <label class="block text-sm text-gray-700">Dispositores</label>
            <textarea wire:model.defer="perfil.DISPOSITORES" rows="2"
                      class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
          </div>
          <div>
            <label class="block text-sm text-gray-700">Progresiones y Retornos</label>
            <textarea wire:model.defer="perfil.PROGRESIONES_RETORNOS" rows="2"
                      class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
          </div>
        </div>
      </section>

      {{-- 6) CÁLCULOS --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-6"
               x-data="{ openSab:false }"
               x-on:sabiano-added.window="openSab=false"
      >
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-lg">Cálculos</h2>

          <button type="button"
                  class="text-sm bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg"
                  @click="openSab=true">
            + Agregar Sabiano
          </button>
        </div>

        {{-- Inputs Sol/Luna --}}
        <div class="grid md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm text-gray-700">Signo del Sol</label>
            <input type="text" wire:model.lazy="perfil.SIGNO_SOL"
                   class="w-full border rounded-lg p-2.5" placeholder="Ej: Aries">
          </div>
          <div>
            <label class="block text-sm text-gray-700">Grado del Sol</label>
            <input type="number" min="0" max="29" wire:model.lazy="perfil.GRADO_SOL"
                   class="w-full border rounded-lg p-2.5">
          </div>
          <div>
            <label class="block text-sm text-gray-700">Signo de la Luna</label>
            <input type="text" wire:model.lazy="perfil.SIGNO_LUNA"
                   class="w-full border rounded-lg p-2.5" placeholder="Ej: Cáncer">
          </div>
          <div>
            <label class="block text-sm text-gray-700">Grado de la Luna</label>
            <input type="number" min="0" max="29" wire:model.lazy="perfil.GRADO_LUNA"
                   class="w-full border rounded-lg p-2.5">
          </div>
        </div>

        {{-- Sabiano principal (si lo querés mantener) --}}
        <div class="pt-6 border-t space-y-4">
          <h3 class="font-semibold">Sabiano (principal)</h3>

          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700">Signo</label>
              <input type="text" wire:model.lazy="perfil.SIGNO_SABIANO"
                     class="w-full border rounded-lg p-2.5" placeholder="Aries">
            </div>

            <div>
              <label class="block text-sm text-gray-700">Grado (1 a 30)</label>
              <input type="number" min="1" max="30" wire:model.lazy="perfil.GRADO_SABIANO"
                     class="w-full border rounded-lg p-2.5">
            </div>

            <div>
              <label class="block text-sm text-gray-700">Título</label>
              <input type="text" class="w-full bg-gray-100 rounded-lg p-2.5"
                     value="{{ $calc['sabiano']['titulo'] ?? '' }}" readonly>
            </div>
          </div>
        </div>

        {{-- MODAL: AGREGAR SABIANO --}}
        <div x-show="openSab" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
             @keydown.escape.window="openSab=false"
             @click.self="openSab=false">
          <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-gray-100 p-5 space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg">Agregar Sabiano</h3>
              <button type="button" class="text-sm text-gray-500 hover:text-gray-800" @click="openSab=false">✕</button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Signo</label>
                <input type="text"
                       wire:model.defer="nuevoSabiano.SIGNO"
                       class="w-full border rounded-lg p-2.5"
                       placeholder="Aries">
              </div>

              <div>
                <label class="block text-sm text-gray-700 mb-1">Grado (1 a 30)</label>
                <input type="number" min="1" max="30"
                       wire:model.defer="nuevoSabiano.GRADO"
                       class="w-full border rounded-lg p-2.5"
                       placeholder="12">
              </div>
            </div>

            {{-- Este error te aparece si usás addError('nuevoSabiano', ...) --}}
            @error('nuevoSabiano') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

            <div class="flex gap-2 justify-end pt-2">
              <button type="button" class="px-4 py-2 rounded-lg border" @click="openSab=false">
                Cancelar
              </button>

              <button type="button"
                      wire:click="agregarSabiano"
                      wire:loading.attr="disabled"
                      wire:target="agregarSabiano"
                      class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                Agregar
                <span wire:loading wire:target="agregarSabiano" class="ml-2 text-white/80 text-xs">…</span>
              </button>
            </div>
          </div>
        </div>

      </section>

      {{-- 9) RESUMEN --}}
      <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
        <h2 class="font-semibold text-lg">Resumen para psicóloga</h2>
        <div class="grid md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700">Resumen (texto)</label>
            <textarea wire:model.defer="perfil.RESUMEN_PARA_PSICOLOGA_TEXTO" rows="3"
                      class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
          </div>
          <div>
            <label class="block text-sm text-gray-700">URL del audio (opcional)</label>
            <input type="url" wire:model.defer="perfil.RESUMEN_PARA_PSICOLOGA_URL_AUDIO"
                   class="w-full border border-gray-300 rounded-lg p-2.5">
          </div>
        </div>
      </section>

      {{-- ACCIONES INFERIORES --}}
      <div class="flex gap-3">
        <button type="button"
                wire:click="guardar"
                wire:loading.attr="disabled"
                wire:target="guardar,subirImagenPaciente,agregarSabiano"
                class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-2 rounded-lg">
          Guardar
          <span wire:loading wire:target="guardar" class="ml-2 text-white/80 text-xs">Guardando…</span>
        </button>
        <a href="{{ route('buscar') }}" class="px-4 py-2 rounded-lg border border-gray-300 bg-white">Volver</a>
      </div>

    </div>

    {{-- SIDEBAR --}}
    <aside class="lg:col-span-1 space-y-4">
      <div class="bg-white p-4 rounded-xl shadow border border-gray-100 sticky top-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold">Se calcula solo</h3>
          <button type="button"
                  class="text-sm text-blue-600 hover:underline"
                  wire:click="$toggle('verMasCalc')">
            {{ $verMasCalc ? 'Ver menos' : 'Ver más' }}
          </button>
        </div>

        <div class="mt-3 space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Edad</span>
            <span class="font-medium">{{ $calc['edad'] !== '' ? $calc['edad'] : '—' }}</span>
          </div>

          <div class="pt-3 border-t">
            <div class="text-gray-600 mb-1">Fase de Lunación</div>
            <div class="font-medium">{{ $calc['fase']['nombre'] ?: '—' }}</div>
            <div class="text-xs text-gray-500">
              Planeta: {{ $calc['fase']['planeta'] ?: '—' }} · Signo: {{ $calc['fase']['signo'] ?: '—' }}
            </div>

            @if($verMasCalc && !empty($calc['fase']['texto']))
              <div class="mt-2 p-2 rounded bg-gray-50 border text-gray-700 whitespace-pre-line">
                {{ $calc['fase']['texto'] }}
              </div>
            @endif
          </div>

          <div class="pt-3 border-t">
            <div class="text-gray-600 mb-1">Sabiano</div>
            <div class="font-medium">{{ $calc['sabiano']['titulo'] ?: '—' }}</div>

            @if($verMasCalc && !empty($calc['sabiano']['texto']))
              <div class="mt-2 p-2 rounded bg-gray-50 border text-gray-700 whitespace-pre-line">
                {{ $calc['sabiano']['texto'] }}
              </div>
            @endif
          </div>
        </div>

      </div>
    </aside>

  </div>

</div>
