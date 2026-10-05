<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethodType;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Scopes\BusinessScope;
use App\Models\User;
use App\Support\PaymentMethods;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengaturan metode pembayaran (PRD section 27, ROADMAP 4.7).
 *
 * Test di sini memeriksa dua hal: data yang benar-benar tersimpan per unit
 * bisnis, dan bagaimana pengaturan itu terbaca oleh halaman pembayaran publik
 * lewat `PaymentMethods`.
 */
class PaymentSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.payment-settings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_page_shows_all_three_methods_with_their_settings(): void
    {
        $user = User::factory()->create();
        $this->cashMethod($user->business, 'Bayar di lokasi.');
        $this->qrisMethod($user->business);
        $this->bankMethod($user->business);

        $this->actingAs($user)
            ->get(route('admin.payment-settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/payment-settings/index')
                ->has('methods', 3)
                ->where('methods.0.type', PaymentMethodType::Cash->value)
                ->where('methods.0.instructions', 'Bayar di lokasi.')
                ->where('methods.1.type', PaymentMethodType::Qris->value)
                ->where('methods.1.merchant_name', 'GoKemping')
                ->where('methods.2.type', PaymentMethodType::BankTransfer->value)
                ->where('methods.2.bank_name', 'BCA')
                ->where('methods.2.account_number', '1234567890')
            );
    }

    public function test_missing_methods_are_shown_as_unconfigured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.payment-settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('methods', 3)
                ->where('methods.0.is_active', false)
                ->where('methods.0.is_ready', false)
                ->where('methods.1.is_active', false)
                ->where('methods.1.is_ready', false)
            );
    }

    public function test_an_active_qris_without_an_image_is_flagged_as_not_ready(): void
    {
        $user = User::factory()->create();
        PaymentMethod::factory()->forBusiness($user->business)->create([
            'type' => PaymentMethodType::Qris,
            'merchant_name' => 'GoKemping',
            'qris_image' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.payment-settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('methods.1.is_active', true)
                ->where('methods.1.is_ready', false)
            );
    }

    public function test_admin_can_update_the_cash_setting(): void
    {
        $user = User::factory()->create();
        $this->cashMethod($user->business, 'Keterangan lama');

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Cash->value]), [
                'is_active' => '0',
                'instructions' => 'Pembayaran langsung di lokasi.',
            ])
            ->assertRedirect(route('admin.payment-settings.index'));

        $method = $this->method($user->business, PaymentMethodType::Cash);

        $this->assertFalse($method->is_active);
        $this->assertSame('Pembayaran langsung di lokasi.', $method->instructions);
    }

    public function test_admin_can_update_the_bank_setting(): void
    {
        $user = User::factory()->create();
        $this->bankMethod($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::BankTransfer->value]), [
                'is_active' => '1',
                'bank_name' => 'Mandiri',
                'account_number' => '9876543210',
                'account_name' => 'GoKemping Garut',
            ])
            ->assertRedirect(route('admin.payment-settings.index'));

        $method = $this->method($user->business, PaymentMethodType::BankTransfer);

        $this->assertTrue($method->is_active);
        $this->assertSame('Mandiri', $method->bank_name);
        $this->assertSame('9876543210', $method->account_number);
        $this->assertSame('GoKemping Garut', $method->account_name);
    }

    public function test_admin_can_upload_a_qris_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->qrisMethod($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Qris->value]), [
                'is_active' => '1',
                'merchant_name' => 'GoKemping',
                'qris_image' => UploadedFile::fake()->image('qris.png', 300, 300),
            ])
            ->assertRedirect(route('admin.payment-settings.index'));

        $method = $this->method($user->business, PaymentMethodType::Qris);
        $path = (string) $method->qris_image;

        $this->assertTrue(str_starts_with($path, 'qris/'.$user->business->getKey().'/'));
        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($method->qris_image_url);
    }

    public function test_replacing_a_qris_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Storage::disk('public')->put('qris/'.$user->business->getKey().'/lama.png', 'lama');
        $this->qrisMethod($user->business, 'qris/'.$user->business->getKey().'/lama.png');

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Qris->value]), [
                'is_active' => '1',
                'merchant_name' => 'GoKemping',
                'qris_image' => UploadedFile::fake()->image('qris.png', 300, 300),
            ])
            ->assertRedirect(route('admin.payment-settings.index'));

        $path = (string) $this->method($user->business, PaymentMethodType::Qris)->qris_image;

        Storage::disk('public')->assertMissing('qris/'.$user->business->getKey().'/lama.png');
        Storage::disk('public')->assertExists($path);
    }

    public function test_saving_a_method_does_not_touch_the_other_methods(): void
    {
        $user = User::factory()->create();
        $this->cashMethod($user->business, 'Keterangan cash');
        $this->bankMethod($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Cash->value]), [
                'is_active' => '1',
                'instructions' => 'Keterangan baru',
            ]);

        $bank = $this->method($user->business, PaymentMethodType::BankTransfer);

        $this->assertSame('BCA', $bank->bank_name);
        $this->assertSame('1234567890', $bank->account_number);
    }

    public function test_saving_only_updates_the_current_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->cashMethod($user->business, 'Punya sendiri');
        $this->cashMethod($other->business, 'Punya unit lain');

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Cash->value]), [
                'is_active' => '1',
                'instructions' => 'Sudah diubah',
            ]);

        $this->assertSame('Sudah diubah', $this->method($user->business, PaymentMethodType::Cash)->instructions);
        $this->assertSame('Punya unit lain', $this->method($other->business, PaymentMethodType::Cash)->instructions);
    }

    public function test_an_unknown_method_type_is_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => 'entah']), [
                'is_active' => '1',
            ])
            ->assertNotFound();
    }

    public function test_the_active_flag_is_required(): void
    {
        $user = User::factory()->create();
        $this->cashMethod($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Cash->value]), [
                'instructions' => 'Tetap dicoba',
            ])
            ->assertSessionHasErrors('is_active');
    }

    public function test_the_qris_image_must_be_an_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->qrisMethod($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Qris->value]), [
                'is_active' => '1',
                'merchant_name' => 'GoKemping',
                'qris_image' => UploadedFile::fake()->create('qris.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('qris_image');
    }

    public function test_a_saved_setting_is_used_by_the_public_payment_page(): void
    {
        $user = User::factory()->create();
        $this->qrisMethod($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payment-settings.update', ['type' => PaymentMethodType::Qris->value]), [
                'is_active' => '0',
                'merchant_name' => 'GoKemping',
            ]);

        $active = PaymentMethods::activeFor($user->business);

        $this->assertFalse(
            $active->contains(fn (PaymentMethod $method): bool => $method->type === PaymentMethodType::Qris),
        );
    }

    /**
     * Baris pengaturan milik satu unit bisnis, dibaca tanpa `BusinessScope`
     * supaya test bisa memeriksa unit lain saat admin sedang login.
     */
    private function method(Business $business, PaymentMethodType $type): PaymentMethod
    {
        return BusinessScope::withoutBusinessScope(
            PaymentMethod::query()
                ->where('business_id', $business->getKey())
                ->where('type', $type->value),
        )->firstOrFail();
    }

    private function cashMethod(Business $business, string $instructions = 'Pembayaran di lokasi.'): PaymentMethod
    {
        return PaymentMethod::factory()->forBusiness($business)->cash($instructions)->create();
    }

    private function qrisMethod(Business $business, string $image = 'qris/1/bukti.png'): PaymentMethod
    {
        return PaymentMethod::factory()->forBusiness($business)->qris()->create([
            'qris_image' => $image,
        ]);
    }

    private function bankMethod(Business $business): PaymentMethod
    {
        return PaymentMethod::factory()->forBusiness($business)->bankTransfer()->create();
    }
}
