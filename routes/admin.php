<?php

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StatisticsController;
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
| Modul laporan dan pengaturan akan ditambahkan pada fase berikutnya.
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

        /*
         * Penyewa memakai id numerik karena `Customer` sengaja tidak punya
         * `business_id`: satu orang bisa menyewa di dua unit. Pembatasan
         * tenant dilakukan di controller lewat booking-nya, dan penyewa yang
         * tidak punya booking di unit ini berakhir sebagai 404.
         */
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        /*
         * Pembayaran memakai id numerik karena tidak punya kode alami seperti
         * booking. Route binding membaca `payments` lewat `BusinessScope`, jadi
         * pembayaran unit lain berhenti sebagai 404.
         */
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::patch('payments/{payment}/verify', [PaymentController::class, 'verify'])
            ->name('payments.verify');
        Route::patch('payments/{payment}/reject', [PaymentController::class, 'reject'])
            ->name('payments.reject');

        /*
         * Pengaturan pembayaran memakai jenis metode sebagai segmen URL, bukan
         * id baris, karena yang diedit selalu satu konfigurasi per jenis.
         * Batasannya diambil dari enum supaya jenis yang tidak dikenal berhenti
         * sebagai 404, bukan membuat baris baru.
         */
        Route::get('payment-settings', [PaymentSettingController::class, 'index'])
            ->name('payment-settings.index');
        Route::patch('payment-settings/{type}', [PaymentSettingController::class, 'update'])
            ->whereIn('type', array_column(PaymentMethodType::cases(), 'value'))
            ->name('payment-settings.update');

        /*
         * Laporan periode. Rentang tanggalnya dibaca dari query string, jadi
         * tautan berisi periode tertentu bisa dibagikan apa adanya.
         */
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        /*
         * Statistik memakai satu tahun penuh sebagai periodenya, dibaca dari
         * query string supaya tahun yang sedang dilihat bisa dibagikan.
         */
        Route::get('statistics', [StatisticsController::class, 'index'])
            ->name('statistics.index');

        /*
         * Ekspor memakai filter yang sama dengan halamannya, dibaca dari query
         * string, jadi tautan unduhan yang disalin menghasilkan berkas yang
         * sama dengan tampilan yang sedang dilihat admin.
         */
        Route::get('exports/bookings', [ExportController::class, 'bookings'])
            ->name('exports.bookings');
        Route::get('exports/report', [ExportController::class, 'report'])
            ->name('exports.report');
        Route::get('exports/report-pdf', [ExportController::class, 'reportPdf'])
            ->name('exports.report-pdf');

        /*
         * Konten unit: informasi layanan disimpan di baris `businesses`,
         * sedangkan banner dan FAQ punya tabelnya sendiri dengan
         * `business_id` masing-masing. Route model binding banner/FAQ memakai
         * `BusinessScope`, jadi konten unit lain berakhir sebagai 404.
         */
        Route::get('content', [ContentController::class, 'index'])
            ->name('content.index');
        Route::patch('content/profile', [ContentController::class, 'updateProfile'])
            ->name('content.profile');

        Route::resource('content/banners', BannerController::class)
            ->parameters(['banners' => 'banner'])
            ->only(['store', 'update', 'destroy'])
            ->names('content.banners');

        Route::resource('content/faqs', FaqController::class)
            ->parameters(['faqs' => 'faq'])
            ->only(['store', 'update', 'destroy'])
            ->names('content.faqs');
    });
