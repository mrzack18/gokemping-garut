import { Form, Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowLeft } from 'lucide-react';
import ProductFormFields from '@/components/admin/product-form-fields';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import productRoutes from '@/routes/admin/products';
import type { AdminProductFormPageProps } from '@/types';

/**
 * Halaman tambah produk (PRD section 23, ROADMAP 4.3).
 *
 * Produk disimpan lebih dulu, baru foto diunggah dari halaman edit. Alasannya
 * foto memakai `products/{id}/{uuid}.webp`, jadi nama foldernya butuh id yang
 * sudah ada di database. Foto tetap bisa dipilih di form ini: berkas dikirim
 * bersama produk dan baru diproses setelah produknya tersimpan.
 */
export default function AdminProductsCreate({
    categories,
    priceUnits,
    maxImages,
}: AdminProductFormPageProps) {
    return (
        <>
            <Head title="Tambah Produk" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        <Button asChild variant="ghost" size="sm">
                            <Link
                                href={productRoutes.index()}
                                className="-ml-3 w-fit"
                            >
                                <ArrowLeft />
                                Kembali ke daftar
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Tambah Produk
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Produk baru langsung tampil di katalog kalau
                            statusnya aktif.
                        </p>
                    </div>
                </motion.div>

                <Form
                    {...productRoutes.store.form()}
                    encType="multipart/form-data"
                    options={{
                        preserveScroll: true,
                    }}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <ProductFormFields
                                categories={categories}
                                priceUnits={priceUnits}
                                categoryRequired
                                errors={errors}
                                defaults={{
                                    name: '',
                                    categoryId: null,
                                    description: '',
                                    specification: [],
                                    rentalTerms: '',
                                    price: '',
                                    priceUnit: 'hari',
                                    stock: '1',
                                    isActive: true,
                                }}
                            />

                            <div className="grid gap-2 rounded-lg border p-4">
                                <Label htmlFor="images">
                                    Foto produk (opsional)
                                </Label>
                                <input
                                    id="images"
                                    name="images[]"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    aria-invalid={errors.images !== undefined}
                                    className="text-sm"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Maksimal {maxImages} foto, tiap foto
                                    maksimal 5 MB. Foto dikompresi ulang jadi
                                    WebP dengan sisi terpanjang 1200 piksel,
                                    jadi berkas aslinya tidak ikut disimpan.
                                </p>
                                <InputError message={errors.images} />
                            </div>

                            <div className="flex flex-wrap items-center justify-end gap-2">
                                <Button asChild variant="outline">
                                    <Link href={productRoutes.index()}>
                                        Batal
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Produk'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

AdminProductsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Produk',
            href: productRoutes.index(),
        },
        {
            title: 'Tambah Produk',
            href: productRoutes.create(),
        },
    ],
};
