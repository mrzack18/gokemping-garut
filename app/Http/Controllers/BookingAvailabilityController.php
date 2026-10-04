<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Endpoint pengecekan ketersediaan barang (ROADMAP 3.6, BR-04).
 *
 * Endpoint ini dipanggil setiap kali tanggal mulai atau tanggal selesai
 * berubah. Datanya dibaca, bukan ditulis, sehingga tidak memakai
 * `lockForUpdate`. Rincian alasannya ada di `AvailabilityService`.
 */
class BookingAvailabilityController extends Controller
{
    public function __invoke(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($startDate->isBefore(Carbon::today()->startOfDay())) {
            return response()->json([
                'message' => 'Tanggal mulai tidak boleh di masa lalu.',
                'errors' => [
                    'start_date' => ['Tanggal mulai tidak boleh di masa lalu.'],
                ],
            ], 422);
        }

        $business = $this->resolveBusiness($request);
        $product = $this->resolveProduct($business, $request);

        $summary = $availability->summarise($product, $startDate, $endDate, [
            'requested' => (int) ($data['quantity'] ?? 1),
        ]);

        return response()->json([
            ...$summary,
            'message' => $this->message($summary['available'], $summary['requested']),
            'holding_statuses' => $this->holdingStatuses(),
        ]);
    }

    private function message(int $available, int $requested): string
    {
        if ($available < 1) {
            return 'Stok tidak tersedia pada periode tersebut.';
        }

        if ($requested > $available) {
            return 'Stok tidak mencukupi pada periode tersebut.';
        }

        return "Tersedia {$available} unit pada periode tersebut.";
    }

    /**
     * Status booking yang menahan stok, dipamerkan di respons supaya angka
     * ketersediaan bisa ditelusuri dari sisi klien.
     *
     * @return list<string>
     */
    private function holdingStatuses(): array
    {
        $statuses = [];

        foreach (BookingStatus::cases() as $status) {
            if ($status->holdsStock()) {
                $statuses[] = $status->value;
            }
        }

        return $statuses;
    }

    private function resolveBusiness(Request $request): Business
    {
        $slug = $request->route('business');

        if (! is_string($slug) || $slug === '') {
            throw new NotFoundHttpException;
        }

        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($business === null) {
            throw new NotFoundHttpException;
        }

        return $business;
    }

    private function resolveProduct(Business $business, Request $request): Product
    {
        $slug = $request->route('product');

        if (! is_string($slug) || $slug === '') {
            throw new NotFoundHttpException;
        }

        $product = BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('products.business_id', $business->getKey())
                ->where('products.slug', $slug)
                ->active()
        )->first();

        if ($product === null) {
            throw new NotFoundHttpException;
        }

        return $product;
    }
}
