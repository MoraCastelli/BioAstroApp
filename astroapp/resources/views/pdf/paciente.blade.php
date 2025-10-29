<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1 { font-size: 18px; margin-bottom: 10px; }
    h2 { font-size: 14px; margin-top: 18px; margin-bottom: 6px; }
    .row { margin-bottom: 6px; }
    .label { font-weight: bold; width: 230px; display:inline-block; }
  </style>
</head>
<body>
  <h1>{{ $perfil['NOMBRE_Y_APELLIDO'] ?? '' }}</h1>

  <h2>Datos natales</h2>
  <div class="row"><span class="label">Fecha Nac:</span> {{ $perfil['FECHA_NAC'] ?? '' }}</div>
  <div class="row"><span class="label">Hora Nac:</span> {{ $perfil['HORA_NAC'] ?? '' }}</div>
  <div class="row"><span class="label">Lugar Nac:</span> {{ ($perfil['CIUDAD_NAC'] ?? '').', '.($perfil['PROVINCIA_NAC'] ?? '').', '.($perfil['PAIS_NAC'] ?? '') }}</div>
  <div class="row"><span class="label">Signo Solar:</span> {{ $perfil['SIGNO_SOLAR'] ?? '' }}</div>

  <h2>Encuentro inicial</h2>
  <div class="row"><span class="label">Fecha/Hora:</span> {{ ($perfil['FECHA_ENCUENTRO_INICIAL'] ?? '').' '.$perfil['HORA_ENCUENTRO_INICIAL'] ?? '' }}</div>
  <div class="row"><span class="label">Edad:</span> {{ $perfil['EDAD_EN_ENCUENTRO_INICIAL'] ?? '' }}</div>
  <div class="row"><span class="label">Primera vez Astrología:</span> {{ $perfil['PRIMERA_VEZ_ASTROLOGIA'] ?? '' }}</div>

  <h2>Claves</h2>
  <div class="row"><span class="label">Signo subyacente:</span> {{ $perfil['SIGNO_SUBYACENTE'] ?? '' }}</div>
  <div class="row"><span class="label">Balance energético:</span> {{ $perfil['BALANCE_ENERGETICO'] ?? '' }}</div>
  <div class="row"><span class="label">Dispositores:</span> {{ $perfil['DISPOSITORES'] ?? '' }}</div>
  <div class="row"><span class="label">Progresiones/Retornos:</span> {{ $perfil['PROGRESIONES_RETORNOS'] ?? '' }}</div>
  <div class="row"><span class="label">Lunación natal:</span> {{ ($perfil['FASE_LUNACION_NATAL'] ?? '').' ('.$perfil['PLANETA_ASOCIADO_LUNACION'] ?? '' .')' }}</div>

  <h2>Resumen para psicóloga</h2>
  <div class="row">{{ $perfil['RESUMEN_PARA_PSICOLOGA_TEXTO'] ?? '' }}</div>

  <h2>Última actualización</h2>
  <div class="row">{{ $perfil['ULTIMA_ACTUALIZACION'] ?? '' }}</div>
</body>
</html>
