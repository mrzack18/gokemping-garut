<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi informasi layanan dan kontak unit (PRD section 28, ROADMAP 5.4).
 *
 * Semua konten tersimpan di baris `businesses` milik admin yang login, jadi
 * tidak ada `business_id` yang dibaca dari request dan tidak ada konten yang
 * bisa berpindah unit.
 */
class UpdateBusinessContentRequest extends FormRequest
{
    /**
     * Jumlah maksimum poin informasi layanan.
     */
    public const MAX_HIGHLIGHTS = 6;

    /**
     * Panjang maksimum satu poin informasi layanan.
     */
    public const MAX_HIGHLIGHT_LENGTH = 120;

    /**
     * Prefix URL embed Google Maps yang diterima.
     *
     * Nilai ini dibatasi karena URL-nya dirender sebagai `<iframe>` di landing
     * page. Tanpa batasan, halaman publik bisa dipakai menampilkan iframe dari
     * domain mana pun.
     *
     * @var list<string>
     */
    private const ALLOWED_MAP_PREFIXES = [
        'https://www.google.com/maps/embed',
        'https://maps.google.com/maps',
    ];

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
            'whatsapp' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]{6,25}$/'],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]{6,25}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'service_intro' => ['nullable', 'string', 'max:500'],
            // Satu poin per baris; batas per baris dan jumlah baris diperiksa
            // di `withValidator` karena isinya belum dipecah di sini.
            'service_highlights' => ['nullable', 'string', 'max:1000'],
            'rental_terms' => ['nullable', 'string', 'max:2000'],
            'maps_embed_url' => [
                'nullable',
                'url',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        $fail('URL lokasi tidak valid.');

                        return;
                    }

                    foreach (self::ALLOWED_MAP_PREFIXES as $prefix) {
                        if (str_starts_with($value, $prefix)) {
                            return;
                        }
                    }

                    $fail('URL lokasi harus berupa tautan embed Google Maps.');
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda plus, tanda hubung, atau tanda kurung.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda plus, tanda hubung, atau tanda kurung.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'address.max' => 'Alamat maksimal 255 karakter.',
            'service_intro.max' => 'Informasi layanan maksimal 500 karakter.',
            'rental_terms.max' => 'Ketentuan sewa maksimal 2000 karakter.',
            'maps_embed_url.url' => 'URL lokasi tidak valid.',
            'maps_embed_url.max' => 'URL lokasi terlalu panjang.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $highlights = $this->highlightLines();

            if (count($highlights) > self::MAX_HIGHLIGHTS) {
                $validator->errors()->add(
                    'service_highlights',
                    'Poin informasi layanan maksimal '.self::MAX_HIGHLIGHTS.' baris.',
                );
            }

            foreach ($highlights as $line) {
                if (mb_strlen($line) > self::MAX_HIGHLIGHT_LENGTH) {
                    $validator->errors()->add(
                        'service_highlights',
                        'Setiap poin maksimal '.self::MAX_HIGHLIGHT_LENGTH.' karakter.',
                    );

                    return;
                }
            }
        });
    }

    /**
     * Isi kolom yang boleh diubah, siap disimpan.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'whatsapp' => trim((string) $this->input('whatsapp', '')),
            'phone' => $this->cleanString('phone'),
            'email' => $this->cleanString('email'),
            'address' => $this->cleanString('address'),
            'service_intro' => $this->cleanString('service_intro'),
            'service_highlights' => $this->highlights(),
            'rental_terms' => $this->cleanString('rental_terms'),
            'maps_embed_url' => $this->cleanString('maps_embed_url'),
        ];
    }

    /**
     * Poin informasi layanan sebagai daftar, atau null kalau dikosongkan.
     *
     * Form mengirimnya sebagai textarea dengan satu poin per baris, jadi baris
     * kosong dibuang dan baris yang tersisa dipangkas. Daftar kosong disimpan
     * sebagai null supaya tidak ada array kosong di kolom JSON.
     *
     * @return list<string>|null
     */
    private function highlights(): ?array
    {
        $lines = $this->highlightLines();

        return $lines === [] ? null : $lines;
    }

    /**
     * @return list<string>
     */
    private function highlightLines(): array
    {
        $value = $this->input('service_highlights');

        if (! is_string($value)) {
            return [];
        }

        $lines = preg_split('/\R/', $value);

        if ($lines === false) {
            return [];
        }

        $cleaned = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line !== '') {
                $cleaned[] = $line;
            }
        }

        return $cleaned;
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
