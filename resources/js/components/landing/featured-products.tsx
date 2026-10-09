import { Link } from '@inertiajs/react';
import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import EmptyState from '@/components/public/empty-state';
import ProductCard from '@/components/public/product-card';
import { Button } from '@/components/ui/button';
import catalogRoutes from '@/routes/catalog';
import services from '@/routes/services';
import type { LandingProduct } from '@/types';
import { Boxes, ArrowRight } from 'lucide-react';

type FeaturedProductsProps = {
    products: LandingProduct[];
};

function productUrl(product: LandingProduct): string {
    if (product.business.slug === 'gokemping') {
        return catalogRoutes.gokemping.show.url(product.slug);
    }

    if (product.business.slug === 'sewa-sepeda-garut') {
        return catalogRoutes.sewaSepedaGarut.show.url(product.slug);
    }

    return `/${product.business.slug}/${product.slug}`;
}

function catalogUrl(slug: string): string {
    if (slug === 'gokemping') {
        return catalogRoutes.gokemping.url();
    }

    if (slug === 'sewa-sepeda-garut') {
        return catalogRoutes.sewaSepedaGarut.url();
    }

    return `/${slug}`;
}

export default function FeaturedProducts({ products }: FeaturedProductsProps) {
    const businesses = Array.from(
        new Map(
            products.map((product) => [
                product.business.slug,
                product.business,
            ]),
        ).values(),
    );
    const productsByBusiness = new Map<string, LandingProduct[]>();

    for (const product of products) {
        const businessProducts =
            productsByBusiness.get(product.business.slug) ?? [];

        businessProducts.push(product);
        productsByBusiness.set(product.business.slug, businessProducts);
    }

    const visibleProducts = businesses.flatMap(
        (business) => productsByBusiness.get(business.slug)?.slice(0, 2) ?? [],
    );

    return (
        <Section
            id="produk"
            eyebrow="Produk pilihan"
            title="Perlengkapan untuk perjalananmu"
            description="Lihat foto, harga sewa, dan ketersediaan sebelum menentukan tanggal perjalanan."
        >
            {products.length === 0 ? (
                <EmptyState
                    icon={Boxes}
                    title="Belum ada produk unggulan"
                    description="Katalog akan menampilkan perlengkapan yang siap disewa setelah tersedia."
                />
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {visibleProducts.map((product, index) => (
                        <Reveal key={product.id} delay={(index % 4) * 0.05}>
                            <ProductCard
                                product={product}
                                href={productUrl(product)}
                            />
                        </Reveal>
                    ))}
                </div>
            )}

            <div className="mt-7 flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-muted-foreground">
                    Katalog lengkap menyediakan filter kategori, harga, dan
                    pencarian.
                </p>
                <div className="flex flex-wrap gap-2">
                    {businesses.length > 0 ? (
                        businesses.map((business) => (
                            <Button
                                key={business.id}
                                asChild
                                variant="outline"
                                size="sm"
                            >
                                <Link href={catalogUrl(business.slug)}>
                                    Katalog {business.name}
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                </Link>
                            </Button>
                        ))
                    ) : (
                        <Button asChild variant="link" size="sm">
                            <Link href={services.index()}>
                                Pilih layanan
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-3.5"
                                />
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </Section>
    );
}
