<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Validasi pengaturan metode pembayaran (PRD section 27, ROADMAP 4.7).
 *
 * Satu request menangani satu metode, sesuai `{type}` di URL. Aturan validasi
 * karena itu dibangun per jenis: QRIS punya nama merchant dan gambar, transfer
 * punya data rekening, cash hanya punya keterangan. Field yang tidak dipakai
 * metode lain tidak divalidasi supaya kesalahan di kartu lain tidak
 * menghalangi penyimpanan kartu ini.
 */
class UpdatePaymentSettingRequest extends FormRequest
{
    /**
     * Panjang maksimum keterangan pembayaran.
     */
    public const MAX_INSTRUCTIONS_LENGTH = 500;

    /**
     * Ukuran maksimum gambar QRIS dalam kilobyte, mengikuti batas unggahan
     * BR-08.
     */
    public const MAX_IMAGE_KB = 5120;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match ($this->type()) {
            PaymentMethodType::Cash => [
                'is_active' => ['required', 'boolean'],
                'instructions' => ['nullable', 'string', 'max:'.self::MAX_INSTRUCTIONS_LENGTH],
            ],
            PaymentMethodType::Qris => [
                'is_active' => ['required', 'boolean'],
                'merchant_name' => ['nullable', 'string', 'max:255'],
                'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],
            ],
            PaymentMethodType::BankTransfer => [
                'is_active' => ['required', 'boolean'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'account_number' => ['nullable', 'string', 'max:50'],
                'account_name' => ['nullable', 'string', 'max:255'],
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_active.required' => 'Status aktif wajib diisi.',
            'is_active.boolean' => 'Status aktif tidak valid.',
            'instructions.max' => 'Keterangan maksimal '.self::MAX_INSTRUCTIONS_LENGTH.' karakter.',
            'qris_image.image' => 'Berkas QRIS harus berupa gambar.',
            'qris_image.mimes' => 'Gambar QRIS harus berformat JPG, PNG, atau WebP.',
            'qris_image.max' => 'Ukuran gambar QRIS maksimal '.intdiv(self::MAX_IMAGE_KB, 1024).' MB.',
        ];
    }

    /**
     * Jenis metode dari segmen URL.
     *
     * Route sudah dibatasi `whereIn`, jadi nilai yang tidak dikenal tidak akan
     * sampai ke sini. Pemeriksaan null ini tetap ada sebagai pengaman kalau
     * batasan route berubah, dan berhenti sebagai 404 yang sama dengan metode
     * yang memang tidak ada.
     */
    public function type(): PaymentMethodType
    {
        $value = $this->route('type');
        $type = is_string($value) ? PaymentMethodType::tryFrom($value) : null;

        if ($type === null) {
            abort(404);
        }

        return $type;
    }

    public function isActive(): bool
    {
        return $this->boolean('is_active');
    }

    /**
     * Isi kolom yang boleh diubah untuk metode ini.
     *
     * Kolom yang tidak relevan tidak ikut dikembalikan, jadi menyimpan kartu
     * QRIS tidak menghapus data rekening bank.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return match ($this->type()) {
            PaymentMethodType::Cash => [
                'instructions' => $this->cleanString('instructions'),
            ],
            PaymentMethodType::Qris => [
                'merchant_name' => $this->cleanString('merchant_name'),
            ],
            PaymentMethodType::BankTransfer => [
                'bank_name' => $this->cleanString('bank_name'),
                'account_number' => $this->cleanString('account_number'),
                'account_name' => $this->cleanString('account_name'),
            ],
        };
    }

    /**
     * Gambar QRIS yang diunggah, atau null kalau kartu ini tidak mengirim
     * berkas baru.
     */
    public function qrisImage(): ?UploadedFile
    {
        $file = $this->file('qris_image');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * Nilai teks yang sudah dibersihkan, atau null kalau memang dikosongkan.
     *
     * Mengosongkan field harus bisa disimpan: data rekening yang salah lebih
     * baik dihapus daripada dibiarkan dan ikut tampil di halaman pembayaran.
     */
    private function cleanString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
