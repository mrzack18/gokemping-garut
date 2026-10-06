import { Link } from '@inertiajs/react';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { Button } from '@/components/ui/button';
import { ArrowRight } from 'lucide-react';
import type { LandingBanner } from '@/types';

/**
 * Banner hero carousel landing page (PRD section 28, ROADMAP 5.4).
 *
 * Banner dari semua unit aktif digabung dalam satu carousel, jadi unit mana
 * pun bisa menyorot programnya. Teks ditimpa di atas gambar dengan gradient
 * gelap supaya tetap terbaca tanpa bergantung pada warna gambarnya. Tautan
 * internal memakai Link Inertia, tautan luar memakai anchor biasa, dan banner
 * tanpa tautan hanya tampil sebagai gambar.
 */
export default function BannerCarousel({
    banners,
}: {
    banners: LandingBanner[];
}) {
    if (banners.length === 0) {
        return null;
    }

    return (
        <section className="border-b bg-muted/30">
            <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-12">
                <Carousel
                    opts={{ loop: banners.length > 1 }}
                    className="w-full"
                >
                    <CarouselContent>
                        {banners.map((banner) => (
                            <CarouselItem key={banner.id}>
                                <div className="relative overflow-hidden rounded-xl border bg-muted">
                                    <img
                                        src={banner.image_url}
                                        alt={banner.title}
                                        loading="lazy"
                                        className="h-56 w-full object-cover sm:h-72"
                                    />
                                    <div
                                        aria-hidden
                                        className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"
                                    />
                                    <div className="absolute inset-x-0 bottom-0 flex flex-col gap-2 p-5 text-white sm:p-7">
                                        <span className="text-xs font-semibold tracking-[0.2em] uppercase opacity-80">
                                            {banner.business.name}
                                        </span>
                                        <h2 className="max-w-2xl text-xl font-semibold sm:text-2xl">
                                            {banner.title}
                                        </h2>
                                        {banner.subtitle ? (
                                            <p className="max-w-2xl text-sm opacity-90">
                                                {banner.subtitle}
                                            </p>
                                        ) : null}
                                        {banner.link_url !== null ? (
                                            <BannerLink url={banner.link_url} />
                                        ) : null}
                                    </div>
                                </div>
                            </CarouselItem>
                        ))}
                    </CarouselContent>

                    {banners.length > 1 ? (
                        <>
                            <CarouselPrevious className="left-3" />
                            <CarouselNext className="right-3" />
                        </>
                    ) : null}
                </Carousel>
            </div>
        </section>
    );
}

/**
 * Tautan banner: internal lewat Inertia, eksternal lewat anchor biasa.
 */
function BannerLink({ url }: { url: string }) {
    if (url.startsWith('/')) {
        return (
            <Button
                asChild
                variant="secondary"
                size="sm"
                className="mt-1 w-fit"
            >
                <Link href={url}>
                    Lihat
                    <ArrowRight className="size-4" />
                </Link>
            </Button>
        );
    }

    return (
        <Button asChild variant="secondary" size="sm" className="mt-1 w-fit">
            <a href={url} target="_blank" rel="noreferrer">
                Lihat
                <ArrowRight className="size-4" />
            </a>
        </Button>
    );
}
