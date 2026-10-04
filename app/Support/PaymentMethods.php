<?php

namespace App\Support;

use App\Enums\PaymentMethodType;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Daftar metode pembayaran aktif untuk satu unit bisnis (ROADMAP 3.9, PRD §16).
 *
 * Konfigurasi setiap metode disimpan di `payment_methods` per `business_id`,
 * jadi halaman pembayaran publik harus membacanya dari sana dan tidak boleh
 * menuliskan nomor rekening atau QRIS langsung di frontend.
 *
 * Metode yang aktif tetapi datanya belum lengkap tetap ditampilkan, hanya
 * dengan `is_ready: false`. Menyingkirkan metode seperti itu secara diam-diam
 * akan menyembunyikan kesalahan konfigurasi dari admin, sementara
 * mengaktifkannya membuat penyewa terjebak di halaman pembayaran yang tidak
 * bisa diselesaikan.
 */
final class PaymentMethods
{
    /**
     * Metode aktif milik satu unit bisnis, urut cash, QRIS, transfer.
     *
     * @return EloquentCollection<int, PaymentMethod>
     */
    public static function activeFor(Business $business): EloquentCollection
    {
        return BusinessScope::withoutBusinessScope(
            PaymentMethod::query()
                ->where('payment_methods.business_id', $business->getKey())
                ->active()
                ->orderBy('payment_methods.id'),
        )->get();
    }

    /**
     * Metode aktif dari satu jenis tertentu, atau `null` kalau tidak tersedia.
     */
    public static function find(Business $business, PaymentMethodType $type): ?PaymentMethod
    {
        return self::activeFor($business)
            ->first(fn (PaymentMethod $method): bool => $method->type === $type);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function options(Business $business): array
    {
        $options = [];

        foreach (self::activeFor($business) as $method) {
            $options[] = self::toOption($method);
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toOption(PaymentMethod $method): array
    {
        return [
            'type' => $method->type->value,
            'label' => $method->type->label(),
            'description' => self::description($method),
            'requires_proof' => $method->type->requiresProof(),
            'is_ready' => self::isReady($method),
            'instructions' => $method->instructions,
            'merchant_name' => $method->merchant_name,
            'qris_image_url' => $method->qris_image_url,
            'bank_name' => $method->bank_name,
            'account_number' => $method->account_number,
            'account_name' => $method->account_name,
        ];
    }

    /**
     * Metode hanya bisa dipilih kalau data minimum yang dibutuhkan penyewa
     * sudah ada: QRIS wajib punya gambar, transfer wajib punya bank dan nomor
     * rekening. Cash tidak butuh data tambahan.
     */
    public static function isReady(PaymentMethod $method): bool
    {
        return match ($method->type) {
            PaymentMethodType::Cash => true,
            PaymentMethodType::Qris => $method->qris_image !== null,
            PaymentMethodType::BankTransfer => $method->bank_name !== null
                && $method->account_number !== null,
        };
    }

    /**
     * Ringkasan satu baris untuk ditampilkan di daftar pilihan metode.
     */
    public static function description(PaymentMethod $method): string
    {
        $description = match ($method->type) {
            PaymentMethodType::Cash => $method->instructions,
            PaymentMethodType::Qris => $method->merchant_name === null
                ? null
                : 'QRIS '.$method->merchant_name,
            PaymentMethodType::BankTransfer => match (true) {
                $method->bank_name !== null && $method->account_number !== null => $method->bank_name.' · '.$method->account_number,
                $method->bank_name !== null => $method->bank_name,
                default => null,
            },
        };

        if ($description !== null && $description !== '') {
            return $description;
        }

        return self::isReady($method)
            ? $method->type->label()
            : $method->type->label().' (belum dikonfigurasi)';
    }
}
