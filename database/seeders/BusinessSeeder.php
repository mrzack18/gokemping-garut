<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Membuat dua unit bisnis yang terpisah beserta kategori, produk, metode
 * pembayaran, dan admin masing-masing.
 */
class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $gokemping = Business::updateOrCreate(
            ['slug' => 'gokemping'],
            [
                'name' => 'GoKemping',
                'description' => 'Penyewaan perlengkapan camping terlengkap di Garut.',
                'service_intro' => 'Sewa perlengkapan camping lengkap untuk kebutuhan camping dan outdoor.',
                'service_highlights' => [
                    'Tenda, sleeping bag, matras, dan kursi lipat',
                    'Kompor camping serta carrier siap pakai',
                    'Perlengkapan dibersihkan sebelum disewakan',
                ],
                'rental_terms' => 'Penyewa wajib membawa KTP asli. Kerusakan atau kehilangan barang menjadi tanggung jawab penyewa selama masa sewa.',
                'whatsapp' => '6281234567890',
                'phone' => '081234567890',
                'email' => 'admin@gokemping.test',
                'address' => 'Jl. Raya Garut No. 1, Garut, Jawa Barat',
                'booking_code_prefix' => 'GK',
                'is_active' => true,
            ],
        );

        $sewaSepeda = Business::updateOrCreate(
            ['slug' => 'sewa-sepeda-garut'],
            [
                'name' => 'Sewa Sepeda Garut',
                'description' => 'Penyewaan sepeda untuk gowes dan rekreasi di Garut.',
                'service_intro' => 'Sewa sepeda untuk gowes, rekreasi, maupun aktivitas outdoor di Garut.',
                'service_highlights' => [
                    'MTB, city bike, dan sepeda anak',
                    'Wajib membawa KTP dan memakai helm',
                    'Sepeda dicek sebelum dan sesudah disewa',
                ],
                'rental_terms' => 'Penyewa wajib membawa KTP asli dan memakai helm. Sepeda dikembalikan dalam kondisi seperti saat diambil.',
                'whatsapp' => '6289876543210',
                'phone' => '089876543210',
                'email' => 'admin@sewasepedagarut.test',
                'address' => 'Jl. Sudirman No. 2, Garut, Jawa Barat',
                'booking_code_prefix' => 'SSG',
                'is_active' => true,
            ],
        );

        $this->seedGoKempingCatalog($gokemping);
        $this->seedSepedaCatalog($sewaSepeda);
        $this->seedFaqs($gokemping);
        $this->seedFaqs($sewaSepeda);
    }

    /**
     * FAQ awal tiap unit. Isinya umum dan disesuaikan lagi lewat halaman
     * konten admin.
     */
    private function seedFaqs(Business $business): void
    {
        $faqs = [
            [
                'question' => 'Apakah harus membuat akun sebelum menyewa?',
                'answer' => 'Tidak. Anda cukup memilih barang, mengisi data penyewa, lalu konfirmasi pesanan melalui WhatsApp admin.',
            ],
            [
                'question' => 'Bagaimana cara membayar?',
                'answer' => 'Pembayaran manual setelah pesanan dibuat: cash saat pengambilan, QRIS, atau transfer bank. Admin mengonfirmasi status pembayarannya.',
            ],
            [
                'question' => 'Apakah barang dijamin tersedia?',
                'answer' => 'Ya. Halaman booking menghitung stok terpakai dari booking aktif pada tanggal yang dipilih, jadi tidak ada overbooking.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            $business->faqs()->updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedGoKempingCatalog(Business $business): void
    {
        $catalog = [
            'Tenda' => [
                ['name' => 'Tenda Dome 4 Person', 'price' => 75000, 'stock' => 5, 'spec' => ['Kapasitas' => '4 orang', 'Berat' => '3,2 kg', 'Musim' => '3 season']],
                ['name' => 'Tenda Family 8 Person', 'price' => 150000, 'stock' => 3, 'spec' => ['Kapasitas' => '8 orang', 'Berat' => '7,5 kg', 'Musim' => '4 season']],
            ],
            'Sleeping Bag' => [
                ['name' => 'Sleeping Bag Nyaman 5°C', 'price' => 35000, 'stock' => 12, 'spec' => ['Rating' => '5°C', 'Bahan' => 'Polyester 190T']],
            ],
            'Matras' => [
                ['name' => 'Matras Camping Self Inflating', 'price' => 45000, 'stock' => 8, 'spec' => ['Ukuran' => '190 x 60 cm', 'Ketebalan' => '5 cm']],
            ],
            'Kompor Camping' => [
                ['name' => 'Kompor Camping Gas Outdoor', 'price' => 40000, 'stock' => 10, 'spec' => ['Bahan bakar' => 'Gas', 'Daya' => '3,5 kW']],
            ],
            'Carrier' => [
                ['name' => 'Carrier 60 Liter', 'price' => 50000, 'stock' => 6, 'spec' => ['Kapasitas' => '60 liter', 'Bahan' => 'Cordura 600D']],
            ],
            'Kursi Camping' => [
                ['name' => 'Kursi Camping Lipat', 'price' => 25000, 'stock' => 20, 'spec' => ['Bahan' => 'Baja', 'Berat' => '2,1 kg']],
            ],
        ];

        foreach ($catalog as $categoryName => $products) {
            $category = $business->categories()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'description' => 'Kategori '.$categoryName.' untuk '.$business->name.'.',
                    'sort_order' => 0,
                    'is_active' => true,
                ],
            );

            foreach ($products as $product) {
                $business->products()->updateOrCreate(
                    ['slug' => Str::slug($product['name'])],
                    [
                        'category_id' => $category->id,
                        'name' => $product['name'],
                        'description' => $product['name'].' untuk kebutuhan outdoor, siap pakai dan sudah dibersihkan sebelum disewakan.',
                        'specification' => $product['spec'],
                        'rental_terms' => 'Wajib dikembalikan dalam kondisi bersih dan utuh. Kerusakan akibat penyewa menjadi tanggung jawab penyewa.',
                        'price' => $product['price'],
                        'price_unit' => 'hari',
                        'stock' => $product['stock'],
                        'is_active' => true,
                    ],
                );
            }
        }

        $this->seedPaymentMethods($business, 'GoKemping');
    }

    private function seedSepedaCatalog(Business $business): void
    {
        $catalog = [
            'Sepeda MTB' => [
                ['name' => 'Sepeda MTB Polygon Bromo', 'price' => 90000, 'stock' => 6, 'spec' => ['Ukuran' => 'M (17-19")', 'Speeds' => '21', 'Wheels' => '26 inch']],
                ['name' => 'Sepeda MTB United Nitro', 'price' => 85000, 'stock' => 4, 'spec' => ['Ukuran' => 'L (19-21")', 'Speeds' => '24', 'Wheels' => '27.5 inch']],
            ],
            'Sepeda City Bike' => [
                ['name' => 'Sepeda City Bike Polygon', 'price' => 70000, 'stock' => 8, 'spec' => ['Ukuran' => 'M', 'Speeds' => '7', 'Wheels' => '26 inch']],
            ],
            'Sepeda Anak' => [
                ['name' => 'Sepeda Anak 12 Inch', 'price' => 45000, 'stock' => 10, 'spec' => ['Usia' => '4-6 tahun', 'Wheels' => '12 inch']],
            ],
        ];

        foreach ($catalog as $categoryName => $products) {
            $category = $business->categories()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'description' => 'Kategori '.$categoryName.' untuk '.$business->name.'.',
                    'sort_order' => 0,
                    'is_active' => true,
                ],
            );

            foreach ($products as $product) {
                $business->products()->updateOrCreate(
                    ['slug' => Str::slug($product['name'])],
                    [
                        'category_id' => $category->id,
                        'name' => $product['name'],
                        'description' => $product['name'].', siap dikendarai dan sudah dicek sebelum disewakan.',
                        'specification' => $product['spec'],
                        'rental_terms' => 'Penyewa wajib bring KTP dan wears helmet. Sepeda harus dikembalikan sesuai tanggal sewa.',
                        'price' => $product['price'],
                        'price_unit' => 'hari',
                        'stock' => $product['stock'],
                        'is_active' => true,
                    ],
                );
            }
        }

        $this->seedPaymentMethods($business, 'Sewa Sepeda Garut');
    }

    private function seedPaymentMethods(Business $business, string $ownerName): void
    {
        $business->paymentMethods()->updateOrCreate(
            ['type' => 'cash'],
            [
                'instructions' => 'Pembayaran cash dilakukan langsung kepada admin '.$business->name.' pada saat pengambilan barang.',
                'is_active' => true,
            ],
        );

        $business->paymentMethods()->updateOrCreate(
            ['type' => 'qris'],
            [
                'merchant_name' => $business->name,
                'is_active' => true,
            ],
        );

        $business->paymentMethods()->updateOrCreate(
            ['type' => 'bank_transfer'],
            [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => $ownerName,
                'is_active' => true,
            ],
        );
    }
}
