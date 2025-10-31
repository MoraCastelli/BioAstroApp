
<div class="max-w-3xl mx-auto p-6 space-y-6">
  <h1 class="text-2xl font-bold">Pacientes</h1>

  @if($error)
    <div class="bg-red-50 text-red-800 p-3 rounded">
      {{ $error }}
    </div>
  @endif

  <form action="{{ route('paciente.crear') }}" method="POST" class="flex gap-3 items-end">
    @csrf
    <div class="flex-1">
      <label class="block text-sm font-medium mb-1">Nombre y Apellido</label>
      <input name="nombre_apellido" required class="w-full border rounded p-2" placeholder="Ej: Ana Pérez">
    </div>
    <button class="bg-emerald-600 text-white px-4 py-2 rounded">+ Nuevo paciente</button>
  </form>

  <div>
    <label class="block text-sm font-medium mb-1">Buscar por primeras letras…</label>
    <input type="text" wire:model.debounce.300ms="q" class="w-full border rounded p-2" placeholder="Ej: Mo, Pe, An…">
  </div>

  <div class="bg-white rounded shadow divide-y">
    @forelse($items as $it)
      <a class="flex items-center justify-between p-3 hover:bg-gray-50" href="{{ route('paciente.editar', $it['id']) }}">
        <div>
          <div class="font-medium">{{ $it['nombre'] }}</div>
          <div class="text-xs text-gray-500">ID: {{ $it['id'] }} · {{ $it['ts'] }}</div>
        </div>
        <div title="Editar" class="text-gray-400">➕</div>
      </a>
    @empty
      <div class="p-3 text-gray-500">No hay pacientes aún.</div>
    @endforelse
  </div>
</div>
