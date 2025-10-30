<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Pacientes\Buscar;
use App\Livewire\Pacientes\Editar;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\GoogleAuthController;

Route::get('/', Buscar::class)->name('buscar');
Route::post('/pacientes', [PacienteController::class, 'crear'])->name('paciente.crear');
Route::get('/pacientes/{id}', Editar::class)->name('paciente.editar');
Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');