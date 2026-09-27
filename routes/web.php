<?php

use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/planificador', [RouteController::class, 'index'])->name('routes.index');
Route::post('/planificador', [RouteController::class, 'prepare'])->name('routes.prepare');
