<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodType;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi booking yang dicatat admin dari penyewaan langsung di tempat.
 *
 * Field identitas penyewa memakai aturan dan normalisasi yang sama dengan
 * booking publik. Produk tetap diverifikasi lewat BusinessScope sehingga admin
 * tidak bisa menyisipkan produk milik unit lain lewat request manual.
 */
class StoreManualBookingRequest extends StoreBookingCustomerRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'product_id' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'payment_method' => ['required', Rule::enum(PaymentMethodType::class)],
            'is_paid' => ['sometimes', 'boolean'],
            'start_rental_now' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'product_id.required' => 'Pilih produk yang disewa.',
            'start_date.required' => 'Pilih tanggal mulai sewa.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'end_date.required' => 'Pilih tanggal selesai sewa.',
            'end_date.after_or_equal' => 'Tanggal selesai harus tanggal mulai atau setelahnya.',
            'quantity.required' => 'Jumlah barang minimal 1 unit.',
            'quantity.min' => 'Jumlah barang minimal 1 unit.',
            'quantity.max' => 'Jumlah barang maksimal 1000 unit.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.enum' => 'Metode pembayaran tidak valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('product_id')) {
                return;
            }

            $productId = $this->input('product_id');

            if (! is_numeric($productId)) {
                return;
            }

            if (! Product::query()->active()->whereKey((int) $productId)->exists()) {
                $validator->errors()->add(
                    'product_id',
                    'Produk tidak ditemukan atau sudah dinonaktifkan.',
                );
            }

            if (
                $this->boolean('start_rental_now')
                && $this->input('start_date') !== now()->toDateString()
            ) {
                $validator->errors()->add(
                    'start_rental_now',
                    'Barang hanya bisa ditandai sedang disewa jika tanggal mulai adalah hari ini.',
                );
            }
        });
    }
}
