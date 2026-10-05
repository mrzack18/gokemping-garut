import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    ArrowLeft,
    ArrowRight,
    Mail,
    MapPin,
    Phone,
    Receipt,
    StickyNote,
    UserRound,
} from 'lucide-react';
import {
    BookingStatusBadge,
    PaymentStatusBadge,
} from '@/components/admin/booking-status-badge';
import { Button } from '@/components/ui/button';
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
import bookingRoutes from '@/routes/admin/bookings';
import customerRoutes from '@/routes/admin/customers';
import type { AdminCustomerShowPageProps } from '@/types';

/**
 * Detail penyewa admin (PRD section 25, ROADMAP 4.5).
 *
 * Halaman ini menjawab satu pertanyaan: siapa orang ini dan bagaimana
 * rekam jejaknya di unit bisnis ini. Karena itu riwayat yang tampil hanya
 * booking di unit ini, dan total transaksi dihitung dari booking yang tidak
 * dibatalkan.
 *
 * NIK yang ditampilkan selalu versi tersamar. Nilai aslinya hanya dipakai untuk
 * pencarian di daftar penyewa, bukan untuk ditampilkan.
 */
export default function AdminCustomerDetail({
    customer,
    bookings,
}: AdminCustomerShowPageProps) {
    return (
        <>
            <Head title={`Penyewa ${customer.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-2"
                >
                    <Button asChild variant="ghost" size="sm">
                        <Link href={customerRoutes.index()}>
                            <ArrowLeft />
                            Daftar penyewa
                        </Link>
                    </Button>

                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {customer.name}
                        </h1>
                        <span className="font-mono text-sm text-muted-foreground">
                            {customer.nik}
                        </span>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Booking pertama {customer.first_booking_at_label} ·{' '}
                        {customer.bookings_count} booking di unit ini
                    </p>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.06 }}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Data Penyewa</CardTitle>
                            <CardDescription>
                                NIK tersamar. NIK penuh hanya dipakai untuk
                                pencarian, tidak pernah ditampilkan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="flex flex-col gap-3 text-sm">
                                <div className="flex flex-col gap-0.5">
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <Phone
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                        WhatsApp
                                    </dt>
                                    <dd className="font-mono tabular-nums">
                                        {customer.whatsapp}
                                    </dd>
                                </div>
                                <div className="flex flex-col gap-0.5">
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <Mail
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                        Email
                                    </dt>
                                    <dd>{customer.email ?? '-'}</dd>
                                </div>
                                <div className="flex flex-col gap-0.5">
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <MapPin
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                        Alamat
                                    </dt>
                                    <dd>
                                        {customer.address}
                                        {customer.city
                                            ? `, ${customer.city}`
                                            : ''}
                                    </dd>
                                </div>
                                <div className="flex flex-col gap-0.5">
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <StickyNote
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                        Catatan
                                    </dt>
                                    <dd>{customer.notes ?? '-'}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Ringkasan Transaksi</CardTitle>
                            <CardDescription>
                                Dihitung dari booking di unit ini saja. Booking
                                yang dibatalkan tetap dihitung sebagai booking,
                                tapi nominalnya tidak ikut dijumlahkan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-1 rounded-lg border p-4">
                                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <UserRound
                                        className="size-3.5"
                                        aria-hidden
                                    />
                                    Jumlah Booking
                                </span>
                                <span className="text-2xl font-semibold tabular-nums">
                                    {customer.bookings_count}
                                </span>
                            </div>
                            <div className="flex flex-col gap-1 rounded-lg border p-4">
                                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Receipt className="size-3.5" aria-hidden />
                                    Total Transaksi
                                </span>
                                <span className="text-2xl font-semibold tabular-nums">
                                    Rp {customer.total_transaction_label}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.12 }}
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Riwayat Booking</CardTitle>
                            <CardDescription>
                                Booking penyewa ini di unit ini, dari yang
                                paling baru. Klik kode booking untuk membuka
                                detailnya.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {bookings.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada booking di unit ini.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Kode</TableHead>
                                            <TableHead className="w-44">
                                                Periode
                                            </TableHead>
                                            <TableHead className="w-36 text-right">
                                                Total
                                            </TableHead>
                                            <TableHead className="w-36">
                                                Pembayaran
                                            </TableHead>
                                            <TableHead className="w-44">
                                                Status
                                            </TableHead>
                                            <TableHead className="w-24 text-right">
                                                Aksi
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {bookings.map((booking) => (
                                            <TableRow
                                                key={booking.booking_code}
                                            >
                                                <TableCell className="font-mono text-xs">
                                                    {booking.booking_code}
                                                    <span className="mt-0.5 block font-sans text-xs font-normal text-muted-foreground">
                                                        Masuk{' '}
                                                        {
                                                            booking.created_at_label
                                                        }
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {booking.period_label}
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    Rp {booking.total_label}
                                                </TableCell>
                                                <TableCell>
                                                    <PaymentStatusBadge
                                                        status={
                                                            booking.payment_status
                                                        }
                                                        label={
                                                            booking.payment_status_label
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <BookingStatusBadge
                                                        status={booking.status}
                                                        label={
                                                            booking.status_label
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end">
                                                        <Button
                                                            asChild
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            <Link
                                                                href={bookingRoutes.show(
                                                                    booking.booking_code,
                                                                )}
                                                            >
                                                                Booking
                                                                <ArrowRight />
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </motion.div>
            </div>
        </>
    );
}

/**
 * Breadcrumb memakai fungsi karena crumb terakhir berisi nama penyewa yang
 * hanya diketahui setelah props halaman dimuat.
 */
AdminCustomerDetail.layout = ({ customer }: AdminCustomerShowPageProps) => ({
    breadcrumbs: [
        {
            title: 'Penyewa',
            href: customerRoutes.index(),
        },
        {
            title: customer.name,
        },
    ],
});
