<?php

use App\Http\Controllers\Api\StationPickerController;
use Illuminate\Support\Facades\Route;

Route::prefix('station-picker')->name('station-picker.')->group(function () {
    Route::get('prefectures', [StationPickerController::class, 'prefectures'])->name('prefectures');
    Route::get('railways', [StationPickerController::class, 'railways'])->name('railways');
    Route::get('stations', [StationPickerController::class, 'stations'])->name('stations');
});
