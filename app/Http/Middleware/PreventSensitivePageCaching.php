<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Larang cache dan indeks untuk halaman yang memuat data pribadi.
 *
 * Halaman sukses booking dan hasil pindai tiket memuat data penyewa tanpa
 * login, jadi isinya tidak boleh tersimpan di cache proxy, cache peramban,
 * atau ikut terindeks mesin pencari. `Referrer-Policy: no-referrer` juga
 * dipasang supaya URL pindai (yang membawa token tiket) tidak ikut terkirim
 * sebagai referer saat penyewa menekan tautan keluar, mis. tombol WhatsApp.
 *
 * Ini lapisan kedua: data sensitif tetap tidak bisa dibuka tanpa session
 * receipt atau token yang sah, tetapi halaman yang sudah terbuka pun tidak
 * meninggalkan salinan yang bisa dibaca orang berikutnya.
 */
class PreventSensitivePageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
