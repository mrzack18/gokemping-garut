<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard unit bisnis. Seluruh angka di sini otomatis ter-scope oleh
 * BusinessScope, sehingga admin hanya melihat miliknya sendiri (BR-05).
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/dashboard', [
            'business' => [
                'name' => $user->business->name,
                'slug' => $user->business->slug,
                'bookingCodePrefix' => $user->business->booking_code_prefix,
                'whatsapp' => $user->business->whatsapp,
            ],
            'stats' => [
                'totalProducts' => Product::query()->where('is_active', true)->count(),
                'bookingsToday' => Booking::query()->whereDate('created_at', today())->count(),
                'rented' => Booking::query()->where('booking_status', 'sedang_disewa')->count(),
                'awaitingConfirmation' => Booking::query()->where('booking_status', 'menunggu_konfirmasi')->count(),
                'pendingPayments' => Payment::query()->where('status', 'menunggu_verifikasi')->count(),
            ],
        ]);
    }
}
