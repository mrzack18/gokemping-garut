<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryStatusRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen kategori (PRD section 23, ROADMAP 4.2).
 *
 * Semua aksi selalu bekerja pada kategori milik unit bisnis admin yang sedang
 * login. Route model binding membaca `categories` lewat `BusinessScope`, jadi
 * slug milik unit lain berakhir sebagai 404, bukan 403, dan admin tidak bisa
 * memeriksa keberadaan kategori di luar scope-nya.
 */
class CategoryController extends Controller
{
    /**
     * Halaman daftar kategori.
     *
     * Jumlah produk dihitung dengan satu agregasi, bukan satu query per baris.
     * Yang dihitung adalah seluruh produk, termasuk yang nonaktif, karena admin
     * perlu melihat kategori yang isinya tidak bisa dihapus.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;

        $categories = Category::query()
            ->forBusiness($business)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => (int) $category->getKey(),
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'sort_order' => (int) $category->sort_order,
                'is_active' => $category->is_active,
                'products_count' => (int) $category->products_count,
            ]);

        return Inertia::render('admin/categories', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $business = $request->user()->business;

        $category = Category::create([
            ...$request->categoryPayload(),
            'business_id' => $business->getKey(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori "'.$category->name.'" berhasil ditambahkan.',
        ]);

        return to_route('admin.categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->categoryPayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori "'.$category->name.'" berhasil diperbarui.',
        ]);

        return to_route('admin.categories.index');
    }

    /**
     * Ubah status aktif dari daftar kategori.
     *
     * Endpoint terpisah supaya aksi status cukup mengirim satu nilai, tanpa
     * mengulang nama dan deskripsi yang tidak sedang diedit. Produk di dalam
     * kategori tidak ikut berubah statusnya, jadi admin yang menonaktifkan
     * kategori tidak ikut menghilangkan produknya dari katalog.
     */
    public function updateStatus(CategoryStatusRequest $request, Category $category): RedirectResponse
    {
        $isActive = $request->boolean('is_active');

        $category->update(['is_active' => $isActive]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori "'.$category->name.'" berhasil '.($isActive ? 'diaktifkan' : 'dinonaktifkan').'.',
        ]);

        return to_route('admin.categories.index');
    }

    /**
     * Hapus kategori.
     *
     * Kategori yang masih punya produk tidak dihapus. Produknya tidak otomatis
     * ikut terhapus dan kategorinya juga tidak dikosongkan diam-diam, karena
     * dua hal itu sama-sama merusak data tanpa disadari admin. Pesan errornya
     * menyebutkan jumlah produk yang menghalangi supaya langkah berikutnya
     * jelas: pindahkan produknya dulu, atau nonaktifkan kategori saja.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $productsCount = (int) $category->products()->count();

        if ($productsCount > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Kategori "'.$category->name.'" masih memiliki '.$productsCount.' produk. '
                    .'Pindahkan produknya dulu, atau nonaktifkan kategori ini kalau produknya masih dipakai.',
            ]);

            return to_route('admin.categories.index');
        }

        $name = $category->name;

        $category->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori "'.$name.'" berhasil dihapus.',
        ]);

        return to_route('admin.categories.index');
    }
}
