<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketLookupRequest;
use App\Models\Booking;
use App\Services\TicketLookupService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cek tiket dari panel admin (ROADMAP 5.5).
 *
 * Dipakai staf untuk memverifikasi tiket saat barang diambil. Pencariannya
 * dibatasi ke unit bisnis admin yang login, berbeda dari section cek tiket di
 * landing page yang lintas unit: di panel admin, data unit lain memang bukan
 * urusan staf ini (BR-05).
 *
 * Verifikasinya tetap kode booking + nomor WhatsApp penyewa, jadi kode booking
 * yang berurutan tidak bisa dipakai menebak tiket orang lain meski dari dalam
 * panel.
 */
class TicketController extends Controller
{
    /**
     * Berapa banyak pencarian per menit per admin.
     *
     * Cukup longgar untuk memeriksa banyak tiket berurutan saat jam
     * pengambilan, tetapi tetap membatasi percobaan beruntun.
     */
    public const THROTTLE = '60,1';

    /**
     * Halaman kosong berisi formulir cek tiket.
     */
    public function index(): Response
    {
        return Inertia::render('admin/tickets/index', [
            'ticket' => null,
        ]);
    }

    /**
     * Cari tiket di unit admin yang login.
     */
    public function lookup(
        TicketLookupRequest $request,
        TicketLookupService $tickets,
    ): Response|RedirectResponse {
        $business = $request->user()->business;
        $whatsapp = $request->normalizedWhatsapp();

        if ($whatsapp === null) {
            return back()->withInput()->withErrors([
                'whatsapp' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        $booking = $tickets->find($request->code(), $whatsapp, $business);

        if (! $booking instanceof Booking) {
            return back()->withInput()->withErrors([
                'booking_code' => 'Tiket tidak ditemukan di unit ini. Periksa kembali kode booking dan nomor WhatsApp penyewanya.',
            ]);
        }

        return Inertia::render('admin/tickets/index', [
            'ticket' => $tickets->payload($booking),
        ]);
    }
}
