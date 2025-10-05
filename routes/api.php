<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\ProgresoController;

Route::get('/cursos', [CursoController::class, 'index']);
Route::get('/cursos/{id}', [CursoController::class, 'show']);

Route::get('/progresos', [ProgresoController::class, 'index']);
Route::post('/progresos', [ProgresoController::class, 'store']);

// simple lesson endpoint (maps to model Leccion if exists)
use App\Models\Leccion;
Route::get('/lecciones/{id}', function($id){
    return Leccion::findOrFail($id);
});
