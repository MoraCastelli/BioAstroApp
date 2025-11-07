<div class="max-w-5xl mx-auto p-6 space-y-8">
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-semibold tracking-tight">{{ $perfil['NOMBRE_Y_APELLIDO'] ?? 'Paciente' }}</h1>
      <p class="text-sm text-gray-600">Vista del perfil astrológico</p>
    </div>
    <div class="flex gap-3">
      <a href="{{ route('paciente.editar', $id) }}"
         class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
        Editar
      </a>
      <a href="{{ route('buscar') }}" class="px-4 py-2 rounded-lg border border-gray-300 bg-white">Volver</a>
    </div>
  </header>

  {{-- FOTO --}}
  @if(!empty($perfil['FOTO_URL']))
    <img src="{{ $perfil['FOTO_URL'] }}" alt="Carta / Foto"
         class="h-60 rounded-lg object-cover border"
         onerror="if(!this.dataset.tried){this.dataset.tried=1; this.src=this.src.replace('export=view','');}">
  @endif

  {{-- DATOS BÁSICOS --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-2">
    <h2 class="font-semibold text-lg">Datos básicos</h2>
    <div class="grid md:grid-cols-2 gap-3 text-sm">
      <div><strong>Fecha de Nacimiento:</strong> {{ $perfil['FECHA_NAC'] ?? '—' }}</div>
      <div><strong>Hora:</strong> {{ $perfil['HORA_NAC'] ?? '—' }}</div>
      <div><strong>Ciudad:</strong> {{ $perfil['CIUDAD_NAC'] ?? '—' }}</div>
      <div><strong>País:</strong> {{ $perfil['PAIS_NAC'] ?? '—' }}</div>
      <div><strong>Signo Solar:</strong> {{ $perfil['SIGNO_SOLAR'] ?? '—' }}</div>
      <div><strong>Edad en encuentro inicial:</strong> {{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] ?? '—' }}</div>
    </div>
  </section>

  {{-- LECTURA --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-4">
    <h2 class="font-semibold text-lg">Lectura Astrológica</h2>
    <div class="grid md:grid-cols-2 gap-4 text-sm">
      <div>
        <strong>Signo Subyacente</strong>
        <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['SIGNO_SUBYACENTE'] ?? '—' }}</p>
      </div>
      <div>
        <strong>Balance Energético</strong>
        <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['BALANCE_ENERGETICO'] ?? '—' }}</p>
      </div>
      <div>
        <strong>Dispositores</strong>
        <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['DISPOSITORES'] ?? '—' }}</p>
      </div>
      <div>
        <strong>Progresiones y Retornos</strong>
        <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['PROGRESIONES_RETORNOS'] ?? '—' }}</p>
      </div>
    </div>
  </section>

  {{-- FASE LUNACIÓN --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100">
    <h2 class="font-semibold text-lg mb-2">Fase de Lunación Natal</h2>
    <p><strong>Fase:</strong> {{ $perfil['FASE_LUNACION_NATAL'] ?? '—' }}</p>
    <p><strong>Planeta Asociado:</strong> {{ $perfil['PLANETA_ASOCIADO_LUNACION'] ?? '—' }}</p>
  </section>

  {{-- DETALLE Y RESUMEN --}}
  <section class="bg-white p-5 rounded-xl shadow border border-gray-100 space-y-3">
    <h2 class="font-semibold text-lg">Resumen y Detalle</h2>
    <div>
      <strong>Motivo de Consulta</strong>
      <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['MOTIVO_CONSULTA'] ?? '—' }}</p>
    </div>
    <div>
      <strong>Detalle del Encuentro Inicial</strong>
      <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['DETALLE_ENCUENTRO_INICIAL'] ?? '—' }}</p>
    </div>
    <div>
      <strong>Resumen para Psicóloga</strong>
      <p class="whitespace-pre-line text-gray-700 mt-1">{{ $perfil['RESUMEN_PARA_PSICOLOGA_TEXTO'] ?? '—' }}</p>
      @if(!empty($perfil['RESUMEN_PARA_PSICOLOGA_URL_AUDIO']))
        <a href="{{ $perfil['RESUMEN_PARA_PSICOLOGA_URL_AUDIO'] }}"
           class="text-blue-600 underline text-sm mt-2 inline-block" target="_blank">Escuchar audio</a>
      @endif
    </div>
  </section>
</div>
