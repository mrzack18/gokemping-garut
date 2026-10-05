<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Penyewa tidak memiliki business_id karena satu orang bisa menyewa di kedua
 * unit bisnis. Keterkaitan ke unit bisnis terjadi lewat booking.
 *
 * @property int $id
 * @property string $name
 * @property string $whatsapp
 * @property string|null $email
 * @property string $nik
 * @property string $address
 * @property string|null $city
 * @property string|null $notes
 * @property-read int|null $bookings_count Hanya terisi saat query memakai `withCount()`.
 * @property-read numeric-string|int|float|null $total_transaction Hanya terisi saat query memakai `withSum()`.
 */
#[Fillable([
    'name',
    'whatsapp',
    'email',
    'nik',
    'address',
    'city',
    'notes',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * NIK tidak boleh ditampilkan terbuka (PRD §33). Nilai aslinya tetap
     * disimpan agar admin dapat melakukan pencarian dan verifikasi.
     */
    /** @return Attribute<string, never> */
    protected function maskedNik(): Attribute
    {
        return Attribute::get(function (): string {
            $nik = (string) $this->nik;
            $length = mb_strlen($nik);

            if ($length <= 6) {
                return Str::repeat('*', $length);
            }

            return Str::repeat('*', $length - 6).mb_substr($nik, -6);
        });
    }
}
