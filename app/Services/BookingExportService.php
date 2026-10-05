<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Support\BookingFilters;
use App\Support\Spreadsheets;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Ekspor Excel daftar booking (ROADMAP 5.2).
 *
 * Baris yang diekspor memakai filter yang sama dengan halaman daftar booking
 * lewat `BookingFilters`, jadi berkas yang diunduh selalu berisi baris yang
 * sedang dilihat admin — bukan seluruh riwayat.
 *
 * Nama produk dibaca dari `booking_items`, bukan dari `products`, supaya
 * booking lama tetap terbaca apa adanya walaupun produknya sudah diubah atau
 * dihapus (BR-09).
 */
final class BookingExportService
{
    /**
     * Isi berkas XLSX berisi booking yang cocok dengan filter, sebagai string
     * biner.
     *
     * @param  array{q: string, status: string|null, from: string|null, to: string|null}  $filters
     */
    public function excel(Business $business, array $filters): string
    {
        $query = Booking::query()
            ->forBusiness($business)
            ->with(['customer', 'items'])
            ->orderByDesc('bookings.start_date')
            ->orderByDesc('bookings.id');

        BookingFilters::apply($query, $filters);

        $bookings = $query->get();

        return Spreadsheets::bytes(function (Writer $writer) use ($bookings): void {
            $writer->addRow(Row::fromValuesWithStyle(
                $this->headers(),
                (new Style)->withFontBold(true),
            ));

            foreach ($bookings as $booking) {
                $writer->addRow(Row::fromValues($this->row($booking)));
            }
        });
    }

    /**
     * @return list<string>
     */
    private function headers(): array
    {
        return [
            'Kode Booking',
            'Tanggal Masuk',
            'Penyewa',
            'WhatsApp',
            'Produk',
            'Jumlah Unit',
            'Mulai Sewa',
            'Selesai Sewa',
            'Total (Rp)',
            'Metode Pembayaran',
            'Status Pembayaran',
            'Status Booking',
        ];
    }

    /**
     * @return list<string|int>
     */
    private function row(Booking $booking): array
    {
        $customer = $booking->customer;

        return [
            (string) $booking->booking_code,
            $booking->created_at?->format('d/m/Y') ?? '-',
            $customer instanceof Customer ? $customer->name : '-',
            $customer instanceof Customer ? $customer->whatsapp : '-',
            $this->productLabel($booking),
            (int) $booking->items->sum('quantity'),
            $booking->start_date->format('d/m/Y'),
            $booking->end_date->format('d/m/Y'),
            (int) $booking->total,
            $booking->payment_method->label(),
            $booking->payment_status->label(),
            $booking->booking_status->label(),
        ];
    }

    /**
     * Ringkasan nama produk, mis. "Tenda Dome, Kursi Lipat".
     *
     * Hanya nama, tanpa jumlah per baris: jumlah total unit sudah punya kolom
     * sendiri, dan menyebutnya dua kali membuat angka di berkas terlihat
     * berbeda dari yang dihitung.
     */
    private function productLabel(Booking $booking): string
    {
        $labels = [];

        foreach ($booking->items as $item) {
            $labels[] = $item->product_name;
        }

        return $labels === [] ? '-' : implode(', ', $labels);
    }
}
