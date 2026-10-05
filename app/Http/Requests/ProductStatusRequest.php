<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi ubah status produk (ROADMAP 4.3).
 *
 * Sama seperti kategori, status punya endpoint sendiri supaya aksi di tabel
 * daftar cukup mengirim satu nilai boolean, tanpa mengulang seluruh form produk.
 */
class ProductStatusRequest extends FormRequest
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
            'is_active.required' => 'Status produk wajib diisi.',
            'is_active.boolean' => 'Status produk tidak valid.',
        ];
    }
}
