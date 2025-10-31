
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ config('app.name', 'BioAstroApp') }}</title>

  {{-- CSS/JS compilado por Vite --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Livewire CSS --}}
  @livewireStyles

  {{-- (Opcional) CSRF meta si hacés POST con fetch/AJAX manual --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
  @php
    $tokenPath = storage_path('app/google/token.json');
    $hasGoogle = file_exists($tokenPath);
    $g = session('google_user'); // ['name','email','picture']
  @endphp

  {{-- Barra superior --}}
  <header class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
      <a href="{{ route('buscar') }}" class="text-lg font-semibold">
        {{ config('app.name', 'BioAstroApp') }}
      </a>

      <div class="flex items-center gap-4">
        @if($g)
          <div class="flex items-center gap-2">
            @if(!empty($g['picture']))
              <img src="{{ $g['picture'] }}" alt="Foto" class="w-8 h-8 rounded-full">
            @endif
            <div class="text-sm text-gray-700">
              <div class="font-medium leading-tight">{{ $g['name'] ?? $g['email'] }}</div>
              @if(!empty($g['name']) && !empty($g['email']))
                <div class="text-gray-500">{{ $g['email'] }}</div>
              @endif
            </div>
          </div>
        @endif

        @unless($hasGoogle)
          <a href="{{ route('google.auth') }}"
             class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-white text-sm font-medium hover:bg-blue-700">
            Iniciar sesión con Google
          </a>
        @endunless
      </div>
    </div>

    {{-- Aviso si falta conectar Google --}}
    @unless($hasGoogle)
      <div class="bg-yellow-50 border-t border-yellow-200">
        <div class="max-w-6xl mx-auto px-4 py-2 text-sm text-yellow-900">
          Para usar la app necesitás conectar tu cuenta de Google (Drive + Sheets).
          Hacé clic en <span class="font-semibold">“Iniciar sesión con Google”</span>.
        </div>
      </div>
    @endunless
  </header>

  {{-- Mensajes flash y errores --}}
  <div class="max-w-6xl mx-auto px-4 mt-4 space-y-3">
    @if(session('ok'))
      <div class="rounded-md bg-green-50 border border-green-200 p-3 text-green-800">
        {{ session('ok') }}
      </div>
    @endif

    @if($errors->any())
      <div class="rounded-md bg-red-50 border border-red-200 p-3 text-red-800">
        <ul class="list-disc list-inside space-y-1">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>

  <main class="max-w-6xl mx-auto px-4 py-6">
    {{ $slot }}
  </main>

  {{-- Livewire JS --}}
  @livewireScripts

  {{-- Lugar para scripts extra --}}
  @stack('scripts')
</body>
</html>
