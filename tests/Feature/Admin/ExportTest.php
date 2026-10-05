<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

/**
 * Ekspor Excel dan PDF admin (ROADMAP 5.2).
 *
 * Isi berkas Excel dibaca kembali dengan OpenSpout supaya yang diuji benar-benar
 * isi berkasnya, bukan hanya status response. PDF diuji dari magic number dan
 * view-nya dirender langsung, karena teks di dalam PDF terkompresi.
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.exports.bookings'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.exports.report'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.exports.report-pdf'))
            ->assertRedirect(route('login'));
    }

    public function test_the_booking_export_follows_the_list_filters(): void
    {
        $user = User::factory()->create();
        $wanted = $this->booking($user->business, '2026-10-05 09:00:00', 'Tenda Dome');
        $other = $this->booking(
            $user->business,
            '2026-11-05 09:00:00',
            'Kursi Lipat',
            startDate: '2026-11-10',
        );

        $response = $this->actingAs($user)->get(route('admin.exports.bookings', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertDownload('data-booking-'.$user->business->slug.'-20261015-120000.xlsx');

        $rows = $this->xlsxRows($response->streamedContent());

        $this->assertSame('Kode Booking', $rows[0][0]);
        $this->assertCount(2, $rows);
        $this->assertSame($wanted->booking_code, $rows[1][0]);
        $this->assertSame('Tenda Dome', $rows[1][4]);
        $this->assertNotContains($other->booking_code, array_column($rows, 0));
    }

    public function test_the_booking_export_only_contains_the_current_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->booking($user->business, '2026-10-05 09:00:00', 'Tenda Dome');
        $foreign = $this->booking($other->business, '2026-10-06 09:00:00', 'Tenda Unit Lain');

        $response = $this->actingAs($user)->get(route('admin.exports.bookings'));
        $rows = $this->xlsxRows($response->streamedContent());

        $codes = array_column($rows, 0);
        $this->assertContains($mine->booking_code, $codes);
        $this->assertNotContains($foreign->booking_code, $codes);
    }

    public function test_the_report_excel_contains_the_period_summary(): void
    {
        $user = User::factory()->create();

        $booking = $this->booking($user->business, '2026-10-05 09:00:00', 'Tenda Dome', quantity: 2, subtotal: 200000);
        $this->payment($user->business, $booking, 200000, '2026-10-06 10:00:00');

        $response = $this->actingAs($user)->get(route('admin.exports.report', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]));

        $response->assertDownload('laporan-'.$user->business->slug.'-2026-10-01-2026-10-31.xlsx');

        $rows = $this->xlsxRows($response->streamedContent());

        $this->assertSame('Laporan '.$user->business->name, $rows[0][0]);
        $this->assertTrue($this->hasRow($rows, ['Periode', '1 Oktober 2026 - 31 Oktober 2026']));
        $this->assertTrue($this->hasRow($rows, ['Jumlah Booking', 1]));
        $this->assertTrue($this->hasRow($rows, ['Total Pendapatan (Rp)', 200000]));
        $this->assertTrue($this->hasRow($rows, ['Tenda Dome', 2, 200000]));
        $this->assertTrue($this->hasRow($rows, ['Cash', 1, 200000, 200000]));
    }

    public function test_the_report_pdf_is_a_downloadable_pdf(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.exports.report-pdf', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertDownload('laporan-'.$user->business->slug.'-2026-10-01-2026-10-31.pdf');

        $contents = $response->streamedContent();

        $this->assertStringStartsWith('%PDF', $contents);
    }

    public function test_the_pdf_view_renders_the_letterhead_and_sections(): void
    {
        $user = User::factory()->create();

        $report = app(ReportService::class)->forPeriod(
            $user->business,
            '2026-10-01',
            '2026-10-31',
        );

        $html = view('reports.period', [
            'business' => $user->business,
            'report' => $report,
            'generatedAt' => '15 Oktober 2026',
        ])->render();

        $this->assertStringContainsString($user->business->name, $html);
        $this->assertStringContainsString($user->business->address, $html);
        $this->assertStringContainsString('Laporan Periode 1 Oktober 2026 - 31 Oktober 2026', $html);
        $this->assertStringContainsString('Ringkasan', $html);
        $this->assertStringContainsString('Rekap Metode Pembayaran', $html);
    }

    /**
     * @return list<list<mixed>>
     */
    private function xlsxRows(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'test-xlsx');

        if ($path === false) {
            $this->fail('Berkas sementara untuk membaca hasil ekspor gagal dibuat.');
        }

        file_put_contents($path, $contents);

        $reader = new Reader;
        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            break;
        }

        $reader->close();
        @unlink($path);

        return $rows;
    }

    /**
     * OpenSpout menambahkan sel kosong sampai lebar kolom terbanyak, jadi
     * baris dibandingkan sepanjang kolom yang diharapkan saja.
     *
     * @param  list<list<mixed>>  $rows
     * @param  list<mixed>  $expected
     */
    private function hasRow(array $rows, array $expected): bool
    {
        foreach ($rows as $row) {
            if (array_slice($row, 0, count($expected)) === $expected) {
                return true;
            }
        }

        return false;
    }

    private function booking(
        Business $business,
        string $createdAt,
        string $productName,
        int $quantity = 1,
        int $subtotal = 100000,
        string $startDate = '2026-10-10',
        string $endDate = '2026-10-12',
    ): Booking {
        $booking = Booking::factory()->forBusiness($business)->create([
            'customer_id' => Customer::factory()->create()->getKey(),
            'booking_status' => BookingStatus::Selesai,
            'completed_at' => '2026-10-06 09:00:00',
            'created_at' => $createdAt,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_name' => $productName,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ]);

        return $booking;
    }

    private function payment(
        Business $business,
        Booking $booking,
        int $amount,
        string $paidAt,
    ): Payment {
        return Payment::factory()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => PaymentMethodType::Cash,
            'amount' => $amount,
            'status' => PaymentStatus::Lunas,
            'verified_at' => $paidAt,
            'created_at' => '2026-10-05 09:30:00',
        ]);
    }
}
