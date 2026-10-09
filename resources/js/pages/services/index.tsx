import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import PublicLayout from '@/layouts/public-layout';
import PageHeader from '@/components/public/page-header';
import EmptyState from '@/components/public/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { serviceCopyFor } from '@/components/landing/service-copy';
import { formatRupiah } from '@/lib/format';
import catalogRoutes from '@/routes/catalog';
import type { ServiceSelectionPageProps } from '@/types';
import {
    ArrowRight,
    Bike,
    MapPin,
    PackageOpen,
    Phone,
    Tent,
} from 'lucide-react';

const icons: Record<string, typeof Tent> = {
    gokemping: Tent,
    'sewa-sepeda-garut': Bike,
};

function catalogHref(slug: string): string {
    if (slug === 'gokemping') {
        return catalogRoutes.gokemping.url();
    }

    if (slug === 'sewa-sepeda-garut') {
        return catalogRoutes.sewaSepedaGarut.url();
    }

    return `/${slug}`;
}

export default function ServiceSelection({
    businesses,
    previewProducts,
}: ServiceSelectionPageProps) {
    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title="Pilih Layanan" />

            <PageHeader
                eyebrow="Pilih layanan"
                title="Mau sewa yang mana?"
                description="Pilih unit yang sesuai dengan rencana perjalananmu. Setelah itu, kamu bisa melihat katalog dan ketersediaan barang."
                className="bg-sand-50"
            />

            <section className="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                {businesses.length === 0 ? (
                    <EmptyState
                        icon={PackageOpen}
                        title="Belum ada layanan aktif"
                        description="Silakan kembali lagi nanti untuk melihat pilihan layanan GoKemping."
                    />
                ) : (
                    <div className="grid gap-6 md:grid-cols-2">
                        {businesses.map((business, index) => {
                            const copy = serviceCopyFor(business);
                            const Icon = icons[business.slug] ?? Tent;
                            const preview = previewProducts.find(
                                (entry) => entry.business_id === business.id,
                            );
                            // Informasi layanan dari admin dipakai lebih dulu;
                            // copy bawaan PRD hanya menjadi cadangan selama
                            // admin belum mengisinya (ROADMAP 5.4).
                            const intro = business.service_intro ?? copy.intro;
                            const highlights =
                                business.service_highlights !== null &&
                                business.service_highlights.length > 0
                                    ? business.service_highlights
                                    : copy.highlights;

                            return (
                                <motion.div
                                    key={business.id}
                                    initial={{ opacity: 0, y: 24 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.45,
                                        delay: index * 0.12,
                                    }}
                                >
                                    <Card className="flex h-full flex-col rounded-lg shadow-none transition-[border-color,box-shadow] duration-200 hover:border-pine-600/40 hover:shadow-sm">
                                        <CardContent className="flex flex-1 flex-col gap-5 p-6">
                                            <div className="flex items-start gap-3">
                                                <span className="flex size-12 shrink-0 items-center justify-center border border-pine-700/20 bg-pine-50 dark:bg-pine-100">
                                                    <Icon
                                                        aria-hidden="true"
                                                        className="size-6 text-pine-700 dark:text-pine-600"
                                                    />
                                                </span>
                                                <div>
                                                    <p className="text-xs font-semibold tracking-[0.14em] text-pine-600 uppercase">
                                                        {business.slug ===
                                                        'gokemping'
                                                            ? 'Outdoor · Camping'
                                                            : 'Outdoor · Sepeda'}
                                                    </p>
                                                    <h2 className="mt-1 font-display text-xl font-semibold tracking-tight">
                                                        {business.name}
                                                    </h2>
                                                    {business.address ? (
                                                        <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                                            <MapPin className="size-3" />
                                                            {business.address}
                                                        </p>
                                                    ) : null}
                                                    {business.phone ? (
                                                        <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Phone className="size-3" />
                                                            {business.phone}
                                                        </p>
                                                    ) : null}
                                                </div>
                                            </div>

                                            <p className="text-sm leading-relaxed text-muted-foreground">
                                                {intro}
                                            </p>

                                            <ul className="space-y-2.5 border-t border-border pt-4 text-sm">
                                                {highlights.map((highlight) => (
                                                    <li
                                                        key={highlight}
                                                        className="flex gap-2.5 text-muted-foreground"
                                                    >
                                                        <span
                                                            aria-hidden="true"
                                                            className="mt-1 size-1.5 shrink-0 rounded-full bg-pine-600"
                                                        />
                                                        {highlight}
                                                    </li>
                                                ))}
                                            </ul>

                                            {business.rental_terms ? (
                                                <div className="rounded-md border border-border bg-sand-50 p-4">
                                                    <p className="text-xs font-medium">
                                                        Ketentuan sewa
                                                    </p>
                                                    <p className="mt-1 text-xs whitespace-pre-line text-muted-foreground">
                                                        {business.rental_terms}
                                                    </p>
                                                </div>
                                            ) : null}

                                            {preview &&
                                            preview.products.length > 0 ? (
                                                <div className="space-y-2">
                                                    <p className="text-xs font-medium text-muted-foreground">
                                                        Contoh barang
                                                    </p>
                                                    <div className="flex flex-wrap gap-2">
                                                        {preview.products.map(
                                                            (product) => (
                                                                <Badge
                                                                    key={
                                                                        product.id
                                                                    }
                                                                    variant="secondary"
                                                                    className="font-normal"
                                                                >
                                                                    {
                                                                        product.name
                                                                    }{' '}
                                                                    &middot;{' '}
                                                                    {formatRupiah(
                                                                        product.price,
                                                                    )}
                                                                    /
                                                                    {
                                                                        product.price_unit
                                                                    }
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            ) : null}

                                            <div className="mt-auto space-y-3 pt-2">
                                                <Button
                                                    asChild
                                                    className="w-full"
                                                >
                                                    <Link
                                                        href={catalogHref(
                                                            business.slug,
                                                        )}
                                                    >
                                                        {copy.buttonLabel}
                                                        <ArrowRight
                                                            aria-hidden="true"
                                                            className="size-4"
                                                        />
                                                    </Link>
                                                </Button>
                                                <p className="text-center text-xs text-muted-foreground">
                                                    Kode booking unit ini
                                                    dimulai dengan{' '}
                                                    <span className="font-medium">
                                                        {
                                                            business.booking_code_prefix
                                                        }
                                                    </span>
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </motion.div>
                            );
                        })}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
