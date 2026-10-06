<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * CRUD FAQ unit (PRD section 28, ROADMAP 5.4).
 *
 * Urutan tayang diatur lewat `sort_order`, dan FAQ nonaktif tetap tersimpan
 * di halaman admin supaya bisa dinyalakan lagi tanpa mengetik ulang. Route
 * model binding pada FAQ unit lain berakhir sebagai 404.
 */
class FaqController extends Controller
{
    /**
     * Tambah FAQ baru untuk unit admin yang login.
     */
    public function store(FaqRequest $request): RedirectResponse
    {
        $business = $request->user()->business;

        $faq = new Faq($request->payload());
        $faq->business_id = $business->getKey();
        $faq->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'FAQ berhasil ditambahkan.',
        ]);

        return to_route('admin.content.index');
    }

    /**
     * Ubah pertanyaan, jawaban, urutan, atau status FAQ.
     */
    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'FAQ berhasil diperbarui.',
        ]);

        return to_route('admin.content.index');
    }

    /**
     * Hapus FAQ.
     */
    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'FAQ berhasil dihapus.',
        ]);

        return to_route('admin.content.index');
    }
}
