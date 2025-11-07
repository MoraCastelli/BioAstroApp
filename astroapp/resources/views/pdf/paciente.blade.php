<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 25mm 18mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    h2 { font-size: 16px; margin: 14px 0 8px; }
    .muted { color: #666; }
    .row { margin-bottom: 6px; }
    .label { width: 210px; display: inline-block; font-weight: bold; vertical-align: top; }
    .box { border: 1px solid #ddd; border-radius: 6px; padding: 10px; margin-top: 8px; }
    .mb8 { margin-bottom: 8px; }
    .mb12 { margin-bottom: 12px; }
    .mb16 { margin-bottom: 16px; }
    .page-break { page-break-before: always; }
    img.photo { max-width: 100%; max-height: 180px; display: block; margin: 8px 0 12px; }
    .key { font-weight: bold; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
  </style>
</head>
<body>

  {{-- ================== PÁGINA 1: PERFIL + PRIMER ENCUENTRO ================== --}}
  <h1>{{ $perfil['NOMBRE_Y_APELLIDO'] ?? '' }}</h1>
  <div class="muted mb12">Actualizado: {{ $perfil['ULTIMA_ACTUALIZACION'] ?? '' }}</div>

  @if(!empty($perfil['FOTO_URL']))
    {{-- Si tu DomPDF tiene enable_remote=true, podés mostrar imagen remota --}}
    <img class="photo" src="{{ $perfil['FOTO_URL'] }}" alt="Foto/Carta">
  @endif

  <div class="row"><span class="label">Contacto:</span> {{ $perfil['CONTACTO'] ?? '' }}</div>
  <div class="row"><span class="label">Fecha/Hora Nac.:</span>
    {{ $perfil['FECHA_NAC'] ?? '' }} {{ $perfil['HORA_NAC'] ?? '' }}
  </div>
  <div class="row"><span class="label">Lugar Nac.:</span>
    {{ $perfil['CIUDAD_NAC'] ?? '' }}, {{ $perfil['PROVINCIA_NAC'] ?? '' }}, {{ $perfil['PAIS_NAC'] ?? '' }}
  </div>

  <div class="row"><span class="label">Signo Solar:</span> {{ $perfil['SIGNO_SOLAR'] ?? '' }}</div>
  <div class="row"><span class="label">Edad (encuentro inicial):</span> {{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] ?? '' }}</div>

  <div class="row"><span class="label">Ciudad último cumpleaños:</span>
    {{ $perfil['CIUDAD_ULT_CUMPLE'] ?? '' }}, {{ $perfil['PROV_ULT_CUMPLE'] ?? '' }}, {{ $perfil['PAIS_ULT_CUMPLE'] ?? '' }}
  </div>

  <div class="row"><span class="label">Filtros:</span>
    @php
      $f = [
        'Mellizos'   => $perfil['FILTRO_MELLIZOS'] ?? '',
        'Adoptado'   => $perfil['FILTRO_ADOPTADO'] ?? '',
        'Abusos'     => $perfil['FILTRO_ABUSOS'] ?? '',
        'Suicidio'   => $perfil['FILTRO_SUICIDIO'] ?? '',
        'Enfermedad' => $perfil['FILTRO_ENFERMEDAD'] ?? '',
      ];
      $marcados = collect($f)->filter(fn($v)=>trim((string)$v)!=='')->keys()->implode(' · ');
    @endphp
    {{ $marcados ?: '—' }}
  </div>

  <div class="row"><span class="label">Signo subyacente:</span> {{ $perfil['SIGNO_SUBYACENTE'] ?? '' }}</div>
  <div class="row"><span class="label">Balance energético:</span> {{ $perfil['BALANCE_ENERGETICO'] ?? '' }}</div>
  <div class="row"><span class="label">Dispositores:</span> {{ $perfil['DISPOSITORES'] ?? '' }}</div>
  <div class="row"><span class="label">Progresiones y Retornos:</span> {{ $perfil['PROGRESIONES_RETORNOS'] ?? '' }}</div>
  <div class="row"><span class="label">Fase Lunación Natal:</span>
    {{ $perfil['FASE_LUNACION_NATAL'] ?? '' }}
    @if(!empty($perfil['PLANETA_ASOCIADO_LUNACION']))
      <span class="muted"> (Planeta: {{ $perfil['PLANETA_ASOCIADO_LUNACION'] }})</span>
    @endif
  </div>

  <div class="box">
    <div class="mb8"><span class="key">¿Primera vez Astrología?</span> {{ $perfil['PRIMERA_VEZ_ASTROLOGIA'] ?? '' }}</div>
    <div class="mb8"><span class="key">Profesión/Ocupación:</span> {{ $perfil['PROFESION'] ?? '' }}</div>
    <div class="mb8"><span class="key">Vivo con:</span> {{ $perfil['VIVO_CON'] ?? '' }}</div>
    <div class="mb8"><span class="key">Hogar de la Infancia:</span> {{ $perfil['HOGAR_INFANCIA'] ?? '' }}</div>
    <div class="mb8"><span class="key">Enfermedades de la Infancia:</span> {{ $perfil['ENF_INFANCIA'] ?? '' }}</div>
    <div class="mb8"><span class="key">Síntomas actuales:</span> {{ $perfil['SINTOMAS_ACTUALES'] ?? '' }}</div>
    <div class="mb8"><span class="key">Motivo de la Consulta:</span> {{ $perfil['MOTIVO_CONSULTA'] ?? '' }}</div>
  </div>

  <h2>Observaciones</h2>
  <div class="box">{!! nl2br(e($perfil['OBSERVACIONES'] ?? '')) !!}</div>

  <h2>Detalle del encuentro inicial</h2>
  <div class="box">{!! nl2br(e($perfil['DETALLE_ENCUENTRO_INICIAL'] ?? '')) !!}</div>

  <h2>Resumen para Psicóloga</h2>
  <div class="box">{!! nl2br(e($perfil['RESUMEN_PARA_PSICOLOGA_TEXTO'] ?? '')) !!}</div>

  {{-- ================== PÁGINAS SIGUIENTES: 1 por encuentro ================== --}}
  @php
    // $encuentros: array de arrays con FECHA, CIUDAD_ULT_CUMPLE, TEMAS_TRATADOS, RESUMEN, EDAD_EN_ESE_ENCUENTRO
    $total = is_countable($encuentros ?? []) ? count($encuentros) : 0;
  @endphp

  @if($total > 0)
    @foreach($encuentros as $i => $e)
      <div class="page-break"></div>
      <h1>Encuentro {{ $i + 1 }}</h1>
      <div class="row"><span class="label">Fecha:</span> {{ $e['FECHA'] ?? '' }}</div>
      <div class="row"><span class="label">Edad en ese encuentro:</span> {{ $e['EDAD_EN_ESE_ENCUENTRO'] ?? '' }}</div>
      <div class="row"><span class="label">Ciudad último cumpleaños:</span> {{ $e['CIUDAD_ULT_CUMPLE'] ?? '' }}</div>
      <div class="row"><span class="label">Temas tratados:</span> {{ $e['TEMAS_TRATADOS'] ?? '' }}</div>

      <h2>Resumen</h2>
      <div class="box">{!! nl2br(e($e['RESUMEN'] ?? '')) !!}</div>
    @endforeach
  @else
    {{-- Sin encuentros cargados --}}
  @endif

</body>
</html>
