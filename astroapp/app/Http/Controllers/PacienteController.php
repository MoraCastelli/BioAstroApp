<?php

namespace App\Http\Controllers;

use App\Repositories\PacienteRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Service\Exception as GoogleServiceException;

class PacienteController extends Controller
{
    public function crear(Request $r)
    {
        dd([
            'who' => \App\Services\DriveService::make()->whoAmI(),
            'templateId' => config('services.google.template_paciente_spreadsheet_id'),
            ]);


        $nombre = trim((string) $r->input('nombre_apellido'));
        abort_if($nombre === '', 400, 'Nombre requerido');

        try {
            // El repo se encarga de:
            // - carpeta paciente + subcarpeta Imagenes
            // - copiar template dentro de la carpeta
            // - seed/ensure sheets
            // - setPerfil inicial
            // - updateIndice (incluyendo folder ids)
            $repo = PacienteRepository::make();
            $id   = $repo->crearDesdeTemplate($nombre);

            return redirect()->route('paciente.editar', $id)
                ->with('ok', 'Paciente creado ✔');
        }

        // Si no hay token OAuth almacenado, pedimos login a Google.
        catch (\RuntimeException $e) {
            if ($e->getMessage() === 'GOOGLE_OAUTH_NOT_AUTHENTICATED') {
                return redirect()->route('google.auth');
            }

            Log::error('RuntimeException en crear paciente: '.$e->getMessage());
            return back()->withErrors('Error interno (OAuth).');
        }

        // Errores de Google API (Drive/Sheets)
        catch (GoogleServiceException $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'storageQuotaExceeded')) {
                $msg = 'No hay espacio disponible en Drive para crear el archivo (quota excedida).';
            } elseif (str_contains($msg, 'The caller does not have permission')) {
                $msg = 'Permisos insuficientes en Drive/Sheets (archivo/carpeta).';
            } elseif (str_contains($msg, 'notFound')) {
                $msg = 'No se encontró el template o la carpeta destino en Drive (ID incorrecto o sin acceso).';
            }

            Log::error('GoogleServiceException en crear paciente: '.$e->getMessage(), [
                'code'   => $e->getCode(),
                'errors' => $e->getErrors(),
            ]);

            return back()->withErrors($msg);
        }

        // Cualquier otro error inesperado
        catch (\Throwable $e) {
            Log::error('Throwable en crear paciente: '.$e->getMessage());
            return back()->withErrors('Error inesperado al crear el paciente.');
        }
    }
}
