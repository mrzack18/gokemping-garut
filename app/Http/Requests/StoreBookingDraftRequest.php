<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Menyimpan draft booking dari form jadwal (ROADMAP 3.5 sampai 3.6).
 *
 * Ketersediaan dicek ulang di server walaupun frontend sudah memanggil
 * endpoint pengecekan. Nilai dari klien tidak pernah dipercaya untuk
 * keputusan yang memoriesepan stok.
 */
class StoreBookingDraftRequest extends FormRequest
{
    /**
     * Resolve business dan product di-cache supaya pemeriksaan ketersediaan dan
     * controller memakai hasil query yang sama.
     */
    private ?Business $resolvedBusiness = null;

    private ?Product $resolvedProduct = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Pilih tanggal mulai.',
            'start_date.date_format' => 'Tanggal mulai tidak valid.',
            'end_date.required' => 'Pilih tanggal selesai.',
            'end_date.date_format' => 'Tanggal selesai tidak valid.',
            'quantity.required' => 'Jumlah barang minimal 1 unit.',
            'quantity.min' => 'Jumlah barang minimal 1 unit.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = $this->dateOrNull('start_date');
            $end = $this->dateOrNull('end_date');

            if ($start === null || $end === null) {
                return;
            }

            if ($start->isBefore(Carbon::today()->startOfDay())) {
                $validator->errors()->add('start_date', 'Tanggal mulai tidak boleh di masa lalu.');

                return;
            }

            if ($end->isBefore($start)) {
                $validator->errors()->add('end_date', 'Tanggal selesai harus tanggal mulai atau setelahnya.');

                return;
            }

            $this->ensureAvailable($start, $end, $validator);
        });
    }

    public function business(): Business
    {
        if ($this->resolvedBusiness !== null) {
            return $this->resolvedBusiness;
        }

        $slug = $this->route('business');

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

        return $this->resolvedBusiness = $business;
    }

    public function product(Business $business): Product
    {
        if ($this->resolvedProduct !== null) {
            return $this->resolvedProduct;
        }

        $slug = $this->route('product');

        if (! is_string($slug) || $slug === '') {
            throw new NotFoundHttpException;
        }

        $product = BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('products.business_id', $business->getKey())
                ->where('products.slug', $slug)
                ->active(),
        )->first();

        if ($product === null) {
            throw new NotFoundHttpException;
        }

        return $this->resolvedProduct = $product;
    }

    /**
     * Cek ulang ketersediaan periode yang dikirim klien.
     *
     * Nilai dari frontend tidak pernah dipercaya untuk keputusan yang menyentuh
     * stok. Pemeriksaan ini sengaja berada di dalam `withValidator()` supaya
     * kegagalan menghentikan request dengan 422 sebelum draft ditulis, bukan
     * sekadar menambah pesan pada validator yang sudah selesai berjalan.
     */
    private function ensureAvailable(Carbon $start, Carbon $end, Validator $validator): void
    {
        $requested = (int) $this->input('quantity');

        if ($requested < 1) {
            return;
        }

        $business = $this->business();
        $product = $this->product($business);

        $available = app(AvailabilityService::class)->availableUnits($product, $start, $end);

        if ($requested > $available) {
            $validator->errors()->add(
                'quantity',
                'Stok tidak mencukupi pada periode tersebut.',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function draftPayload(Business $business, Product $product): array
    {
        return [
            'business' => $business->slug,
            'product_id' => (int) $product->getKey(),
            'start_date' => $this->dateOrNull('start_date')?->toDateString(),
            'end_date' => $this->dateOrNull('end_date')?->toDateString(),
            'quantity' => (int) $this->input('quantity'),
        ];
    }

    private function dateOrNull(string $key): ?Carbon
    {
        $value = $this->input($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
