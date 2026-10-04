<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Support\BookingDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload, ganti, dan hapus bukti pembayaran (ROADMAP 3.10, BR-08).
 *
 * Test ini mengunci dua aturan yang tidak boleh bocor dari klien: format dan
 * ukuran berkas selalu divalidasi server, dan bukti hanya bisa menyentuh disk
 * kalau metodenya memang tercatat di draft milik unit bisnis yang diakses.
 *
 * Disk `public` dipalsukan di setiap test supaya berkas uji tidak bercampur
 * dengan berkas asli di `storage/app/public`.
 */
class BookingPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private Business $camping;

    private Product $tent;

    private Business $bike;

    private Product $bicycle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->camping = Business::factory()->create([
            'slug' => 'gokemping',
            'is_active' => true,
        ]);

        $this->tent = Product::factory()->forBusiness($this->camping)->create([
            'slug' => 'tenda-4-orang',
            'name' => 'Tenda Dome 4 Person',
            'price' => 50000,
            'price_unit' => 'hari',
            'stock' => 4,
        ]);

        $this->bike = Business::factory()->create([
            'slug' => 'sewa-sepeda-garut',
            'is_active' => true,
        ]);

        $this->bicycle = Product::factory()->forBusiness($this->bike)->create([
            'slug' => 'sepeda-770',
            'price' => 25000,
            'price_unit' => 'hari',
            'stock' => 6,
        ]);
    }

    public function test_bukti_tersimpan_ke_disk_dan_ditampilkan_di_halaman(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertRedirect(route('booking.gokemping.payment.show', ['method' => 'qris']));

        $path = $this->draftProofPath();

        $this->assertIsString($path);
        $this->assertStringStartsWith('payments/', $path);
        Storage::disk('public')->assertExists($path);

        $this->get($this->paymentRoute($this->camping, 'qris'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('proof.path', $path)
                ->where('proof.name', 'bukti.jpg')
                ->has('proof.url')
            );
    }

    public function test_nama_berkas_asli_tidak_dipakai_di_disk(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof(
            $this->camping,
            UploadedFile::fake()->image('../../bukti-transfer.png'),
        )->assertRedirect();

        $path = $this->draftProofPath();

        $this->assertIsString($path);
        $this->assertStringStartsWith('payments/', $path);
        $this->assertStringNotContainsString('..', $path);
    }

    public function test_semua_format_gambar_diterima(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $index => $extension) {
            $this->uploadProof(
                $this->camping,
                UploadedFile::fake()->image('bukti.'.$extension),
            )->assertSessionHasNoErrors();

            $this->assertIsString($this->draftProofPath());
            unset($index);
        }
    }

    public function test_format_bukan_gambar_ditolak(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof(
            $this->camping,
            UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        )->assertSessionHasErrors('proof');

        $this->assertArrayNotHasKey('payment_proof', session(BookingDraft::SESSION_KEY));
        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_berkas_lebih_dari_lima_mb_ditolak(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof(
            $this->camping,
            UploadedFile::fake()->create('bukti.jpg', 5121, 'image/jpeg'),
        )->assertSessionHasErrors('proof');

        $this->assertArrayNotHasKey('payment_proof', session(BookingDraft::SESSION_KEY));
        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_berkas_tepat_lima_mb_diterima(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof(
            $this->camping,
            UploadedFile::fake()->create('bukti.jpg', 5120, 'image/jpeg'),
        )->assertSessionHasNoErrors();

        $this->assertIsString($this->draftProofPath());
    }

    public function test_bukti_wajib_untuk_qris(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, null)->assertSessionHasErrors('proof');

        $this->assertArrayNotHasKey('payment_proof', session(BookingDraft::SESSION_KEY));
    }

    public function test_bukti_wajib_untuk_transfer_bank(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->bankTransfer()->create();
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'bank_transfer');

        $this->uploadProof($this->camping, null)->assertSessionHasErrors('proof');
    }

    public function test_bukti_tidak_wajib_untuk_cash(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->cash()->create();
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->uploadProof($this->camping, null)
            ->assertRedirect(route('booking.gokemping.payment.show', ['method' => 'cash']));

        $this->assertArrayNotHasKey('payment_proof', session(BookingDraft::SESSION_KEY));
    }

    public function test_bukti_cash_boleh_diunggah_bila_mau(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->cash()->create();
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertSessionHasNoErrors();

        $this->assertIsString($this->draftProofPath());
    }

    public function test_mengganti_bukti_menghapus_berkas_lama(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('lama.jpg'))
            ->assertSessionHasNoErrors();

        $first = $this->draftProofPath();

        $this->uploadProof($this->camping, UploadedFile::fake()->image('baru.jpg'))
            ->assertSessionHasNoErrors();

        $second = $this->draftProofPath();

        $this->assertIsString($first);
        $this->assertIsString($second);
        $this->assertNotSame($first, $second);

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->assertCount(1, Storage::disk('public')->allFiles('payments'));
    }

    public function test_menghapus_bukti_menghapus_berkas_dari_disk(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'));
        $path = $this->draftProofPath();

        $this->deleteProof($this->camping)
            ->assertRedirect(route('booking.gokemping.payment.show', ['method' => 'qris']));

        $this->assertIsString($path);
        Storage::disk('public')->assertMissing($path);

        $this->assertArrayNotHasKey('payment_proof', session(BookingDraft::SESSION_KEY));
        $this->assertArrayNotHasKey('payment_proof_original_name', session(BookingDraft::SESSION_KEY));
    }

    public function test_menghapus_bukti_tidak_menghapus_data_draft_lain(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');
        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'));

        $this->deleteProof($this->camping);

        $draft = session(BookingDraft::SESSION_KEY);

        $this->assertSame('qris', $draft['payment_method']);
        $this->assertSame('Zaki', $draft['customer']['name']);
        $this->assertSame('2026-10-10', $draft['start_date']);
    }

    public function test_halaman_pembayaran_menampilkan_bukti_lama(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');
        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'));

        $this->get($this->paymentRoute($this->camping, 'qris'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('proof.name', 'bukti.jpg')
                ->where('proof.url', Storage::disk('public')->url($this->draftProofPath()))
            );
    }

    public function test_halaman_pembayaran_tanpa_bukti_menampilkan_null(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->get($this->paymentRoute($this->camping, 'qris'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('proof', null));
    }

    public function test_upload_tanpa_draft_menghasilkan_404(): void
    {
        $this->qrisMethod($this->camping);

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertNotFound();

        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_upload_tanpa_metode_diarahkan_ke_review(): void
    {
        $this->qrisMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->saveCustomer($this->camping);

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertRedirect(route('booking.gokemping.review'));

        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_upload_melewati_biodata_diarahkan_ke_form_biodata(): void
    {
        $this->qrisMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertRedirect(route('booking.gokemping.biodata'));

        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_upload_ke_unit_lain_menghasilkan_404(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->bike, UploadedFile::fake()->image('bukti.jpg'))
            ->assertNotFound();

        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_metode_jadi_nonaktif_diarahkan_ke_review(): void
    {
        $method = $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $method->update(['is_active' => false]);

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertRedirect(route('booking.gokemping.review'));

        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_qris_tanpa_gambar_diarahkan_ke_review(): void
    {
        $method = PaymentMethod::factory()->forBusiness($this->camping)->qris()->create();
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $method->update(['qris_image' => null]);

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertRedirect(route('booking.gokemping.review'));

        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));
    }

    public function test_bukti_transfer_tidak_boleh_diunggah_ke_unit_lain(): void
    {
        PaymentMethod::factory()->forBusiness($this->bike)->bankTransfer()->create();
        $this->createCompleteDraft($this->bike, $this->bicycle);
        $this->selectMethod($this->bike, 'bank_transfer');

        $this->uploadProof($this->bike, UploadedFile::fake()->image('bukti.jpg'))
            ->assertSessionHasNoErrors();

        $this->assertIsString($this->draftProofPath());
    }

    public function test_hapus_bukti_tanpa_draft_menghasilkan_404(): void
    {
        $this->qrisMethod($this->camping);

        $this->deleteProof($this->camping)->assertNotFound();
    }

    public function test_hapus_bukti_tanpa_metode_diarahkan_ke_review(): void
    {
        $this->qrisMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->saveCustomer($this->camping);

        $this->deleteProof($this->camping)
            ->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_hapus_bukti_tanpa_berkas_tetap_berhasil(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->deleteProof($this->camping)
            ->assertRedirect(route('booking.gokemping.payment.show', ['method' => 'qris']));

        $this->assertArrayNotHasKey('payment_proof', session(BookingDraft::SESSION_KEY));
    }

    public function test_upload_tidak_menulis_ke_database(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'));

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_items', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_upload_tidak_membutuhkan_login(): void
    {
        $this->qrisMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->assertGuest();

        $this->uploadProof($this->camping, UploadedFile::fake()->image('bukti.jpg'))
            ->assertSessionHasNoErrors();
    }

    public function test_upload_untuk_unit_sepeda(): void
    {
        PaymentMethod::factory()->forBusiness($this->bike)->bankTransfer()->create();
        $this->createPeriodDraft($this->bike, $this->bicycle, ['quantity' => 3]);
        $this->saveCustomer($this->bike, ['renter_count' => 3]);
        $this->selectMethod($this->bike, 'bank_transfer');

        $this->uploadProof($this->bike, UploadedFile::fake()->image('bukti.jpg'))
            ->assertRedirect(route('booking.sewaSepedaGarut.payment.show', ['method' => 'bank_transfer']));

        $this->assertIsString($this->draftProofPath());
    }

    public function test_alur_lengkap_unggah_lalu_ganti_lalu_hapus(): void
    {
        $this->qrisMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->saveCustomer($this->camping);
        $this->selectMethod($this->camping, 'qris');

        $this->uploadProof($this->camping, UploadedFile::fake()->image('pertama.jpg'));
        $first = $this->draftProofPath();

        $this->uploadProof($this->camping, UploadedFile::fake()->image('kedua.jpg'));
        $second = $this->draftProofPath();

        $this->deleteProof($this->camping);

        $this->assertIsString($first);
        $this->assertIsString($second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertMissing($second);
        $this->assertEmpty(Storage::disk('public')->allFiles('payments'));

        $this->get($this->paymentRoute($this->camping, 'qris'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('proof', null));
    }

    private function qrisMethod(Business $business): PaymentMethod
    {
        return PaymentMethod::factory()->forBusiness($business)->qris()->create();
    }

    private function createCompleteDraft(Business $business, Product $product): void
    {
        $this->createPeriodDraft($business, $product);
        $this->saveCustomer($business);
    }

    private function createPeriodDraft(
        Business $business,
        Product $product,
        array $period = [],
    ): void {
        $this->post($this->draftRoute($business, $product), array_merge([
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'quantity' => 2,
        ], $period))->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function saveCustomer(Business $business, array $overrides = []): void
    {
        $this->post(route('booking.'.$this->prefix($business).'.biodata.store'), array_merge([
            'name' => 'Zaki',
            'whatsapp' => '08123456789',
            'nik' => '3201234567890001',
            'address' => 'Garut',
            'city' => 'Garut',
        ], $overrides))->assertRedirect();
    }

    private function selectMethod(Business $business, string $method)
    {
        return $this->post(
            route('booking.'.$this->prefix($business).'.payment.store'),
            ['method' => $method],
        );
    }

    private function uploadProof(Business $business, ?UploadedFile $file)
    {
        return $this->post(
            route('booking.'.$this->prefix($business).'.payment.proof.store'),
            $file === null ? [] : ['proof' => $file],
        );
    }

    private function deleteProof(Business $business)
    {
        return $this->delete(
            route('booking.'.$this->prefix($business).'.payment.proof.destroy'),
        );
    }

    /**
     * Path bukti yang tersimpan di draft, atau `null` kalau belum ada.
     */
    private function draftProofPath(): ?string
    {
        $path = session(BookingDraft::SESSION_KEY)['payment_proof'] ?? null;

        return is_string($path) ? $path : null;
    }

    private function prefix(Business $business): string
    {
        return $business->slug === 'gokemping' ? 'gokemping' : 'sewaSepedaGarut';
    }

    private function draftRoute(Business $business, Product $product): string
    {
        return route('booking.'.$this->prefix($business).'.draft.store', ['product' => $product->slug]);
    }

    private function paymentRoute(Business $business, string $method): string
    {
        return route('booking.'.$this->prefix($business).'.payment.show', ['method' => $method]);
    }
}
