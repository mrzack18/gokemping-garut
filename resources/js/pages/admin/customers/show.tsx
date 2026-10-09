import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarRange,
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
import AdminEmptyState from '@/components/admin/empty-state';
import AdminPageHeader from '@/components/admin/page-header';
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

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title={customer.name}
                    titleAdornment={
                        <span className="font-mono text-sm text-muted-foreground">
                            {customer.nik}
                        </span>
                    }
                    description={`Booking pertama ${customer.first_booking_at_label} · ${customer.bookings_count} booking di unit ini`}
                    backHref={customerRoutes.index.url()}
                    backLabel="Daftar penyewa"
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="rounded-lg shadow-none">
                        <CardHeader>
                            <CardTitle className="font-display text-base">
                                Data Penyewa
                            </CardTitle>
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
                                            aria-hidden="true"
                                            className="size-3.5"
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
                                            aria-hidden="true"
                                            className="size-3.5"
                                        />
                                        Email
                                    </dt>
                                    <dd>{customer.email ?? '-'}</dd>
                                </div>
                                <div className="flex flex-col gap-0.5">
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <MapPin
                                            aria-hidden="true"
                                            className="size-3.5"
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
                                            aria-hidden="true"
                                            className="size-3.5"
                                        />
                                        Catatan
                                    </dt>
                                    <dd>{customer.notes ?? '-'}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="rounded-lg shadow-none lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="font-display text-base">
                                Ringkasan Transaksi
                            </CardTitle>
                            <CardDescription>
                                Dihitung dari booking di unit ini saja. Booking
                                yang dibatalkan tetap dihitung sebagai booking,
                                tapi nominalnya tidak ikut dijumlahkan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-1 rounded-lg border border-border bg-muted/30 p-4">
                                <span className="flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    <UserRound
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                    Jumlah Booking
                                </span>
                                <span className="font-display text-2xl font-semibold tracking-tight tabular-nums">
                                    {customer.bookings_count}
                                </span>
                            </div>
                            <div className="flex flex-col gap-1 rounded-lg border border-border bg-muted/30 p-4">
                                <span className="flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    <Receipt
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                    Total Transaksi
                                </span>
                                <span className="font-display text-2xl font-semibold tracking-tight tabular-nums">
                                    Rp {customer.total_transaction_label}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Riwayat Booking
                        </CardTitle>
                        <CardDescription>
                            Booking penyewa ini di unit ini, dari yang paling
                            baru. Klik kode booking untuk membuka detailnya.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {bookings.length === 0 ? (
                            <AdminEmptyState
                                icon={CalendarRange}
                                title="Belum ada booking di unit ini"
                                description="Riwayat booking penyewa ini akan muncul setelah transaksi pertama tercatat."
                            />
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
                                        <TableRow key={booking.booking_code}>
                                            <TableCell className="font-mono text-xs">
                                                {booking.booking_code}
                                                <span className="mt-0.5 block font-sans text-xs font-normal text-muted-foreground">
                                                    Masuk{' '}
                                                    {booking.created_at_label}
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
                                                    label={booking.status_label}
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
                                                            <ArrowRight aria-hidden="true" />
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
