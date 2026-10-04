import { Head } from '@inertiajs/react';
import About from '@/components/landing/about';
import Advantages from '@/components/landing/advantages';
import Contact from '@/components/landing/contact';
import Faq from '@/components/landing/faq';
import FeaturedProducts from '@/components/landing/featured-products';
import Hero from '@/components/landing/hero';
import RentalSteps from '@/components/landing/rental-steps';
import Services from '@/components/landing/services';
import PublicLayout from '@/layouts/public-layout';
import type { LandingPageProps } from '@/types';

export default function Welcome({
    businesses,
    featuredProducts,
}: LandingPageProps) {
    return (
        <PublicLayout businesses={businesses}>
            <Head title="Sewa Camping dan Sepeda di Garut" />

            <Hero businesses={businesses} />

            <About businesses={businesses} />

            <Services businesses={businesses} />

            <FeaturedProducts products={featuredProducts} />

            <RentalSteps businesses={businesses} />

            <Advantages />

            <Faq />

            <Contact businesses={businesses} />
        </PublicLayout>
    );
}
