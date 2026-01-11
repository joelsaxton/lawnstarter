<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\StarWarsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index'])->name('health');

Route::prefix('starwars')->group(function () {
    Route::get('/person', [StarWarsController::class, 'getPersonByName'])->name('starwars.person.name');
    Route::get('/person/{id}', [StarWarsController::class, 'getPersonById'])->name('starwars.person.id');
    Route::get('/film', [StarWarsController::class, 'getFilmByTitle'])->name('starwars.film.title');
    Route::get('/film/{id}', [StarWarsController::class, 'getFilmById'])->name('starwars.film.id');
    Route::get('/stats', [StarWarsController::class, 'getApiStats'])->name('starwars.api.stats');
});
