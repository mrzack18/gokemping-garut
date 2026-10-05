import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { BadgeCheck, Clock, Package, Wallet } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import BookingChart from '@/components/admin/booking-chart';
import { BookingStatusBadge } from '@/components/admin/booking-status-badge';
import type { AdminDashboardPageProps } from '@/types';
import { dashboard } from '@/routes/admin';
import bookingRoutes from '@/routes/admin/bookings';

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

/**
 * Warna badge status booking diambil dari komponen badge bersama supaya
 * dashboard dan halaman booking memberi pembacaan yang sama.
 */

export default function AdminDashboard({
    business,
    stats,
}: AdminDashboardPageProps) {
    const { revenue } = stats;

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
                                    <div className="text-3xl font-semibold tabular-nums">
                                        {stats[card.key]}
                                    </div>
                                </CardContent>
                            </Card>
                        </motion.div>
                    ))}

                    <motion.div
                        initial={{ opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.3 }}
                    >
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Pendapatan Bulan Ini
                                </CardTitle>
                                <Wallet className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-semibold tabular-nums">
                                    Rp {revenue.paid_label}
                                </div>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Lunas dan dikonfirmasi admin,{' '}
                                    {revenue.period_label}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Belum diverifikasi: Rp{' '}
                                    {revenue.pending_label}
                                </p>
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>

                <div className="grid gap-4 lg:grid-cols-5">
                    <motion.div
                        initial={{ opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.36 }}
                        className="lg:col-span-2"
                    >
                        <Card className="h-full">
                            <CardHeader>
                                <CardTitle>Booking 7 Hari Terakhir</CardTitle>
                                <CardDescription>
                                    Dihitung dari tanggal booking masuk, bukan
                                    tanggal mulai sewa.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <BookingChart points={stats.bookingChart} />
                            </CardContent>
                        </Card>
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.42 }}
                        className="lg:col-span-3"
                    >
                        <Card className="h-full">
                            <CardHeader>
                                <CardTitle>Booking Terbaru</CardTitle>
                                <CardDescription>
                                    Lima booking terakhir yang masuk di unit
                                    ini.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {stats.recentBookings.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada booking masuk.
                                    </p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Kode</TableHead>
                                                <TableHead>Penyewa</TableHead>
                                                <TableHead>Produk</TableHead>
                                                <TableHead>Periode</TableHead>
                                                <TableHead className="text-right">
                                                    Total
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {stats.recentBookings.map(
                                                (booking) => (
                                                    <TableRow
                                                        key={
                                                            booking.booking_code
                                                        }
                                                    >
                                                        <TableCell className="font-mono text-xs">
                                                            <Link
                                                                href={bookingRoutes.show(
                                                                    booking.booking_code,
                                                                )}
                                                                className="underline underline-offset-4"
                                                            >
                                                                {
                                                                    booking.booking_code
                                                                }
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell>
                                                            {
                                                                booking.customer_name
                                                            }
                                                        </TableCell>
                                                        <TableCell>
                                                            {
                                                                booking.product_name
                                                            }
                                                            <span className="text-muted-foreground">
                                                                {' '}
                                                                ×
                                                                {
                                                                    booking.quantity
                                                                }
                                                            </span>
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {
                                                                booking.period_label
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-right tabular-nums">
                                                            Rp{' '}
                                                            {
                                                                booking.total_label
                                                            }
                                                        </TableCell>
                                                        <TableCell>
                                                            <BookingStatusBadge
                                                                status={
                                                                    booking.status
                                                                }
                                                                label={
                                                                    booking.status_label
                                                                }
                                                            />
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>
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
