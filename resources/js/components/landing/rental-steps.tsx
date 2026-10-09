import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import type { LandingBusiness } from '@/types';
import { CalendarDays, MessageCircle, ShoppingBag, Wallet } from 'lucide-react';

type RentalStepsProps = {
    businesses: LandingBusiness[];
};

const steps = [
    {
        icon: ShoppingBag,
        title: 'Pilih perlengkapan',
        description:
            'Telusuri katalog, bandingkan harga, lalu buka detail barang.',
    },
    {
        icon: CalendarDays,
        title: 'Tentukan jadwal',
        description:
            'Pilih tanggal sewa dan jumlah barang; stok dicek otomatis.',
    },
    {
        icon: Wallet,
        title: 'Isi data & pembayaran',
        description: 'Lengkapi data penyewa dan ikuti instruksi pembayaran.',
    },
    {
        icon: MessageCircle,
        title: 'Konfirmasi ke admin',
        description: 'Kirim ringkasan booking ke WhatsApp untuk diproses.',
    },
];

export default function RentalSteps({ businesses }: RentalStepsProps) {
    return (
        <Section
            id="cara-sewa"
            eyebrow="Cara penyewaan"
            title="Empat langkah sampai siap berangkat"
            description="Pemesanan dilakukan tanpa registrasi akun dan setiap tahap memberi ringkasan yang jelas."
            tone="sand"
        >
            <ol className="grid gap-x-6 md:grid-cols-2 lg:grid-cols-4">
                {steps.map((step, index) => (
                    <Reveal
                        as="li"
                        key={step.title}
                        delay={index * 0.06}
                        className="list-none border-t-2 border-pine-700 py-5 sm:py-6"
                    >
                        <div className="flex items-center justify-between gap-4">
                            <span className="font-display text-4xl leading-none font-semibold tracking-tight text-pine-700/30 dark:text-pine-600/45">
                                0{index + 1}
                            </span>
                            <step.icon
                                aria-hidden="true"
                                className="size-5 text-pine-700 dark:text-pine-600"
                            />
                        </div>
                        <h3 className="mt-5 font-display text-lg font-semibold tracking-tight">
                            {step.title}
                        </h3>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {step.description}
                        </p>
                    </Reveal>
                ))}
            </ol>

            <p className="mt-4 border-t border-border pt-4 text-xs text-muted-foreground">
                Pesanan diteruskan ke admin unit yang kamu pilih:{' '}
                {businesses.map((business) => business.name).join(' dan ')}.
            </p>
        </Section>
    );
}
