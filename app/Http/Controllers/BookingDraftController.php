<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingDraftRequest;
use App\Support\BookingDraft;
use App\Support\BookingRoutes;
use Illuminate\Http\RedirectResponse;

/**
 * Menyimpan draft booking dari form jadwal lalu meneruskan ke halaman biodata
 * (ROADMAP 3.7).
 *
 * Draft disimpan di session, bukan ke tabel `bookings`. Penyimpanan booking
 * yang sesungguhnya ada di ROADMAP 3.11.
 */
class BookingDraftController extends Controller
{
    public function __invoke(
        StoreBookingDraftRequest $request,
        BookingDraft $draft,
    ): RedirectResponse {
        $business = $request->business();
        $product = $request->product($business);

        /**
         * `merge()` supaya data penyewa dari langkah sebelumnya tidak hilang
         * saat penyewa mengubah jadwal di form dan menekan "Lanjut" lagi.
         * Ketersediaan sudah diperiksa di dalam FormRequest, jadi request ini
         * hanya dijalankan setelah periode lolos.
         */
        $draft->merge($request->draftPayload($business, $product));

        return to_route('booking.'.BookingRoutes::prefix($business->slug).'.biodata');
    }
}
