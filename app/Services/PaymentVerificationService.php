<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Verifikasi dan penolakan pembayaran oleh admin (PRD section 26, ROADMAP 4.6).
 *
 * Sama seperti perubahan status booking, keputusan pembayaran punya dua efek
 * yang harus selalu berjalan bersama: status di baris `payments` dan salinan
 * status di `bookings.payment_status`. Salinan itu yang dipakai daftar booking
 * dan dashboard, jadi kalau salah satu tertinggal, dua halaman bisa menampilkan
 * status pembayaran yang berbeda untuk transaksi yang sama.
 *
 * Keputusan ditulis dalam satu transaksi dengan `lockForUpdate` pada baris
 * pembayaran. Status dibaca ulang dari database di dalam transaksi supaya dua
 * admin yang memutuskan bersamaan tidak sama-sama lolos, dan aturan
 * perpindahannya dipegang `PaymentStatus::canTransitionTo()`.
 */
final class PaymentVerificationService
{
    /**
     * Tandai pembayaran lunas.
     *
     * Cash memulai siklusnya di `belum_dibayar` dan langsung menuju `lunas`
     * saat uang diterima (PRD section 26). QRIS dan transfer harus lewat
     * `menunggu_verifikasi` karena buktinya perlu diperiksa dulu.
     *
     * @throws InvalidArgumentException kalau pembayaran sudah final
     */
    public function verify(Payment $payment): Payment
    {
        return $this->transition($payment, PaymentStatus::Lunas, null);
    }

    /**
     * Tolak pembayaran yang buktinya tidak bisa diverifikasi.
     *
     * @throws InvalidArgumentException kalau pembayaran belum menunggu verifikasi
     *                                  atau sudah final
     */
    public function reject(Payment $payment, string $reason): Payment
    {
        return $this->transition($payment, PaymentStatus::Ditolak, $reason);
    }

    /**
     * Satu-satunya tempat yang mengubah status pembayaran.
     *
     * @param  string|null  $reason  Alasan penolakan, null untuk verifikasi.
     */
    private function transition(Payment $payment, PaymentStatus $target, ?string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $target, $reason): Payment {
            $locked = $this->lock($payment);
            $from = $locked->status;

            if (! $from->canTransitionTo($target)) {
                throw new InvalidArgumentException(
                    "Pembayaran berstatus {$from->value} tidak bisa diubah menjadi {$target->value}."
                );
            }

            $attributes = [
                'status' => $target,
                'rejection_reason' => $reason,
                'verified_at' => now(),
                'verified_by' => $this->changedBy(),
            ];

            $locked->fill($attributes)->save();

            $this->syncBooking($locked, $target);

            return $locked;
        }, 3);
    }

    /**
     * Salin status ke booking yang sama.
     *
     * `bookings.payment_status` adalah kolom ringkasan yang dibaca daftar
     * booking dan dashboard. Nilainya selalu ditulis dari keputusan ini, bukan
     * dihitung ulang dari `payments`, supaya tidak ada dua sumber kebenaran
     * yang bisa berbeda saat transaksi berjalan.
     */
    private function syncBooking(Payment $payment, PaymentStatus $status): void
    {
        $booking = $payment->booking()->lockForUpdate()->first();

        if (! $booking instanceof Booking) {
            return;
        }

        $booking->fill(['payment_status' => $status])->save();
    }

    /**
     * Baris pembayaran yang dikunci untuk dibaca ulang di dalam transaksi.
     */
    private function lock(Payment $payment): Payment
    {
        return Payment::query()
            ->whereKey($payment->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Id admin yang memutuskan.
     *
     * Selalu terisi di halaman admin karena route-nya di balik middleware auth.
     * Pengembalian null dipakai sebagai pengaman kalau keputusan yang sama
     * dipanggil dari luar konteks admin, mis. perbaikan data.
     */
    private function changedBy(): ?int
    {
        $user = auth()->user();

        return $user instanceof User ? (int) $user->getAuthIdentifier() : null;
    }
}
