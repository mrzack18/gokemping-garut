import Section from '@/components/landing/section';
import { Card, CardContent } from '@/components/ui/card';
import { motion } from 'motion/react';
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
        title: 'Stok real-time',
        description:
            'Sisa stok dihitung dari booking aktif pada tanggal yang dipilih sehingga tidak ada overbooking.',
    },
    {
        icon: Wallet,
        title: 'Pembayaran fleksibel',
        description:
            'Cash, QRIS toko, atau transfer bank. Semua dikonfirmasi manual oleh admin.',
    },
    {
        icon: PackageSearch,
        title: 'Kondisi dijamin',
        description:
            'Setiap barang melewati pemeriksaan sebelum disewakan dan sebelum dikembalikan.',
    },
    {
        icon: BadgeCheck,
        title: 'Harga transparan',
        description:
            'Harga dan satuan tampil di katalog, termasuk ketentuan penyewaan per barang.',
    },
    {
        icon: Headset,
        title: 'Admin responsif',
        description:
            'Pertanyaan dan konfirmasi pesenan ditangani langsung oleh WhatsApp admin unit.',
    },
    {
        icon: ShieldCheck,
        title: 'Data aman',
        description:
            'Katalog tiap unit terpisah sehingga data dan stok tidak bercampur antar usaha.',
    },
];

export default function Advantages() {
    return (
        <Section
            id="keunggulan"
            eyebrow="Keunggulan"
            title="Kenapa menyewa lewat GoKemping"
            description="Fokusnya satu: proses pemesanan yang mudah dan tidak rebutan stok."
        >
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {advantages.map((advantage, index) => (
                    <motion.div
                        key={advantage.title}
                        initial={{ opacity: 0, y: 20 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true, amount: 0.2 }}
                        transition={{
                            duration: 0.4,
                            delay: (index % 3) * 0.08,
                        }}
                    >
                        <Card className="h-full transition-shadow duration-300 hover:shadow-md">
                            <CardContent className="space-y-3 p-6">
                                <advantage.icon className="size-6 text-primary" />
                                <h3 className="font-medium">
                                    {advantage.title}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {advantage.description}
                                </p>
                            </CardContent>
                        </Card>
                    </motion.div>
                ))}
            </div>
        </Section>
    );
}
