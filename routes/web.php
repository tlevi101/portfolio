<?php

use App\Http\Controllers\CvController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/cv/download', [CvController::class, 'download'])->name('cv.download');
