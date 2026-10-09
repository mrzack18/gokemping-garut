import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import {
    BadgeCheck,
    CalendarClock,
    Headset,
    PackageSearch,
    ShieldCheck,
    Wallet,
} from 'lucide-react';

const advantages = [
    {
        icon: CalendarClock,
        title: 'Stok terpantau',
        description:
            'Ketersediaan dihitung dari booking aktif pada tanggal yang kamu pilih.',
    },
    {
        icon: Wallet,
        title: 'Pilihan pembayaran jelas',
        description:
            'Pilih cash, QRIS, atau transfer bank dengan verifikasi admin.',
    },
    {
        icon: PackageSearch,
        title: 'Perlengkapan diperiksa',
        description:
            'Barang melewati pemeriksaan sebelum disewakan dan saat dikembalikan.',
    },
    {
        icon: BadgeCheck,
        title: 'Harga transparan',
        description:
            'Harga dan satuan sewa ditampilkan sebelum kamu mengirim booking.',
    },
    {
        icon: Headset,
        title: 'Admin siap membantu',
        description:
            'Pertanyaan dan konfirmasi pesanan ditangani langsung oleh admin unit.',
    },
    {
        icon: ShieldCheck,
        title: 'Data dikelola terpisah',
        description:
            'Katalog dan stok setiap unit tidak bercampur satu sama lain.',
    },
];

export default function Advantages() {
    return (
        <Section
            id="keunggulan"
            eyebrow="Kenapa GoKemping"
            title="Lebih siap sebelum berangkat"
            description="Informasi yang kamu butuhkan tersedia sejak memilih barang sampai konfirmasi booking."
            tone="sand"
        >
            <div className="grid gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
                {advantages.map((advantage, index) => (
                    <Reveal
                        key={advantage.title}
                        delay={(index % 3) * 0.06}
                        className="border-t border-border py-5 sm:py-6"
                    >
                        <div className="flex items-start gap-4">
                            <advantage.icon
                                aria-hidden="true"
                                className="mt-0.5 size-5 shrink-0 text-pine-700 dark:text-pine-600"
                            />
                            <div>
                                <h3 className="font-semibold">
                                    {advantage.title}
                                </h3>
                                <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                                    {advantage.description}
                                </p>
                            </div>
                        </div>
                    </Reveal>
                ))}
            </div>
        </Section>
    );
}
