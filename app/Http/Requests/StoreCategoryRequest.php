<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi penyimpanan kategori baru (ROADMAP 4.2).
 *
 * `business_id` tidak pernah diambil dari request. Kategori selalu milik unit
 * bisnis admin yang sedang login, jadi tidak ada field yang bisa diisi untuk
 * memindahkan kategori ke unit lain.
 *
 * Slug dibuat dari nama kategori memakai `Category::generateSlug()`, sehingga
 * form tidak pernah meninggalkan slug kosong dan tabrakan nama diselesaikan
 * di server, bukan ditolak ke admin.
 */
class StoreCategoryRequest extends FormRequest
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
     * Atribut siap disimpan.
     *
     * @return array<string, mixed>
     */
    public function categoryPayload(): array
    {
        $business = $this->business();
        $name = trim((string) $this->input('name'));

        return [
            'name' => $name,
            'slug' => Category::generateSlug($business, $name),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'sort_order' => max(0, (int) $this->input('sort_order', 0)),
            'is_active' => true,
        ];
    }

    protected function business(): Business
    {
        /** @var Business $business */
        $business = $this->user()->business;

        return $business;
    }
}
