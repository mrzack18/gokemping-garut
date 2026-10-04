<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodType;
use App\Models\Business;
use App\Support\BookingDraft;
use App\Support\PaymentMethods;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Upload bukti pembayaran (ROADMAP 3.10, PRD section 18 dan BR-08).
 *
 * Bukti wajib untuk QRIS dan transfer bank, opsional untuk cash. Aturan itu
 * tidak bisa ditulis sebagai `required_if` biasa karena syaratnya bergantung
 * pada metode yang tercatat di draft, bukan pada input request, jadi
 * pemeriksaan wajib/tidaknya dilakukan di `withValidator()`.
 *
 * Berkas disimpan di disk `public` pada folder `payments/`, mengikuti kolom
 * `payments.proof`. Penulisan ke database baru dilakukan di ROADMAP 3.11, jadi
 * request ini hanya mengembalikan nama berkas yang siap disimpan ke draft.
 *
 * Nama method-nya bukan `method()` karena `Illuminate\Http\Request` sudah memakai
 * nama itu untuk verb HTTP.
 */
class StoreBookingPaymentProofRequest extends FormRequest
{
    private ?Business $resolvedBusiness = null;

    private bool $businessResolved = false;

    private ?UploadedFile $proof = null;

    private bool $proofResolved = false;

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
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.file' => 'Bukti pembayaran harus berupa berkas gambar.',
            'proof.mimes' => 'Bukti pembayaran harus berformat JPG, JPEG, PNG, atau WebP.',
            'proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /**
             * Bukti hanya diminta untuk metode yang mewajibkannya (BR-08).
             * Cash tidak wajib, jadi unggahan tetap diterima kalau ada, tetapi
             * tidak diminta kalau tidak ada.
             */
            if ($this->paymentMethodRequiresProof() && $this->proof() === null) {
                $validator->errors()->add('proof', 'Bukti pembayaran wajib diunggah untuk metode ini.');
            }
        });
    }

    public function business(): Business
    {
        if ($this->businessResolved) {
            /** @var Business $business */
            $business = $this->resolvedBusiness;

            return $business;
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

        $this->businessResolved = true;

        return $this->resolvedBusiness = $business;
    }

    /**
     * Metode pembayaran yang tercatat di draft, atau `null` kalau draft tidak
     * punya metode yang masih bisa dipakai.
     */
    public function paymentMethodType(): ?PaymentMethodType
    {
        $draft = app(BookingDraft::class)->read();
        $value = is_array($draft) ? ($draft['payment_method'] ?? null) : null;

        if (! is_string($value)) {
            return null;
        }

        $type = PaymentMethodType::tryFrom($value);

        if ($type === null) {
            return null;
        }

        $found = PaymentMethods::find($this->business(), $type);

        return $found !== null && PaymentMethods::isReady($found) ? $type : null;
    }

    public function paymentMethodRequiresProof(): bool
    {
        return $this->paymentMethodType()?->requiresProof() ?? false;
    }

    /**
     * Berkas bukti yang sudah divalidasi, atau `null` kalau tidak ada.
     */
    public function proof(): ?UploadedFile
    {
        if ($this->proofResolved) {
            return $this->proof;
        }

        $this->proofResolved = true;

        $file = $this->file('proof');

        return $this->proof = $file instanceof UploadedFile ? $file : null;
    }
}
