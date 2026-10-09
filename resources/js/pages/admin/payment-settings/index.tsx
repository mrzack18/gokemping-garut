import { Head } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import AdminPageHeader from '@/components/admin/page-header';
import PaymentSettingCard from '@/components/admin/payment-setting-card';
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
    const reducedMotion = useReducedMotion();

    return (
        <>
            <Head title="Pengaturan Pembayaran" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Pengaturan Pembayaran"
                    description="Atur metode pembayaran yang bisa dipilih penyewa di unit ini. Metode nonaktif tidak muncul di halaman pembayaran, dan metode yang datanya belum lengkap tetap ditampilkan di sini dengan peringatan."
                    backHref={paymentRoutes.index.url()}
                    backLabel="Daftar pembayaran"
                />

                <div className="grid items-start gap-6 lg:grid-cols-2 xl:grid-cols-3">
                    {methods.map((method, index) => (
                        <motion.div
                            key={method.type}
                            initial={
                                reducedMotion ? false : { opacity: 0, y: 10 }
                            }
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: reducedMotion ? 0 : 0.3,
                                delay: reducedMotion ? 0 : 0.05 * (index + 1),
                                ease: [0.22, 1, 0.36, 1],
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
