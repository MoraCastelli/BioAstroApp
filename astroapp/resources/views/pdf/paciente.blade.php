<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
    h1 { font-size: 20px; margin-bottom: 6px; }
    .muted { color: #666; }
    .row { margin-bottom: 6px; }
    hr { margin: 10px 0; }
    .label { width: 210px; display: inline-block; font-weight: bold; }
  </style>
</head>
<body>
  <h1>{{ $perfil['NOMBRE_Y_APELLIDO'] ?? '' }}</h1>
  <div class="muted">Actualizado: {{ $perfil['ULTIMA_ACTUALIZACION'] ?? '' }}</div>
  <hr>

  <div class="row"><span class="label">Contacto:</span> {{ $perfil['CONTACTO'] ?? '' }}</div>
  <div class="row"><span class="label">Fecha/Hora Nac.:</span> {{ ($perfil['FECHA_NAC'] ?? '') }} {{ ($perfil['HORA_NAC'] ?? '') }}</div>
  <div class="row"><span class="label">Lugar Nac.:</span> {{ ($perfil['CIUDAD_NAC'] ?? '') }}, {{ ($perfil['PROVINCIA_NAC'] ?? '') }}, {{ ($perfil['PAIS_NAC'] ?? '') }}</div>

  <div class="row"><span class="label">Signo Solar:</span> {{ $perfil['SIGNO_SOLAR'] ?? '' }}</div>
  <div class="row"><span class="label">Edad (encuentro inicial):</span> {{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] ?? '' }}</div>

  <hr>
  <div class="row"><span class="label">Observaciones:</span> {!! nl2br(e($perfil['OBSERVACIONES'] ?? '')) !!}</div>

  <hr>
  <div class="row"><span class="label">Detalle Encuentro Inicial:</span><br>{!! nl2br(e($perfil['DETALLE_ENCUENTRO_INICIAL'] ?? '')) !!}</div>

  <hr>
  <div class="row"><span class="label">Resumen para Psicóloga:</span><br>{!! nl2br(e($perfil['RESUMEN_PARA_PSICOLOGA_TEXTO'] ?? '')) !!}</div>
</body>
</html>

