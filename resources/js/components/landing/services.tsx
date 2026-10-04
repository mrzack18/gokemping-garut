import { motion } from 'motion/react';
import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import { serviceCopyFor, serviceUrl } from '@/components/landing/service-copy';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { LandingBusiness } from '@/types';
import { ArrowRight, Check } from 'lucide-react';

type ServicesProps = {
    businesses: LandingBusiness[];
};

export default function Services({ businesses }: ServicesProps) {
    return (
        <Section
            id="layanan"
            eyebrow="Layanan"
            title="Pilih sesuai kebutuhan aktivitas"
            description="Dua layanan dengan proses booking yang sama. Katalog, stok, dan admin dikelola terpisah untuk tiap unit."
        >
            <div className="grid gap-4 md:grid-cols-2">
                {businesses.map((business, index) => {
                    const copy = serviceCopyFor(business);

                    return (
                        <motion.div
                            key={business.id}
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.2 }}
                            transition={{
                                duration: 0.45,
                                delay: index * 0.1,
                                ease: [0.22, 1, 0.36, 1],
                            }}
                        >
                            <Card className="h-full transition-shadow duration-300 hover:shadow-lg">
                                <CardContent className="space-y-5 p-6">
                                    <div className="space-y-2">
                                        <Badge variant="secondary">
                                            {business.slug === 'gokemping'
                                                ? 'Camping'
                                                : 'Sepeda'}
                                        </Badge>
                                        <h3 className="text-xl font-semibold">
                                            {business.name}
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            {business.description}
                                        </p>
                                    </div>

                                    {copy.highlights.length > 0 ? (
                                        <ul className="space-y-2 text-sm">
                                            {copy.highlights.map(
                                                (highlight) => (
                                                    <li
                                                        key={highlight}
                                                        className="flex gap-2"
                                                    >
                                                        <Check className="mt-0.5 size-4 shrink-0 text-primary" />
                                                        <span className="text-muted-foreground">
                                                            {highlight}
                                                        </span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    ) : null}

                                    <Button
                                        asChild
                                        className="w-full sm:w-auto"
                                    >
                                        <a href={serviceUrl(business)}>
                                            {copy.cta}
                                            <ArrowRight className="size-4" />
                                        </a>
                                    </Button>
                                </CardContent>
                            </Card>
                        </motion.div>
                    );
                })}
            </div>

            <Reveal delay={0.2} className="mt-4">
                <p className="text-sm text-muted-foreground">
                    Belum yakin memilih unit mana? Chat WhatsApp adminnya dulu,
                    nanti direkomendasikan katalog yang paling pas untuk
                    kebutuhan Anda.
                </p>
            </Reveal>
        </Section>
    );
}
