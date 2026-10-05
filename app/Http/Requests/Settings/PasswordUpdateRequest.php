<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * Pesan validasi dalam bahasa Indonesia (ROADMAP 4.8).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak cocok.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
            'password.min' => 'Password minimal :min karakter.',
            'password.letters' => 'Password harus memuat huruf.',
            'password.mixed' => 'Password harus memuat huruf besar dan huruf kecil.',
            'password.numbers' => 'Password harus memuat angka.',
            'password.symbols' => 'Password harus memuat simbol.',
            'password.uncompromised' => 'Password ini pernah bocor di internet. Pilih password lain.',
        ];
    }
}
