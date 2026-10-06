<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\WhatsappNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint deteksi pelanggan lama untuk prefill biodata (PRD section 25).
 *
 * Penyewa tidak punya akun, jadi endpoint ini tidak boleh berada di belakang
 * login. Konsekuensinya, endpoint ini hanya mengembalikan data milik
 * nomor yang memang diberikan pemanggil, tidak pernah daftar pelanggan, dan
 * dibatasi throttle supaya tidak dipakai menebak data orang.
 *
 * NIK hanya dikembalikan kalau pemanggil memang sudah mengetiknya (lookup
 * lewat NIK). Lookup lewat WhatsApp mengosongkan field itu, karena kalau tidak,
 * siapa pun yang tahu nomor telepon seseorang bisa membaca NIK lengkapnya dari
 * halaman publik (PRD section 33: NIK tidak ditampilkan secara terbuka).
 */
class BookingCustomerLookupController extends Controller
{
    /**
     * Berapa banyak percobaan lookup per menit per kombinasi IP dan session.
     */
    public const THROTTLE = '30,1';

    /**
     * Field yang boleh dikembalikan ke form publik.
     *
     * @var list<string>
     */
    private const PUBLIC_FIELDS = [
        'name',
        'whatsapp',
        'email',
        'nik',
        'address',
        'city',
        'notes',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'nik' => ['nullable', 'string', 'max:16'],
        ]);

        $whatsapp = is_string($data['whatsapp'] ?? null)
            ? WhatsappNumber::normalize($data['whatsapp'])
            : null;

        $nik = $this->normalizeNik($data['nik'] ?? null);

        if ($whatsapp === null && $nik === null) {
            return $this->notFound();
        }

        /**
         * `customers.whatsapp` unik, jadi nomor WhatsApp adalah kunci yang
         * paling kuat. NIK hanya dipakai kalau nomor tidak diberikan.
         */
        $customer = $whatsapp !== null
            ? Customer::query()->where('whatsapp', $whatsapp)->first()
            : Customer::query()->where('nik', $nik)->first();

        if ($customer === null) {
            return $this->notFound();
        }

        $payload = $customer->only(self::PUBLIC_FIELDS);

        /**
         * Lookup lewat WhatsApp tidak boleh mengembalikan NIK: pemanggil hanya
         * membuktikan bahwa ia tahu nomor teleponnya, bukan bahwa ia pemilik
         * NIK tersebut. Lookup lewat NIK justru sudah mengetik NIK-nya sendiri,
         * jadi mengembalikannya tidak membocorkan apa pun.
         */
        if ($whatsapp !== null) {
            $payload['nik'] = null;
        }

        return response()->json([
            'found' => true,
            'customer' => $payload,
        ]);
    }

    private function normalizeNik(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $nik = trim($value);

        return preg_match('/^\d{16}$/', $nik) === 1 ? $nik : null;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'found' => false,
            'customer' => null,
        ]);
    }
}
