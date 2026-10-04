<?php

namespace App\Http\Requests;

use App\Support\WhatsappNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi biodata penyewa (ROADMAP 3.7, PRD section 14).
 *
 * Nama, WhatsApp, NIK, dan alamat adalah data utama penyewa. Email, kota, dan
 * catatan opsional. Jumlah penyewa hanya dipakai untuk unit sewa sepeda dan
 * divalidasi sebagai bilangan bulat positif.
 */
class StoreBookingCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'nik' => ['required', 'string', 'digits:16'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'renter_count' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.whatsapp' => 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx.',
            'email.email' => 'Format email tidak valid.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'address.required' => 'Alamat wajib diisi.',
            'renter_count.integer' => 'Jumlah penyewa harus berupa angka bulat.',
            'renter_count.min' => 'Jumlah penyewa minimal 1 orang.',
            'renter_count.max' => 'Jumlah penyewa maksimal 100 orang.',
        ];
    }

    /**
     * Nomor WhatsApp dinormalkan ke format `62...` sebelum divalidasi supaya
     * `customers.whatsapp` yang unik tidak terpecah oleh format berbeda.
     */
    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('whatsapp');

        if (is_string($whatsapp)) {
            $normalized = WhatsappNumber::normalize($whatsapp);

            $this->merge(['whatsapp' => $normalized ?? $whatsapp]);
        }

        $nik = $this->input('nik');

        if (is_string($nik)) {
            $this->merge(['nik' => trim($nik)]);
        }

        $renterCount = $this->input('renter_count');

        $this->merge([
            'renter_count' => $renterCount === '' || $renterCount === null
                ? null
                : $renterCount,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $whatsapp = $this->input('whatsapp');

            if (! is_string($whatsapp) || WhatsappNumber::normalize($whatsapp) === null) {
                $validator->errors()->add(
                    'whatsapp',
                    'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx.',
                );
            }
        });
    }

    /**
     * Data penyewa siap disimpan ke draft. Field yang tidak ada pada unit
     * sewa sepeda selalu dikembalikan `null` supaya tidak ikut ke draft.
     *
     * @return array<string, mixed>
     */
    public function customerPayload(bool $isBikeRental): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'whatsapp' => WhatsappNumber::normalize((string) $this->input('whatsapp')),
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'nik' => trim((string) $this->input('nik')),
            'address' => trim((string) $this->input('address')),
            'city' => $this->filled('city') ? trim((string) $this->input('city')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'renter_count' => $isBikeRental && $this->filled('renter_count')
                ? (int) $this->input('renter_count')
                : null,
        ];
    }
}
