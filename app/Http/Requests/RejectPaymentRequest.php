<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi penolakan pembayaran (PRD section 26, ROADMAP 4.6).
 *
 * Alasan penolakan wajib diisi karena penyewa perlu tahu apa yang harus
 * diperbaiki: bukti buram, nominal tidak sama, atau transfer ke rekening yang
 * salah. Tanpa alasan, status `ditolak` tidak bisa ditindaklanjuti siapa pun.
 *
 * Yang boleh ditolak hanya pembayaran yang sedang menunggu verifikasi. Cash
 * berjalan langsung dari `belum_dibayar` ke `lunas`, jadi tidak pernah masuk
 * daftar tunggu verifikasi.
 */
class RejectPaymentRequest extends FormRequest
{
    /**
     * Panjang maksimum alasan penolakan, mengikuti lebar kolom
     * `payments.rejection_reason`.
     */
    public const MAX_REASON_LENGTH = 255;

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
            'rejection_reason' => [
                'required',
                'string',
                'max:'.self::MAX_REASON_LENGTH,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.max' => 'Alasan penolakan maksimal '.self::MAX_REASON_LENGTH.' karakter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $payment = $this->payment();

        $validator->after(function (Validator $validator) use ($payment): void {
            if ($payment === null || $payment->status->canTransitionTo(PaymentStatus::Ditolak)) {
                return;
            }

            $validator->errors()->add(
                'rejection_reason',
                'Pembayaran berstatus '.$payment->status->label()
                    .' tidak bisa ditolak.',
            );
        });
    }

    /**
     * Alasan penolakan siap disimpan, dengan spasi luar dibuang.
     */
    public function reason(): string
    {
        $reason = $this->input('rejection_reason');

        return is_string($reason) ? trim($reason) : '';
    }

    /**
     * Pembayaran dari route, kalau route model binding sudah memuatnya.
     */
    private function payment(): ?Payment
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment ? $payment : null;
    }
}
