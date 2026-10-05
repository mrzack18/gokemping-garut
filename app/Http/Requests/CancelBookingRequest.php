<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi pembatalan booking (ROADMAP 4.4).
 *
 * Alasan pembatalan wajib diisi. Booking yang dibatalkan mengembalikan barang
 * yang sebelumnya menahan stok, dan pembatalan sering muncul karena kondisi
 * transaksi yang tidak bisa menunggu: barang sudah dibawa penyewa, penyewa
 * tidak muncul, atau tanggal sewa harus digeser. Tanpa alasan, halaman riwayat
 * hanya menampilkan "dibatalkan" tanpa informasi apa pun yang bisa
 * ditindaklanjuti.
 */
class CancelBookingRequest extends FormRequest
{
    /**
     * Panjang maksimum alasan pembatalan, mengikuti lebar kolom
     * `bookings.cancellation_reason`.
     */
    public const MAX_REASON_LENGTH = 200;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'required',
                'string',
                'max:'.self::MAX_REASON_LENGTH,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancellation_reason.max' => 'Alasan pembatalan maksimal '.self::MAX_REASON_LENGTH.' karakter.',
        ];
    }

    /**
     * Booking yang selesai atau sudah dibatalkan tidak bisa dibatalkan lagi.
     *
     * Booking yang selesai ditolak di sini, bukan diam-diam diabaikan, supaya
     * admin yang menekan tombol pembatalan tahu aksi yang gagal. Booking yang
     * sudah dibatalkan juga ditolak: membatalkan dua kali akan menulis baris
     * riwayat kedua untuk perubahan yang sama.
     */
    public function withValidator(Validator $validator): void
    {
        $booking = $this->booking();

        $validator->after(function (Validator $validator) use ($booking): void {
            if ($booking === null) {
                return;
            }

            if (! $booking->booking_status->isCancellable()) {
                $validator->errors()->add(
                    'cancellation_reason',
                    'Booking yang sudah '.$booking->booking_status->label()
                        .' tidak bisa dibatalkan.',
                );
            }
        });
    }

    /**
     * Booking dari route, kalau route model binding sudah memuatnya.
     *
     * Nilai route selalu berupa model `Booking` kalau binding berhasil, dan
     * proses berhenti sebagai 404 kalau tidak. Nilai itu dibaca di sini supaya
     * aturan validasi tidak bergantung pada tebakan bentuk nilai route.
     */
    private function booking(): ?Booking
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking ? $booking : null;
    }

    /**
     * Alasan pembatalan siap disimpan, dengan spasi luar dibuang.
     */
    public function reason(): string
    {
        $reason = $this->input('cancellation_reason');

        return is_string($reason) ? trim($reason) : '';
    }
}
