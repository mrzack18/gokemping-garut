<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Perubahan status booking oleh admin (ROADMAP 4.4, PRD section 21).
 *
 * Semua perubahan status lewat kelas ini, bukan langsung dari controller,
 * karena satu perubahan status selalu punya tiga efek: mengubah
 * `bookings.booking_status`, mengisi satu dari timestamp tahapan, dan menulis
 * satu baris riwayat. Kalau salah satu dilupakan di tempat lain, riwayat dan
 * data utama bisa berbeda pendapat.
 *
 * Perubahan ditulis dalam satu transaksi dengan `lockForUpdate` pada baris
 * booking. Tanpa lock itu, dua admin yang menekan tombol yang sama di saat
 * hampir bersamaan bisa sama-sama membaca status lama, lalu sama-sama
 * menjalankan tahap yang sama dan sama-sama menulis baris riwayat, sehingga
 * riwayat menampilkan tahap yang dilewati padahal tidak ada yang hacerlo.
 */
final class BookingStatusService
{
    /**
     * Majukan booking satu tahap.
     *
     * @throws InvalidArgumentException kalau status sekarang sudah tahap
     *                                  terakhir, yaitu selesai atau dibatalkan
     */
    public function advance(Booking $booking): Booking
    {
        return $this->transition($booking, $booking->booking_status->next(), null);
    }

    /**
     * Batalkan booking dan tutup pembayarannya kalau belum diverifikasi.
     *
     * @throws InvalidArgumentException kalau booking sudah selesai atau sudah
     *                                  dibatalkan
     */
    public function cancel(Booking $booking, string $reason): Booking
    {
        return $this->transition($booking, BookingStatus::Dibatalkan, $reason);
    }

    /**
     * Satu-satunya tempat yang mengubah status booking.
     *
     * @param  BookingStatus|null  $target  Null berarti tidak ada tahap berikutnya.
     */
    private function transition(Booking $booking, ?BookingStatus $target, ?string $note): Booking
    {
        return DB::transaction(function () use ($booking, $target, $note): Booking {
            $locked = $this->lock($booking);
            $from = $locked->booking_status;

            if ($target === null) {
                throw new InvalidArgumentException(
                    "Status booking {$from->value} tidak punya tahap berikutnya."
                );
            }

            if (! $from->canTransitionTo($target)) {
                throw new InvalidArgumentException(
                    "Status booking {$from->value} tidak bisa diubah menjadi {$target->value}."
                );
            }

            $attributes = [
                'booking_status' => $target,
                ...$this->timestampFor($target),
            ];

            if ($target === BookingStatus::Dibatalkan) {
                $attributes['cancellation_reason'] = $note;
                $attributes['payment_status'] = $this->cancelPayment($locked, $note);
            }

            $locked->fill($attributes)->save();

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $target,
                'note' => $note,
                'changed_by' => $this->changedBy(),
            ]);

            return $locked;
        }, 3);
    }

    /**
     * Timestamp tahapan yang diisi saat status berubah.
     *
     * `confirmed_at` sampai `cancelled_at` sudah ada di tabel, jadi riwayat tidak
     * perlu menyimpan waktunya sendiri: kapan sebuah tahap terjadi dibaca dari
     * timestamp yang sesuai, dan riwayatnya sendiri cukup menyimpan urutan,
     * alasan, dan siapa yang mengubahnya.
     *
     * @return array<string, mixed>
     */
    private function timestampFor(BookingStatus $target): array
    {
        return match ($target) {
            BookingStatus::Dikonfirmasi => ['confirmed_at' => now()],
            BookingStatus::SedangDisewa => ['started_at' => now()],
            BookingStatus::Selesai => ['completed_at' => now()],
            BookingStatus::Dibatalkan => ['cancelled_at' => now()],
            BookingStatus::MenungguKonfirmasi => [],
        };
    }

    /**
     * Tutup pembayaran yang belum diverifikasi, dan biarkan yang sudah final.
     *
     * Pembayaran `lunas` sengaja tidak diubah. Uang sudah diterima, jadi status
     * pembayarannya bukan milik booking yang sedang dibatalkan, dan pengembalian
     * uang diputuskan manual oleh admin. Membalik `lunas` jadi `ditolak` di sini
     * akan ikut mengubah angka pendapatan dashboard, karena dashboard hanya
     * menghitung pembayaran `lunas`.
     *
     * Pembayaran `belum_dibayar` dan `menunggu_verifikasi` ditutup jadi `ditolak`
     * dengan alasan yang menyebut pembatalan booking, supaya admin yang membuka
     * daftar pembayaran tidak melihat bukti yang sudah tidak berlaku masih
     * menunggu verifikasi.
     */
    private function cancelPayment(Booking $booking, ?string $note): PaymentStatus
    {
        $payment = $booking->payment()->lockForUpdate()->first();

        if ($payment === null) {
            return $booking->payment_status;
        }

        if (! $this->stillOpen($payment->status)) {
            return $payment->status;
        }

        $payment->update([
            'status' => PaymentStatus::Ditolak,
            'rejection_reason' => 'Booking '.$booking->booking_code.' dibatalkan'
                .($note === null || $note === '' ? '.' : ': '.$note),
        ]);

        return PaymentStatus::Ditolak;
    }

    /**
     * Status pembayaran yang masih bisa ditutup karena bookingnya dibatalkan.
     *
     * `lunas` berarti uangnya sudah masuk, dan `ditolak` sudah final. Menutup
     * `ditolak` lagi hanya menulis ulang alasan yang sudah ada tanpa memperbaiki
     * apa pun, jadi tidak ada gunanya.
     */
    private function stillOpen(PaymentStatus $status): bool
    {
        return $status === PaymentStatus::BelumDibayar
            || $status === PaymentStatus::MenungguVerifikasi;
    }

    /**
     * Baris booking yang dikunci untuk dibaca ulang di dalam transaksi.
     *
     * Status dibaca dari database, bukan dari instance yang dioper controller.
     * Instance itu sudah dimuat sebelum transaksi dimulai, jadi membacanya
     * berarti memvalidasi status lama yang bisa saja sudah berubah.
     */
    private function lock(Booking $booking): Booking
    {
        return Booking::query()
            ->whereKey($booking->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Id admin yang melakukan perubahan.
     *
     * null kalau tidak ada admin yang login. Status booking juga pernah diubah
     * di luar halaman admin pada saat seeding atau perbaikan data, dan riwayat
     * tidak boleh gagal ditulis hanya karena tidak ada yang dikenal sebagai
     * pelakunya.
     */
    private function changedBy(): ?int
    {
        $user = auth()->user();

        return $user instanceof User ? (int) $user->getAuthIdentifier() : null;
    }
}
