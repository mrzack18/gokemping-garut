<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Models\Banner;
use App\Services\BannerImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

/**
 * CRUD banner hero (PRD section 28, ROADMAP 5.4).
 *
 * Route model binding membaca `banners` lewat `BusinessScope`, jadi banner
 * unit lain berakhir sebagai 404, bukan 403. Gambar lama dihapus setelah
 * banner barunya tersimpan, dan gambar baru dibatalkan kalau penyimpanan baris
 * gagal, supaya tidak pernah ada banner yang menunjuk berkas yang sudah
 * hilang atau berkas yatim di disk.
 */
class BannerController extends Controller
{
    /**
     * Tambah banner baru untuk unit admin yang login.
     */
    public function store(BannerRequest $request, BannerImageService $images): RedirectResponse
    {
        $business = $request->user()->business;
        $file = $request->uploadedImage();

        if ($file === null) {
            throw ValidationException::withMessages([
                'image' => 'Gambar banner wajib diunggah.',
            ]);
        }

        $path = $images->store($business->getKey(), $file);

        $banner = new Banner($request->payload());
        $banner->business_id = $business->getKey();
        $banner->image = $path;

        try {
            $banner->save();
        } catch (Throwable $e) {
            $images->delete($path);

            throw $e;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Banner "'.$banner->title.'" berhasil ditambahkan.',
        ]);

        return to_route('admin.content.index');
    }

    /**
     * Ubah banner, termasuk mengganti gambarnya.
     */
    public function update(
        BannerRequest $request,
        Banner $banner,
        BannerImageService $images,
    ): RedirectResponse {
        $oldPath = $banner->image;
        $file = $request->uploadedImage();
        $newPath = $file === null ? null : $images->store($banner->business_id, $file);

        $banner->fill([
            ...$request->payload(),
            ...($newPath === null ? [] : ['image' => $newPath]),
        ]);

        try {
            $banner->save();
        } catch (Throwable $e) {
            $images->delete($newPath);

            throw $e;
        }

        if ($newPath !== null) {
            $images->delete($oldPath);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Banner "'.$banner->title.'" berhasil diperbarui.',
        ]);

        return to_route('admin.content.index');
    }

    /**
     * Hapus banner beserta berkas gambarnya.
     */
    public function destroy(Banner $banner, BannerImageService $images): RedirectResponse
    {
        $path = $banner->image;
        $title = $banner->title;

        $banner->delete();
        $images->delete($path);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Banner "'.$title.'" berhasil dihapus.',
        ]);

        return to_route('admin.content.index');
    }
}
