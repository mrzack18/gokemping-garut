<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingCodeGenerator;
use App\Support\BookingPeriod;
use App\Support\BookingRoutes;
use App\Support\WhatsappNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Penyimpanan booking dari draft session ke database (ROADMAP 3.11).
 *
 * Semua baris yang menyentuh transaksi disimpan dalam satu `DB::transaction`.
 * Booking, item, dan pembayaran harus tersimpan bersama. Booking tanpa item
 * atau tanpa record pembayaran akan menyesatkan admin dan membuat dashboard
 * menghitung angka yang salah.
 *
 * Ketersediaan dicek ulang di dalam transaksi, tepat sebelum stok dihitung,
 * karena nilai yang tampil di halaman review bisa jadi basi. Pengecekan di
 * endpoint publik tanpa lock hanya informatif; yang menentukan adalah baris
 * produk yang dikunci di sini.
 *
 * Urutan lock di dalam transaksi dijaga: baris business dulu, lalu baris
 * produk. Kunci business dipakai sebagai mutex pembuatan kode booking.
 */
final class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingCodeGenerator $codes,
    ) {}

    /**
     * Simpan booking beserta item dan pembayaran dari draft.
     *
     * @param  array<string, mixed>  $draft  Isi draft yang sudah lolos validasi.
     *
     * @throws InsufficientStockException kalau stok sudah tidak cukup
     */
    public function store(Business $business, Product $product, array $draft): Booking
    {
        $method = PaymentMethodType::from((string) $draft['payment_method']);
        $startDate = (string) $draft['start_date'];
        $endDate = (string) $draft['end_date'];
        $quantity = max(1, (int) $draft['quantity']);
        $duration = BookingPeriod::durationInDays($startDate, $endDate);

        return DB::transaction(function () use ($business, $product, $draft, $method, $startDate, $endDate, $quantity, $duration): Booking {
            /**
             * Urutan lock selalu sama: business dulu, baru produk.
             *
             * Baris business adalah mutex pembuatan kode booking. Baris booking
             * belum ada pada kode pertama, jadi `lockForUpdate` di tabel
             * `bookings` tidak memblokir apa pun dan dua request bersamaan bisa
             * sama-sama membaca urutan terakhir yang sama.
             */
            $lockedBusiness = Business::query()->whereKey($business->getKey())->lockForUpdate()->firstOrFail();

            /**
             * Baris produk dikunci supaya dua booking bersamaan untuk produk
             * yang sama tidak bisa sama-sama membaca stok yang sama lalu
             * sama-sama menyimpannya.
             */
            $lockedProduct = BusinessScope::withoutBusinessScope(
                Product::query()->whereKey($product->getKey())->lockForUpdate(),
            )->firstOrFail();

            $available = $this->availability->availableUnits($lockedProduct, $startDate, $endDate);

            if ($available < $quantity) {
                throw new InsufficientStockException($available, $quantity);
            }

            $customer = $this->resolveCustomer($draft);

            /**
             * Harga disalin dari `products` saat ini, bukan dari nilai yang
             * pernah dikirim browser atau disimpan di draft. Ini yang membuat
             * BR-09 benar: perubahan harga produk tidak mengubah booking lama.
             */
            $subtotal = BookingPeriod::total((int) $lockedProduct->price, $quantity, $duration);

            $paymentStatus = PaymentStatus::initialFor($method);

            $booking = Booking::query()->create([
                'booking_code' => $this->codes->next($lockedBusiness, Carbon::now()),
                'business_id' => $lockedBusiness->getKey(),
                'customer_id' => $customer->getKey(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $duration,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'payment_method' => $method->value,
                'payment_status' => $paymentStatus->value,
                'booking_status' => BookingStatus::MenungguKonfirmasi->value,
                'renter_count' => $this->renterCount($lockedBusiness, $draft),
                'notes' => $this->notes($draft),
            ]);

            BookingItem::query()->create([
                'booking_id' => $booking->getKey(),
                'product_id' => $lockedProduct->getKey(),
                'product_name' => $lockedProduct->name,
                'price_unit' => $lockedProduct->price_unit,
                'price' => (int) $lockedProduct->price,
                'quantity' => $quantity,
                'total_days' => $duration,
                'subtotal' => $subtotal,
            ]);

            Payment::query()->create([
                'booking_id' => $booking->getKey(),
                'business_id' => $lockedBusiness->getKey(),
                'method' => $method->value,
                'amount' => $subtotal,
                'proof' => $this->proofPath($draft),
                'status' => $paymentStatus->value,
            ]);

            // Baris pertama riwayat status ditulis saat booking dibuat, bukan
            // saat admin pertama kali mengubah status, supaya halaman riwayat
            // selalu punya titik awal yang jelas: booking ini masuk pada status
            // apa, dan sejak kapan.
            //
            // `changed_by` dikosongkan karena yang membuat booking adalah penyewa
            // lewat halaman publik, bukan admin.
            $booking->statusHistories()->create([
                'from_status' => null,
                'to_status' => BookingStatus::MenungguKonfirmasi,
                'note' => null,
                'changed_by' => null,
            ]);

            return $booking;
        }, 3);
    }

    /**
     * Penyewa dicari berdasarkan nomor WhatsApp yang sudah dinormalkan.
     *
     * `customers.whatsapp` punya unique constraint dan sudah dipakai sebagai
     * kunci deduplikasi di endpoint deteksi pelanggan lama (ROADMAP 3.7), jadi
     * booking kedua oleh orang yang sama memperbarui baris yang sudah ada
     * alih-alih membuat duplikat.
     *
     * @param  array<string, mixed>  $draft
     */
    private function resolveCustomer(array $draft): Customer
    {
        $data = $draft['customer'] ?? [];

        $whatsapp = WhatsappNumber::normalize((string) ($data['whatsapp'] ?? ''));

        if ($whatsapp === null) {
            throw new LogicException('Draft tanpa nomor WhatsApp tidak bisa disimpan.');
        }

        $attributes = [
            'name' => (string) ($data['name'] ?? ''),
            'whatsapp' => $whatsapp,
            'email' => $this->nullableString($data['email'] ?? null),
            'nik' => (string) ($data['nik'] ?? ''),
            'address' => (string) ($data['address'] ?? ''),
            'city' => $this->nullableString($data['city'] ?? null),
        ];

        $customer = Customer::query()->where('whatsapp', $whatsapp)->first();

        if ($customer !== null) {
            $customer->fill($attributes)->save();

            return $customer;
        }

        return Customer::query()->create($attributes);
    }

    /**
     * Jumlah penyewa hanya relevan untuk unit sewa sepeda (PRD section 14).
     *
     * @param  array<string, mixed>  $draft
     */
    private function renterCount(Business $business, array $draft): ?int
    {
        if (! BookingRoutes::isBikeRental($business->slug)) {
            return null;
        }

        $value = $draft['customer']['renter_count'] ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        return max(1, (int) $value);
    }

    /**
     * Catatan penyewa disimpan pada booking, bukan pada customer.
     *
     * Catatan customer milik orang tersebut secara permanen, sedangkan catatan
     * di formulir booking bisa berbeda tiap kali menyewa.
     *
     * @param  array<string, mixed>  $draft
     */
    private function notes(array $draft): ?string
    {
        return $this->nullableString($draft['customer']['notes'] ?? null);
    }

    /**
     * Path bukti pembayaran dari draft, dipindahkan ke kolom `payments.proof`.
     *
     * @param  array<string, mixed>  $draft
     */
    private function proofPath(array $draft): ?string
    {
        $path = $draft['payment_proof'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
