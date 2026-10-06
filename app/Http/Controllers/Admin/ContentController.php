<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessContentRequest;
use App\Models\Banner;
use App\Models\Business;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen konten unit (PRD section 28, ROADMAP 5.4).
 *
 * Halaman ini mengelola konten yang tampil di halaman publik: informasi
 * layanan beserta kontak dan lokasi (tersimpan di baris `businesses`), banner
 * hero, dan FAQ. Semua data terikat ke `business_id` admin yang login, jadi
 * konten satu unit tidak pernah bocor ke unit lain.
 */
class ContentController extends Controller
{
    /**
     * Halaman konten: profil layanan, banner, dan FAQ.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;

        return Inertia::render('admin/content/index', [
            'content' => $this->content($business),
            'banners' => $this->banners($business),
            'faqs' => $this->faqs($business),
        ]);
    }

    /**
     * Simpan informasi layanan, kontak, ketentuan sewa, dan lokasi.
     */
    public function updateProfile(UpdateBusinessContentRequest $request): RedirectResponse
    {
        $business = $request->user()->business;
        $business->fill($request->payload())->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Informasi layanan berhasil disimpan.',
        ]);

        return to_route('admin.content.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function content(Business $business): array
    {
        return [
            'service_intro' => $business->service_intro,
            'service_highlights' => $business->service_highlights ?? [],
            'rental_terms' => $business->rental_terms,
            'phone' => $business->phone,
            'maps_embed_url' => $business->maps_embed_url,
            'whatsapp' => $business->whatsapp,
            'email' => $business->email,
            'address' => $business->address,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function banners(Business $business): array
    {
        return array_values(
            $business->banners()
                ->ordered()
                ->get()
                ->map(fn (Banner $banner): array => [
                    'id' => (int) $banner->getKey(),
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'image_url' => $banner->image_url,
                    'link_url' => $banner->link_url,
                    'sort_order' => $banner->sort_order,
                    'is_active' => $banner->is_active,
                ])
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function faqs(Business $business): array
    {
        return array_values(
            $business->faqs()
                ->ordered()
                ->get()
                ->map(fn (Faq $faq): array => [
                    'id' => (int) $faq->getKey(),
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'sort_order' => $faq->sort_order,
                    'is_active' => $faq->is_active,
                ])
                ->all()
        );
    }
}
