<?php

namespace App\Http\Controllers;

use App\Repositories\PacienteRepository;
use App\Services\SheetsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PacienteController extends Controller
{
    public function crear(Request $r) {
        $nombre = trim($r->string('nombre_apellido'));
        abort_if($nombre === '', 400, 'Nombre requerido');

        $repo = PacienteRepository::make();
        $id = $repo->crearDesdeTemplate($nombre);

        // Agregar al índice
        $indiceId = env('GOOGLE_SHEETS_INDEX_ID');
        SheetsService::make()->updateIndice($indiceId, [
            $nombre, $id, Carbon::now()->toIso8601String()
        ]);

        return redirect()->route('paciente.editar', $id);
    }
}
