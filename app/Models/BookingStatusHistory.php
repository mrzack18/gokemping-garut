<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu perubahan status pada satu booking (ROADMAP 4.4).
 *
 * Baris ini ditulis setiap kali status booking berubah, jadi halaman detail
 * bisa menampilkan kronologi tanpa menebak dari timestamp di `bookings`.
 * Timestamp di `bookings` (`confirmed_at`, `started_at`, dan seterusnya)
 * menyimpan kapan tiap tahap terjadi, tapi tidak menyimpan urutan atau alasan
 * perubahan, dan tidak bisa membedakan perubahan yang ditolak dari perubahan
 * yang berhasil.
 *
 * Tabel ini tidak punya `business_id`. Riwayat selalu dibaca lewat booking-nya,
 * yang sudah ter-scope `BusinessScope` (BR-05), jadi menambahkan kolom tenant
 * di sini hanya menambah satu tempat yang bisa salah diisi.
 *
 * @property int $id
 * @property int $booking_id
 * @property BookingStatus|null $from_status
 * @property BookingStatus $to_status
 * @property string|null $note
 * @property int|null $changed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'booking_id',
    'from_status',
    'to_status',
    'note',
    'changed_by',
])]
class BookingStatusHistory extends Model
{
    /** @use HasFactory<BookingStatusHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
