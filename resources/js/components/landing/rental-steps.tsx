import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import type { LandingBusiness } from '@/types';
import { motion } from 'motion/react';
import { MessageCircle, PackageCheck, ShoppingBag, Wallet } from 'lucide-react';

type RentalStepsProps = {
    businesses: LandingBusiness[];
};

const steps = [
    {
        icon: ShoppingBag,
        title: 'Pilih barang',
        description:
            'Telusuri katalog sesuai layanan, cek harga dan stok yang tersedia.',
    },
    {
        icon: Wallet,
        title: 'Atur tanggal dan bayar',
        description:
            'Tentukan tanggal mulai dan selesai, lalu pilih cash, QRIS, atau transfer bank.',
    },
    {
        icon: PackageCheck,
        title: 'Ambil dan kembalikan',
        description:
            'Barang diperiksa bersama admin saat pengambilan dan pengembalian.',
    },
    {
        icon: MessageCircle,
        title: 'Konfirmasi via WhatsApp',
        description:
            'Pesanan diteruskan ke admin dengan kode booking sebagai bukti.',
    },
];

export default function RentalSteps({ businesses }: RentalStepsProps) {
    return (
        <Section
            id="cara-sewa"
            eyebrow="Cara penyewaan"
            title="Empat langkah, tanpa daftar akun"
            description="Tidak ada proses registrasi. Pengunjung cukup mengisi data penyewa dan konfirmasi melalui WhatsApp."
        >
            <ol className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {steps.map((step, index) => (
                    <motion.li
                        key={step.title}
                        initial={{ opacity: 0, y: 20 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true, amount: 0.2 }}
                        transition={{
                            duration: 0.4,
                            delay: index * 0.08,
                        }}
                        className="relative rounded-xl border bg-card p-5 text-card-foreground"
                    >
                        <div className="flex items-center gap-3">
                            <span className="flex size-9 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                {index + 1}
                            </span>
                            <step.icon className="size-5 text-muted-foreground" />
                        </div>
                        <h3 className="mt-4 font-medium">{step.title}</h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {step.description}
                        </p>
                    </motion.li>
                ))}
            </ol>

            <Reveal delay={0.2} className="mt-4">
                <p className="text-sm text-muted-foreground">
                    Admin yang akan menghubungi Anda:{' '}
                    {businesses.map((business) => business.name).join(' dan ')}.
                </p>
            </Reveal>
        </Section>
    );
}
