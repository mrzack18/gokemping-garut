<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePaymentSettingRequest;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Support\PaymentMethods;
use App\Support\QrisImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengaturan metode pembayaran per unit bisnis (PRD section 27, ROADMAP 4.7).
 *
 * Halaman ini mengisi data yang dibaca halaman pembayaran publik: nama
 * merchant dan gambar QRIS, rekening bank, serta keterangan cash. Satu baris
 * per metode per unit bisnis, sesuai `unique(business_id, type)` di tabel,
 * jadi menyimpan satu kartu tidak pernah menyentuh metode lain.
 *
 * Penyimpanan selalu memakai `business_id` admin yang login, tidak pernah dari
 * request, sehingga tidak ada field yang bisa dipindahkan ke unit lain.
 * `BusinessScope` pada model `PaymentMethod` tetap menjadi lapisan kedua.
 */
class PaymentSettingController extends Controller
{
    /**
     * Halaman pengaturan, selalu menampilkan ketiga metode.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/payment-settings/index', [
            'methods' => $this->methods($request->user()->business),
        ]);
    }

    /**
     * Simpan satu metode.
     *
     * Gambar QRIS baru ditulis lebih dulu, lalu barisnya disimpan, dan baru
     * setelah itu gambar lama dihapus. Urutan ini menjaga gambar lama tetap ada
     * selama penggantian belum berhasil, jadi halaman pembayaran tidak pernah
     * menunjuk berkas yang sudah hilang.
     */
    public function update(UpdatePaymentSettingRequest $request): RedirectResponse
    {
        $business = $request->user()->business;
        $type = $request->type();

        $method = $this->findOrNew($business, $type);
        $oldPath = $method->qris_image;

        $file = $request->qrisImage();
        $newPath = $file === null ? null : QrisImages::store($file, $business->getKey());

        $method->fill([
            ...$request->payload(),
            'is_active' => $request->isActive(),
            ...($newPath === null ? [] : ['qris_image' => $newPath]),
        ]);

        try {
            $method->save();
        } catch (\Throwable $e) {
            QrisImages::delete($newPath);

            throw $e;
        }

        if ($newPath !== null) {
            QrisImages::delete($oldPath);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pengaturan '.$type->label().' berhasil disimpan.',
        ]);

        return to_route('admin.payment-settings.index');
    }

    /**
     * Baris pengaturan metode ini, atau instance baru yang belum disimpan.
     *
     * Instance baru sudah memegang `business_id`, jadi unit bisnis tidak pernah
     * diambil dari request dan baris pertama tiap metode selalu lahir dari
     * halaman ini.
     */
    private function findOrNew(Business $business, PaymentMethodType $type): PaymentMethod
    {
        $method = $business->paymentMethods()
            ->where('type', $type->value)
            ->first();

        if ($method instanceof PaymentMethod) {
            return $method;
        }

        $method = new PaymentMethod;
        $method->business_id = $business->getKey();
        $method->type = $type;
        $method->is_active = false;

        return $method;
    }

    /**
     * Ketiga metode dalam urutan tetap, walau barisnya belum ada.
     *
     * Halaman pengaturan harus menampilkan metode yang belum dikonfigurasi:
     * itulah tempat konfigurasinya dimulai. Metode yang aktif tetapi datanya
     * belum lengkap tetap dikirim dengan `is_ready: false` supaya admin melihat
     * peringatannya, bukan menemukan halaman pembayaran publik yang
     * membingungkan.
     *
     * @return list<array<string, mixed>>
     */
    private function methods(Business $business): array
    {
        $existing = $business->paymentMethods()
            ->get()
            ->keyBy(fn (PaymentMethod $method): string => $method->type->value);

        $methods = [];

        foreach (PaymentMethodType::all() as $type) {
            $method = $existing->get($type->value);
            $isMethod = $method instanceof PaymentMethod;

            $methods[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'requires_proof' => $type->requiresProof(),
                'is_active' => $isMethod && $method->is_active,
                'is_ready' => $isMethod && PaymentMethods::isReady($method),
                'merchant_name' => $isMethod ? $method->merchant_name : null,
                'qris_image_url' => $isMethod ? $method->qris_image_url : null,
                'bank_name' => $isMethod ? $method->bank_name : null,
                'account_number' => $isMethod ? $method->account_number : null,
                'account_name' => $isMethod ? $method->account_name : null,
                'instructions' => $isMethod ? $method->instructions : null,
            ];
        }

        return $methods;
    }
}
