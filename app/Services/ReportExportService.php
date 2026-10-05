<?php

namespace App\Services;

use App\Models\Business;
use App\Support\BookingPeriod;
use App\Support\Spreadsheets;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Ekspor laporan periode: Excel rekap dan PDF dengan kop surat (ROADMAP 5.2).
 *
 * Keduanya membaca data dari `ReportService` yang sama dengan halaman laporan,
 * jadi angka di layar, di Excel, dan di PDF tidak bisa berbeda. Yang berbeda
 * hanya bentuk penyajiannya.
 */
final class ReportExportService
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * Isi berkas XLSX rekap laporan, sebagai string biner.
     */
    public function excel(Business $business, string $from, string $to): string
    {
        $report = $this->reports->forPeriod($business, $from, $to);

        return Spreadsheets::bytes(function (Writer $writer) use ($business, $report): void {
            $bold = (new Style)->withFontBold(true);

            $writer->addRow(Row::fromValuesWithStyle(
                ['Laporan '.$business->name],
                $bold,
            ));
            $writer->addRow(Row::fromValues(['Periode', $report['period']['label']]));
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValuesWithStyle(['Ringkasan'], $bold));
            $stats = $report['stats'];
            $writer->addRow(Row::fromValues(['Jumlah Booking', $stats['bookings']]));
            $writer->addRow(Row::fromValues(['Booking Selesai', $stats['finished']]));
            $writer->addRow(Row::fromValues(['Booking Dibatalkan', $stats['cancelled']]));
            $writer->addRow(Row::fromValues(['Jumlah Penyewa', $stats['customers']]));
            $writer->addRow(Row::fromValues(['Total Pendapatan (Rp)', $stats['revenue']]));
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValuesWithStyle(['Produk Paling Banyak Disewa'], $bold));
            $writer->addRow(Row::fromValuesWithStyle(
                ['Produk', 'Unit', 'Nilai Sewa (Rp)'],
                $bold,
            ));

            if ($report['top_products'] === []) {
                $writer->addRow(Row::fromValues(['Tidak ada produk tersewa pada periode ini.']));
            }

            foreach ($report['top_products'] as $product) {
                $writer->addRow(Row::fromValues([
                    $product['product_name'],
                    $product['quantity'],
                    $product['revenue'],
                ]));
            }

            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValuesWithStyle(['Rekap Metode Pembayaran'], $bold));
            $writer->addRow(Row::fromValuesWithStyle(
                ['Metode', 'Transaksi', 'Nominal (Rp)', 'Lunas (Rp)'],
                $bold,
            ));

            foreach ($report['payment_methods'] as $method) {
                $writer->addRow(Row::fromValues([
                    $method['method_label'],
                    $method['transactions'],
                    $method['amount'],
                    $method['paid'],
                ]));
            }

            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValuesWithStyle(
                ['Pendapatan per '.$report['revenue_chart']['granularity']],
                $bold,
            ));
            $writer->addRow(Row::fromValuesWithStyle(
                ['Periode', 'Pendapatan (Rp)'],
                $bold,
            ));

            foreach ($report['revenue_chart']['points'] as $point) {
                $writer->addRow(Row::fromValues([
                    $point['full_label'],
                    $point['amount'],
                ]));
            }

            $writer->addRow(Row::fromValuesWithStyle(
                ['Total', $report['revenue_chart']['total']],
                $bold,
            ));
        });
    }

    /**
     * Isi berkas PDF laporan periode, sebagai string biner.
     *
     * View-nya Blade biasa, bukan Inertia, karena dompdf mencetak HTML statis
     * dan tidak menjalankan JavaScript.
     */
    public function pdf(Business $business, string $from, string $to): string
    {
        $report = $this->reports->forPeriod($business, $from, $to);

        return Pdf::loadView('reports.period', [
            'business' => $business,
            'report' => $report,
            'generatedAt' => BookingPeriod::readableDate(today()),
        ])->output();
    }
}
