<div class="p-6 max-w-4xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Editar paciente</h1>

  @if($mensaje)
    <div class="mb-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3">{{ $mensaje }}</div>
  @endif

  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="text-sm">Nombre y Apellido</label>
      <input type="text" class="w-full border rounded-xl px-3 py-2" wire:model.defer="perfil.NOMBRE_Y_APELLIDO">
    </div>

    <div>
      <label class="text-sm">Contacto (quién deriva)</label>
      <input type="text" class="w-full border rounded-xl px-3 py-2" wire:model.defer="perfil.CONTACTO">
    </div>

    <div>
      <label class="text-sm">Fecha Nac (DD/MM/AAAA)</label>
      <input type="text" class="w-full border rounded-xl px-3 py-2" wire:model.defer="perfil.FECHA_NAC">
    </div>

    <div>
      <label class="text-sm">Hora Nac (NN:NN AM/PM)</label>
      <input type="text" class="w-full border rounded-xl px-3 py-2" wire:model.defer="perfil.HORA_NAC">
    </div>

    <div class="md:col-span-2">
      <label class="text-sm">Observaciones</label>
      <textarea class="w-full border rounded-xl px-3 py-2" rows="3" wire:model.defer="perfil.OBSERVACIONES"></textarea>
    </div>
  </div>

  <div class="mt-4 flex gap-2">
    <button wire:click="guardar" class="bg-emerald-600 text-white px-4 py-2 rounded-xl">Guardar</button>
    <a href="{{ route('buscar') }}" class="border px-4 py-2 rounded-xl">Volver</a>
  </div>
</div>
