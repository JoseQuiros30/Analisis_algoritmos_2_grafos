<?php

use App\Http\Controllers\RouteController;
use App\Http\Controllers\ScenarioController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/planificador', [RouteController::class, 'index'])->name('routes.index');
Route::post('/planificador', [RouteController::class, 'prepare'])->name('routes.prepare');

Route::get('/escenarios', [ScenarioController::class, 'index'])->name('scenarios.index');
