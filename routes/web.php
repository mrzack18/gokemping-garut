<?php

use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
