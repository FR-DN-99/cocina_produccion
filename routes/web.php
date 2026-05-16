<?php

use App\Http\Controllers\CocinaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas SCP - Sistema de Cocina de Producción
|--------------------------------------------------------------------------
*/

Route::get('/', [CocinaController::class, 'escenarios'])->name('escenarios');

Route::get('/escenarios/{escenarioId}/recetas', [CocinaController::class, 'recetas'])
    ->name('recetas');

Route::match(['get', 'post'], '/escenarios/{escenarioId}/calcular', [CocinaController::class, 'calcular'])
    ->name('calcular');

// Edición temporal de escenarios (en sesión, no persiste)
Route::post('/escenarios/{escenarioId}/modificar', [CocinaController::class, 'modificarEscenario'])
    ->name('escenarios.modificar');

Route::post('/escenarios/{escenarioId}/restaurar', [CocinaController::class, 'restaurarEscenario'])
    ->name('escenarios.restaurar');

// API para catálogo de recetas (modal de consulta)
Route::get('/api/catalogo', [CocinaController::class, 'catalogoRecetas'])
    ->name('api.catalogo');
