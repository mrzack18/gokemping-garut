<?php

use App\Http\Controllers\Admin\DashboardController;
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
| Modul produk, kategori, booking, penyewa, pembayaran, laporan, dan
| pengaturan akan ditambahkan pada fase berikutnya.
|
*/

Route::middleware(['auth', 'business'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
