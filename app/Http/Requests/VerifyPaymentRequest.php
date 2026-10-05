<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi verifikasi pembayaran (ROADMAP 4.6).
 *
 * Verifikasi tidak punya isian: yang diperiksa hanya apakah pembayaran ini
 * memang boleh menjadi lunas. Aturan transisinya tetap dipegang
 * `PaymentStatus::canTransitionTo()` supaya form request, service, dan test
 * memakai definisi yang sama.
 */
class VerifyPaymentRequest extends FormRequest
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
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $payment = $this->payment();

        $validator->after(function (Validator $validator) use ($payment): void {
            if ($payment === null || $payment->status->canTransitionTo(PaymentStatus::Lunas)) {
                return;
            }

            $validator->errors()->add(
                'status',
                'Pembayaran yang sudah '.$payment->status->label()
                    .' tidak bisa diverifikasi lagi.',
            );
        });
    }

    /**
     * Pembayaran dari route, kalau route model binding sudah memuatnya.
     *
     * Route binding membaca `payments` lewat `BusinessScope`, jadi pembayaran
     * unit lain berhenti sebagai 404 sebelum sampai ke sini.
     */
    private function payment(): ?Payment
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment ? $payment : null;
    }
}
