<div class="max-w-5xl mx-auto p-6 space-y-8">
  <header class="space-y-2">
    <h1 class="text-3xl font-semibold tracking-tight">Pacientes</h1>
    <p class="text-sm text-gray-600">Buscá, filtrá y gestioná tus pacientes.</p>
  </header>

  @if($error)
    <div class="bg-red-50 text-red-800 p-3 rounded border border-red-200">
      {{ $error }}
    </div>
  @endif

  {{-- Barra de control superior --}}
  <div class="flex flex-wrap gap-3 items-center justify-between bg-white p-4 rounded-xl shadow border border-gray-100">
    {{-- Buscador --}}
    <input
      type="text"
      wire:model.debounce.300ms="q"
      class="flex-1 border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg p-2.5"
      placeholder="Buscar paciente por nombre…">

    {{-- Filtros --}}
    <div class="relative">
      <button type="button"
              class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 transition"
              onclick="document.getElementById('filtros').classList.toggle('hidden')">
        Filtros ▼
      </button>
      <div id="filtros"
           class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow p-3 space-y-1 z-10">
        @foreach($filtrosDisponibles as $campo => $label)
          <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="filtrosSeleccionados" value="{{ $campo }}" class="rounded">
            {{ $label }}
          </label>
        @endforeach
      </div>
    </div>

    {{-- Crear paciente vacío --}}
    <button wire:click="crearPacienteVacio"
            class="bg-emerald-600 hover:bg-emerald-700 transition text-white px-4 py-2 rounded-lg">
      + Agregar paciente
    </button>
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

        {{-- Acciones (ver / editar / agregar encuentro) --}}
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

          {{-- Agregar encuentro (símbolo +) --}}
          <a href="{{ route('paciente.nuevo-encuentro', $it['id']) }}" 
             class="text-gray-400 hover:text-emerald-600 transition" 
             title="Agregar encuentro">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
          </a>

          {{-- Eliminar (basura) --}}
          <a href="{{ route('paciente.eliminar', $it['id']) }}" 
            class="text-gray-400 hover:text-red-600 transition" 
            title="Eliminar paciente">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 7h12M9 7V4h6v3m2 0v13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7h12z" />
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
