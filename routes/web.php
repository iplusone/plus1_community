<?php

use App\Http\Controllers\Admin\SpotController as AdminSpotController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SpotController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/spots', [SpotController::class, 'index'])->name('spots.index');
Route::get('/spots/{slug}', [SpotController::class, 'show'])->name('spots.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('spots', AdminSpotController::class)->except('show');
});
