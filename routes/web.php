<?php

use App\Http\Controllers\Admin\SpotController as AdminSpotController;
use App\Http\Controllers\Admin\SpotCouponController;
use App\Http\Controllers\Admin\SpotMediaController;
use App\Http\Controllers\Admin\SpotMenuController;
use App\Http\Controllers\Admin\SpotServiceController;
use App\Http\Controllers\Admin\SpotStaffController;
use App\Http\Controllers\Admin\SpotStationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchSuggestionController;
use App\Http\Controllers\SpotController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/spots', [SpotController::class, 'index'])->name('spots.index');
Route::get('/spots/{slug}', [SpotController::class, 'show'])->name('spots.show');
Route::get('/suggestions/genres', [SearchSuggestionController::class, 'genres'])->name('suggestions.genres');
Route::get('/suggestions/tags', [SearchSuggestionController::class, 'tags'])->name('suggestions.tags');
Route::get('/suggestions/area', [SearchSuggestionController::class, 'area'])->name('suggestions.area');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('spots', AdminSpotController::class)->except('show');

    Route::prefix('spots/{spot}')->name('spots.')->group(function () {
        Route::resource('staff', SpotStaffController::class)->except('show');
        Route::resource('coupons', SpotCouponController::class)->except('show');
        Route::resource('media', SpotMediaController::class)->except('show');
        Route::resource('services', SpotServiceController::class)->except('show');
        Route::resource('stations', SpotStationController::class)->only(['index', 'store', 'destroy']);
        Route::post('stations/recalculate', [SpotStationController::class, 'recalculate'])->name('stations.recalculate');
    });

    Route::prefix('spots/{spot}/services/{service}')->name('spots.services.')->group(function () {
        Route::resource('menus', SpotMenuController::class)->except('show');
    });
});
