<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PacienteController;
use App\Livewire\Pacientes\Buscar;
use App\Livewire\Pacientes\Editar;

// Home mínima
Route::get('/', function () {
    $tokenPath = storage_path('app/google/token.json');
    return view('welcome', ['google_connected' => file_exists($tokenPath)]);
})->name('home');

// OAuth
Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::post('/google/logout', [GoogleAuthController::class, 'logout'])->name('google.logout');

// Rutas que usan Drive/Sheets (requieren estar logueado con Google)
Route::middleware([\App\Http\Middleware\EnsureGoogleConnected::class])->group(function () {
    // LISTAR/BUSCAR (GET)  -> NO crea nada, solo muestra
    Route::get('/pacientes', Buscar::class)->name('buscar');

    // CREAR (POST) -> crea la hoja y actualiza índice
    Route::post('/pacientes', [PacienteController::class, 'crear'])->name('paciente.crear');

    // EDITAR (GET) -> abre el editor Livewire por spreadsheetId
    Route::get('/pacientes/{id}', Editar::class)->name('paciente.editar');
});