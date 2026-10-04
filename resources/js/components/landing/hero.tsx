import { motion } from 'motion/react';
import { Button } from '@/components/ui/button';
import { serviceCopyFor, serviceUrl } from '@/components/landing/service-copy';
import type { LandingBusiness } from '@/types';
import { ArrowRight, ShieldCheck, Wallet } from 'lucide-react';

type HeroProps = {
    businesses: LandingBusiness[];
};

const container = {
    hidden: {},
    visible: {
        transition: { staggerChildren: 0.12, delayChildren: 0.05 },
    },
};

const item = {
    hidden: { opacity: 0, y: 20 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as const },
    },
};

const highlights = [
    {
        icon: ShieldCheck,
        title: 'Stok Tercek Otomatis',
        description:
            'Ketersediaan dihitung dari booking aktif, jadi tidak ada overbooking.',
    },
    {
        icon: Wallet,
        title: 'Bayar Manual, Dikonfirmasi Admin',
        description:
            'Cash, QRIS, atau transfer bank dengan verifikasi bukti pembayaran.',
    },
];

export default function Hero({ businesses }: HeroProps) {
    return (
        <section className="relative overflow-hidden border-b">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_60%_at_50%_0%,var(--color-primary)_0%,transparent_70%)] opacity-10"
            />

            <motion.div
                variants={container}
                initial="hidden"
                animate="visible"
                className="relative mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 sm:py-24"
            >
                <motion.p
                    variants={item}
                    className="text-xs font-semibold tracking-[0.2em] text-primary uppercase"
                >
                    Sewa barang tanpa ribet di Garut
                </motion.p>

                <motion.h1
                    variants={item}
                    className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl"
                >
                    Mau Camping atau Gowes?
                </motion.h1>

                <motion.p
                    variants={item}
                    className="mt-5 max-w-2xl text-base text-muted-foreground sm:text-lg"
                >
                    Sewa perlengkapan camping dan sepeda dengan mudah di Garut.
                    Pilih barang, tentukan tanggal sewa, lalu selesaikan
                    pembayaran lewat WhatsApp.
                </motion.p>

                <motion.div
                    variants={item}
                    className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap"
                >
                    {businesses.map((business) => (
                        <Button key={business.id} asChild size="lg">
                            <a href={serviceUrl(business)}>
                                {serviceCopyFor(business).cta}
                                <ArrowRight className="size-4" />
                            </a>
                        </Button>
                    ))}
                </motion.div>

                <motion.dl
                    variants={item}
                    className="mt-12 grid gap-6 border-t pt-8 sm:grid-cols-2"
                >
                    {highlights.map((highlight) => (
                        <div key={highlight.title} className="flex gap-3">
                            <highlight.icon className="mt-0.5 size-5 shrink-0 text-primary" />
                            <div>
                                <dt className="text-sm font-medium">
                                    {highlight.title}
                                </dt>
                                <dd className="mt-1 text-sm text-muted-foreground">
                                    {highlight.description}
                                </dd>
                            </div>
                        </div>
                    ))}
                </motion.dl>
            </motion.div>
        </section>
    );
}
