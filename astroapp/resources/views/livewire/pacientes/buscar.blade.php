<div class="max-w-3xl mx-auto p-6 space-y-8">
  <header class="space-y-2">
    <h1 class="text-3xl font-semibold tracking-tight">Pacientes</h1>
    <p class="text-sm text-gray-600">Creá nuevos registros, buscá por nombre y editá la información.</p>
  </header>

  @if($error)
    <div class="bg-red-50 text-red-800 p-3 rounded border border-red-200">
      {{ $error }}
    </div>
  @endif

  {{-- Crear paciente --}}
  <form action="{{ route('paciente.crear') }}" method="POST" class="flex gap-3 items-end bg-white p-4 rounded-xl shadow border border-gray-100">
    @csrf
    <div class="flex-1">
      <label class="block text-sm font-medium mb-1 text-gray-700">Nombre y Apellido</label>
      <input
        name="nombre_apellido"
        required
        class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
        placeholder="Ej: Ana Pérez">
    </div>
    <button class="bg-emerald-600 hover:bg-emerald-700 transition text-white px-4 py-2 rounded-lg">
      + Nuevo paciente
    </button>
  </form>

  {{-- Encuentro rápido --}}
  <div class="bg-white p-4 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Agregar encuentro rápido</h2>
    <p class="text-sm text-gray-600">Buscá un paciente, seleccionalo y agregá un encuentro sin entrar a editar.</p>

    {{-- Buscador --}}
    <div class="relative flex gap-2 items-center">
      <input
        type="text"
        wire:keyup.debounce.200ms="buscar($event.target.value)"
        class="flex-1 border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
        placeholder="Escribí las primeras letras del nombre…">
      
      {{-- Botón Listo --}}
      <button type="button"
              wire:click="limpiarBusqueda"
              class="px-3 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm text-gray-700 transition">
        Listo
      </button>

      <div class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500" wire:loading wire:target="buscar">
        buscando…
      </div>
    </div>


    {{-- Sugerencias (solo si hay texto) --}}
    @if(trim($q) !== '')
      <div class="mt-2 max-h-56 overflow-auto border rounded-lg divide-y">
        @forelse($sugs as $sg)
          <button type="button"
                  wire:key="sug-{{ $sg['id'] }}"
                  wire:click="seleccionarPaciente('{{ $sg['id'] }}')"
                  class="w-full text-left p-2 hover:bg-gray-50">
            {{ $sg['nombre'] }}
          </button>
        @empty
          <div class="p-2 text-gray-500 text-sm">Sin resultados</div>
        @endforelse
      </div>
    @endif


    {{-- Form encuentro cuando hay selección --}}
    @if($selId)
      <div class="p-3 bg-gray-50 rounded-lg border text-sm">
        <div class="mb-2 text-gray-700">
          <span class="font-medium">Paciente seleccionado:</span>
          <span>{{ $selNombre }}</span>
        </div>

        <div class="grid md:grid-cols-5 gap-3 items-end">
          <div>
            <label class="block text-xs text-gray-700">Fecha (DD/MM/AAAA)</label>
            <input type="text" wire:model.lazy="enc.FECHA"
                  class="w-full border border-gray-300 rounded-lg p-2" placeholder="10/08/2025">
            @error('enc.FECHA') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-xs text-gray-700">Ciudad último cumple</label>
            <input type="text" wire:model.defer="enc.CIUDAD_ULT_CUMPLE" class="w-full border rounded-lg p-2">
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs text-gray-700">Temas tratados</label>
            <input type="text" wire:model.defer="enc.TEMAS_TRATADOS" class="w-full border rounded-lg p-2">
          </div>
          <div>
            <label class="block text-xs text-gray-700">Edad (auto)</label>
            <input type="text" class="w-full border rounded-lg p-2 bg-gray-100"
                  value="{{ $enc['EDAD_EN_ESE_ENCUENTRO'] ?? '' }}" readonly>
          </div>
          <div class="md:col-span-5">
            <label class="block text-xs text-gray-700">Resumen</label>
            <textarea wire:model.defer="enc.RESUMEN" rows="2"
                      class="w-full border border-gray-300 rounded-lg p-2"></textarea>
          </div>
        </div>

        <div class="mt-3">
          <button type="button"
                  wire:click="agregarEncuentroRapido"
                  wire:loading.attr="disabled"
                  wire:target="agregarEncuentroRapido"
                  class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
            Agregar encuentro
            <span wire:loading wire:target="agregarEncuentroRapido" class="ml-2 text-white/80 text-xs">Agregando…</span>
          </button>
          @if($msgEncuentro)
            <span class="ml-3 text-emerald-700">{{ $msgEncuentro }}</span>
          @endif
        </div>
      </div>
    @endif
  </div>


  {{-- Lista --}}
<section class="space-y-3">
  <h2 class="text-sm font-medium text-gray-700 uppercase tracking-wider">Lista de pacientes</h2>

  <div class="bg-white rounded-xl shadow border border-gray-100 divide-y">
    @forelse($items as $it)
      <div class="flex items-center justify-between p-4 hover:bg-gray-50 transition">
        {{-- Info principal --}}
        <div>
          <div class="font-medium text-gray-900">{{ $it['nombre'] }}</div>
          <div class="text-xs text-gray-500">
            @php
              $signo = $it['signo'] ?? '';
              $edad  = $it['edad'] ?? '';
              $edad2 = $edad !== '' ? str_pad($edad, 2, '0', STR_PAD_LEFT) : '';
            @endphp
            @if($signo || $edad2 !== '')
              <span>Signo Solar: {{ $signo ?: '—' }}</span>
              <span class="mx-2">·</span>
              <span>Edad: {{ $edad2 !== '' ? $edad2 : '—' }}</span>
            @else
              <span class="italic text-gray-400">Sin datos aún</span>
            @endif
          </div>
        </div>

        {{-- Acciones (ver / editar) --}}
        <div class="flex items-center gap-3">
          {{-- Ver (ojo) --}}
          <a href="{{ route('paciente.ver', $it['id']) }}" 
             class="text-gray-400 hover:text-blue-600 transition" 
             title="Ver paciente">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
            </svg>
          </a>

          {{-- Editar (lápiz) --}}
          <a href="{{ route('paciente.editar', $it['id']) }}" 
             class="text-gray-400 hover:text-emerald-600 transition" 
             title="Editar paciente">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232a3 3 0 1 1 4.243 4.243L7.5 21H3v-4.5l12.232-11.268z" />
            </svg>
          </a>
        </div>
      </div>
    @empty
      <div class="p-4 text-gray-500">No hay pacientes aún.</div>
    @endforelse
  </div>
</section>

</div>
