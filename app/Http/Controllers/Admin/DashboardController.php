<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard unit bisnis (PRD section 22, ROADMAP 4.1).
 *
 * Angkanya dihitung `DashboardService` dari `business_id` milik admin yang
 * login, sehingga admin hanya melihat miliknya sendiri (BR-05).
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): Response
    {
        $business = $request->user()->business;

        return Inertia::render('admin/dashboard', [
            'business' => [
                'name' => $business->name,
                'slug' => $business->slug,
                'bookingCodePrefix' => $business->booking_code_prefix,
                'whatsapp' => $business->whatsapp,
            ],
            'stats' => $dashboard->stats($business),
        ]);
    }
}
