<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi perubahan kategori (ROADMAP 4.2).
 *
 * `is_active` tidak ikut di form ini karena status punya endpoint sendiri
 * (`CategoryStatusRequest`) supaya tombol ubah status di tabel cukup mengirim
 * satu nilai tanpa mengulang nama dan deskripsi.
 *
 * Slug sengaja tidak bisa diubah: slug dipakai sebagai filter kategori di URL
 * katalog publik, jadi membuatnya ulang saat nama diganti akan memutus tautan
 * yang sudah dibagikan pengunjung.
 */
class UpdateCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'description.max' => 'Deskripsi kategori maksimal 1000 karakter.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan minimal 0.',
        ];
    }

    /**
     * Atribut siap disimpan. Slug kategori lama dipertahankan.
     *
     * @return array<string, mixed>
     */
    public function categoryPayload(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'sort_order' => max(0, (int) $this->input('sort_order', 0)),
        ];
    }
}
