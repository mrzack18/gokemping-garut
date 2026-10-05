<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\BookingExportService;
use App\Services\ReportExportService;
use App\Support\BookingFilters;
use App\Support\ReportFilters;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor berkas admin (PRD section 29, ROADMAP 5.2).
 *
 * Semua ekspor memakai filter yang sama dengan halamannya masing-masing:
 * daftar booking memakai `BookingFilters`, laporan memakai `ReportFilters`.
 * Dengan begitu berkas yang diunduh selalu berisi data yang sedang dilihat
 * admin, bukan seluruh riwayat.
 */
class ExportController extends Controller
{
    /**
     * MIME type berkas Excel.
     */
    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * Ekspor Excel data booking sesuai filter daftar booking.
     */
    public function bookings(Request $request, BookingExportService $export): StreamedResponse
    {
        $business = $request->user()->business;
        $filters = BookingFilters::fromRequest($request);
        $contents = $export->excel($business, $filters);

        return $this->download(
            $contents,
            'data-booking-'.$business->slug.'-'.now()->format('Ymd-His').'.xlsx',
            self::XLSX_MIME,
        );
    }

    /**
     * Ekspor Excel rekap laporan sesuai periode yang dipilih.
     */
    public function report(Request $request, ReportExportService $export): StreamedResponse
    {
        $business = $request->user()->business;
        $filters = ReportFilters::fromRequest($request);
        $contents = $export->excel($business, $filters['from'], $filters['to']);

        return $this->download(
            $contents,
            $this->reportFileName($business, $filters['from'], $filters['to'], 'xlsx'),
            self::XLSX_MIME,
        );
    }

    /**
     * Ekspor PDF laporan periode beserta kop surat.
     */
    public function reportPdf(Request $request, ReportExportService $export): StreamedResponse
    {
        $business = $request->user()->business;
        $filters = ReportFilters::fromRequest($request);
        $contents = $export->pdf($business, $filters['from'], $filters['to']);

        return $this->download(
            $contents,
            $this->reportFileName($business, $filters['from'], $filters['to'], 'pdf'),
            'application/pdf',
        );
    }

    /**
     * Nama berkas laporan yang memuat periode, supaya dua unduhan dari periode
     * berbeda tidak saling menimpa di folder unduhan admin.
     */
    private function reportFileName(
        Business $business,
        string $from,
        string $to,
        string $extension,
    ): string {
        return 'laporan-'.$business->slug.'-'.$from.'-'.$to.'.'.$extension;
    }

    /**
     * Kirim isi berkas sebagai unduhan tanpa menulisnya ke disk.
     *
     * Berkas dibuat di memori karena ukurannya kecil dan tidak perlu disimpan:
     * admin mengunduhnya sekali, lalu selesai. Streaming dipakai supaya isinya
     * tidak melewati konversi string response biasa.
     */
    private function download(string $contents, string $fileName, string $contentType): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            $fileName,
            ['Content-Type' => $contentType],
        );
    }
}
