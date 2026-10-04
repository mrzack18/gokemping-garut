<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Tests\TestCase;

/**
 * Manajemen kategori dari sisi admin (PRD section 23, ROADMAP 4.2).
 *
 * Setiap test memakai admin dengan business-nya sendiri supaya pengujian
 * isolasi tenant tidak bergantung pada state dari test lain.
 */
class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_cannot_store_a_category(): void
    {
        $this->post(route('admin.categories.store'), [
            'name' => 'Sepeda MTB',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_sees_their_own_categories_with_product_counts(): void
    {
        $user = User::factory()->create();
        $business = $user->business;

        $sepeda = Category::factory()->forBusiness($business)->create([
            'name' => 'Sepeda',
            'sort_order' => 1,
        ]);

        Product::factory()
            ->forBusiness($business)
            ->withCategory($sepeda)
            ->create();

        Product::factory()
            ->forBusiness($business)
            ->withCategory($sepeda)
            ->inactive()
            ->create();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/categories')
                ->has('categories', 1)
                ->where('categories.0.name', 'Sepeda')
                ->where('categories.0.slug', $sepeda->slug)
                ->where('categories.0.sort_order', 1)
                ->where('categories.0.is_active', true)
                // Produk nonaktif ikut dihitung: kategori dengan produk tidak
                // boleh dihapus, jadi admin perlu melihat jumlahnya.
                ->where('categories.0.products_count', 2)
            );
    }

    public function test_category_list_excludes_other_business(): void
    {
        $user = User::factory()->create();
        $otherBusiness = Business::factory()->create();

        Category::factory()->forBusiness($user->business)->create();
        Category::factory()->forBusiness($otherBusiness)->create();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('categories', 1));
    }

    public function test_empty_list_is_returned_for_a_business_without_categories(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/categories')
                ->has('categories', 0)
            );
    }

    public function test_categories_are_ordered_by_sort_order_then_name(): void
    {
        $user = User::factory()->create();
        $business = $user->business;

        Category::factory()->forBusiness($business)->create(['name' => 'Tenda', 'sort_order' => 2]);
        Category::factory()->forBusiness($business)->create(['name' => 'Sepeda', 'sort_order' => 1]);
        Category::factory()->forBusiness($business)->create(['name' => 'Alat Dapur', 'sort_order' => 1]);

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories', 3)
                ->where('categories.0.name', 'Alat Dapur')
                ->where('categories.1.name', 'Sepeda')
                ->where('categories.2.name', 'Tenda')
            );
    }

    public function test_admin_can_store_a_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), [
                'name' => '  Sewa Sepeda MTB  ',
                'description' => '  Sepeda untuk jalur berat.  ',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()
            ->forBusiness($user->business)
            ->where('name', 'Sewa Sepeda MTB')
            ->firstOrFail();

        $this->assertSame('sewa-sepeda-mtb', $category->slug);
        $this->assertSame('Sepeda untuk jalur berat.', $category->description);
        $this->assertSame(3, $category->sort_order);
        $this->assertTrue($category->is_active);
    }

    public function test_stored_category_defaults_to_active_and_zero_sort_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => 'Tenda'])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->forBusiness($user->business)->firstOrFail();

        $this->assertTrue($category->is_active);
        $this->assertSame(0, $category->sort_order);
        $this->assertNull($category->description);
    }

    public function test_duplicate_category_name_gets_a_numbered_slug(): void
    {
        $user = User::factory()->create();
        $business = $user->business;

        Category::factory()->forBusiness($business)->create(['name' => 'Sepeda', 'slug' => 'sepeda']);

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => 'Sepeda'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'business_id' => $business->getKey(),
            'slug' => 'sepeda-2',
        ]);
    }

    public function test_another_business_may_reuse_the_same_category_name(): void
    {
        $user = User::factory()->create();
        $otherBusiness = Business::factory()->create();

        Category::factory()->forBusiness($otherBusiness)->create(['name' => 'Sepeda', 'slug' => 'sepeda']);

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => 'Sepeda'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'business_id' => $user->business_id,
            'slug' => 'sepeda',
        ]);
    }

    public function test_category_name_without_latin_letters_still_gets_a_usable_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => '🔥🔥🔥'])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->forBusiness($user->business)->firstOrFail();

        $this->assertSame('kategori', $category->slug);
    }

    public function test_category_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => '  '])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_sort_order_must_be_a_non_negative_integer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), [
                'name' => 'Tenda',
                'sort_order' => -1,
            ])
            ->assertSessionHasErrors('sort_order');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_can_update_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create([
            'name' => 'Sepeda',
            'slug' => 'sepeda',
            'description' => 'Lama',
            'sort_order' => 5,
        ]);

        $this->actingAs($user)
            ->from(route('admin.categories.index'))
            ->patch(route('admin.categories.update', $category), [
                'name' => 'Sepeda MTB',
                'description' => '',
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $category->refresh();

        $this->assertSame('Sepeda MTB', $category->name);
        // Slug lama dipertahankan supaya filter katalog publik tidak putus.
        $this->assertSame('sepeda', $category->slug);
        $this->assertNull($category->description);
        $this->assertSame(2, $category->sort_order);
    }

    public function test_editing_a_category_keeps_its_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->inactive()->create([
            'name' => 'Sepeda',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.categories.update', $category), ['name' => 'Sepeda MTB'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertFalse($category->refresh()->is_active);
    }

    public function test_admin_cannot_update_a_category_of_another_business(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->patch(route('admin.categories.update', $otherCategory), [
                'name' => 'Diretas',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('categories', [
            'id' => $otherCategory->getKey(),
            'name' => 'Diretas',
        ]);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->patch(route('admin.categories.status', $category), ['is_active' => '0'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertFalse($category->refresh()->is_active);

        $this->actingAs($user)
            ->patch(route('admin.categories.status', $category), ['is_active' => '1'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertTrue($category->refresh()->is_active);
    }

    public function test_toggling_status_does_not_change_the_products_inside(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        $this->actingAs($user)
            ->patch(route('admin.categories.status', $category), ['is_active' => '0'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_status_requires_a_boolean_value(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->patch(route('admin.categories.status', $category), ['is_active' => 'nanti'])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($category->refresh()->is_active);
    }

    public function test_admin_cannot_toggle_status_of_another_business(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->patch(route('admin.categories.status', $otherCategory), ['is_active' => '0'])
            ->assertNotFound();

        $this->assertTrue($otherCategory->refresh()->is_active);
    }

    public function test_admin_can_delete_a_category_without_products(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->getKey()]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        $this->actingAs($user)
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->getKey()]);
        // Produknya tidak ikut terhapus atau dilepas dari kategori.
        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'category_id' => $category->getKey(),
        ]);
    }

    public function test_admin_cannot_delete_a_category_of_another_business(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->delete(route('admin.categories.destroy', $otherCategory))
            ->assertNotFound();

        $this->assertDatabaseHas('categories', ['id' => $otherCategory->getKey()]);
    }

    public function test_flash_toast_reports_the_blocked_deletion(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create(['name' => 'Sepeda']);

        Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        $response = $this->actingAs($user)
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category));

        $response
            ->assertRedirect(route('admin.categories.index'))
            ->assertInertiaFlash('toast');

        /** @var array{type: string, message: string} $toast */
        $toast = session(SessionKey::FLASH_DATA.'.toast');

        $this->assertSame('error', $toast['type']);
        $this->assertStringContainsString('Sepeda', $toast['message']);
        $this->assertStringContainsString('1 produk', $toast['message']);
    }

    public function test_flash_toast_confirms_successful_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create(['name' => 'Tenda']);

        $response = $this->actingAs($user)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertInertiaFlash('toast');

        /** @var array{type: string, message: string} $toast */
        $toast = session(SessionKey::FLASH_DATA.'.toast');

        $this->assertSame('success', $toast['type']);
        $this->assertStringContainsString('Tenda', $toast['message']);
    }
}
