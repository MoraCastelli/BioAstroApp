<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PacienteController;
use App\Livewire\Pacientes\Buscar;
use App\Livewire\Pacientes\Editar;
use App\Livewire\Pacientes\Ver;
use App\Livewire\Pacientes\NuevoEncuentro;
use App\Livewire\Pacientes\Eliminar;

// Home mínima
Route::get('/', function () {
    $tokenPath = storage_path('app/google/token.json');
    return view('welcome', ['google_connected' => file_exists($tokenPath)]);
})->name('home');

// OAuth
Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::post('/google/logout', [GoogleAuthController::class, 'logout'])->name('google.logout');
Route::get('/google/logout', [GoogleAuthController::class, 'logout'])->name('google.logout.get');

// Rutas que usan Drive/Sheets (requieren estar logueado con Google)
Route::middleware([\App\Http\Middleware\EnsureGoogleConnected::class])->group(function () {
    Route::get('/debug-google', function () {
        $drive = \App\Services\DriveService::make();

        $templateId = config('services.google.template_paciente_spreadsheet_id');
        $dbFolderId = config('services.google.db_folder_id');

        return dd([
            'whoAmI' => $drive->whoAmI(),
            'templateId' => $templateId,
            'templateName' => $drive->getFileName($templateId),
            'db_folder_id' => $dbFolderId,
            'db_folder_name' => $drive->getFileName($dbFolderId), // 👈 si esto falla, era la carpeta
        ]);
    });

    Route::get('/pacientes/{id}/ver', Ver::class)->name('paciente.ver');
    Route::get('/pacientes/{id}/nuevo-encuentro', NuevoEncuentro::class)->name('paciente.nuevo-encuentro');
    Route::get('/pacientes/{id}/eliminar', Eliminar::class)->name('paciente.eliminar');
    
    // LISTAR/BUSCAR (GET)  -> NO crea nada, solo muestra
    Route::get('/pacientes', Buscar::class)->name('buscar');

    // CREAR (POST) -> crea la hoja y actualiza índice
    Route::post('/pacientes', [PacienteController::class, 'crear'])->name('paciente.crear');

    // EDITAR (GET) -> abre el editor Livewire por spreadsheetId
    Route::get('/pacientes/{id}', Editar::class)->name('paciente.editar');
});