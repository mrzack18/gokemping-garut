<?php

use App\Http\Controllers\BookingAvailabilityController;
use App\Http\Controllers\BookingBiodataController;
use App\Http\Controllers\BookingCustomerLookupController;
use App\Http\Controllers\BookingDraftController;
use App\Http\Controllers\BookingFormController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ServiceSelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/pilih-layanan', ServiceSelectionController::class)->name('services.index');

// Pencarian data penyewa lama tidak butuh unit bisnis karena `customers` tidak
// punya business_id: satu orang bisa menyewa di kedua unit.
Route::get('/booking/customer-lookup', BookingCustomerLookupController::class)
    ->middleware('throttle:'.BookingCustomerLookupController::THROTTLE)
    ->name('booking.customerLookup');

// Path katalog mengikuti PRD section 34: /gokemping dan /sewa-sepeda-garut.
// Slug unit dikirim sebagai route default supaya controller tidak perlu
// membaca path URL secara manual.
//
// Route dengan path literal harus didaftarkan sebelum route berparameter
// produk, karena `/gokemping/booking/biodata` juga cocok dengan
// `/gokemping/booking/{product}`.
Route::get('/gokemping', CatalogController::class)
    ->defaults('business', 'gokemping')
    ->name('catalog.gokemping');

Route::get('/gokemping/booking/biodata', [BookingBiodataController::class, 'show'])
    ->defaults('business', 'gokemping')
    ->name('booking.gokemping.biodata');

Route::post('/gokemping/booking/biodata', [BookingBiodataController::class, 'store'])
    ->defaults('business', 'gokemping')
    ->name('booking.gokemping.biodata.store');

Route::post('/gokemping/booking/{product}/draft', BookingDraftController::class)
    ->defaults('business', 'gokemping')
    ->name('booking.gokemping.draft.store');

Route::get('/gokemping/{product}', ProductDetailController::class)
    ->defaults('business', 'gokemping')
    ->name('catalog.gokemping.show');

Route::get('/gokemping/booking/{product}', BookingFormController::class)
    ->defaults('business', 'gokemping')
    ->name('booking.gokemping.create');

Route::get('/gokemping/booking/{product}/availability', BookingAvailabilityController::class)
    ->defaults('business', 'gokemping')
    ->name('booking.gokemping.availability');

Route::get('/sewa-sepeda-garut', CatalogController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('catalog.sewaSepedaGarut');

Route::get('/sewa-sepeda-garut/booking/biodata', [BookingBiodataController::class, 'show'])
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('booking.sewaSepedaGarut.biodata');

Route::post('/sewa-sepeda-garut/booking/biodata', [BookingBiodataController::class, 'store'])
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('booking.sewaSepedaGarut.biodata.store');

Route::post('/sewa-sepeda-garut/booking/{product}/draft', BookingDraftController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('booking.sewaSepedaGarut.draft.store');

Route::get('/sewa-sepeda-garut/{product}', ProductDetailController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('catalog.sewaSepedaGarut.show');

Route::get('/sewa-sepeda-garut/booking/{product}', BookingFormController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('booking.sewaSepedaGarut.create');

Route::get('/sewa-sepeda-garut/booking/{product}/availability', BookingAvailabilityController::class)
    ->defaults('business', 'sewa-sepeda-garut')
    ->name('booking.sewaSepedaGarut.availability');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
