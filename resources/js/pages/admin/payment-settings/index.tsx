import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowLeft } from 'lucide-react';
import PaymentSettingCard from '@/components/admin/payment-setting-card';
import { Button } from '@/components/ui/button';
import paymentRoutes from '@/routes/admin/payments';
import paymentSettingRoutes from '@/routes/admin/payment-settings';
import type { AdminPaymentSettingsPageProps } from '@/types';

/**
 * Pengaturan pembayaran admin (PRD section 27, ROADMAP 4.7).
 *
 * Halaman ini mengisi data yang dibaca halaman pembayaran publik. Semua
 * perubahan langsung berlaku: menyimpan rekening baru atau mematikan metode
 * mengubah pilihan yang dilihat penyewa pada request berikutnya, tanpa langkah
 * publikasi terpisah.
 */
export default function AdminPaymentSettings({
    methods,
}: AdminPaymentSettingsPageProps) {
    return (
        <>
            <Head title="Pengaturan Pembayaran" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-2"
                >
                    <Button asChild variant="ghost" size="sm">
                        <Link href={paymentRoutes.index()}>
                            <ArrowLeft />
                            Daftar pembayaran
                        </Link>
                    </Button>

                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Pengaturan Pembayaran
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Atur metode pembayaran yang bisa dipilih penyewa di
                            unit ini. Metode nonaktif tidak muncul di halaman
                            pembayaran, dan metode yang datanya belum lengkap
                            tetap ditampilkan di sini dengan peringatan.
                        </p>
                    </div>
                </motion.div>

                <div className="grid items-start gap-6 lg:grid-cols-2 xl:grid-cols-3">
                    {methods.map((method, index) => (
                        <motion.div
                            key={method.type}
                            initial={{ opacity: 0, y: 12 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.3,
                                delay: 0.06 * (index + 1),
                            }}
                        >
                            <PaymentSettingCard method={method} />
                        </motion.div>
                    ))}
                </div>
            </div>
        </>
    );
}

AdminPaymentSettings.layout = {
    breadcrumbs: [
        {
            title: 'Pembayaran',
            href: paymentRoutes.index(),
        },
        {
            title: 'Pengaturan Pembayaran',
            href: paymentSettingRoutes.index(),
        },
    ],
};
