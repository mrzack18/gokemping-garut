<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketLookupRequest;
use App\Models\Banner;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page publik (ROADMAP 3.1) sekaligus tempat cek tiket (5.5).
 *
 * Halaman ini menampilkan unit bisnis dan produk unggulan dari seluruh unit,
 * jadi global scope BusinessScope harus dinonaktifkan secara eksplisit.
 * Tanpa itu, admin yang sedang login hanya akan melihat produk unitnya sendiri
 * di halaman yang seharusnya sama untuk semua pengunjung.
 *
 * Cek tiket sengaja tinggal di halaman ini, bukan halaman terpisah, supaya
 * penyewa dan staf menemukannya di tempat yang sama dengan informasi layanan.
 * Hasil pencariannya dirender ulang di section `#cek-tiket`.
 */
class LandingController extends Controller
{
    /**
     * Jumlah produk unggulan per unit bisnis supaya tiap unit sama-sama
     * terlihat di landing page.
     */
    private const FEATURED_PER_BUSINESS = 4;

    /**
     * Berapa banyak percobaan cek tiket per menit per alamat IP.
     *
     * Lebih longgar dari lookup biodata karena staf bisa memeriksa banyak
     * tiket berurutan saat jam pengambilan barang.
     */
    public const TICKET_THROTTLE = '30,1';

    /**
     * Kolom produk yang dikirim ke frontend. `business_id` dan `category_id`
     * wajib ikut agar relasi eager load dapat dihidrasi.
     *
     * @var list<string>
     */
    private const PRODUCT_COLUMNS = [
        'id',
        'business_id',
        'category_id',
        'name',
        'slug',
        'price',
        'price_unit',
        'stock',
    ];

    public function __invoke(): Response
    {
        return $this->page(null);
    }

    /**
     * Cari tiket berdasarkan kode booking dan nomor WhatsApp penyewanya.
     *
     * Tiket tidak dibuka hanya dengan kode booking: kode booking berurutan per
     * hari dan bisa ditebak. Hasilnya dirender di landing page yang sama,
     * lengkap dengan data landing lainnya, supaya penyewa atau staf tidak
     * berpindah halaman.
     */
    public function checkTicket(TicketLookupRequest $request): Response|RedirectResponse
    {
        $whatsapp = $request->normalizedWhatsapp();

        if ($whatsapp === null) {
            return back()->withInput()->withErrors([
                'whatsapp' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        /**
         * Scope tenancy dinonaktifkan karena halaman ini lintas unit dan tidak
         * butuh login. Kalau admin sedang login, scope akan menyaring tiket
         * unit lain dan halaman cek tiket justru rusak untuk staf.
         */
        $booking = BusinessScope::withoutBusinessScope(
            Booking::query()
                ->with(['business', 'customer', 'items', 'payment'])
                ->where('booking_code', $request->code())
                ->whereHas('customer', fn (Builder $query): Builder => $query->where('whatsapp', $whatsapp))
        )->first();

        if (! $booking instanceof Booking) {
            return back()->withInput()->withErrors([
                'booking_code' => 'Tiket tidak ditemukan. Periksa kembali kode booking dan nomor WhatsApp-nya.',
            ]);
        }

        return $this->page($this->ticket($booking));
    }

    /**
     * Landing page dengan data tiket, atau tanpa tiket sama sekali.
     *
     * @param  array<string, mixed>|null  $ticket
     */
    private function page(?array $ticket): Response
    {
        $businesses = $this->businesses();

        return Inertia::render('welcome', [
            'businesses' => $businesses,
            'featuredProducts' => $this->featuredProducts($businesses),
            'banners' => $this->banners($businesses),
            'faqs' => $this->faqs($businesses),
            'ticket' => $ticket,
        ]);
    }

    /**
     * Data tiket yang boleh dilihat publik, tanpa NIK dan tanpa alamat.
     *
     * @return array<string, mixed>
     */
    private function ticket(Booking $booking): array
    {
        $business = $booking->business;
        $customer = $booking->customer;

        return [
            'booking_code' => (string) $booking->booking_code,
            'business' => [
                'name' => $business instanceof Business ? $business->name : '-',
                'whatsapp' => $business instanceof Business ? $business->whatsapp : null,
            ],
            'customer_name' => $customer instanceof Customer ? $customer->name : '-',
            'status' => $booking->booking_status->value,
            'status_label' => $booking->booking_status->label(),
            'payment_status' => $booking->payment_status->value,
            'payment_status_label' => $booking->payment_status->label(),
            'payment_method_label' => $booking->payment_method->label(),
            'items' => array_values(
                $booking->items
                    ->map(fn (BookingItem $item): array => [
                        'product_name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'subtotal_label' => number_format((int) $item->subtotal, 0, ',', '.'),
                    ])
                    ->all()
            ),
            'period' => [
                'start_date_label' => BookingPeriod::readableDate($booking->start_date),
                'end_date_label' => BookingPeriod::readableDate($booking->end_date),
                'total_days_label' => ((int) $booking->total_days).' hari',
            ],
            'total' => (int) $booking->total,
            'total_label' => number_format((int) $booking->total, 0, ',', '.'),
            'cancellation_reason' => $booking->cancellation_reason,
            'created_at_label' => BookingPeriod::readableDate($booking->created_at),
        ];
    }

    /**
     * @return Collection<int, Business>
     */
    private function businesses(): Collection
    {
        return Business::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'service_intro',
                'service_highlights',
                'rental_terms',
                'whatsapp',
                'phone',
                'email',
                'address',
                'maps_embed_url',
            ]);
    }

    /**
     * Banner aktif dari seluruh unit, digabung dengan unitnya.
     *
     * Landing page memang lintas tenant, jadi scope dinonaktifkan secara
     * eksplisit. Banner nonaktif tidak pernah dikirim supaya admin bisa
     * menyiapkan banner lalu menayangkannya belakangan.
     *
     * @param  Collection<int, Business>  $businesses
     * @return Collection<int, Banner>
     */
    private function banners(Collection $businesses): Collection
    {
        return BusinessScope::withoutBusinessScope(
            Banner::query()
                ->whereIn('business_id', $businesses->pluck('id'))
                ->active()
                ->ordered()
                ->with('business:id,name,slug')
        )->get();
    }

    /**
     * FAQ aktif dari seluruh unit, diurutkan mengikuti urutan unitnya.
     *
     * @param  Collection<int, Business>  $businesses
     * @return Collection<int, Faq>
     */
    private function faqs(Collection $businesses): Collection
    {
        return BusinessScope::withoutBusinessScope(
            Faq::query()
                ->whereIn('business_id', $businesses->pluck('id'))
                ->active()
                ->ordered()
                ->with('business:id,name,slug')
        )->get();
    }

    /**
     * Produk unggulan dari seluruh unit bisnis aktif.
     *
     * Setiap unit/business_id mengambil FEATURED_PER_BUSINESS produk terbaru
     * supaya unit dengan katalog lebih besar tidak menutupi unit lain.
     * Scope dinonaktifkan per unit karena halaman ini memang lintas tenant.
     *
     * @param  Collection<int, Business>  $businesses
     * @return Collection<int, Product>
     */
    private function featuredProducts(Collection $businesses): Collection
    {
        return $businesses
            ->flatMap(fn (Business $business) => BusinessScope::withoutBusinessScope(
                Product::query()
                    ->where('business_id', $business->getKey())
                    ->where('is_active', true)
                    ->where('stock', '>', 0)
                    ->with(['business:id,name,slug', 'category:id,name'])
                    ->latest('id')
                    ->limit(self::FEATURED_PER_BUSINESS)
            )->get(self::PRODUCT_COLUMNS))
            ->values();
    }
}
