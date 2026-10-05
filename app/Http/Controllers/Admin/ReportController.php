<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\ReportFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laporan periode admin (PRD section 29, ROADMAP 5.1).
 *
 * Controller ini hanya menormalkan filter tanggal; seluruh angkanya dihitung
 * `ReportService` untuk unit bisnis admin yang login, jadi laporan tiap unit
 * tidak pernah tercampur (BR-05).
 */
class ReportController extends Controller
{
    /**
     * Laporan untuk rentang tanggal yang dipilih.
     */
    public function index(Request $request, ReportService $reports): Response
    {
        $business = $request->user()->business;
        $filters = ReportFilters::fromRequest($request);

        return Inertia::render('admin/reports/index', [
            'filters' => $filters,
            'report' => $reports->forPeriod($business, $filters['from'], $filters['to']),
        ]);
    }
}
