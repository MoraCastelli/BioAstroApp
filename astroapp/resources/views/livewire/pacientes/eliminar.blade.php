<div class="max-w-lg mx-auto p-8 space-y-6">
  <header>
    <h1 class="text-2xl font-semibold tracking-tight text-center text-red-700">Eliminar paciente</h1>
    <p class="text-sm text-gray-600 text-center mt-2">
      Una vez eliminado se borrará todo registro del mismo de la base de datos,
      incluyendo archivos y registro en PDF.
    </p>
  </header>

  <div class="bg-white p-6 rounded-xl shadow border border-gray-100 text-center space-y-4">
    <p class="text-gray-700">
      ¿Estás segura que deseas eliminar al paciente <strong>{{ $nombre }}</strong>?
    </p>

    <div class="flex justify-center gap-4 mt-6">
    <button wire:click="eliminar"
            wire:loading.attr="disabled"
            wire:target="eliminar"
            class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg transition">
      <span wire:loading.remove wire:target="eliminar">Eliminar</span>
      <span wire:loading wire:target="eliminar">Eliminando...</span>
    </button>

      <a href="{{ route('paciente.ver', $id) }}"
         class="px-5 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-100 transition">
        Cancelar
      </a>
    </div>
  </div>
</div>
