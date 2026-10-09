import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import {
    BadgeCheck,
    CalendarRange,
    Clock,
    Package,
    Wallet,
} from 'lucide-react';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminPageHeader from '@/components/admin/page-header';
import AdminStatCard from '@/components/admin/stat-card';
import BookingChart from '@/components/admin/booking-chart';
import { BookingStatusBadge } from '@/components/admin/booking-status-badge';
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
import type { AdminDashboardPageProps } from '@/types';
import { dashboard } from '@/routes/admin';
import bookingRoutes from '@/routes/admin/bookings';

export default function AdminDashboard({
    business,
    stats,
}: AdminDashboardPageProps) {
    const { revenue } = stats;
    const reducedMotion = useReducedMotion();

    const statCards = [
        {
            key: 'totalProducts',
            label: 'Total Produk',
            icon: Package,
            tone: 'default' as const,
        },
        {
            key: 'bookingsToday',
            label: 'Booking Hari Ini',
            icon: Clock,
            tone: 'default' as const,
        },
        {
            key: 'rented',
            label: 'Sedang Disewa',
            icon: BadgeCheck,
            tone: 'success' as const,
        },
        {
            key: 'awaitingConfirmation',
            label: 'Menunggu Konfirmasi',
            icon: Clock,
            tone: 'warning' as const,
        },
        {
            key: 'pendingPayments',
            label: 'Menunggu Verifikasi Bayar',
            icon: Wallet,
            tone: 'warning' as const,
        },
    ] as const;

    return (
        <>
            <Head title={`Dashboard ${business.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title={`Dashboard ${business.name}`}
                    description="Ringkasan operasional unit usaha Anda hari ini. Data di halaman ini hanya mencakup unit ini."
                    meta={
                        <>
                            <Badge
                                variant="outline"
                                className="border-pine-700/20 bg-pine-50 font-mono text-pine-700 dark:border-pine-100/20 dark:bg-pine-100/30 dark:text-pine-600"
                            >
                                {business.bookingCodePrefix}
                            </Badge>
                            <span className="text-xs text-muted-foreground">
                                WhatsApp admin: {business.whatsapp}
                            </span>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {statCards.map((card, index) => (
                        <motion.div
                            key={card.key}
                            initial={
                                reducedMotion ? false : { opacity: 0, y: 10 }
                            }
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: reducedMotion ? 0 : 0.3,
                                delay: reducedMotion ? 0 : index * 0.05,
                                ease: [0.22, 1, 0.36, 1],
                            }}
                        >
                            <AdminStatCard
                                icon={card.icon}
                                label={card.label}
                                value={stats[card.key]}
                                tone={card.tone}
                            />
                        </motion.div>
                    ))}

                    <motion.div
                        initial={reducedMotion ? false : { opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            duration: reducedMotion ? 0 : 0.3,
                            delay: reducedMotion ? 0 : 0.25,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                    >
                        <AdminStatCard
                            icon={Wallet}
                            label="Pendapatan Bulan Ini"
                            value={`Rp ${revenue.paid_label}`}
                            hint={`Lunas ${revenue.period_label} · Menunggu Rp ${revenue.pending_label}`}
                        />
                    </motion.div>
                </div>

                <div className="grid gap-4 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <Card className="h-full rounded-lg shadow-none">
                            <CardHeader>
                                <CardTitle className="font-display text-base">
                                    Booking 7 Hari Terakhir
                                </CardTitle>
                                <CardDescription>
                                    Dihitung dari tanggal booking masuk, bukan
                                    tanggal mulai sewa.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <BookingChart points={stats.bookingChart} />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="lg:col-span-3">
                        <Card className="h-full rounded-lg shadow-none">
                            <CardHeader>
                                <CardTitle className="font-display text-base">
                                    Booking Terbaru
                                </CardTitle>
                                <CardDescription>
                                    Lima booking terakhir yang masuk di unit
                                    ini.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {stats.recentBookings.length === 0 ? (
                                    <AdminEmptyState
                                        icon={CalendarRange}
                                        title="Belum ada booking masuk"
                                        description="Booking baru dari penyewa akan muncul di sini beserta statusnya."
                                    />
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
                                                                className="font-medium text-pine-700 underline underline-offset-4 dark:text-pine-600"
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
                    </div>
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
