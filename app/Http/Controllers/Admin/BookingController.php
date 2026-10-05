<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingStatusService;
use App\Support\BookingPeriod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen booking admin (PRD section 24, ROADMAP 4.4).
 *
 * Semua query ter-scope ke unit bisnis admin yang sedang login lewat
 * `BusinessScope` pada model `Booking` (BR-05). Route model binding memakai
 * `booking_code`, jadi kode booking milik unit lain berakhir sebagai 404, bukan
 * 403, dan admin tidak bisa memeriksa keberadaan booking di luar scope-nya.
 *
 * `Customer` tidak memakai `BusinessScope` sama sekali: satu orang bisa menyewa
 * di kedua unit bisnis. Isolasi penyewa dilakukan lewat booking-nya, bukan
 * lewat baris customer.
 *
 * Harga dan nama produk selalu dibaca dari `booking_items`, bukan dari tabel
 * `products`, supaya booking lama tetap terbaca apa adanya walaupun produknya
 * sudah diubah atau dihapus (BR-09).
 */
class BookingController extends Controller
{
    /**
     * Jumlah booking per halaman.
     */
    public const PER_PAGE = 20;

    /**
     * Nilai filter yang berarti "semua" pada filter status.
     */
    private const ALL_STATUSES = 'semua';

    /**
     * Daftar booking dengan pencarian, filter status, dan filter tanggal mulai
     * sewa.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $filters = $this->filters($request);

        return Inertia::render('admin/bookings/index', [
            'bookings' => $this->rows($business, $filters),
            'filters' => $filters,
            'statusOptions' => array_map(
                fn (BookingStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                BookingStatus::cases(),
            ),
        ]);
    }

    /**
     * Detail satu booking: penyewa, produk, pembayaran, dan riwayat status.
     */
    public function show(Booking $booking): Response
    {
        $booking->load(['customer', 'items', 'payment.verifier']);

        return Inertia::render('admin/bookings/show', [
            'booking' => $this->detail($booking),
            'history' => $this->history($booking),
        ]);
    }

    /**
     * Majukan status booking satu tahap.
     *
     * Frontend hanya mengirim tahap berikutnya, jadi daftar status tidak pernah
     * dikirim ke browser dan server tidak mempercayai pilihan admin untuk
     * melompati tahap atau mundur. Aturan transisinya dipegang
     * `BookingStatus::canTransitionTo()`.
     */
    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $target = $request->status();

        if ($target === null) {
            return to_route('admin.bookings.show', $booking);
        }

        app(BookingStatusService::class)->advance($booking);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Booking '.$booking->booking_code.' ditandai "'.$target->label().'".',
        ]);

        return to_route('admin.bookings.show', $booking);
    }

    /**
     * Batalkan booking, sekaligus menutup pembayarannya yang belum diverifikasi.
     *
     * Pembatalan tidak menghapus data apa pun. Barang yang sebelumnya menahan stok
     * langsung terbaca tersedia lagi karena `Booking::scopeHoldingStock()` tidak
     * menghitung status `dibatalkan`, dan riwayat booking tetap utuh.
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): RedirectResponse
    {
        app(BookingStatusService::class)->cancel($booking, $request->reason());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Booking '.$booking->booking_code.' dibatalkan. '
                .'Barang yang di-booking tersedia lagi untuk tanggal tersebut.',
        ]);

        return to_route('admin.bookings.show', $booking);
    }

    /**
     * Query booking dasar yang selalu ter-scope ke satu unit bisnis.
     *
     * @return Builder<Booking>
     */
    private function query(Business $business): Builder
    {
        return Booking::query()
            ->forBusiness($business)
            ->with(['customer', 'items', 'payment']);
    }

    /**
     * Daftar booking beserta filter yang sudah diterapkan, dengan bentuk yang
     * sudah siap tampil.
     *
     * Paginator bawaan Laravel dipakai untuk query-nya, lalu dipetakan satu
     * kali di sini. Closure `map()` bawaan Laravel dieksekusi ulang setiap kali
     * paginator di-resolve, termasuk saat `toArray()` dipanggil untuk
     * serialisasi Inertia.
     *
     * `links` bawaan Laravel tidak dikirim. Labelnya berisi markup navigasi
     * yang sudah dirender server, sedangkan halaman ini memakai daftar nomor
     * halaman sendiri supaya filter dan penomoran tetap bisa ikut di URL.
     *
     * @param  array{q: string, status: string|null, from: string|null, to: string|null}  $filters
     * @return array<string, mixed>
     */
    private function rows(Business $business, array $filters): array
    {
        $query = $this->query($business);

        if ($filters['q'] !== '') {
            $this->search($query, $filters['q']);
        }

        if ($filters['status'] !== null) {
            $query->where('bookings.booking_status', $filters['status']);
        }

        $this->period($query, $filters['from'], $filters['to']);

        $bookings = $query
            ->orderByDesc('bookings.start_date')
            ->orderByDesc('bookings.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'data' => $bookings->getCollection()
                ->map(fn (Booking $booking): array => $this->row($booking))
                ->values()
                ->all(),
            'current_page' => $bookings->currentPage(),
            'last_page' => $bookings->lastPage(),
            'from' => $bookings->firstItem(),
            'to' => $bookings->lastItem(),
            'total' => $bookings->total(),
            'per_page' => $bookings->perPage(),
        ];
    }

    /**
     * Pencarian bebas pada kode booking, nama dan WhatsApp penyewa, serta nama
     * produk yang di-booking.
     *
     * Nama produk dicari lewat `booking_items`, bukan lewat `products`. Kalau
     * produknya sudah dihapus atau namanya sudah diganti, booking lama tetap
     * bisa dicari dengan nama yang tersimpan di booking itu (BR-09).
     *
     * @param  Builder<Booking>  $query
     */
    private function search(Builder $query, string $term): void
    {
        $term = '%'.addcslashes($term, '\\%_').'%';

        $query->where(function (Builder $query) use ($term): void {
            $query->where('bookings.booking_code', 'like', $term)
                ->orWhereHas('customer', function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('whatsapp', 'like', $term);
                })
                ->orWhereHas('items', function (Builder $query) use ($term): void {
                    $query->where('product_name', 'like', $term);
                });
        });
    }

    /**
     * Filter tanggal periode sewa.
     *
     * Yang difilter adalah tanggal mulai sewa, bukan tanggal booking dibuat.
     * Admin yang bekerja dengan jadwal bertanya "barang ini terpakai tanggal
     * berapa", sedangkan tanggal booking dibuat jarang menjawab pertanyaan itu:
     * booking untuk bulan depan bisa dibuat minggu ini.
     *
     * @param  Builder<Booking>  $query
     */
    private function period(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate('bookings.start_date', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('bookings.start_date', '<=', $to);
        }
    }

    /**
     * Filter dari query string, sudah ternormalisasi.
     *
     * Nilai yang tidak valid dibuang, bukan dibalas 422. Daftar booking adalah
     * halaman kerja, bukan form: admin tidak boleh terkunci karena tautan yang
     * disalin tidak lengkap atau tanggalnya salah ketik.
     *
     * @return array{q: string, status: string|null, from: string|null, to: string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->statusFilter($request->query('status')),
            'from' => $this->dateFilter($request->query('from')),
            'to' => $this->dateFilter($request->query('to')),
        ];
    }

    /**
     * Status dari query string, atau null untuk "semua status".
     *
     * Daftar case enum adalah sumber kebenarannya, jadi filter ini tidak bisa
     * mengarah ke status yang tidak ada di enum.
     */
    private function statusFilter(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === self::ALL_STATUSES) {
            return null;
        }

        return BookingStatus::tryFrom($value)?->value;
    }

    /**
     * Tanggal dari query string dalam bentuk `YYYY-MM-DD`, atau null kalau tidak
     * valid.
     *
     * Tanggal tidak dipakai langsung dari query string, karena `whereDate` akan
     * memperlakukannya sebagai teks dan nilainya bisa disisipkan apa adanya.
     * `Carbon::createFromFormat()` dipakai supaya tanggal yang tidak benar-benar
     * ada, seperti `2026-02-31`, ditolak di sini dan tidak sampai jadi filter
     * yang diam-diam tidak cocok dengan apa pun.
     */
    private function dateFilter(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $date->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * Satu baris daftar booking.
     *
     * @return array<string, mixed>
     */
    private function row(Booking $booking): array
    {
        $customer = $booking->customer;
        $payment = $booking->payment;

        return [
            'booking_code' => $booking->booking_code,
            'customer_name' => $customer instanceof Customer ? $customer->name : '-',
            'customer_whatsapp' => $customer instanceof Customer ? $customer->whatsapp : null,
            'product_label' => $this->productLabel($booking),
            'quantity' => (int) $booking->items->sum('quantity'),
            'period_label' => BookingPeriod::readableShortDate($booking->start_date)
                .' - '.BookingPeriod::readableShortDate($booking->end_date),
            'total' => (int) $booking->total,
            'total_label' => number_format((int) $booking->total, 0, ',', '.'),
            'payment_method_label' => $booking->payment_method->label(),
            'payment_status' => $booking->payment_status->value,
            'payment_status_label' => $booking->payment_status->label(),
            'status' => $booking->booking_status->value,
            'status_label' => $booking->booking_status->label(),
            'created_at_label' => BookingPeriod::readableShortDate($booking->created_at),
        ];
    }

    /**
     * Ringkasan nama produk dan jumlahnya, mis. "Tenda Dome x2".
     *
     * Booking yang produknya lebih dari satu tidak mungkin terjadi lewat halaman
     * publik, tapi tabel `booking_items` tidak ikut memaksa itu, jadi daftar ini
     * tetap menyebut semua baris yang ada daripada hanya yang pertama.
     */
    private function productLabel(Booking $booking): string
    {
        $labels = [];

        foreach ($booking->items as $item) {
            $quantity = (int) $item->quantity;
            $labels[] = $quantity > 1
                ? $item->product_name.' x'.$quantity
                : $item->product_name;
        }

        return $labels === [] ? '-' : implode(', ', $labels);
    }

    /**
     * Detail booking untuk halaman detail.
     *
     * @return array<string, mixed>
     */
    private function detail(Booking $booking): array
    {
        $customer = $booking->customer;
        $payment = $booking->payment;

        return [
            'booking_code' => $booking->booking_code,
            'status' => $booking->booking_status->value,
            'status_label' => $booking->booking_status->label(),
            'next_status' => $booking->booking_status->next()?->value,
            'next_status_label' => $booking->booking_status->next()?->label(),
            'is_cancellable' => $booking->booking_status->isCancellable(),
            'period' => [
                'start_date' => $booking->start_date->toDateString(),
                'end_date' => $booking->end_date->toDateString(),
                'start_date_label' => BookingPeriod::readableDate($booking->start_date),
                'end_date_label' => BookingPeriod::readableDate($booking->end_date),
                'total_days' => (int) $booking->total_days,
                'total_days_label' => $this->durationLabel((int) $booking->total_days),
            ],
            'items' => $booking->items
                ->map(fn (BookingItem $item): array => [
                    'product_name' => $item->product_name,
                    'price' => (int) $item->price,
                    'price_label' => number_format((int) $item->price, 0, ',', '.'),
                    'price_unit' => $item->price_unit,
                    'quantity' => (int) $item->quantity,
                    'total_days' => (int) $item->total_days,
                    'subtotal' => (int) $item->subtotal,
                    'subtotal_label' => number_format((int) $item->subtotal, 0, ',', '.'),
                ])
                ->values()
                ->all(),
            'total' => (int) $booking->total,
            'total_label' => number_format((int) $booking->total, 0, ',', '.'),
            'subtotal' => (int) $booking->subtotal,
            'subtotal_label' => number_format((int) $booking->subtotal, 0, ',', '.'),
            'notes' => $booking->notes,
            'renter_count' => $booking->renter_count === null ? null : (int) $booking->renter_count,
            'cancellation_reason' => $booking->cancellation_reason,
            'timestamps' => [
                'created_at_label' => BookingPeriod::readableDate($booking->created_at),
                'confirmed_at_label' => $this->timestampLabel($booking->confirmed_at),
                'started_at_label' => $this->timestampLabel($booking->started_at),
                'completed_at_label' => $this->timestampLabel($booking->completed_at),
                'cancelled_at_label' => $this->timestampLabel($booking->cancelled_at),
            ],
            'customer' => $this->customer($customer),
            'payment' => $this->payment($payment),
        ];
    }

    /**
     * Data penyewa, dengan NIK tetap disamarkan (PRD section 33).
     *
     * NIK disamarkan di halaman detail booking supaya admin tetap bisa mengenali
     * penyewanya tanpa menampilkan NIK penuh di layar yang bisa di-screenshot
     * atau dibaca orang lain. NIK penuh masih bisa dicari di Manajemen Penyewa
     * (ROADMAP 4.5) yang memang untuk itu.
     *
     * @return array<string, mixed>|null
     */
    private function customer(?Customer $customer): ?array
    {
        if (! $customer instanceof Customer) {
            return null;
        }

        return [
            'name' => $customer->name,
            'whatsapp' => $customer->whatsapp,
            'email' => $customer->email,
            'nik' => $customer->masked_nik,
            'address' => $customer->address,
            'city' => $customer->city,
        ];
    }

    /**
     * Data pembayaran beserta bukti dan alasan penolakannya.
     *
     * @return array<string, mixed>|null
     */
    private function payment(?Payment $payment): ?array
    {
        if (! $payment instanceof Payment) {
            return null;
        }

        $verifier = $payment->verifier;

        return [
            'method_label' => $payment->method->label(),
            'amount' => (int) $payment->amount,
            'amount_label' => number_format((int) $payment->amount, 0, ',', '.'),
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'proof_url' => $payment->proof_url,
            'rejection_reason' => $payment->rejection_reason,
            'verified_at_label' => $this->timestampLabel($payment->verified_at),
            'verified_by' => $verifier instanceof User ? $verifier->name : null,
            'needs_verification' => $payment->status === PaymentStatus::MenungguVerifikasi,
        ];
    }

    /**
     * Riwayat status booking, dari yang paling lama.
     *
     * @return list<array<string, mixed>>
     */
    private function history(Booking $booking): array
    {
        return array_values(
            $booking->statusHistories()
                ->with('author')
                ->get()
                ->map(fn (BookingStatusHistory $history): array => [
                    'from_status' => $history->from_status?->value,
                    'from_status_label' => $history->from_status?->label(),
                    'to_status' => $history->to_status->value,
                    'to_status_label' => $history->to_status->label(),
                    'note' => $history->note,
                    'author' => $history->author instanceof User
                        ? $history->author->name
                        : null,
                    'created_at_label' => BookingPeriod::readableDate($history->created_at),
                ])
                ->all()
        );
    }

    /**
     * Label durasi sewa, contoh "2 hari".
     */
    private function durationLabel(int $days): string
    {
        return $days.' hari';
    }

    /**
     * Label timestamp, atau null kalau tahapnya belum terjadi.
     *
     * Tipe parameternya `CarbonInterface`, bukan `Carbon`, karena aplikasi
     * mengaktifkan `CarbonImmutable` di `AppServiceProvider`, jadi cast
     * `datetime` mengembalikan immutable.
     */
    private function timestampLabel(?CarbonInterface $moment): ?string
    {
        return $moment?->copy()->locale('id')->translatedFormat('j F Y H:i');
    }
}
