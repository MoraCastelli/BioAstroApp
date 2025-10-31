<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>BioAstroApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            padding: 2rem;
            background: #f9fafb;
            color: #111827;
        }
        h1 { margin-bottom: 1rem; }
        a.button {
            display: inline-block;
            background: #1a73e8;
            color: #fff;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
        }
        a.button:hover { background: #1558b0; }

        button {
            background: #eee;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        button:hover { background: #ddd; }

        .alert {
            background: #e6ffed;
            color: #065f46;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        hr { margin: 2rem 0; border: none; border-top: 1px solid #ddd; }
    </style>
</head>
<body>

    {{-- Mensaje de confirmación --}}
    @if (session('ok'))
        <div class="alert">
            {{ session('ok') }}
        </div>
    @endif

    @if(!$google_connected)
        <h1>Conectar con Google</h1>
        <p>Para usar la aplicación, primero conectá tu cuenta de <strong>Google Drive</strong> y <strong>Google Sheets</strong>.</p>

        <p>
            <a href="{{ route('google.auth') }}" class="button">
                Iniciar sesión con Google
            </a>
        </p>

    @else
        <h1>Conectado a Google ✔</h1>

        <form method="POST" action="{{ route('google.logout') }}">
            @csrf
            <button type="submit">Desconectar</button>
        </form>

        <hr>

        <h2>Secciones disponibles</h2>
        <p>
            <a href="{{ route('buscar') }}" class="button">Ir a Pacientes</a>
        </p>
    @endif

</body>
</html>
