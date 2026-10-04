import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import services from '@/routes/services';
import { ArrowRight } from 'lucide-react';
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

            <div className="border-b">
                <div className="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6">
                    <Button
                        asChild
                        variant="outline"
                        className="w-full sm:w-auto"
                    >
                        <Link href={services.index()}>
                            Lihat semua layanan
                            <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                </div>
            </div>

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
