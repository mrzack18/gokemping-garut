import { Head } from '@inertiajs/react';
import { motion } from 'motion/react';
import { BadgeCheck, Clock, Package, Wallet } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes/admin';

type DashboardProps = {
    business: {
        name: string;
        slug: string;
        bookingCodePrefix: string;
        whatsapp: string;
    };
    stats: {
        totalProducts: number;
        bookingsToday: number;
        rented: number;
        awaitingConfirmation: number;
        pendingPayments: number;
    };
};

const statCards = [
    { key: 'totalProducts', label: 'Total Produk', icon: Package },
    { key: 'bookingsToday', label: 'Booking Hari Ini', icon: Clock },
    { key: 'rented', label: 'Sedang Disewa', icon: BadgeCheck },
    { key: 'awaitingConfirmation', label: 'Menunggu Konfirmasi', icon: Clock },
    {
        key: 'pendingPayments',
        label: 'Menunggu Verifikasi Bayar',
        icon: Wallet,
    },
] as const;

export default function AdminDashboard({ business, stats }: DashboardProps) {
    return (
        <>
            <Head title={`Dashboard ${business.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-1"
                >
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard {business.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Seluruh data di halaman ini hanya menampilkan unit
                        bisnis Anda.
                    </p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">
                            Prefix kode booking: {business.bookingCodePrefix}
                        </Badge>
                        <Badge variant="outline">
                            WhatsApp: {business.whatsapp}
                        </Badge>
                    </div>
                </motion.div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {statCards.map((card, index) => (
                        <motion.div
                            key={card.key}
                            initial={{ opacity: 0, y: 12 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.3,
                                delay: index * 0.06,
                            }}
                        >
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        {card.label}
                                    </CardTitle>
                                    <card.icon className="size-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {stats[card.key]}
                                    </div>
                                </CardContent>
                            </Card>
                        </motion.div>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Fase 0 selesai</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        Autentikasi admin, isolasi unit bisnis, dan seluruh
                        tabel inti sudah siap. Modul produk, booking, dan
                        pembayaran menyusul pada fase berikutnya.
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
