import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import type { CarouselApi } from '@/components/ui/carousel';
import { Button } from '@/components/ui/button';
import { ArrowRight } from 'lucide-react';
import type { LandingBanner } from '@/types';

/** Banner carousel untuk konten aktif dari semua unit bisnis. */
export default function BannerCarousel({
    banners,
}: {
    banners: LandingBanner[];
}) {
    const [api, setApi] = useState<CarouselApi>();
    const [selectedIndex, setSelectedIndex] = useState(0);

    useEffect(() => {
        if (!api) {
            return;
        }

        const onSelect = () => setSelectedIndex(api.selectedScrollSnap());
        onSelect();
        api.on('select', onSelect);
        api.on('reInit', onSelect);

        return () => {
            api.off('select', onSelect);
            api.off('reInit', onSelect);
        };
    }, [api]);

    if (banners.length === 0) {
        return null;
    }

    return (
        <section className="border-b border-border bg-background">
            <div className="mx-auto w-full max-w-6xl px-4 py-7 sm:px-6 sm:py-9">
                <Carousel
                    setApi={setApi}
                    opts={{ loop: banners.length > 1, align: 'start' }}
                    className="w-full"
                    aria-label="Sorotan layanan GoKemping"
                >
                    <CarouselContent>
                        {banners.map((banner) => (
                            <CarouselItem key={banner.id}>
                                <article className="relative isolate aspect-[4/3] overflow-hidden rounded-lg bg-pine-900 text-pine-50 sm:aspect-[16/9] lg:aspect-[16/7]">
                                    <img
                                        src={banner.image_url}
                                        alt={banner.title}
                                        loading="lazy"
                                        width={1600}
                                        height={700}
                                        className="absolute inset-0 z-0 size-full object-cover"
                                    />
                                    <div
                                        aria-hidden="true"
                                        className="absolute inset-0 z-10 bg-gradient-to-t from-black/75 via-black/20 to-transparent"
                                    />
                                    <div className="absolute inset-x-0 bottom-0 z-20 flex flex-col items-start gap-2 p-5 sm:max-w-3xl sm:p-8 lg:p-10">
                                        <span className="text-xs font-semibold tracking-[0.17em] text-ember-500 uppercase">
                                            {banner.business.name}
                                        </span>
                                        <h2 className="font-display text-2xl font-semibold tracking-tight text-balance sm:text-3xl lg:text-4xl">
                                            {banner.title}
                                        </h2>
                                        {banner.subtitle ? (
                                            <p className="max-w-2xl text-sm leading-relaxed text-white/85 sm:text-base">
                                                {banner.subtitle}
                                            </p>
                                        ) : null}
                                        {banner.link_url !== null ? (
                                            <BannerLink url={banner.link_url} />
                                        ) : null}
                                    </div>
                                </article>
                            </CarouselItem>
                        ))}
                    </CarouselContent>

                    {banners.length > 1 ? (
                        <>
                            <CarouselPrevious
                                aria-label="Banner sebelumnya"
                                className="left-3 border-white/30 bg-black/30 text-white hover:bg-black/50 hover:text-white sm:left-5"
                            />
                            <CarouselNext
                                aria-label="Banner berikutnya"
                                className="right-3 border-white/30 bg-black/30 text-white hover:bg-black/50 hover:text-white sm:right-5"
                            />
                        </>
                    ) : null}
                </Carousel>

                {banners.length > 1 ? (
                    <div
                        role="group"
                        className="mt-4 flex justify-center gap-2"
                        aria-label="Pilih banner"
                    >
                        {banners.map((banner, index) => (
                            <button
                                key={banner.id}
                                type="button"
                                aria-label={`Tampilkan banner ${index + 1}`}
                                aria-current={
                                    selectedIndex === index ? 'true' : undefined
                                }
                                onClick={() => api?.scrollTo(index)}
                                className={`h-1.5 rounded-full transition-all focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:outline-none ${selectedIndex === index ? 'w-8 bg-primary' : 'w-3 bg-border hover:bg-muted-foreground/50'}`}
                            />
                        ))}
                    </div>
                ) : null}
            </div>
        </section>
    );
}

/** Tautan banner: internal lewat Inertia, eksternal lewat anchor biasa. */
function BannerLink({ url }: { url: string }) {
    const className = 'mt-2 w-fit';

    if (url.startsWith('/')) {
        return (
            <Button asChild variant="secondary" size="sm" className={className}>
                <Link href={url}>
                    Lihat detail
                    <ArrowRight aria-hidden="true" className="size-4" />
                </Link>
            </Button>
        );
    }

    return (
        <Button asChild variant="secondary" size="sm" className={className}>
            <a href={url} target="_blank" rel="noreferrer">
                Lihat detail
                <ArrowRight aria-hidden="true" className="size-4" />
            </a>
        </Button>
    );
}
