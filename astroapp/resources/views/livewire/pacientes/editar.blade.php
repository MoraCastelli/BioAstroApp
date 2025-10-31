<div class="max-w-5xl mx-auto p-6 space-y-8">
  <h1 class="text-2xl font-bold">Paciente: {{ $perfil['NOMBRE_Y_APELLIDO'] ?: '—' }}</h1>

  @if($mensaje)
    <div class="bg-green-100 text-green-800 p-3 rounded">{{ $mensaje }}</div>
  @endif

  {{-- BOTÓN GUARDAR ARRIBA --}}
  <div class="flex gap-3">
    <button wire:click="guardar" class="bg-blue-600 text-white px-4 py-2 rounded">Guardar</button>
    <a href="{{ route('buscar') }}" class="px-4 py-2 rounded border">Volver</a>
  </div>

  {{-- 1) Identificación --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">1) Nombre y apellido / Foto</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium">Nombre y Apellido</label>
        <input type="text" wire:model.defer="perfil.NOMBRE_Y_APELLIDO" class="w-full border rounded p-2" placeholder="Ej: Ana Pérez">
        @error('perfil.NOMBRE_Y_APELLIDO') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm font-medium">URL de Foto (opcional)</label>
        <input type="url" wire:model.defer="perfil.FOTO_URL" class="w-full border rounded p-2" placeholder="https://...">
      </div>
      @if(($perfil['FOTO_URL'] ?? '') !== '')
        <div class="md:col-span-2">
          <img src="{{ $perfil['FOTO_URL'] }}" alt="Foto" class="h-40 rounded object-cover border">
        </div>
      @endif
    </div>
  </section>

  {{-- 2) Contacto --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">2) Contacto</h2>
    <input type="text" wire:model.defer="perfil.CONTACTO" class="w-full border rounded p-2" placeholder="Quién lo deriva / recomienda">
  </section>

  {{-- 3) Datos natales --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">3) Datos natales</h2>
    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm">Fecha de Nacimiento (DD/MM/AAAA)</label>
        <input type="text" wire:model.lazy="perfil.FECHA_NAC" class="w-full border rounded p-2" placeholder="01/02/1990">
        @error('perfil.FECHA_NAC') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm">Hora (NN:NN AM/PM)</label>
        <input type="text" wire:model.defer="perfil.HORA_NAC" class="w-full border rounded p-2" placeholder="03:15 PM">
      </div>
      <div>
        <label class="block text-sm">Año (AAAA)</label>
        <input type="text" wire:model.defer="perfil.ANIO_NAC" class="w-full border rounded p-2" placeholder="1990">
      </div>

      <div>
        <label class="block text-sm">Ciudad</label>
        <input type="text" wire:model.defer="perfil.CIUDAD_NAC" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="block text-sm">Provincia</label>
        <input type="text" wire:model.defer="perfil.PROVINCIA_NAC" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="block text-sm">País</label>
        <input type="text" wire:model.defer="perfil.PAIS_NAC" class="w-full border rounded p-2">
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mt-4">
      <div>
        <label class="block text-sm">Ciudad último cumpleaños</label>
        <input type="text" wire:model.defer="perfil.CIUDAD_ULT_CUMPLE" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="block text-sm">Provincia último cumpleaños</label>
        <input type="text" wire:model.defer="perfil.PROV_ULT_CUMPLE" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="block text-sm">País último cumpleaños</label>
        <input type="text" wire:model.defer="perfil.PAIS_ULT_CUMPLE" class="w-full border rounded p-2">
      </div>
    </div>

    <div class="mt-4">
      <label class="block text-sm">Observaciones</label>
      <textarea wire:model.defer="perfil.OBSERVACIONES" rows="3" class="w-full border rounded p-2"></textarea>
    </div>
  </section>

  {{-- 4) Filtros + Signo Solar + Edad automática --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">4) Filtros</h2>
    <div class="grid md:grid-cols-3 gap-3">
      @foreach (['MELLIZOS','ADOPTADO','ABUSOS','SUICIDIO','ENFERMEDAD'] as $f)
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" wire:model.defer="perfil.{{ 'FILTRO_'.$f }}" value="SI" class="scale-110">
          <span>{{ ucfirst(strtolower($f)) }}</span>
        </label>
      @endforeach
      <div>
        <label class="block text-sm">Signo Solar</label>
        <input type="text" wire:model.defer="perfil.SIGNO_SOLAR" class="w-full border rounded p-2" placeholder="Ej: Aries">
      </div>
      <div>
        <label class="block text-sm">Edad (automática)</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] }}" readonly>
      </div>
    </div>
  </section>

  {{-- 5) Fecha y Hora del encuentro inicial --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">5) Fecha y hora del encuentro</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Fecha (DD/MM/AAAA)</label>
        <input type="text" wire:model.lazy="perfil.FECHA_ENCUENTRO_INICIAL" class="w-full border rounded p-2" placeholder="10/03/2025">
        @error('perfil.FECHA_ENCUENTRO_INICIAL') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm">Hora (NN:NN AM/PM)</label>
        <input type="text" wire:model.defer="perfil.HORA_ENCUENTRO_INICIAL" class="w-full border rounded p-2" placeholder="11:00 AM">
      </div>
    </div>
  </section>

  {{-- 6) Edad ya mostrada arriba (automática) --}}

  {{-- 7–10) Campos de texto libres --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">7–10) Subyacente / Balance / Dispositores / Progresiones</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">7) Signo Subyacente</label>
        <textarea wire:model.defer="perfil.SIGNO_SUBYACENTE" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
      <div>
        <label class="block text-sm">8) Balance Energético</label>
        <textarea wire:model.defer="perfil.BALANCE_ENERGETICO" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
      <div>
        <label class="block text-sm">9) Dispositores</label>
        <textarea wire:model.defer="perfil.DISPOSITORES" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
      <div>
        <label class="block text-sm">10) Progresiones y Retornos</label>
        <textarea wire:model.defer="perfil.PROGRESIONES_RETORNOS" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
    </div>
  </section>

  {{-- 11) Fase de Lunación Natal --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">11) Fase de Lunación Natal</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Fase (8 opciones)</label>
        <select wire:model="perfil.FASE_LUNACION_NATAL" class="w-full border rounded p-2">
          <option value="">— Elegir —</option>
          @foreach($fasesLunacion as $fase => $planeta)
            <option value="{{ $fase }}">{{ $fase }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm">Planeta asociado (auto)</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ $perfil['PLANETA_ASOCIADO_LUNACION'] }}" readonly>
      </div>
    </div>
  </section>

  {{-- 12) Encuentro (preguntas) --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">12) Encuentro</h2>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="flex items-center gap-3">
        <span class="text-sm font-medium">¿Primera vez Astrología?</span>
        <label class="inline-flex items-center gap-1">
          <input type="radio" wire:model.defer="perfil.PRIMERA_VEZ_ASTROLOGIA" value="SI"> <span>SI</span>
        </label>
        <label class="inline-flex items-center gap-1">
          <input type="radio" wire:model.defer="perfil.PRIMERA_VEZ_ASTROLOGIA" value="NO"> <span>NO</span>
        </label>
      </div>
      <div>
        <label class="block text-sm">Profesión / Ocupación</label>
        <input type="text" wire:model.defer="perfil.PROFESION" class="w-full border rounded p-2">
      </div>

      <div>
        <label class="block text-sm">Vivo con</label>
        <input type="text" wire:model.defer="perfil.VIVO_CON" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="block text-sm">Hogar de la Infancia</label>
        <input type="text" wire:model.defer="perfil.HOGAR_INFANCIA" class="w-full border rounded p-2">
      </div>

      <div>
        <label class="block text-sm">Enfermedades de la Infancia</label>
        <input type="text" wire:model.defer="perfil.ENF_INFANCIA" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="block text-sm">Síntomas actuales</label>
        <input type="text" wire:model.defer="perfil.SINTOMAS_ACTUALES" class="w-full border rounded p-2">
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm">Motivo de la Consulta</label>
        <textarea wire:model.defer="perfil.MOTIVO_CONSULTA" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
    </div>
  </section>

  {{-- 13) Detalle del Encuentro / 14) Resumen para Psicóloga --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">13–14) Detalle y Resumen para Psicóloga</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Detalle del encuentro</label>
        <textarea wire:model.defer="perfil.DETALLE_ENCUENTRO_INICIAL" rows="5" class="w-full border rounded p-2"></textarea>
      </div>
      <div>
        <label class="block text-sm">URL Audio Resumen (opcional)</label>
        <input type="url" wire:model.defer="perfil.RESUMEN_PARA_PSICOLOGA_URL_AUDIO" class="w-full border rounded p-2" placeholder="https://...">
        <label class="block text-sm mt-3">Resumen (texto)</label>
        <textarea wire:model.defer="perfil.RESUMEN_PARA_PSICOLOGA_TEXTO" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
    </div>
  </section>

  {{-- 15) Nuevo encuentro (append a hoja Encuentros) --}}
  <section class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold text-lg mb-3">15) Nuevo encuentro</h2>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Fecha (DD/MM/AAAA)</label>
        <input type="text" wire:model.lazy="nuevoEncuentro.FECHA" class="w-full border rounded p-2" placeholder="15/04/2025">
        @error('nuevoEncuentro.FECHA') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm">Ciudad que pasó último cumpleaños</label>
        <input type="text" wire:model.defer="nuevoEncuentro.CIUDAD_ULT_CUMPLE" class="w-full border rounded p-2">
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm">Temas tratados</label>
        <textarea wire:model.defer="nuevoEncuentro.TEMAS_TRATADOS" rows="3" class="w-full border rounded p-2"></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm">Resumen</label>
        <textarea wire:model.defer="nuevoEncuentro.RESUMEN" rows="3" class="w-full border rounded p-2"></textarea>
      </div>

      <div class="md:col-span-2">
        <button wire:click="agregarEncuentro" class="bg-emerald-600 text-white px-4 py-2 rounded">
          Agregar encuentro
        </button>
      </div>
    </div>
  </section>

  {{-- BOTÓN GUARDAR ABAJO --}}
  <div class="flex gap-3">
    <button wire:click="guardar" class="bg-blue-600 text-white px-4 py-2 rounded">Guardar</button>
    <a href="{{ route('buscar') }}" class="px-4 py-2 rounded border">Volver</a>
  </div>
</div>
