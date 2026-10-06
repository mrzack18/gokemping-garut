import { Head } from '@inertiajs/react';
import { motion } from 'motion/react';
import PublicLayout from '@/layouts/public-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { serviceCopyFor } from '@/components/landing/service-copy';
import { formatRupiah } from '@/lib/format';
import type { ServiceSelectionPageProps } from '@/types';
import { ArrowRight, Bike, MapPin, Phone, Tent } from 'lucide-react';

const icons: Record<string, typeof Tent> = {
    gokemping: Tent,
    'sewa-sepeda-garut': Bike,
};

export default function ServiceSelection({
    businesses,
    previewProducts,
}: ServiceSelectionPageProps) {
    return (
        <PublicLayout businesses={businesses}>
            <Head title="Pilih Layanan" />

            <section className="border-b">
                <div className="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45 }}
                    >
                        <p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
                            Pilih layanan
                        </p>
                        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Mau sewa yang mana?
                        </h1>
                        <p className="mt-3 max-w-2xl text-muted-foreground">
                            Pilih salah satu unit di bawah ini. Setelah memilih,
                            kamu langsung diarahkan ke katalog unit tersebut.
                        </p>
                    </motion.div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                {businesses.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada layanan yang aktif. Silakan kembali lagi
                        nanti.
                    </p>
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
                                    <Card className="flex h-full flex-col transition-shadow duration-300 hover:shadow-lg">
                                        <CardContent className="flex flex-1 flex-col gap-5 p-6">
                                            <div className="flex items-start gap-3">
                                                <span className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                                    <Icon className="size-6 text-primary" />
                                                </span>
                                                <div>
                                                    <h2 className="text-xl font-semibold">
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

                                            <p className="text-sm text-muted-foreground">
                                                {intro}
                                            </p>

                                            <ul className="space-y-2 text-sm">
                                                {highlights.map((highlight) => (
                                                    <li
                                                        key={highlight}
                                                        className="flex gap-2 text-muted-foreground"
                                                    >
                                                        <span aria-hidden>
                                                            &middot;
                                                        </span>
                                                        {highlight}
                                                    </li>
                                                ))}
                                            </ul>

                                            {business.rental_terms ? (
                                                <div className="rounded-lg border bg-muted/40 p-3">
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
                                                    <a href={copy.catalogUrl}>
                                                        {copy.buttonLabel}
                                                        <ArrowRight className="size-4" />
                                                    </a>
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
