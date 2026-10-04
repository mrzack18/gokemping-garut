<?php

namespace Tests\Feature;

use App\Support\BookingPeriod;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingPeriodTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function periods(): array
    {
        return [
            'satu hari' => ['2026-10-10', '2026-10-11', 1],
            'dua hari sesuai PRD' => ['2026-10-10', '2026-10-12', 2],
            'tujuh hari' => ['2026-10-10', '2026-10-17', 7],
            'bulan berikutnya' => ['2026-10-30', '2026-11-02', 3],
            'melewati tahun' => ['2026-12-30', '2027-01-02', 3],
            'tanggal sama dihitung satu hari' => ['2026-10-10', '2026-10-10', 1],
        ];
    }

    #[DataProvider('periods')]
    public function test_durasi_mengikuti_selisih_tanggal(
        string $start,
        string $end,
        int $expected,
    ): void {
        $this->assertSame($expected, BookingPeriod::durationInDays($start, $end));
    }

    public function test_durasi_terbalik_tetap_dihitung_sebagai_selisih(): void
    {
        $this->assertSame(2, BookingPeriod::durationInDays('2026-10-12', '2026-10-10'));
    }

    public function test_total_mengalikan_harga_jumlah_dan_durasi(): void
    {
        $this->assertSame(200000, BookingPeriod::total(50000, 2, 2));
        $this->assertSame(300000, BookingPeriod::total(75000, 2, 2));
        $this->assertSame(600000, BookingPeriod::total(50000, 3, 4));
    }

    public function test_total_dengan_jumlah_nol_atau_negatif(): void
    {
        $this->assertSame(0, BookingPeriod::total(50000, 0, 2));
        $this->assertSame(0, BookingPeriod::total(50000, -1, 2));
        $this->assertSame(0, BookingPeriod::total(50000, 2, -1));
    }

    public function test_tanggal_disiapkan_untuk_tampil(): void
    {
        $this->assertSame('10 Oktober 2026', BookingPeriod::readableDate('2026-10-10'));
        $this->assertSame('1 Desember 2026', BookingPeriod::readableDate('2026-12-01'));
        $this->assertSame('2 Januari 2027', BookingPeriod::readableDate('2027-01-02'));
    }

    public function test_tanggal_boleh_diberikan_sebagai_carbon(): void
    {
        $this->assertSame(
            BookingPeriod::readableDate('2026-10-10'),
            BookingPeriod::readableDate(Carbon::parse('2026-10-10')),
        );
    }
}
