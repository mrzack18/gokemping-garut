import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { whatsappLink } from '@/lib/format';
import type { LandingBusiness } from '@/types';
import { Mail, MapPin, MessageCircle } from 'lucide-react';

type ContactProps = {
    businesses: LandingBusiness[];
};

export default function Contact({ businesses }: ContactProps) {
    return (
        <Section
            id="kontak"
            eyebrow="Kontak & lokasi"
            title="Temu langsung atau chat admin"
            description="Setiap unit punya admin sendiri. Pilih unit yang ingin Anda ajukan pertanyaannya."
        >
            <div className="grid gap-4 md:grid-cols-2">
                {businesses.map((business, index) => (
                    <Reveal key={business.id} delay={index * 0.1}>
                        <Card className="h-full">
                            <CardContent className="space-y-5 p-6">
                                <div>
                                    <h3 className="text-lg font-semibold">
                                        {business.name}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {business.description}
                                    </p>
                                </div>

                                <dl className="space-y-3 text-sm">
                                    {business.address ? (
                                        <div className="flex gap-2">
                                            <dt className="sr-only">Alamat</dt>
                                            <MapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                            <dd className="text-muted-foreground">
                                                {business.address}
                                            </dd>
                                        </div>
                                    ) : null}

                                    {business.email ? (
                                        <div className="flex gap-2">
                                            <dt className="sr-only">Email</dt>
                                            <Mail className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                            <dd>
                                                <a
                                                    href={`mailto:${business.email}`}
                                                    className="underline underline-offset-4 hover:text-foreground"
                                                >
                                                    {business.email}
                                                </a>
                                            </dd>
                                        </div>
                                    ) : null}

                                    <div className="flex gap-2">
                                        <dt className="sr-only">WhatsApp</dt>
                                        <MessageCircle className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                        <dd>
                                            <a
                                                href={whatsappLink(
                                                    business.whatsapp,
                                                    `Halo ${business.name}, saya mau tanya soal sewa.`,
                                                )}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="underline underline-offset-4 hover:text-foreground"
                                            >
                                                {business.whatsapp}
                                            </a>
                                        </dd>
                                    </div>
                                </dl>

                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <Button asChild>
                                        <a
                                            href={whatsappLink(
                                                business.whatsapp,
                                                `Halo ${business.name}, saya mau tanya soal sewa.`,
                                            )}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            <MessageCircle className="size-4" />
                                            Chat admin
                                        </a>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </Reveal>
                ))}
            </div>
        </Section>
    );
}
