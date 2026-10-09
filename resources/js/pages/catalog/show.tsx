import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import PageHeader from '@/components/public/page-header';
import StatusPill from '@/components/public/status-pill';
import { Button } from '@/components/ui/button';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import PublicLayout from '@/layouts/public-layout';
import { formatRupiah } from '@/lib/format';
import bookingRoutes from '@/routes/booking';
import catalogRoutes from '@/routes/catalog';
import type { ProductDetailPageProps } from '@/types';
import { ArrowRight, ImageOff } from 'lucide-react';

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

            <PageHeader
                title={product.name}
                eyebrow={`${business.name}${product.category ? ` · ${product.category.name}` : ''}`}
                backHref={catalogUrl}
                backLabel={`Katalog ${business.name}`}
                actions={
                    <StatusPill
                        tone={
                            !product.is_available
                                ? 'unavailable'
                                : product.available_now > 0
                                  ? 'available'
                                  : 'pending'
                        }
                    >
                        {!product.is_available
                            ? 'Stok habis'
                            : product.available_now > 0
                              ? `${product.available_now} tersedia hari ini`
                              : 'Terbooking hari ini'}
                    </StatusPill>
                }
                className="bg-sand-50"
            />

            <section
                aria-label="Informasi produk"
                className="mx-auto w-full max-w-6xl px-4 py-8 pb-28 sm:px-6 sm:py-10 sm:pb-28 lg:pb-16"
            >
                <div className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12">
                    <motion.div
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                        className="space-y-3"
                    >
                        {product.images.length === 0 ? (
                            <div className="flex aspect-4/3 w-full flex-col items-center justify-center gap-3 rounded-lg border border-border bg-muted text-muted-foreground">
                                <ImageOff
                                    aria-hidden="true"
                                    className="size-8"
                                />
                                <span className="text-sm">
                                    Foto produk belum tersedia
                                </span>
                            </div>
                        ) : product.images.length === 1 ? (
                            <div className="aspect-4/3 w-full overflow-hidden rounded-lg bg-muted">
                                <img
                                    src={product.images[0].url}
                                    alt={product.name}
                                    width={1000}
                                    height={750}
                                    fetchPriority="high"
                                    className="size-full object-cover"
                                />
                            </div>
                        ) : (
                            <Carousel
                                opts={{ align: 'start', loop: false }}
                                className="w-full"
                                aria-label={`Foto ${product.name}`}
                            >
                                <CarouselContent>
                                    {product.images.map((image, index) => (
                                        <CarouselItem key={image.url}>
                                            <div className="aspect-4/3 w-full overflow-hidden rounded-lg bg-muted">
                                                <img
                                                    src={image.url}
                                                    alt={`${product.name} — foto ${index + 1}`}
                                                    width={1000}
                                                    height={750}
                                                    loading={
                                                        index === 0
                                                            ? 'eager'
                                                            : 'lazy'
                                                    }
                                                    className="size-full object-cover"
                                                />
                                            </div>
                                        </CarouselItem>
                                    ))}
                                </CarouselContent>
                                <CarouselPrevious
                                    aria-label="Foto sebelumnya"
                                    className="left-3"
                                />
                                <CarouselNext
                                    aria-label="Foto berikutnya"
                                    className="right-3"
                                />
                            </Carousel>
                        )}

                        {product.images.length > 1 ? (
                            <p className="text-xs text-muted-foreground">
                                {product.images.length} foto · gunakan tombol
                                panah atau tombol keyboard untuk melihat foto
                                lain.
                            </p>
                        ) : null}
                    </motion.div>

                    <motion.aside
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45, delay: 0.08 }}
                        className="flex flex-col border-y border-border py-5 lg:sticky lg:top-24 lg:h-fit"
                    >
                        <p className="text-xs font-semibold tracking-[0.15em] text-pine-600 uppercase">
                            Harga sewa
                        </p>
                        <p className="mt-2 font-display text-3xl font-semibold tracking-tight tabular-nums sm:text-4xl">
                            {formatRupiah(product.price)}
                            <span className="ml-2 font-sans text-sm font-normal text-muted-foreground">
                                / {product.price_unit}
                            </span>
                        </p>
                        <p className="mt-3 text-sm text-muted-foreground">
                            {product.is_available
                                ? product.available_now > 0
                                    ? `${product.available_now} dari ${product.stock} unit tersedia hari ini. Stok untuk tanggal lain akan dicek saat booking.`
                                    : 'Semua unit sedang dibooking atau disewa hari ini. Kamu tetap bisa memilih tanggal lain untuk memeriksa ketersediaan.'
                                : 'Stok produk ini sedang kosong. Hubungi admin untuk menanyakan jadwal berikutnya.'}
                        </p>

                        {product.booked_periods.length > 0 ? (
                            <div className="mt-5 border-t border-border pt-4">
                                <h2 className="font-display text-sm font-semibold">
                                    Periode booking dan sewa
                                </h2>
                                <ul className="mt-3 space-y-2">
                                    {product.booked_periods
                                        .slice(0, 5)
                                        .map((period, index) => (
                                            <li
                                                key={`${period.period_label}-${index}`}
                                                className="flex items-start justify-between gap-3 text-xs"
                                            >
                                                <div>
                                                    <p className="font-medium">
                                                        {period.period_label}
                                                    </p>
                                                    <p className="mt-0.5 text-muted-foreground">
                                                        {period.status_label}
                                                        {period.is_overdue
                                                            ? ' · melewati tanggal pengembalian'
                                                            : ''}
                                                    </p>
                                                </div>
                                                <span className="shrink-0 text-muted-foreground tabular-nums">
                                                    {period.quantity} unit
                                                </span>
                                            </li>
                                        ))}
                                </ul>
                                {product.booked_periods_count > 5 ? (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        +{product.booked_periods_count - 5}{' '}
                                        periode booking lainnya
                                    </p>
                                ) : null}
                            </div>
                        ) : null}

                        <div className="mt-6 border-t border-border pt-5">
                            {product.is_available ? (
                                <Button asChild size="lg" className="w-full">
                                    <Link href={bookingUrl}>
                                        Atur jadwal sewa
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="lg" className="w-full" disabled>
                                    Stok belum tersedia
                                </Button>
                            )}
                            <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                                Ketersediaan akhir dikonfirmasi berdasarkan
                                tanggal dan jumlah yang kamu pilih.
                            </p>
                        </div>
                    </motion.aside>
                </div>

                <div className="mt-12 grid gap-x-12 gap-y-9 border-t border-border pt-8 lg:grid-cols-[1fr_0.9fr]">
                    <div className="space-y-8">
                        {product.description ? (
                            <section>
                                <h2 className="font-display text-lg font-semibold tracking-tight">
                                    Tentang produk
                                </h2>
                                <p className="mt-3 text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                                    {product.description}
                                </p>
                            </section>
                        ) : null}

                        {product.rental_terms ? (
                            <section className="border-t border-border pt-6">
                                <h2 className="font-display text-lg font-semibold tracking-tight">
                                    Ketentuan penyewaan
                                </h2>
                                <p className="mt-3 text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                                    {product.rental_terms}
                                </p>
                            </section>
                        ) : null}
                    </div>

                    {specification.length > 0 ? (
                        <section>
                            <h2 className="font-display text-lg font-semibold tracking-tight">
                                Spesifikasi
                            </h2>
                            <dl className="mt-3 divide-y divide-border border-y border-border">
                                {specification.map(([label, value]) => (
                                    <div
                                        key={label}
                                        className="grid gap-1 py-3 text-sm sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)] sm:gap-4"
                                    >
                                        <dt className="text-muted-foreground">
                                            {label}
                                        </dt>
                                        <dd className="font-medium">
                                            {String(value)}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    ) : null}
                </div>
            </section>

            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-1">
                    <div className="min-w-0">
                        <p className="truncate text-xs text-muted-foreground">
                            {product.price_unit}
                        </p>
                        <p className="font-semibold tabular-nums">
                            {formatRupiah(product.price)}
                        </p>
                    </div>
                    {product.is_available ? (
                        <Button asChild className="shrink-0">
                            <Link href={bookingUrl}>Sewa sekarang</Link>
                        </Button>
                    ) : (
                        <Button className="shrink-0" disabled>
                            Stok habis
                        </Button>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
