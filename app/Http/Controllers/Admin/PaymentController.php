<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Support\BookingPeriod;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen pembayaran admin (PRD section 26, ROADMAP 4.6).
 *
 * Halaman ini adalah meja kerja verifikasi: admin memeriksa bukti transfer yang
 * diunggah penyewa, lalu memutuskan lunas atau ditolak dengan alasan. Cash
 * tidak punya bukti dan berjalan langsung dari `belum_dibayar` ke `lunas` saat
 * uang diterima.
 *
 * Semua query ter-scope ke unit bisnis admin lewat `BusinessScope` pada model
 * `Payment` (BR-05), jadi pembayaran unit lain berakhir sebagai 404, bukan 403.
 * Keputusan statusnya sendiri dipegang `PaymentVerificationService` supaya
 * `payments.status` dan salinan `bookings.payment_status` tidak pernah
 * berbeda.
 */
class PaymentController extends Controller
{
    /**
     * Jumlah pembayaran per halaman.
     */
    public const PER_PAGE = 20;

    /**
     * Nilai filter yang berarti "semua".
     */
    private const ALL = 'semua';

    /**
     * Daftar pembayaran dengan filter status dan metode.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $filters = $this->filters($request);

        return Inertia::render('admin/payments/index', [
            'payments' => $this->rows($business, $filters),
            'filters' => $filters,
            'statusOptions' => array_map(
                fn (PaymentStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                PaymentStatus::cases(),
            ),
            'methodOptions' => array_map(
                fn (PaymentMethodType $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ],
                PaymentMethodType::all(),
            ),
        ]);
    }

    /**
     * Tandai pembayaran lunas.
     *
     * Dipakai untuk dua jalur yang sah: QRIS dan transfer yang buktinya sudah
     * diperiksa, dan cash yang uangnya diterima langsung di lokasi.
     */
    public function verify(VerifyPaymentRequest $request, Payment $payment): RedirectResponse
    {
        app(PaymentVerificationService::class)->verify($payment);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pembayaran Rp '.number_format((int) $payment->amount, 0, ',', '.')
                .' untuk booking '.$this->bookingCode($payment).' ditandai Lunas.',
        ]);

        return back();
    }

    /**
     * Tolak pembayaran yang buktinya tidak bisa diverifikasi.
     *
     * Alasan penolakan ikut disimpan supaya penyewa tahu apa yang harus
     * diperbaiki, bukan sekadar melihat statusnya berubah.
     */
    public function reject(RejectPaymentRequest $request, Payment $payment): RedirectResponse
    {
        app(PaymentVerificationService::class)->reject($payment, $request->reason());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pembayaran untuk booking '.$this->bookingCode($payment).' ditolak.',
        ]);

        return back();
    }

    /**
     * Daftar pembayaran beserta filter yang sudah diterapkan.
     *
     * Pembayaran terbaru tampil lebih dulu karena yang menunggu verifikasi
     * datang dari sana. Paginator bawaan dipakai untuk query-nya, lalu
     * dipetakan satu kali; `links` tidak dikirim karena labelnya berisi markup
     * navigasi yang sudah dirender server.
     *
     * @param  array{status: string|null, method: string|null}  $filters
     * @return array<string, mixed>
     */
    private function rows(Business $business, array $filters): array
    {
        $query = Payment::query()
            ->forBusiness($business)
            ->with(['booking.customer'])
            ->orderByDesc('payments.id');

        if ($filters['status'] !== null) {
            $query->where('payments.status', $filters['status']);
        }

        if ($filters['method'] !== null) {
            $query->where('payments.method', $filters['method']);
        }

        $payments = $query
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'data' => $payments->getCollection()
                ->map(fn (Payment $payment): array => $this->row($payment))
                ->values()
                ->all(),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'from' => $payments->firstItem(),
            'to' => $payments->lastItem(),
            'total' => $payments->total(),
            'per_page' => $payments->perPage(),
        ];
    }

    /**
     * Satu baris daftar pembayaran.
     *
     * `can_verify` dan `can_reject` dihitung dari aturan transisi yang sama
     * dengan service, supaya tombol yang tampil tidak pernah menawarkan
     * keputusan yang pasti ditolak backend.
     *
     * @return array<string, mixed>
     */
    private function row(Payment $payment): array
    {
        $booking = $payment->booking;
        $customer = $booking?->customer;
        $verifier = $payment->verifier;

        return [
            'id' => (int) $payment->getKey(),
            'booking_code' => $this->bookingCode($payment),
            'customer_name' => $customer instanceof Customer ? $customer->name : '-',
            'method' => $payment->method->value,
            'method_label' => $payment->method->label(),
            'amount' => (int) $payment->amount,
            'amount_label' => number_format((int) $payment->amount, 0, ',', '.'),
            'proof_url' => $payment->proof_url,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'rejection_reason' => $payment->rejection_reason,
            'can_verify' => $payment->status->canTransitionTo(PaymentStatus::Lunas),
            'can_reject' => $payment->status->canTransitionTo(PaymentStatus::Ditolak),
            'created_at_label' => BookingPeriod::readableShortDate($payment->created_at),
            'verified_at_label' => $this->timestampLabel($payment->verified_at),
            'verified_by' => $verifier instanceof User ? $verifier->name : null,
        ];
    }

    /**
     * Filter dari query string, sudah ternormalisasi.
     *
     * Nilai yang tidak valid dibuang, bukan dibalas 422. Daftar pembayaran
     * adalah halaman kerja, bukan form, dan admin tidak boleh terkunci karena
     * tautan yang disalin tidak lengkap.
     *
     * @return array{status: string|null, method: string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'status' => $this->statusFilter($request->query('status')),
            'method' => $this->methodFilter($request->query('method')),
        ];
    }

    private function statusFilter(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === self::ALL) {
            return null;
        }

        return PaymentStatus::tryFrom($value)?->value;
    }

    private function methodFilter(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === self::ALL) {
            return null;
        }

        return PaymentMethodType::tryFrom($value)?->value;
    }

    /**
     * Kode booking pemilik pembayaran.
     *
     * Relasi booking dan customer di-eager-load, jadi label ini tidak memicu
     * query tambahan per baris.
     */
    private function bookingCode(Payment $payment): string
    {
        $booking = $payment->booking;

        return $booking instanceof Booking ? (string) $booking->booking_code : '-';
    }

    /**
     * Label waktu keputusan admin, atau null kalau belum diputuskan.
     *
     * Tipe parameternya `CarbonInterface`, bukan `Carbon`, karena cast
     * `datetime` mengembalikan `CarbonImmutable` di aplikasi ini.
     */
    private function timestampLabel(?CarbonInterface $moment): ?string
    {
        return $moment?->copy()->locale('id')->translatedFormat('j F Y H:i');
    }
}
