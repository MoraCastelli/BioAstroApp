<div class="max-w-5xl mx-auto p-6 space-y-8">

  {{-- OVERLAY LOADER GLOBAL --}}
  <div wire:loading.delay.class.remove="hidden" class="hidden fixed inset-0 z-40 grid place-items-center bg-black/10">
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
    <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-3 rounded">{{ $mensaje }}</div>
  @endif

  {{-- ACCIONES SUPERIORES --}}
  <div class="flex gap-3">
    <button wire:click="guardar"
            wire:loading.attr="disabled"
            wire:target="guardar,subirFoto,agregarEncuentro"
            class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-2 rounded-lg">
      Guardar
      <span wire:loading wire:target="guardar" class="ml-2 text-white/80 text-xs">Guardando…</span>
    </button>
    <a href="{{ route('buscar') }}" class="px-4 py-2 rounded-lg border border-gray-300 bg-white">Volver</a>
  </div>

  {{-- 1) NOMBRE / FOTO --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Nombre y carta</h2>

    <div class="grid md:grid-cols-3 gap-4">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1 text-gray-700">Nombre y Apellido</label>
        <input type="text" wire:model.defer="perfil.NOMBRE_Y_APELLIDO"
               class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
               placeholder="Ej: Ana Pérez">
        @error('perfil.NOMBRE_Y_APELLIDO') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 text-gray-700">URL de Foto (opcional)</label>
        <input type="url" wire:model.defer="perfil.FOTO_URL"
               class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
               placeholder="https://...">
      </div>
    </div>

    {{-- Drag & Drop / Selector --}}
    <div
      @drop.prevent="$refs.finput.files = event.dataTransfer.files; $refs.finput.dispatchEvent(new Event('change'))"
      @dragover.prevent
      class="border-2 border-dashed rounded-xl p-4 text-center text-gray-600">
      <p class="mb-2">Arrastrá una imagen acá o elegila desde tu equipo</p>
      <input type="file" x-ref="finput" accept="image/*" wire:model="fotoUpload" class="hidden">
      <button type="button" onclick="this.previousElementSibling.click()" ...>Seleccionar archivo</button>
      <div class="text-xs text-gray-500 mt-1" wire:loading wire:target="fotoUpload">Cargando archivo…</div>
      @error('fotoUpload') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror

      <div class="mt-3">
        <button type="button" wire:click="subirFoto"
                wire:loading.attr="disabled"
                wire:target="subirFoto,fotoUpload"
                class="px-3 py-1.5 rounded bg-emerald-600 text-white">
          Subir a “Cartas Astrales”
          <span wire:loading wire:target="subirFoto" class="ml-2 text-white/80 text-xs">Subiendo…</span>
        </button>
      </div>
    </div>

    @if(($perfil['FOTO_URL'] ?? '') !== '')
      <img
        src="{{ $perfil['FOTO_URL'] }}"
        alt="Carta / Foto"
        class="h-44 rounded-lg object-cover border"
        onerror="if(!this.dataset.tried){this.dataset.tried=1; this.src=this.src.replace('export=view','');}"
      >
    @endif

  </section>

  {{-- 2) CONTACTO --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-3">
    <h2 class="font-semibold text-lg">Contacto</h2>
    <input type="text" wire:model.defer="perfil.CONTACTO"
           class="w-full border border-gray-300 rounded-lg p-2.5"
           placeholder="Nombre y apellido de quien lo deriva / recomienda">
  </section>

  {{-- 3) DATOS NATALES (+ Signo Solar + Edad) --}}
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
        <input type="text" wire:model.defer="perfil.HORA_NAC" class="w-full border border-gray-300 rounded-lg p-2.5" placeholder="03:15 PM">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Año (AAAA)</label>
        <input type="text" wire:model.defer="perfil.ANIO_NAC" class="w-full border border-gray-300 rounded-lg p-2.5" placeholder="1990">
      </div>

      <div>
        <label class="block text-sm text-gray-700">Ciudad</label>
        <input type="text" wire:model.defer="perfil.CIUDAD_NAC" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Provincia</label>
        <input type="text" wire:model.defer="perfil.PROVINCIA_NAC" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">País</label>
        <input type="text" wire:model.defer="perfil.PAIS_NAC" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>

      <div>
        <label class="block text-sm text-gray-700">Ciudad último cumpleaños</label>
        <input type="text" wire:model.defer="perfil.CIUDAD_ULT_CUMPLE" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Provincia último cumpleaños</label>
        <input type="text" wire:model.defer="perfil.PROV_ULT_CUMPLE" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">País último cumpleaños</label>
        <input type="text" wire:model.defer="perfil.PAIS_ULT_CUMPLE" class="w-full border border-gray-300 rounded-lg p-2.5">
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
               value="{{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] }}" readonly>
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
    <div class="grid md:grid-cols-3 gap-4">
      <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_MELLIZOS"> Mellizos</label>
      <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_ADOPTADO"> Adoptado</label>
      <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_ABUSOS"> Abusos</label>
      <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_SUICIDIO"> Suicidio</label>
      <label class="flex items-center gap-2"><input type="checkbox" wire:model.defer="perfil.FILTRO_ENFERMEDAD"> Enfermedad</label>
    </div>
  </section>

  {{-- 5–6) FECHA/HORA ENCUENTRO + EDAD EN ESE ENCUENTRO (se usa arriba también) --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Encuentro inicial</h2>
    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm text-gray-700">Fecha del encuentro (DD/MM/AAAA)</label>
        <input type="text" wire:model.lazy="perfil.FECHA_ENCUENTRO_INICIAL"
               class="w-full border border-gray-300 rounded-lg p-2.5" placeholder="15/07/2025">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Hora (NN:NN AM/PM)</label>
        <input type="text" wire:model.defer="perfil.HORA_ENCUENTRO_INICIAL"
               class="w-full border border-gray-300 rounded-lg p-2.5" placeholder="10:30 AM">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Edad (auto)</label>
        <input type="text" class="w-full border rounded-lg p-2.5 bg-gray-100"
               value="{{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] }}" readonly>
      </div>
    </div>
  </section>

  {{-- 7–10) TEXTO LIBRE --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Lectura</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-gray-700">Signo Subyacente</label>
        <textarea wire:model.defer="perfil.SIGNO_SUBYACENTE" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
      </div>
      <div>
        <label class="block text-sm text-gray-700">Balance Energético</label>
        <textarea wire:model.defer="perfil.BALANCE_ENERGETICO" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
      </div>
      <div>
        <label class="block text-sm text-gray-700">Dispositores</label>
        <textarea wire:model.defer="perfil.DISPOSITORES" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
      </div>
      <div>
        <label class="block text-sm text-gray-700">Progresiones y Retornos</label>
        <textarea wire:model.defer="perfil.PROGRESIONES_RETORNOS" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
      </div>
    </div>
  </section>

  {{-- 11) FASE DE LUNACIÓN + planeta asociado --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-6">

      {{-- BLOQUE: DATOS PARA CALCULAR FASE --}}
      <div class="space-y-4">
          <h2 class="font-semibold text-lg">Fase de Lunación Natal</h2>

          <div class="grid md:grid-cols-4 gap-4">

              <div>
                  <label class="block text-sm text-gray-700">Signo del Sol</label>
                  <input type="text" wire:model.lazy="perfil.SIGNO_SOL"
                        placeholder="Ej: Aries"
                        class="w-full border rounded-lg p-2.5">
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Grado del Sol</label>
                  <input type="number" min="0" max="29" wire:model.lazy="perfil.GRADO_SOL"
                        class="w-full border rounded-lg p-2.5">
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Signo de la Luna</label>
                  <input type="text" wire:model.lazy="perfil.SIGNO_LUNA"
                        placeholder="Ej: Cáncer"
                        class="w-full border rounded-lg p-2.5">
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Grado de la Luna</label>
                  <input type="number" min="0" max="29" wire:model.lazy="perfil.GRADO_LUNA"
                        class="w-full border rounded-lg p-2.5">
              </div>

          </div>

          <div class="pt-4 border-t space-y-3">
              <div>
                  <label class="block text-sm text-gray-700">Fase calculada</label>
                  <input type="text" class="w-full bg-gray-100 p-2.5 rounded-lg"
                        value="{{ $perfil['FASE_LUNACION_NATAL'] ?? '' }}" readonly>
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Planeta asociado</label>
                  <input type="text" class="w-full bg-gray-100 p-2.5 rounded-lg"
                        value="{{ $perfil['PLANETA_ASOCIADO_LUNACION'] ?? '' }}" readonly>
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Signo asociado</label>
                  <input type="text" class="w-full bg-gray-100 p-2.5 rounded-lg"
                        value="{{ $perfil['SIGNO_ASOCIADO_LUNACION'] ?? '' }}" readonly>
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Texto asociado</label>
                  <input type="text" class="w-full bg-gray-100 p-2.5 rounded-lg"
                        value="{{ $perfil['TEXTO_FASE_LUNACION'] ?? '' }}" readonly>
              </div>

              @if(!empty($perfil['IMAGEN_FASE_LUNACION']))
                  <img src="{{ asset('images/fases/' . $perfil['IMAGEN_FASE_LUNACION']) }}"
                      class="h-40 rounded-lg border object-cover">
              @endif
          </div>
      </div>


      {{-- BLOQUE INFERIOR: GRADO SABIANO --}}
      <div class="pt-6 border-t space-y-4">
          <h2 class="font-semibold text-lg">Grado Sabiano</h2>

          <div class="grid md:grid-cols-3 gap-4">
              <div>
                  <label class="block text-sm text-gray-700">Signo</label>
                  <input type="text" wire:model.lazy="perfil.SIGNO_SABIANO"
                        placeholder="Aries"
                        class="w-full border rounded-lg p-2.5">
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Grado (1 a 30)</label>
                  <input type="number" min="1" max="30"
                        wire:model.lazy="perfil.GRADO_SABIANO"
                        class="w-full border rounded-lg p-2.5">
              </div>

              <div>
                  <label class="block text-sm text-gray-700">Título</label>
                  <input type="text" class="w-full bg-gray-100 rounded-lg p-2.5"
                        value="{{ $perfil['TITULO_SABIANO'] ?? '' }}" readonly>
              </div>
          </div>

          @if(!empty($perfil['IMAGEN_SABIANO']))
              <img src="{{ asset($perfil['IMAGEN_SABIANO']) }}"
                  class="h-48 rounded-lg border object-cover">
          @endif
      </div>

  </section>

  {{-- 12) ENCUENTRO (preguntas) --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Encuentro (preguntas)</h2>

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm text-gray-700">¿Primera vez Astrología?</label>
        <select wire:model.defer="perfil.PRIMERA_VEZ_ASTROLOGIA" class="w-full border border-gray-300 rounded-lg p-2.5">
          <option value="">Seleccionar…</option>
          <option value="SI">Sí</option>
          <option value="NO">No</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-gray-700">Profesión / Ocupación</label>
        <input type="text" wire:model.defer="perfil.PROFESION" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>

      <div>
        <label class="block text-sm text-gray-700">Vivo con</label>
        <input type="text" wire:model.defer="perfil.VIVO_CON" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Hogar de la Infancia</label>
        <input type="text" wire:model.defer="perfil.HOGAR_INFANCIA" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Enfermedades de la Infancia</label>
        <input type="text" wire:model.defer="perfil.ENF_INFANCIA" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm text-gray-700">Síntomas actuales</label>
        <textarea wire:model.defer="perfil.SINTOMAS_ACTUALES" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm text-gray-700">Motivo de la Consulta</label>
        <textarea wire:model.defer="perfil.MOTIVO_CONSULTA" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
      </div>
    </div>
  </section>

  {{-- 13) DETALLE DEL ENCUENTRO --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-3">
    <h2 class="font-semibold text-lg">Detalle del encuentro</h2>
    <textarea wire:model.defer="perfil.DETALLE_ENCUENTRO_INICIAL" rows="4"
              class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
  </section>

  {{-- 14) RESUMEN PARA PSICÓLOGA (texto + URL de audio opcional) --}}
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

  {{-- ACCIONES INFERIORES (duplicadas para facilidad) --}}
  <div class="flex gap-3">
    <button wire:click="guardar"
            wire:loading.attr="disabled"
            wire:target="guardar,subirFoto,agregarEncuentro"
            class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-2 rounded-lg">
      Guardar
      <span wire:loading wire:target="guardar" class="ml-2 text-white/80 text-xs">Guardando…</span>
    </button>
    <a href="{{ route('buscar') }}" class="px-4 py-2 rounded-lg border border-gray-300 bg-white">Volver</a>
  </div>

  {{-- Eventos UI --}}
  <script>
    document.addEventListener('livewire:init', () => {
      Livewire.on('scroll-top', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    });
  </script>
</div>
