<?php

namespace App\Http\Requests;

use App\Models\Banner;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Validasi banner hero (PRD section 28, ROADMAP 5.4).
 *
 * Satu request dipakai untuk tambah dan ubah karena field-nya sama; yang
 * berbeda hanya gambar: wajib saat menambah, opsional saat mengubah. Mengubah
 * banner tanpa mengunggah gambar baru mempertahankan gambar lama, jadi admin
 * tidak perlu mengunggah ulang hanya untuk memperbaiki judul.
 *
 * `business_id` tidak pernah dibaca dari request; baris baru selalu memakai
 * unit bisnis admin yang login.
 */
class BannerRequest extends FormRequest
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
        $isUpdate = $this->route('banner') instanceof Banner;

        return [
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => [
                $isUpdate ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'link_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        $fail('Tautan banner tidak valid.');

                        return;
                    }

                    if (str_starts_with($value, '/') || str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                        return;
                    }

                    $fail('Tautan banner harus diawali "/" atau "http(s)://".');
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul banner wajib diisi.',
            'title.max' => 'Judul banner maksimal 150 karakter.',
            'subtitle.max' => 'Subjudul banner maksimal 255 karakter.',
            'image.required' => 'Gambar banner wajib diunggah.',
            'image.image' => 'Berkas banner harus berupa gambar.',
            'image.mimes' => 'Gambar banner harus berformat JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar banner maksimal 5 MB.',
            'is_active.required' => 'Status banner wajib diisi.',
            'is_active.boolean' => 'Status banner tidak valid.',
        ];
    }

    public function isActive(): bool
    {
        return $this->boolean('is_active');
    }

    /**
     * Gambar banner yang diunggah, atau null kalau form tidak mengirim berkas
     * baru (hanya terjadi saat mengubah banner).
     *
     * Namanya `uploadedImage`, bukan `image`, karena `Request::image()` sudah
     * dipakai Laravel untuk membaca input, dan menimpanya akan melanggar
     * kontrak induknya.
     */
    public function uploadedImage(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'title' => (string) $this->input('title', ''),
            'subtitle' => $this->cleanString('subtitle'),
            'link_url' => $this->cleanString('link_url'),
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_active' => $this->isActive(),
        ];
    }

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
