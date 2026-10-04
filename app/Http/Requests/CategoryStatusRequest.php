<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi ubah status kategori (ROADMAP 4.2).
 *
 * Endpoint terpisah supaya aksi status bisa dikirim dari tabel daftar tanpa
 * form penuh. Nilai `is_active` datang sebagai `0` atau `1` dari input
 * tersembunyi, karena input HTML tidak pernah mengirim nilai kosong.
 */
class CategoryStatusRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_active.required' => 'Status kategori wajib diisi.',
            'is_active.boolean' => 'Status kategori tidak valid.',
        ];
    }
}
