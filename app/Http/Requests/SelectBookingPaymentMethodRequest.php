<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodType;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Support\PaymentMethods;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Menyimpan metode pembayaran yang dipilih di halaman review (ROADMAP 3.9).
 *
 * Nilai dari klien tidak pernah dipercaya: metode harus salah satu dari
 * `PaymentMethodType`, harus berstatus aktif, harus milik unit bisnis yang
 * sedang diakses, dan harus punya data minimum yang dibutuhkan penyewa.
 * Pemeriksaan ini berada di dalam `withValidator()` supaya request berhenti
 * dengan 422 sebelum draft ditulis.
 */
class SelectBookingPaymentMethodRequest extends FormRequest
{
    private ?Business $resolvedBusiness = null;

    private ?PaymentMethod $resolvedMethod = null;

    private bool $methodResolved = false;

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
            'method' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.required' => 'Pilih metode pembayaran dulu.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->paymentMethod() === null) {
                $validator->errors()->add('method', 'Metode pembayaran tidak tersedia.');
            }
        });
    }

    public function business(): Business
    {
        if ($this->resolvedBusiness !== null) {
            return $this->resolvedBusiness;
        }

        $slug = $this->route('business');

        if (! is_string($slug) || $slug === '') {
            throw new NotFoundHttpException;
        }

        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($business === null) {
            throw new NotFoundHttpException;
        }

        return $this->resolvedBusiness = $business;
    }

    /**
     * Metode pembayaran yang valid untuk unit bisnis ini, atau `null` kalau
     * tidak ada.
     *
     * Namanya bukan `method()` karena `Illuminate\Http\Request` sudah memakai
     * nama itu untuk verb HTTP, dan menimpanya akan merusak pembacaan method
     * request di tempat lain.
     */
    public function paymentMethod(): ?PaymentMethod
    {
        if ($this->methodResolved) {
            return $this->resolvedMethod;
        }

        $this->methodResolved = true;

        $type = $this->input('method');

        if (! is_string($type) || $type === '') {
            return $this->resolvedMethod = null;
        }

        $method = PaymentMethodType::tryFrom($type);

        if ($method === null) {
            return $this->resolvedMethod = null;
        }

        $found = PaymentMethods::find($this->business(), $method);

        /**
         * Metode yang aktif tapi datanya belum lengkap tetap ditolak di sini.
         * Melanjutkannya hanya memindahkan penyewa ke halaman pembayaran yang
         * tidak punya QRIS atau nomor rekening untuk dipindai.
         */
        return $this->resolvedMethod = $found !== null && PaymentMethods::isReady($found)
            ? $found
            : null;
    }
}
