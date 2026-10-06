<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi FAQ unit (PRD section 28, ROADMAP 5.4).
 *
 * Satu request dipakai untuk tambah dan ubah karena field-nya sama.
 * `business_id` tidak pernah dibaca dari request; baris baru selalu memakai
 * unit bisnis admin yang login, dan route model binding pada FAQ milik unit
 * lain berakhir sebagai 404.
 */
class FaqRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
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
            'question.required' => 'Pertanyaan wajib diisi.',
            'question.max' => 'Pertanyaan maksimal 255 karakter.',
            'answer.required' => 'Jawaban wajib diisi.',
            'answer.max' => 'Jawaban maksimal 2000 karakter.',
            'is_active.required' => 'Status FAQ wajib diisi.',
            'is_active.boolean' => 'Status FAQ tidak valid.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'question' => trim((string) $this->input('question', '')),
            'answer' => trim((string) $this->input('answer', '')),
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
