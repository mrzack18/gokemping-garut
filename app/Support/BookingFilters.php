<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Normalisasi filter daftar booking (ROADMAP 4.4 & 5.2).
 *
 * Dipakai bersama oleh halaman daftar booking dan ekspor Excel-nya, supaya
 * berkas yang diunduh berisi baris yang sama dengan yang sedang dilihat admin.
 * Kalau keduanya menyusun query sendiri-sendiri, cepat atau lambat filter di
 * layar dan di berkas akan berbeda.
 *
 * Nilai yang tidak valid dibuang, bukan dibalas 422. Daftar booking adalah
 * halaman kerja, bukan form: admin tidak boleh terkunci karena tautan yang
 * disalin tidak lengkap atau tanggalnya salah ketik.
 */
final class BookingFilters
{
    /**
     * Nilai filter yang berarti "semua" pada filter status.
     */
    private const ALL_STATUSES = 'semua';

    /**
     * Filter dari query string, sudah ternormalisasi.
     *
     * @return array{q: string, status: string|null, from: string|null, to: string|null}
     */
    public static function fromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => self::status($request->query('status')),
            'from' => self::date($request->query('from')),
            'to' => self::date($request->query('to')),
        ];
    }

    /**
     * Terapkan filter ke query booking.
     *
     * @param  Builder<Booking>  $query
     * @param  array{q: string, status: string|null, from: string|null, to: string|null}  $filters
     */
    public static function apply(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            self::search($query, $filters['q']);
        }

        if ($filters['status'] !== null) {
            $query->where('bookings.booking_status', $filters['status']);
        }

        self::period($query, $filters['from'], $filters['to']);
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
    private static function search(Builder $query, string $term): void
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
    private static function period(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate('bookings.start_date', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('bookings.start_date', '<=', $to);
        }
    }

    /**
     * Status dari query string, atau null untuk "semua status".
     *
     * Daftar case enum adalah sumber kebenarannya, jadi filter ini tidak bisa
     * mengarah ke status yang tidak ada di enum.
     */
    private static function status(mixed $value): ?string
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
    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $date->format('Y-m-d') === $value ? $value : null;
    }
}
