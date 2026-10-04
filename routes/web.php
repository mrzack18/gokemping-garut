<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\ServiceSelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/pilih-layanan', ServiceSelectionController::class)->name('services.index');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
