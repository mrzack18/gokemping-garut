import { Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import { Button } from '@/components/ui/button';
import TopographyPattern from '@/components/brand/topography-pattern';
import { serviceCopyFor, serviceUrl } from '@/components/landing/service-copy';
import type { LandingBanner, LandingBusiness, LandingProduct } from '@/types';
import {
    ArrowDown,
    ArrowRight,
    CalendarCheck2,
    MessageCircle,
    ShieldCheck,
} from 'lucide-react';

type HeroProps = {
    businesses: LandingBusiness[];
    banner: LandingBanner | null;
    featuredProducts: LandingProduct[];
};

const trustItems = [
    { icon: ShieldCheck, label: 'Tanpa buat akun' },
    { icon: CalendarCheck2, label: 'Pilih jadwal secara online' },
    { icon: MessageCircle, label: 'Dikonfirmasi lewat WhatsApp' },
];

export default function Hero({
    businesses,
    banner,
    featuredProducts,
}: HeroProps) {
    const reducedMotion = useReducedMotion();
    const feature = featuredProducts.find((product) => product.photo) ?? null;
    const image = banner?.image_url ?? feature?.photo ?? null;

    return (
        <section className="relative overflow-hidden border-b border-border bg-sand-50">
            <div className="mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:gap-12 lg:py-20">
                <motion.div
                    initial={reducedMotion ? false : { opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                        duration: reducedMotion ? 0 : 0.45,
                        ease: [0.22, 1, 0.36, 1],
                    }}
                    className="lg:col-span-7"
                >
                    <p className="text-xs font-semibold tracking-[0.18em] text-pine-600 uppercase">
                        Petualangan dimulai dari Garut
                    </p>
                    <h1 className="mt-4 max-w-3xl font-display text-4xl leading-[1.04] font-semibold tracking-[-0.035em] text-balance sm:text-5xl lg:text-6xl">
                        Perlengkapan siap. Tinggal berangkat.
                    </h1>
                    <p className="mt-5 max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg">
                        Sewa perlengkapan camping dan sepeda di Garut dengan
                        jadwal yang jelas, stok terpantau, dan konfirmasi
                        langsung bersama admin.
                    </p>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        {businesses.map((business, index) => (
                            <Button
                                key={business.id}
                                asChild
                                size="lg"
                                variant={index === 0 ? 'default' : 'outline'}
                                className="w-full justify-between sm:w-auto sm:justify-center"
                            >
                                <Link href={serviceUrl(business)}>
                                    {serviceCopyFor(business).cta}
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </Link>
                            </Button>
                        ))}
                    </div>

                    <ul className="mt-9 grid gap-x-5 gap-y-3 border-t border-border pt-6 sm:grid-cols-3">
                        {trustItems.map(({ icon: Icon, label }) => (
                            <li
                                key={label}
                                className="flex items-center gap-2 text-xs font-medium text-muted-foreground sm:text-sm"
                            >
                                <Icon
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-pine-700 dark:text-pine-600"
                                />
                                {label}
                            </li>
                        ))}
                    </ul>
                </motion.div>

                <motion.div
                    initial={reducedMotion ? false : { opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                        duration: reducedMotion ? 0 : 0.55,
                        delay: reducedMotion ? 0 : 0.08,
                        ease: [0.22, 1, 0.36, 1],
                    }}
                    className="relative lg:col-span-5"
                >
                    <div className="relative isolate aspect-[4/3] overflow-hidden rounded-lg bg-pine-900 text-pine-50 shadow-sm sm:aspect-[5/4]">
                        {image ? (
                            <img
                                src={image}
                                alt={
                                    banner?.title ??
                                    feature?.name ??
                                    'Perlengkapan outdoor GoKemping'
                                }
                                fetchPriority="high"
                                width={900}
                                height={720}
                                className="absolute inset-0 size-full object-cover"
                            />
                        ) : null}
                        <TopographyPattern className="pointer-events-none absolute inset-0 z-10 size-full text-pine-50 opacity-20" />
                        {image ? (
                            <div
                                aria-hidden="true"
                                className="absolute inset-0 z-20 bg-gradient-to-t from-black/75 via-black/10 to-transparent"
                            />
                        ) : null}
                        <div className="absolute inset-x-0 bottom-0 z-30 p-5 sm:p-7">
                            <p className="text-xs font-semibold tracking-[0.15em] text-ember-500 uppercase">
                                {banner?.business.name ??
                                    feature?.business.name ??
                                    'GoKemping Garut'}
                            </p>
                            <p className="mt-2 max-w-sm font-display text-xl leading-tight font-semibold tracking-tight sm:text-2xl">
                                {banner?.title ??
                                    feature?.name ??
                                    'Perjalanan luar ruang, dimulai dari perlengkapan yang tepat.'}
                            </p>
                            {banner?.subtitle ? (
                                <p className="mt-2 text-sm text-pine-50/80">
                                    {banner.subtitle}
                                </p>
                            ) : feature ? (
                                <p className="mt-2 text-sm text-pine-50/80">
                                    Siap disewa untuk petualangan berikutnya
                                </p>
                            ) : null}
                            {banner?.link_url ? (
                                <HeroBannerLink url={banner.link_url} />
                            ) : null}
                        </div>
                        <div className="absolute top-4 right-4 z-30 flex items-center gap-2 rounded-md border border-white/15 bg-black/25 px-3 py-2 text-xs text-white backdrop-blur-sm">
                            <span
                                aria-hidden="true"
                                className="size-1.5 rounded-full bg-ember-500"
                            />
                            Jelajahi Garut
                        </div>
                    </div>
                    <a
                        href="#layanan"
                        className="mt-4 inline-flex items-center gap-2 rounded-sm text-xs font-medium text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                    >
                        Temukan layanan yang sesuai
                        <ArrowDown aria-hidden="true" className="size-3.5" />
                    </a>
                </motion.div>
            </div>
        </section>
    );
}

function HeroBannerLink({ url }: { url: string }) {
    if (url.startsWith('/')) {
        return (
            <Button asChild variant="secondary" size="sm" className="mt-4">
                <Link href={url}>
                    Lihat promo
                    <ArrowRight aria-hidden="true" className="size-4" />
                </Link>
            </Button>
        );
    }

    return (
        <Button asChild variant="secondary" size="sm" className="mt-4">
            <a href={url} target="_blank" rel="noreferrer">
                Lihat promo
                <ArrowRight aria-hidden="true" className="size-4" />
            </a>
        </Button>
    );
}
