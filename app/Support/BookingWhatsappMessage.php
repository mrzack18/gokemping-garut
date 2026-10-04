<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Business;

/**
 * Penyusun pesan WhatsApp yang meneruskan detail booking ke admin
 * (BR-06, PRD section 19).
 *
 * Pesan disusun di server, bukan di frontend, karena isinya berasal dari
 * database: nama produk, total, dan metode pembayaran. Kalau dirakit di browser,
 * nilainya bisa berbeda dari yang benar-benar disimpan di ROADMAP 3.11.
 *
 * Nomor tujuan selalu mengikuti `business.whatsapp` unit yang dipakai penyewa,
 * jadi penyewa Sewa Sepeda Garut tidak pernah mengirim detail booking-nya ke
 * admin GoKemping karena salah tombol.
 */
final class BookingWhatsappMessage
{
    /**
     * Pola `wa.me` resmi. Format `https://wa.me/<nomor>?text=<pesan>`.
     */
    private const BASE_URL = 'https://wa.me/';

    /**
     * Garis pemisah bagian. PRD section 19 memakai panjang yang berbeda-beda
     * untuk tiap judul; satu panjang seragam lebih enak dibaca di chat.
     */
    private const RULE = '━━━━━━━━━━━━━━━';

    /**
     * Penghalang untuk NIK. Panjang tetap supaya panjang NIK tidak ikut
     * terekspos dari jumlah titik.
     */
    private const MASK = '••••••••';

    /**
     * Jumlah digit NIK yang tetap ditampilkan di depan dan di belakang.
     */
    private const NIK_VISIBLE = 4;

    /**
     * Isi pesan WhatsApp untuk satu booking.
     *
     * Urutan bagian mengikuti PRD section 19: detail booking, data penyewa,
     * lalu metode pembayaran.
     */
    public static function build(Booking $booking, Business $business): string
    {
        $booking->loadMissing(['customer', 'items', 'payment']);

        $lines = [
            'Halo Admin '.$business->name.',',
            '',
            'Saya ingin melakukan booking sewa.',
            '',
            'DETAIL BOOKING',
            self::RULE,
            '',
            'Kode Booking: '.$booking->booking_code,
            '',
            'Produk:',
        ];

        foreach ($booking->items as $item) {
            $lines[] = '- '.$item->product_name.' ('.$item->quantity.' Unit)';
        }

        if ($booking->items->isEmpty()) {
            $lines[] = '-';
        }

        $lines[] = '';
        $lines[] = 'Jumlah:';
        $lines[] = $booking->items->sum('quantity').' Unit';
        $lines[] = '';
        $lines[] = 'Tanggal Sewa:';
        $lines[] = BookingPeriod::readableDate($booking->start_date).' - '.BookingPeriod::readableDate($booking->end_date);
        $lines[] = '';
        $lines[] = 'Durasi:';
        $lines[] = $booking->total_days.' Hari';
        $lines[] = '';
        $lines[] = 'Total:';
        $lines[] = 'Rp'.number_format((int) $booking->total, 0, ',', '.');

        foreach (self::customerLines($booking) as $line) {
            $lines[] = $line;
        }

        foreach (self::paymentLines($booking) as $line) {
            $lines[] = $line;
        }

        $lines[] = 'Mohon konfirmasi booking saya.';
        $lines[] = '';
        $lines[] = 'Terima kasih.';

        return implode("\n", $lines);
    }

    /**
     * Nomor WhatsApp admin unit tersebut, sudah dinormalkan ke `62...`.
     *
     * Mengembalikan `null` kalau nomor unit tidak bisa dipakai, supaya frontend
     * menyembunyikan tombol daripada membuka WhatsApp ke nomor yang salah.
     */
    public static function number(Business $business): ?string
    {
        return WhatsappNumber::normalize($business->whatsapp);
    }

    /**
     * Tautan `wa.me` yang sudah membawa isi pesan.
     *
     * Mengembalikan `null` kalau nomor admin tidak valid. Isi pesan tetap
     * dikirim ke frontend supaya penyewa bisa menyalinnya secara manual.
     */
    public static function link(Business $business, string $message): ?string
    {
        $number = self::number($business);

        if ($number === null) {
            return null;
        }

        return self::BASE_URL.$number.'?text='.rawurlencode($message);
    }

    /**
     * Blok DATA PENYEWA.
     *
     * `bookings.customer_id` tidak boleh null dan cascade hapus customer
     * dibatasi, jadi booking yang tersimpan pasti punya penyewa. NIK
     * ditampilkan tersamar: pesan ini dikirim lewat chat yang bisa diteruskan
     * dan di-screenshot, sementara admin tetap bisa membuka NIK lengkap dari
     * booking berdasarkan kode booking.
     *
     * @return list<string>
     */
    private static function customerLines(Booking $booking): array
    {
        $customer = $booking->customer;

        $address = $customer->address;

        if ($customer->city !== null && trim($customer->city) !== '') {
            $address .= ', '.$customer->city;
        }

        $lines = [
            '',
            'DATA PENYEWA',
            self::RULE,
            '',
            'Nama:',
            $customer->name,
            '',
            'No. WhatsApp:',
            $customer->whatsapp,
            '',
            'NIK:',
            self::mask($customer->nik),
            '',
            'Alamat:',
            $address,
        ];

        if ($booking->renter_count !== null) {
            $lines[] = '';
            $lines[] = 'Jumlah Penyewa:';
            $lines[] = $booking->renter_count.' orang';
        }

        if ($booking->notes !== null && trim($booking->notes) !== '') {
            $lines[] = '';
            $lines[] = 'Catatan Penyewa:';
            $lines[] = $booking->notes;
        }

        return $lines;
    }

    /**
     * Blok METODE PEMBAYARAN, termasuk status bukti (BR-08).
     *
     * @return list<string>
     */
    private static function paymentLines(Booking $booking): array
    {
        $method = $booking->payment_method;
        $proof = $booking->payment;

        $lines = [
            '',
            'METODE PEMBAYARAN',
            self::RULE,
            '',
            $method->label(),
            '',
            'Bukti pembayaran:',
        ];

        if ($proof?->proof !== null) {
            $lines[] = 'Sudah diupload';

            return $lines;
        }

        $lines[] = $method->requiresProof()
            ? 'Belum diupload'
            : 'Tidak diperlukan ('.$method->label().')';

        return $lines;
    }

    /**
     * NIK dengan sebagian digit diawalan dan diakhiri, sisanya disamar.
     */
    private static function mask(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) <= self::NIK_VISIBLE * 2) {
            return self::MASK;
        }

        return substr($digits, 0, self::NIK_VISIBLE)
            .self::MASK
            .substr($digits, -self::NIK_VISIBLE);
    }
}
