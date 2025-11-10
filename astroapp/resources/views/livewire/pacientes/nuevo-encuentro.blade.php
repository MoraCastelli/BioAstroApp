<div class="max-w-4xl mx-auto p-6 space-y-8">
  <header>
    <h1 class="text-3xl font-semibold tracking-tight">Nuevo encuentro</h1>
    <p class="text-sm text-gray-600">Agregá un nuevo encuentro para este paciente.</p>
  </header>

  <section class="bg-white p-5 rounded-xl shadow border border-gray-100">
    <h2 class="font-semibold text-lg mb-3">Datos del paciente</h2>
    <p><strong>Nombre:</strong> {{ $perfil['NOMBRE_Y_APELLIDO'] ?? '—' }}</p>
    <p><strong>Fecha de Nacimiento:</strong> {{ $perfil['FECHA_NAC'] ?? '—' }}</p>
    <p><strong>Signo Solar:</strong> {{ $perfil['SIGNO_SOLAR'] ?? '—' }}</p>
  </section>

  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Nuevo encuentro</h2>
    @if($mensaje)
      <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-3 rounded">{{ $mensaje }}</div>
    @endif

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm text-gray-700">Fecha (DD/MM/AAAA)</label>
        <input type="text" wire:model.lazy="enc.FECHA" class="w-full border border-gray-300 rounded-lg p-2.5">
        @error('enc.FECHA') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm text-gray-700">Ciudad último cumpleaños</label>
        <input type="text" wire:model.defer="enc.CIUDAD_ULT_CUMPLE" class="w-full border border-gray-300 rounded-lg p-2.5">
      </div>
      <div>
        <label class="block text-sm text-gray-700">Edad (auto)</label>
        <input type="text" class="w-full border rounded-lg p-2.5 bg-gray-100"
               value="{{ $enc['EDAD_EN_ESE_ENCUENTRO'] ?? '' }}" readonly>
      </div>
    </div>

    <div>
      <label class="block text-sm text-gray-700">Temas tratados</label>
      <input type="text" wire:model.defer="enc.TEMAS_TRATADOS" class="w-full border border-gray-300 rounded-lg p-2.5">
    </div>
    <div>
      <label class="block text-sm text-gray-700">Resumen</label>
      <textarea wire:model.defer="enc.RESUMEN" rows="4"
                class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
    </div>

    <div class="flex gap-3 items-center">
    {{-- Botón principal --}}
    <button wire:click="guardar"
            wire:loading.attr="disabled"
            wire:target="guardar"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <svg wire:loading.remove wire:target="guardar" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 13l4 4L19 7" />
        </svg>
        <svg wire:loading wire:target="guardar" class="w-5 h-5 animate-spin text-white/90" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <circle cx="12" cy="12" r="9" stroke-opacity=".25" stroke-width="4"></circle>
        <path d="M21 12a9 9 0 0 1-9 9" stroke-width="4"></path>
        </svg>
        <span>
        <span wire:loading.remove wire:target="guardar">Guardar encuentro</span>
        <span wire:loading wire:target="guardar">Guardando...</span>
        </span>
    </button>

    {{-- Botón cancelar --}}
    <a href="{{ route('paciente.ver', $id) }}" 
        class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 transition">
        Cancelar
    </a>
    </div>

  </section>
  <div wire:loading.delay wire:target="guardar"
     class="fixed inset-0 bg-black/10 grid place-items-center z-40">
  <div class="bg-white border shadow rounded-xl px-4 py-3 flex items-center gap-3">
    <svg class="w-5 h-5 animate-spin text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <circle cx="12" cy="12" r="9" stroke-opacity=".25" stroke-width="4"></circle>
      <path d="M21 12a9 9 0 0 1-9 9" stroke-width="4"></path>
    </svg>
    <span class="text-sm text-gray-700">Guardando encuentro…</span>
  </div>
</div>
</div>
{{-- Overlay global de loading --}}

