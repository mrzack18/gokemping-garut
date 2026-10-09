import { Head } from '@inertiajs/react';
import About from '@/components/landing/about';
import Advantages from '@/components/landing/advantages';
import BannerCarousel from '@/components/landing/banner-carousel';
import Contact from '@/components/landing/contact';
import Faq from '@/components/landing/faq';
import FeaturedProducts from '@/components/landing/featured-products';
import Hero from '@/components/landing/hero';
import RentalSteps from '@/components/landing/rental-steps';
import Services from '@/components/landing/services';
import TicketCheck from '@/components/landing/ticket-check';
import PublicLayout from '@/layouts/public-layout';
import type { LandingPageProps } from '@/types';

export default function Welcome({
    businesses,
    featuredProducts,
    banners,
    faqs,
    ticket,
}: LandingPageProps) {
    return (
        <PublicLayout businesses={businesses}>
            <Head title="Sewa Camping dan Sepeda di Garut" />

            <Hero
                businesses={businesses}
                banner={banners[0] ?? null}
                featuredProducts={featuredProducts.slice(0, 2)}
            />

            <Services businesses={businesses} products={featuredProducts} />

            <FeaturedProducts products={featuredProducts} />

            <BannerCarousel banners={banners.slice(1)} />

            <RentalSteps businesses={businesses} />

            <About />

            <Advantages />

            <TicketCheck ticket={ticket} />

            <Faq faqs={faqs} />

            <Contact businesses={businesses} />
        </PublicLayout>
    );
}
