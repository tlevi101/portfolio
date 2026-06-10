<?php

use App\Http\Controllers\BeaconController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::post('/beacon', [BeaconController::class, 'store'])->name('beacon');
Route::get('/cv/{slug}', [CvController::class, 'download'])->name('cv.download');
Route::get('/{slug}', [LandingController::class, 'show'])
    ->name('portfolio.show')
    ->where('slug', '[A-Za-z0-9-]+');
