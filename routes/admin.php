<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes - GoKemping & Sewa Sepeda Garut
|--------------------------------------------------------------------------
|
| Seluruh route di sini berada di balik middleware auth + business, sehingga
| hanya admin yang sudah login dan tertaut ke sebuah unit bisnis yang bisa
| mengaksesnya. Query di dalamnya otomatis ter-scope oleh BusinessScope
| berdasarkan business_id user (BR-05).
|
| Modul penyewa, pembayaran, laporan, dan pengaturan akan ditambahkan pada
| fase berikutnya.
|
*/

Route::middleware(['auth', 'business'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('categories', CategoryController::class)
            ->parameters(['categories' => 'category'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::patch('categories/{category}/status', [CategoryController::class, 'updateStatus'])
            ->name('categories.status');

        Route::resource('products', ProductController::class)
            ->parameters(['products' => 'product'])
            ->except(['show']);

        Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])
            ->name('products.status');

        /*
         * Foto produk memakai sub-resource dari produk, bukan resource terpisah.
         * `product_images` tidak punya `business_id`, jadi foto hanya boleh
         * dijangkau lewat produknya: setiap aksi memuat `$product->images()`
         * (yang sudah ter-scope `BusinessScope`) lalu memeriksa keanggotaannya.
         */
        Route::post('products/{product}/images', [ProductController::class, 'storeImages'])
            ->name('products.images.store');

        Route::patch('products/{product}/images/{image}', [ProductImageController::class, 'update'])
            ->name('products.images.update');

        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
            ->name('products.images.destroy');

        /*
         * Booking memakai kode booking sebagai route model binding, bukan id.
         * Kode itulah yang disebut penyewa saat menghubungi admin, jadi URL
         * detailnya bisa dibaca tanpa membuka daftar lebih dulu.
         */
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])
            ->name('bookings.status');
        Route::delete('bookings/{booking}', [BookingController::class, 'cancel'])
            ->name('bookings.cancel');
    });
