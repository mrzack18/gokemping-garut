import { Link } from '@inertiajs/react';
import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import { serviceCopyFor, serviceUrl } from '@/components/landing/service-copy';
import { Button } from '@/components/ui/button';
import services from '@/routes/services';
import type { LandingBusiness, LandingProduct } from '@/types';
import { ArrowRight, Bike, Check, Tent } from 'lucide-react';

type ServicesProps = {
    businesses: LandingBusiness[];
    products: LandingProduct[];
};

export default function Services({ businesses, products }: ServicesProps) {
    return (
        <Section
            id="layanan"
            eyebrow="Pilih layanan"
            title="Apa yang ingin kamu jelajahi?"
            description="Mulai dari camping sampai gowes—pilih layanan yang cocok, lalu lihat perlengkapan yang tersedia."
            tone="sand"
        >
            <div className="grid gap-5 lg:grid-cols-2">
                {businesses.map((business, index) => {
                    const copy = serviceCopyFor(business);
                    const Icon = business.slug === 'gokemping' ? Tent : Bike;
                    const featuredProduct = products.find(
                        (product) =>
                            product.business.id === business.id &&
                            product.photo !== null,
                    );
                    const highlights = business.service_highlights?.length
                        ? business.service_highlights
                        : copy.highlights;

                    return (
                        <Reveal
                            as="article"
                            key={business.id}
                            delay={index * 0.08}
                            className="group flex h-full flex-col overflow-hidden rounded-lg border border-border bg-background transition-[border-color,box-shadow] duration-200 hover:border-pine-700/40 hover:shadow-sm"
                        >
                            <div className="relative aspect-[16/10] overflow-hidden bg-pine-900">
                                {featuredProduct?.photo ? (
                                    <img
                                        src={featuredProduct.photo}
                                        alt={featuredProduct.name}
                                        loading="lazy"
                                        decoding="async"
                                        width={800}
                                        height={500}
                                        className="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-pine-50">
                                        <Icon
                                            aria-hidden="true"
                                            className="size-12 opacity-80"
                                        />
                                    </div>
                                )}
                                <div
                                    aria-hidden="true"
                                    className="absolute inset-0 bg-gradient-to-t from-black/65 via-black/5 to-transparent"
                                />
                                <span className="absolute bottom-4 left-4 rounded-sm bg-black/35 px-2.5 py-1 text-xs font-semibold tracking-wide text-white backdrop-blur-sm">
                                    {business.slug === 'gokemping'
                                        ? 'CAMPING'
                                        : 'SEPEDA'}
                                </span>
                            </div>

                            <div className="flex flex-1 flex-col p-5 sm:p-6">
                                <p className="text-xs font-semibold tracking-[0.15em] text-pine-600 uppercase">
                                    {business.slug === 'gokemping'
                                        ? 'Outdoor · Camping'
                                        : 'Outdoor · Sepeda'}
                                </p>
                                <div className="mt-1 flex items-start justify-between gap-4">
                                    <h3 className="font-display text-2xl font-semibold tracking-tight text-balance">
                                        {business.name}
                                    </h3>
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-pine-50 text-pine-700 dark:bg-pine-100 dark:text-pine-600">
                                        <Icon
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </span>
                                </div>
                                <p className="mt-3 max-w-md text-sm leading-relaxed text-muted-foreground">
                                    {business.service_intro ??
                                        business.description ??
                                        copy.intro}
                                </p>

                                {highlights.length > 0 ? (
                                    <ul className="mt-5 space-y-2.5 border-t border-border pt-4 text-sm">
                                        {highlights
                                            .slice(0, 3)
                                            .map((highlight) => (
                                                <li
                                                    key={highlight}
                                                    className="flex gap-2.5"
                                                >
                                                    <Check
                                                        aria-hidden="true"
                                                        className="mt-0.5 size-4 shrink-0 text-pine-700 dark:text-pine-600"
                                                    />
                                                    <span className="text-muted-foreground">
                                                        {highlight}
                                                    </span>
                                                </li>
                                            ))}
                                    </ul>
                                ) : null}

                                <div className="mt-auto flex flex-col gap-3 pt-6 sm:flex-row sm:items-center">
                                    <Button
                                        asChild
                                        className="w-full sm:w-auto"
                                    >
                                        <Link href={serviceUrl(business)}>
                                            {copy.cta}
                                            <ArrowRight
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        </Link>
                                    </Button>
                                    <span className="text-xs text-muted-foreground">
                                        Booking tanpa registrasi akun
                                    </span>
                                </div>
                            </div>
                        </Reveal>
                    );
                })}
            </div>

            <Reveal className="mt-6 flex flex-col gap-2 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-muted-foreground">
                    Masih membandingkan? Lihat semua detail kedua layanan.
                </p>
                <Button
                    asChild
                    variant="link"
                    className="h-auto w-fit px-0 text-pine-700 dark:text-pine-600"
                >
                    <Link href={services.index()}>
                        Bandingkan layanan
                        <ArrowRight aria-hidden="true" className="size-4" />
                    </Link>
                </Button>
            </Reveal>
        </Section>
    );
}
