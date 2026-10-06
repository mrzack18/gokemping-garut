<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\Business;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Manajemen konten unit (PRD section 28, ROADMAP 5.4).
 *
 * Test di sini memeriksa CRUD konten admin, isolasi tenant, dan bagaimana
 * konten itu terbaca di halaman publik.
 */
class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.content.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_page_shows_the_content_banners_and_faqs(): void
    {
        $user = User::factory()->create();
        Banner::factory()->forBusiness($user->business)->create([
            'title' => 'Promo Akhir Pekan',
        ]);
        Faq::factory()->forBusiness($user->business)->create([
            'question' => 'Apakah harus membawa KTP?',
        ]);

        $this->actingAs($user)
            ->get(route('admin.content.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/content/index')
                ->has('content')
                ->has('banners', 1)
                ->where('banners.0.title', 'Promo Akhir Pekan')
                ->has('faqs', 1)
                ->where('faqs.0.question', 'Apakah harus membawa KTP?')
            );
    }

    public function test_admin_can_update_the_service_and_contact_information(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.content.profile'), [
                'whatsapp' => '628111222333',
                'phone' => '08111222333',
                'email' => 'kontak@unit.test',
                'address' => 'Jl. Baru No. 9, Garut',
                'service_intro' => 'Layanan sewa lengkap.',
                'service_highlights' => "Poin pertama\n\n Poin kedua \n",
                'rental_terms' => 'Wajib membawa KTP.',
                'maps_embed_url' => 'https://www.google.com/maps/embed?pb=abc',
            ])
            ->assertRedirect(route('admin.content.index'));

        $business = $user->business->refresh();

        $this->assertSame('628111222333', $business->whatsapp);
        $this->assertSame('08111222333', $business->phone);
        $this->assertSame('kontak@unit.test', $business->email);
        $this->assertSame('Layanan sewa lengkap.', $business->service_intro);
        $this->assertSame(['Poin pertama', 'Poin kedua'], $business->service_highlights);
        $this->assertSame('Wajib membawa KTP.', $business->rental_terms);
        $this->assertSame('https://www.google.com/maps/embed?pb=abc', $business->maps_embed_url);
    }

    public function test_the_maps_url_must_be_a_google_maps_embed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.content.profile'), [
                'whatsapp' => '628111222333',
                'maps_embed_url' => 'https://example.com/iframe',
            ])
            ->assertSessionHasErrors('maps_embed_url');
    }

    public function test_the_highlights_are_limited_by_lines_and_length(): void
    {
        $user = User::factory()->create();

        // Tujuh baris melebihi batas enam baris.
        $this->actingAs($user)
            ->patch(route('admin.content.profile'), [
                'whatsapp' => '628111222333',
                'service_highlights' => implode("\n", [
                    'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh',
                ]),
            ])
            ->assertSessionHasErrors('service_highlights');

        // Satu baris melebihi 120 karakter.
        $this->actingAs($user)
            ->patch(route('admin.content.profile'), [
                'whatsapp' => '628111222333',
                'service_highlights' => str_repeat('a', 121),
            ])
            ->assertSessionHasErrors('service_highlights');
    }

    public function test_the_whatsapp_number_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.content.profile'), [
                'service_intro' => 'Tanpa kontak',
            ])
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_admin_can_create_a_banner_with_an_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.content.banners.store'), [
                'title' => 'Promo Akhir Pekan',
                'subtitle' => 'Diskon untuk sewa dua hari.',
                'image' => UploadedFile::fake()->image('banner.png', 800, 400),
                'link_url' => '/gokemping',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.content.index'));

        $banner = Banner::query()->where('business_id', $user->business->getKey())->firstOrFail();

        $this->assertSame('Promo Akhir Pekan', $banner->title);
        $this->assertSame(1, $banner->sort_order);
        $this->assertTrue($banner->is_active);
        $this->assertTrue(str_starts_with($banner->image, 'banners/'.$user->business->getKey().'/'));
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_updating_a_banner_without_an_image_keeps_the_old_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Storage::disk('public')->put('banners/'.$user->business->getKey().'/lama.webp', 'lama');
        $banner = Banner::factory()->forBusiness($user->business)->create([
            'image' => 'banners/'.$user->business->getKey().'/lama.webp',
        ]);

        $this->actingAs($user)
            ->put(route('admin.content.banners.update', $banner), [
                'title' => 'Judul Baru',
                'subtitle' => null,
                'sort_order' => 2,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.content.index'));

        $banner->refresh();

        $this->assertSame('Judul Baru', $banner->title);
        $this->assertSame('banners/'.$user->business->getKey().'/lama.webp', $banner->image);
        Storage::disk('public')->assertExists('banners/'.$user->business->getKey().'/lama.webp');
    }

    public function test_replacing_a_banner_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Storage::disk('public')->put('banners/'.$user->business->getKey().'/lama.webp', 'lama');
        $banner = Banner::factory()->forBusiness($user->business)->create([
            'image' => 'banners/'.$user->business->getKey().'/lama.webp',
        ]);

        $this->actingAs($user)
            ->put(route('admin.content.banners.update', $banner), [
                'title' => 'Judul Baru',
                'image' => UploadedFile::fake()->image('baru.png', 800, 400),
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.content.index'));

        $banner->refresh();

        Storage::disk('public')->assertMissing('banners/'.$user->business->getKey().'/lama.webp');
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_deleting_a_banner_removes_the_row_and_the_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Storage::disk('public')->put('banners/'.$user->business->getKey().'/hapus.webp', 'x');
        $banner = Banner::factory()->forBusiness($user->business)->create([
            'image' => 'banners/'.$user->business->getKey().'/hapus.webp',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.content.banners.destroy', $banner))
            ->assertRedirect(route('admin.content.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->getKey()]);
        Storage::disk('public')->assertMissing('banners/'.$user->business->getKey().'/hapus.webp');
    }

    public function test_a_banner_from_another_business_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Banner::factory()->forBusiness($other->business)->create();

        $this->actingAs($user)
            ->delete(route('admin.content.banners.destroy', $foreign))
            ->assertNotFound();

        $this->assertDatabaseHas('banners', ['id' => $foreign->getKey()]);
    }

    public function test_admin_can_create_update_and_delete_a_faq(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.content.faqs.store'), [
                'question' => 'Apakah harus membawa KTP?',
                'answer' => 'Ya, KTP asli wajib dibawa saat pengambilan.',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.content.index'));

        $faq = Faq::query()->where('business_id', $user->business->getKey())->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.content.faqs.update', $faq), [
                'question' => 'Apakah KTP wajib dibawa?',
                'answer' => 'Ya, wajib.',
                'sort_order' => 0,
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.content.index'));

        $faq->refresh();

        $this->assertSame('Apakah KTP wajib dibawa?', $faq->question);
        $this->assertFalse($faq->is_active);

        $this->actingAs($user)
            ->delete(route('admin.content.faqs.destroy', $faq))
            ->assertRedirect(route('admin.content.index'));

        $this->assertDatabaseMissing('faqs', ['id' => $faq->getKey()]);
    }

    public function test_a_faq_from_another_business_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Faq::factory()->forBusiness($other->business)->create();

        $this->actingAs($user)
            ->delete(route('admin.content.faqs.destroy', $foreign))
            ->assertNotFound();

        $this->assertDatabaseHas('faqs', ['id' => $foreign->getKey()]);
    }

    public function test_the_landing_page_exposes_content_banners_and_faqs(): void
    {
        $business = Business::factory()->create([
            'service_intro' => 'Layanan lengkap.',
            'maps_embed_url' => 'https://www.google.com/maps/embed?pb=abc',
            'phone' => '08123456789',
        ]);

        Banner::factory()->forBusiness($business)->create(['title' => 'Banner Aktif']);
        Banner::factory()->forBusiness($business)->inactive()->create(['title' => 'Banner Nonaktif']);
        Faq::factory()->forBusiness($business)->create(['question' => 'Pertanyaan aktif']);
        Faq::factory()->forBusiness($business)->inactive()->create(['question' => 'Pertanyaan nonaktif']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->has('banners', 1)
                ->where('banners.0.title', 'Banner Aktif')
                ->has('faqs', 1)
                ->where('faqs.0.question', 'Pertanyaan aktif')
                ->where('businesses.0.service_intro', 'Layanan lengkap.')
                ->where('businesses.0.phone', '08123456789')
            );
    }

    public function test_the_services_page_exposes_the_service_information(): void
    {
        Business::factory()->create([
            'service_intro' => 'Layanan lengkap.',
            'service_highlights' => ['Poin satu', 'Poin dua'],
            'rental_terms' => 'Wajib KTP.',
            'phone' => '08123456789',
        ]);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('services/index')
                ->where('businesses.0.service_intro', 'Layanan lengkap.')
                ->where('businesses.0.service_highlights', ['Poin satu', 'Poin dua'])
                ->where('businesses.0.rental_terms', 'Wajib KTP.')
                ->where('businesses.0.phone', '08123456789')
            );
    }
}
