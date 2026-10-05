import { Form, Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowLeft } from 'lucide-react';
import ProductFormFields from '@/components/admin/product-form-fields';
import ProductPhotoGallery from '@/components/admin/product-photo-gallery';
import { Button } from '@/components/ui/button';
import productRoutes from '@/routes/admin/products';
import type { AdminProductEditPageProps } from '@/types';

/**
 * Halaman edit produk (PRD section 23, ROADMAP 4.3).
 *
 * Nama boleh diubah, URL produk tidak. Slug produk ada di tautan katalog dan di
 * halaman booking yang sudah dibagikan, jadi perubahan nama tidak boleh memutus
 * tautan itu. Slug ditampilkan sebagai teks bacaan supaya admin tahu halaman
 * publiknya tidak ikut berubah.
 *
 * Galeri foto punya form sendiri. Unggah foto lewat form produk akan mengirim
 * ulang seluruh isian yang belum disimpan, jadi isian yang sedang diketik bisa
 * hilang tanpa sebab yang jelas.
 */
export default function AdminProductsEdit({
    product,
    categories,
    priceUnits,
    maxImages,
    remainingImages,
}: AdminProductEditPageProps) {
    return (
        <>
            <Head title={`Edit ${product.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-1"
                >
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
                        Edit Produk
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        URL publik: /{product.slug}
                        {product.bookingCount > 0
                            ? ` · ${product.bookingCount} booking memakai produk ini`
                            : ''}
                    </p>
                </motion.div>

                <Form
                    {...productRoutes.update.form({
                        product: product.slug,
                    })}
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
                                categoryRequired={false}
                                errors={errors}
                                defaults={{
                                    name: product.name,
                                    categoryId: product.category_id,
                                    description: product.description ?? '',
                                    specification: product.specification,
                                    rentalTerms: product.rental_terms ?? '',
                                    price: String(product.price),
                                    priceUnit: product.price_unit,
                                    stock: String(product.stock),
                                    isActive: product.is_active,
                                }}
                            />

                            <div className="flex flex-wrap items-center justify-end gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Perubahan'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <ProductPhotoGallery
                    product={product}
                    maxImages={maxImages}
                    remainingImages={remainingImages}
                />
            </div>
        </>
    );
}

AdminProductsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Produk',
            href: productRoutes.index(),
        },
        {
            title: 'Edit Produk',
        },
    ],
};
