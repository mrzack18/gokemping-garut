import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { whatsappLink } from '@/lib/format';
import type { LandingBusiness } from '@/types';
import { Mail, MapPin, MessageCircle, Phone } from 'lucide-react';

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
            <div className="grid gap-5 md:grid-cols-2">
                {businesses.map((business, index) => (
                    <Reveal key={business.id} delay={index * 0.1}>
                        <Card className="h-full rounded-lg shadow-none">
                            <CardContent className="space-y-5 p-5 sm:p-6">
                                <div>
                                    <h3 className="text-lg font-semibold">
                                        {business.name}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {business.service_intro ??
                                            business.description}
                                    </p>
                                </div>

                                <dl className="space-y-3 text-sm">
                                    {business.address ? (
                                        <div className="flex gap-2">
                                            <dt className="sr-only">Alamat</dt>
                                            <MapPin
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0 text-pine-700 dark:text-pine-600"
                                            />
                                            <dd className="text-muted-foreground">
                                                {business.address}
                                            </dd>
                                        </div>
                                    ) : null}

                                    {business.email ? (
                                        <div className="flex gap-2">
                                            <dt className="sr-only">Email</dt>
                                            <Mail
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0 text-pine-700 dark:text-pine-600"
                                            />
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

                                    {business.phone ? (
                                        <div className="flex gap-2">
                                            <dt className="sr-only">Telepon</dt>
                                            <Phone
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0 text-pine-700 dark:text-pine-600"
                                            />
                                            <dd>
                                                <a
                                                    href={`tel:${business.phone}`}
                                                    className="underline underline-offset-4 hover:text-foreground"
                                                >
                                                    {business.phone}
                                                </a>
                                            </dd>
                                        </div>
                                    ) : null}

                                    <div className="flex gap-2">
                                        <dt className="sr-only">WhatsApp</dt>
                                        <MessageCircle
                                            aria-hidden="true"
                                            className="mt-0.5 size-4 shrink-0 text-pine-700 dark:text-pine-600"
                                        />
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

                                {business.maps_embed_url ? (
                                    <div className="space-y-2 border-t border-border pt-4">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Lokasi
                                        </p>
                                        <iframe
                                            src={business.maps_embed_url}
                                            title={`Lokasi ${business.name}`}
                                            loading="lazy"
                                            referrerPolicy="no-referrer-when-downgrade"
                                            className="aspect-video w-full rounded-md border border-border bg-muted"
                                        />
                                    </div>
                                ) : null}

                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <Button
                                        asChild
                                        className="w-full sm:w-auto"
                                    >
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
