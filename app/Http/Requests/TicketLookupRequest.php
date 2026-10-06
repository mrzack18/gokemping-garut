<?php

namespace App\Http\Requests;

use App\Support\WhatsappNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form cek tiket (halaman publik).
 *
 * Kode booking dinormalkan ke huruf besar karena kode yang dikirim lewat
 * WhatsApp dan pesan konfirmasi selalu huruf besar, sedangkan penyewa bisa
 * saja mengetiknya huruf kecil. Nomor WhatsApp dinormalkan lewat
 * `WhatsappNumber` supaya `0812...`, `+62 812...`, dan `62812...` sama-sama
 * cocok dengan yang tersimpan.
 */
class TicketLookupRequest extends FormRequest
{
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
            'booking_code' => ['required', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:25'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'booking_code.required' => 'Kode booking wajib diisi.',
            'booking_code.max' => 'Kode booking maksimal 20 karakter.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.max' => 'Nomor WhatsApp maksimal 25 karakter.',
        ];
    }

    /**
     * Kode booking siap dicari.
     */
    public function code(): string
    {
        return strtoupper(trim((string) $this->input('booking_code')));
    }

    /**
     * Nomor WhatsApp dalam format penyimpanan, atau null kalau tidak valid.
     */
    public function normalizedWhatsapp(): ?string
    {
        $value = $this->input('whatsapp');

        return is_string($value) ? WhatsappNumber::normalize($value) : null;
    }
}
