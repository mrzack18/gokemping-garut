<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingStatusHistory>
 */
class BookingStatusHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'from_status' => BookingStatus::MenungguKonfirmasi,
            'to_status' => BookingStatus::Dikonfirmasi,
            'note' => null,
            'changed_by' => User::factory(),
        ];
    }

    /**
     * Baris pertama riwayat, yaitu saat booking dibuat.
     *
     * Baris ini tidak punya status sebelumnya dan tidak dicatat oleh admin,
     * karena booking dibuat oleh penyewa lewat halaman publik.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => null,
            'to_status' => BookingStatus::MenungguKonfirmasi,
            'note' => null,
            'changed_by' => null,
        ]);
    }

    /**
     * Baris pembatalan, lengkap dengan alasannya.
     */
    public function cancelled(string $reason = 'Dibatalkan oleh admin'): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => BookingStatus::Dikonfirmasi,
            'to_status' => BookingStatus::Dibatalkan,
            'note' => $reason,
        ]);
    }
}
