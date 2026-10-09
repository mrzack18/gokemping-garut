import { Form, Head, Link } from '@inertiajs/react';
import AdminPageHeader from '@/components/admin/page-header';
import FileInput from '@/components/admin/file-input';
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

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Tambah Produk"
                    description="Produk baru langsung tampil di katalog kalau statusnya aktif."
                    backHref={productRoutes.index.url()}
                    backLabel="Kembali ke daftar"
                />

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

                            <div className="grid gap-2 rounded-lg border border-dashed border-border bg-muted/30 p-4">
                                <Label htmlFor="images">
                                    Foto produk (opsional)
                                </Label>
                                <FileInput
                                    id="images"
                                    name="images[]"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    aria-invalid={errors.images !== undefined}
                                />
                                <p className="text-sm leading-relaxed text-muted-foreground">
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
