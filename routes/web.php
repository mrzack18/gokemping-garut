<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ServiceSelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/pilih-layanan', ServiceSelectionController::class)->name('services.index');

// Path katalog mengikuti PRD section 34: /gokemping dan /sewa-sepeda-garut.
// Slug unit dikirim sebagai route default supaya controller tidak perlu
// membaca path URL secara manual.
Route::get('/gokemping', CatalogController::class)
    ->defaults('business', 'gokemping')
    ->name('catalog.gokemping');

Route::get('/gokemping/{product}', ProductDetailController::class)
    ->defaults('business', 'gokemping')
    ->name('catalog.gokemping.show');

Route::get('/sewa-sepeda-garut', CatalogController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('catalog.sewaSepedaGarut');

Route::get('/sewa-sepeda-garut/{product}', ProductDetailController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('catalog.sewaSepedaGarut.show');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
