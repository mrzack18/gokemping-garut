import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { Separator } from '@/components/ui/separator';
import PublicLayout from '@/layouts/public-layout';
import { formatRupiah } from '@/lib/format';
import bookingRoutes from '@/routes/booking';
import catalogRoutes from '@/routes/catalog';
import type { ProductDetailPageProps } from '@/types';
import { ArrowLeft, ImageOff } from 'lucide-react';

const catalogUrlBySlug: Record<string, string> = {
    gokemping: catalogRoutes.gokemping.url(),
    'sewa-sepeda-garut': catalogRoutes.sewaSepedaGarut.url(),
};

export default function ProductDetailPage({
    business,
    businesses,
    product,
}: ProductDetailPageProps) {
    const catalogUrl = catalogUrlBySlug[business.slug] ?? `/${business.slug}`;
    const bookingUrlBySlug: Record<string, string> = {
        gokemping: bookingRoutes.gokemping.create.url(product.slug),
        'sewa-sepeda-garut': bookingRoutes.sewaSepedaGarut.create.url(
            product.slug,
        ),
    };
    const bookingUrl =
        bookingUrlBySlug[business.slug] ?? `/booking/${product.slug}`;
    const specification = Object.entries(product.specification);

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={product.name} />

            <section className="border-b">
                <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-3"
                        >
                            <Link href={catalogUrl}>
                                <ArrowLeft className="size-4" />
                                Katalog {business.name}
                            </Link>
                        </Button>

                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            {product.category ? (
                                <Badge
                                    variant="secondary"
                                    className="font-normal"
                                >
                                    {product.category.name}
                                </Badge>
                            ) : null}
                            <Badge
                                variant={
                                    product.is_available
                                        ? 'default'
                                        : 'destructive'
                                }
                            >
                                {product.is_available
                                    ? 'Tersedia'
                                    : 'Tidak tersedia'}
                            </Badge>
                        </div>

                        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            {product.name}
                        </h1>

                        <div className="mt-4 flex flex-wrap items-baseline gap-x-2">
                            <span className="text-3xl font-semibold tabular-nums">
                                {formatRupiah(product.price)}
                            </span>
                            <span className="text-muted-foreground">
                                / {product.price_unit}
                            </span>
                        </div>

                        <p className="mt-2 text-sm text-muted-foreground">
                            {product.is_available
                                ? `Sisa ${product.stock} unit tersedia`
                                : 'Stok produk ini sedang kosong'}
                        </p>
                    </motion.div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                <div className="grid gap-10 lg:grid-cols-2">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45 }}
                        className="space-y-4"
                    >
                        {product.images.length === 0 ? (
                            <div className="flex aspect-4/3 w-full flex-col items-center justify-center gap-2 rounded-lg border bg-muted text-muted-foreground">
                                <ImageOff className="size-8" />
                                <span className="text-sm">
                                    Foto produk belum tersedia
                                </span>
                            </div>
                        ) : product.images.length === 1 ? (
                            <div className="aspect-4/3 w-full overflow-hidden rounded-lg bg-muted">
                                <img
                                    src={product.images[0].url}
                                    alt={product.name}
                                    className="size-full object-cover"
                                />
                            </div>
                        ) : (
                            <Carousel
                                opts={{ align: 'start', loop: false }}
                                className="w-full"
                            >
                                <CarouselContent>
                                    {product.images.map((image, index) => (
                                        <CarouselItem key={image.url}>
                                            <div className="aspect-4/3 w-full overflow-hidden rounded-lg bg-muted">
                                                <img
                                                    src={image.url}
                                                    alt={`${product.name} - foto ${index + 1}`}
                                                    className="size-full object-cover"
                                                />
                                            </div>
                                        </CarouselItem>
                                    ))}
                                </CarouselContent>
                                <CarouselPrevious className="left-2" />
                                <CarouselNext className="right-2" />
                            </Carousel>
                        )}

                        {product.images.length > 1 ? (
                            <p className="text-xs text-muted-foreground">
                                {product.images.length} foto tersedia. Gunakan
                                tombol panah atau tombol keyboard untuk
                                berpindah foto.
                            </p>
                        ) : null}
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45, delay: 0.1 }}
                        className="space-y-4"
                    >
                        {product.description ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Deskripsi
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                                        {product.description}
                                    </p>
                                </CardContent>
                            </Card>
                        ) : null}

                        {specification.length > 0 ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Spesifikasi
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="divide-y">
                                        {specification.map(([label, value]) => (
                                            <div
                                                key={label}
                                                className="grid grid-cols-3 gap-4 py-2 text-sm"
                                            >
                                                <dt className="text-muted-foreground">
                                                    {label}
                                                </dt>
                                                <dd className="col-span-2">
                                                    {String(value)}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                </CardContent>
                            </Card>
                        ) : null}

                        {product.rental_terms ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Ketentuan penyewaan
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                                        {product.rental_terms}
                                    </p>
                                </CardContent>
                            </Card>
                        ) : null}

                        <Separator />

                        <div className="space-y-3">
                            {product.is_available ? (
                                <Button
                                    asChild
                                    className="w-full sm:w-auto"
                                    size="lg"
                                >
                                    <Link href={bookingUrl}>Sewa Sekarang</Link>
                                </Button>
                            ) : (
                                <Button
                                    className="w-full sm:w-auto"
                                    size="lg"
                                    disabled
                                >
                                    Sewa Sekarang
                                </Button>
                            )}
                            <p className="text-xs text-muted-foreground">
                                {product.is_available
                                    ? 'Form booking untuk memilih jadwal sewa dan jumlah barang.'
                                    : 'Pemesanan belum bisa dilakukan karena stok kosong. Hubungi admin unit ini untuk informasi ketersediaan.'}
                            </p>
                        </div>
                    </motion.div>
                </div>
            </section>
        </PublicLayout>
    );
}
