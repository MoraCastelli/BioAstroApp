<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Pacientes\Buscar;
use App\Livewire\Pacientes\Editar;
use App\Http\Controllers\PacienteController;

Route::post('/pacientes', [PacienteController::class, 'crear'])->name('paciente.crear');
Route::get('/', Buscar::class)->name('buscar');
Route::get('/pacientes/{id}', Editar::class)->name('paciente.editar');
