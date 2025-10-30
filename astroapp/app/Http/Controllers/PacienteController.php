<?php

namespace App\Http\Controllers;

use App\Repositories\PacienteRepository;
use App\Services\SheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Google\Service\Exception as GoogleServiceException;

class PacienteController extends Controller
{
    public function crear(Request $r)
    {
        $nombre = trim((string) $r->input('nombre_apellido'));
        abort_if($nombre === '', 400, 'Nombre requerido');

        try {
            // Crear hoja del paciente (usa GoogleClientFactory por dentro).
            $repo = PacienteRepository::make();
            $id   = $repo->crearDesdeTemplate($nombre);

            // Agregar al índice (si está configurado)
            $indiceId = env('GOOGLE_SHEETS_INDEX_ID');
            if ($indiceId) {
                SheetsService::make()->updateIndice($indiceId, [
                    $nombre,
                    $id,
                    Carbon::now()->toIso8601String(),
                ]);
            } else {
                Log::warning('GOOGLE_SHEETS_INDEX_ID vacío: no se actualizó el índice.');
            }

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

            // Mensajes más claros para casos frecuentes
            if (str_contains($msg, 'storageQuotaExceeded')) {
                $msg = 'No hay espacio disponible en Drive para crear el archivo (quota excedida).';
            } elseif (str_contains($msg, 'The caller does not have permission')) {
                $msg = 'Permisos insuficientes en la carpeta/archivo de Drive o en Google Sheets.';
            }

            Log::error('GoogleServiceException en crear paciente: '.$e->getMessage(), [
                'code' => $e->getCode(),
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
