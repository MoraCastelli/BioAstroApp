<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{{ $perfil['NOMBRE_Y_APELLIDO'] ?? ($perfil['nombre'] ?? 'Paciente') }}</title>
  <style>
    @page { margin: 2cm; }
    body { font-family: DejaVu Sans, sans-serif; color: #222; line-height: 1.4; font-size: 11pt; }
    h1, h2, h3 { margin: 0; color: #222; }
    h1 { font-size: 24pt; text-align: center; margin-bottom: 20px; }
    h2 { font-size: 14pt; border-bottom: 1px solid #ccc; margin-top: 25px; padding-bottom: 4px; }
    h3 { font-size: 12pt; margin-top: 12px; }
    .foto { width: 180px; height: 180px; object-fit: cover; border-radius: 10px; border: 1px solid #ccc; margin: 0 auto 20px; display: block; }
    .page-break { page-break-after: always; }
    .label { font-weight: bold; color: #444; display: inline-block; width: 160px; vertical-align: top; }
    .value { display: inline-block; width: calc(100% - 165px); }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10pt; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f0f0f0; font-weight: bold; }
    .small { font-size: 10pt; color: #555; }
  </style>
</head>
<body>

{{-- =================== PÁGINA 1 =================== --}}
@php
  $nombre = trim($perfil['NOMBRE_Y_APELLIDO'] ?? ($perfil['nombre'] ?? 'Paciente sin nombre'));
@endphp

<h1>{{ $nombre }}</h1>

@if(!empty($perfil['FOTO_URL']))
  <img src="{{ $perfil['FOTO_URL'] }}" class="foto" alt="Foto">
@endif

<h2>Resumen de encuentros</h2>
@if(count($encuentros))
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Fecha</th>
        <th>Edad</th>
        <th>Ciudad Últ. Cumple</th>
        <th>Temas tratados</th>
      </tr>
    </thead>
    <tbody>
      @foreach($encuentros as $i => $e)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $e['FECHA'] ?? '' }}</td>
          <td>{{ is_numeric($e['EDAD_EN_ESE_ENCUENTRO'] ?? '') ? round($e['EDAD_EN_ESE_ENCUENTRO']) : ($e['EDAD_EN_ESE_ENCUENTRO'] ?? '') }}</td>
          <td>{{ $e['CIUDAD_ULT_CUMPLE'] ?? '' }}</td>
          <td>{{ $e['TEMAS_TRATADOS'] ?? '' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
@else
  <p class="small">Aún no se registraron encuentros.</p>
@endif

<p class="small" style="margin-top:12px;">
  Total de encuentros: {{ count($encuentros) }}<br>
  Última actualización: {{ \Carbon\Carbon::parse($perfil['ULTIMA_ACTUALIZACION'] ?? now())->format('d/m/Y H:i') }}
</p>

<div class="page-break"></div>


{{-- =================== PÁGINA 2 — DATOS PERSONALES =================== --}}
<h2>Datos personales</h2>
<div>
  <div><span class="label">Fecha de Nacimiento:</span> <span class="value">{{ $perfil['FECHA_NAC'] ?? '—' }}</span></div>
  <div><span class="label">Hora de Nacimiento:</span> <span class="value">{{ $perfil['HORA_NAC'] ?? '—' }}</span></div>
  <div><span class="label">Lugar de Nacimiento:</span> <span class="value">{{ $perfil['CIUDAD_NAC'] ?? '' }} {{ $perfil['PROVINCIA_NAC'] ?? '' }} {{ $perfil['PAIS_NAC'] ?? '' }}</span></div>
  <div><span class="label">Signo Solar:</span> <span class="value">{{ $perfil['SIGNO_SOLAR'] ?? '—' }}</span></div>
  <div><span class="label">Fase de Lunación:</span> <span class="value">{{ $perfil['FASE_LUNACION_NATAL'] ?? '—' }} ({{ $perfil['PLANETA_ASOCIADO_LUNACION'] ?? '—' }})</span></div>
  <div><span class="label">Contacto / Derivación:</span> <span class="value">{{ $perfil['CONTACTO'] ?? '—' }}</span></div>
</div>

<div class="section">
  <h2>Lectura Astrológica Inicial</h2>
  <p><strong>Signo Subyacente:</strong> {{ $perfil['SIGNO_SUBYACENTE'] ?? '—' }}</p>
  <p><strong>Balance Energético:</strong> {{ $perfil['BALANCE_ENERGETICO'] ?? '—' }}</p>
  <p><strong>Dispositores:</strong> {{ $perfil['DISPOSITORES'] ?? '—' }}</p>
  <p><strong>Progresiones y Retornos:</strong> {{ $perfil['PROGRESIONES_RETORNOS'] ?? '—' }}</p>
</div>

<div class="section">
  <h2>Motivo de Consulta</h2>
  <p>{{ $perfil['MOTIVO_CONSULTA'] ?? '—' }}</p>
</div>

<div class="section">
  <h2>Observaciones</h2>
  <p>{{ $perfil['OBSERVACIONES'] ?? '—' }}</p>
</div>

<div class="page-break"></div>


{{-- =================== ENCUENTROS =================== --}}
@php $n = 1; @endphp
@foreach($encuentros as $e)
  <h2>Encuentro {{ $n }}</h2>
  <div><span class="label">Fecha:</span> <span class="value">{{ $e['FECHA'] ?? '—' }}</span></div>
  <div><span class="label">Edad:</span> <span class="value">{{ is_numeric($e['EDAD_EN_ESE_ENCUENTRO'] ?? '') ? round($e['EDAD_EN_ESE_ENCUENTRO']) : ($e['EDAD_EN_ESE_ENCUENTRO'] ?? '—') }}</span></div>
  <div><span class="label">Ciudad último cumpleaños:</span> <span class="value">{{ $e['CIUDAD_ULT_CUMPLE'] ?? '—' }}</span></div>
  <div><span class="label">Temas tratados:</span> <span class="value">{{ $e['TEMAS_TRATADOS'] ?? '—' }}</span></div>

  <h3>Resumen</h3>
  <p style="white-space: pre-line;">{{ $e['RESUMEN'] ?? '—' }}</p>

  @if(!$loop->last)
    <div class="page-break"></div>
  @endif
  @php $n++; @endphp
@endforeach

</body>
</html>
