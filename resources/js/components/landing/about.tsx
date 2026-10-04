import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import type { LandingBusiness } from '@/types';
import { whatsappLink } from '@/lib/format';
import { MapPin, MessageCircle } from 'lucide-react';

type AboutProps = {
    businesses: LandingBusiness[];
};

export default function About({ businesses }: AboutProps) {
    return (
        <Section
            id="tentang"
            eyebrow="Tentang GoKemping"
            title="Dua unit usaha, satu cara menyewa"
            description="GoKemping menjalankan dua unit bisnis di Garut dengan sistem booking yang sama, tetapi katalog dan stoknya dikelola terpisah."
        >
            <div className="grid gap-4 md:grid-cols-2">
                {businesses.map((business, index) => (
                    <Reveal
                        key={business.id}
                        as="article"
                        delay={index * 0.1}
                        className="rounded-xl border bg-card p-6 text-card-foreground"
                    >
                        <h3 className="text-lg font-semibold">
                            {business.name}
                        </h3>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {business.description}
                        </p>

                        <dl className="mt-5 space-y-2 text-sm">
                            {business.address ? (
                                <div className="flex gap-2">
                                    <dt className="sr-only">Alamat</dt>
                                    <MapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                    <dd className="text-muted-foreground">
                                        {business.address}
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
                                        Chat WhatsApp
                                    </a>
                                </dd>
                            </div>
                        </dl>
                    </Reveal>
                ))}
            </div>

            <Reveal
                delay={0.15}
                className="mt-4 rounded-xl border border-dashed p-6"
            >
                <p className="text-sm text-muted-foreground">
                    Tidak ada pendaftaran akun. Pengunjung cukup memilih
                    layanan, mengisi data penyewa, lalu konfirmasi pesanan
                    melalui WhatsApp. Data tiap unit dikelola terpisah sehingga
                    katalog dan stok antar unit tidak tercampur.
                </p>
            </Reveal>
        </Section>
    );
}
