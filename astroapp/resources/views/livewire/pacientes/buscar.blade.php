<div class="p-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Pacientes</h1>
  <input wire:model.debounce.300ms="q" type="text" placeholder="Buscar por primeras letras…"
         class="w-full border rounded-xl px-4 py-3 text-lg" />

  <div class="mt-4 space-y-2">
    @foreach($items as $it)
      <a href="{{ route('paciente.editar', $it['id']) }}"
         class="block border rounded-xl px-4 py-3 hover:bg-gray-50">
        <div class="font-semibold">{{ $it['nombre'] }}</div>
        <div class="text-sm text-gray-500">Última actualización: {{ $it['ts'] }}</div>
      </a>
    @endforeach
  </div>

  <div class="mt-6">
    <a href="#" onclick="document.getElementById('nuevo').showModal();return false;"
       class="inline-block bg-emerald-600 text-white px-4 py-2 rounded-xl">+ Nuevo paciente</a>
  </div>

    <dialog id="nuevo" class="rounded-xl p-0">
    <form method="POST" action="{{ route('paciente.crear') }}" class="p-4">
        @csrf
        <label class="text-sm">Nombre y Apellido</label>
        <input name="nombre_apellido" class="w-full border rounded-xl px-3 py-2" required>
        <div class="mt-3 flex gap-2">
        <button class="bg-emerald-600 text-white px-4 py-2 rounded-xl">Crear</button>
        <button type="button" onclick="this.closest('dialog').close()" class="border px-4 py-2 rounded-xl">Cancelar</button>
        </div>
    </form>
    </dialog>

</div>
