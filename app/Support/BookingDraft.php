<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Draft booking publik yang disimpan di session.
 *
 * Penyimpanan booking ke database ada di ROADMAP 3.11, sedangkan alur publik
 * sudah spanned beberapa halaman pada 3.5 sampai 3.10. Session dipakai sebagai
 * penampung sementara supaya produk, periode, dan data penyewa tidak hilang
 * saat pindah halaman.
 *
 * Draft hanya menyimpan identitas dan input pengguna, bukan hasil hitungan.
 * Harga, stok, dan durasi selalu dihitung ulang dari database setiap kali
 * halaman dibuka supaya nilai yang tampil tidak pernah basi. Ini juga yang
 * membuat BR-09 tetap benar nanti: harga disalin dari `products` saat booking
 * benar-benar disimpan.
 */
class BookingDraft
{
    public const SESSION_KEY = 'booking.draft';

    /**
     * Field biodata yang wajib terisi sebelum review dan pembayaran boleh dibuka.
     *
     * @var list<string>
     */
    public const REQUIRED_CUSTOMER_FIELDS = ['name', 'whatsapp', 'nik', 'address'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function write(array $data): void
    {
        $this->session()->put(self::SESSION_KEY, $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        $draft = $this->session()->get(self::SESSION_KEY);

        return is_array($draft) ? $draft : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function merge(array $data): array
    {
        $merged = array_merge($this->read() ?? [], $data);

        $this->write($merged);

        return $merged;
    }

    /**
     * Buang beberapa key dari draft tanpa menyentuh key lain.
     *
     * Dipakai saat bukti pembayaran dibatalkan: key bukti dihapus dari draft
     * sementara produk, periode, dan biodata tetap utuh supaya penyewa tidak
     * harus mengulang langkah sebelumnya.
     *
     * @param  list<string>  $keys
     */
    public function forgetKeys(array $keys): void
    {
        $draft = $this->read();

        if ($draft === null) {
            return;
        }

        foreach ($keys as $key) {
            unset($draft[$key]);
        }

        $this->write($draft);
    }

    public function forget(): void
    {
        $this->session()->forget(self::SESSION_KEY);
    }

    /**
     * Produk dari draft untuk satu unit bisnis.
     *
     * Mengembalikan `null` kalau draft tidak ada, sudah milik unit lain, atau
     * produknya sudah nonaktif, terhapus, atau tidak ditemukan.
     *
     * @return array{business: Business, product: Product}|null
     */
    public function resolveFor(string $businessSlug): ?array
    {
        $draft = $this->read();

        if ($draft === null || ($draft['business'] ?? null) !== $businessSlug) {
            return null;
        }

        $productId = $draft['product_id'] ?? null;

        if (! is_int($productId)) {
            return null;
        }

        $business = Business::query()
            ->where('slug', $businessSlug)
            ->where('is_active', true)
            ->first();

        if ($business === null) {
            return null;
        }

        $product = BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('business_id', $business->getKey())
                ->whereKey($productId)
                ->active(),
        )->first(['id', 'business_id', 'category_id', 'name', 'slug', 'price', 'price_unit', 'stock']);

        if ($product === null) {
            return null;
        }

        return ['business' => $business, 'product' => $product];
    }

    /**
     * Apakah draft sudah punya data penyewa minimum yang wajib ada sebelum
     * review (ROADMAP 3.8) dan halaman pembayaran (ROADMAP 3.9) boleh dibuka.
     *
     * Alur publik punya banyak halaman, jadi pemeriksaan ini dipusatkan di sini
     * supaya review dan pembayaran tidak bisa berbeda pendapat soal data yang
     * dianggap sudah lengkap.
     */
    public function customerIsComplete(): bool
    {
        $customer = $this->read()['customer'] ?? null;

        if (! is_array($customer)) {
            return false;
        }

        foreach (self::REQUIRED_CUSTOMER_FIELDS as $field) {
            $value = $customer[$field] ?? null;

            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve draft atau hentikan request dengan 404.
     *
     * @return array{business: Business, product: Product, draft: array<string, mixed>}
     */
    public function resolveOrFail(string $businessSlug): array
    {
        $resolved = $this->resolveFor($businessSlug);

        if ($resolved === null) {
            throw new NotFoundHttpException;
        }

        return [
            'business' => $resolved['business'],
            'product' => $resolved['product'],
            'draft' => $this->read() ?? [],
        ];
    }

    private function session(): Session
    {
        return app(Session::class);
    }
}
