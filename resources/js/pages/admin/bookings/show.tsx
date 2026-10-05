import { Form, Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    ArrowLeft,
    Ban,
    CircleCheck,
    Clock,
    FileText,
    MapPin,
    Phone,
    Receipt,
    User,
    type LucideIcon,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import BookingCancelDialog from '@/components/admin/booking-cancel-dialog';
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
import type {
    AdminBookingHistoryEntry,
    AdminBookingShowPageProps,
} from '@/types';

/**
 * Detail booking admin (PRD section 24, ROADMAP 4.4).
 *
 * Halaman ini adalah tempat admin memutuskan apa yang terjadi berikutnya pada
 * sebuah booking, jadi isinya diurutkan menurut urutan keputusan itu: status
 * sekarang dan tindakan yang tersedia, lalu apa yang disewa, siapa yang
 * menyewa, pembayaran, dan terakhir riwayat statusnya.
 *
 * Hanya ada satu tombol majukan status, yaitu tahap berikutnya. Backend juga
 * hanya menerima tahap berikutnya, jadi halaman ini tidak pernah menawarkan
 * pilihan yang pasti ditolak.
 */
export default function AdminBookingDetail({
    booking,
    history,
}: AdminBookingShowPageProps) {
    const [cancelOpen, setCancelOpen] = useState(false);
    const { period, customer, payment, timestamps } = booking;

    return (
        <>
            <Head title={`Booking ${booking.booking_code}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-2">
                        <Button asChild variant="ghost" size="sm">
                            <Link href={bookingRoutes.index()}>
                                <ArrowLeft />
                                Daftar booking
                            </Link>
                        </Button>

                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="font-mono text-2xl font-semibold tracking-tight">
                                {booking.booking_code}
                            </h1>
                            <BookingStatusBadge
                                status={booking.status}
                                label={booking.status_label}
                            />
                        </div>

                        <p className="text-sm text-muted-foreground">
                            Masuk {timestamps.created_at_label} ·{' '}
                            {period.start_date_label} - {period.end_date_label}{' '}
                            ({period.total_days_label})
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {booking.next_status !== null &&
                        booking.next_status_label !== null ? (
                            <Form
                                {...bookingRoutes.status.form({
                                    booking: booking.booking_code,
                                })}
                                options={{
                                    preserveScroll: true,
                                }}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="status"
                                            value={booking.next_status ?? ''}
                                        />
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button type="submit">
                                                <CircleCheck />
                                                Tandai{' '}
                                                {booking.next_status_label}
                                            </button>
                                        </Button>
                                        {errors.status ? (
                                            <span className="text-sm text-destructive">
                                                {errors.status}
                                            </span>
                                        ) : null}
                                    </>
                                )}
                            </Form>
                        ) : null}

                        {booking.is_cancellable ? (
                            <Button
                                variant="outline"
                                onClick={() => setCancelOpen(true)}
                            >
                                <Ban />
                                Batalkan Booking
                            </Button>
                        ) : null}
                    </div>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.06 }}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Penyewa</CardTitle>
                            <CardDescription>
                                NIK disamarkan di halaman ini. NIK penuh hanya
                                bisa dibaca di Manajemen Penyewa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {customer === null ? (
                                <p className="text-sm text-muted-foreground">
                                    Data penyewa sudah dihapus.
                                </p>
                            ) : (
                                <dl className="flex flex-col gap-3 text-sm">
                                    <DetailRow
                                        icon={User}
                                        label="Nama"
                                        value={customer.name}
                                    />
                                    <DetailRow
                                        icon={Phone}
                                        label="WhatsApp"
                                        value={customer.whatsapp}
                                        mono
                                    />
                                    <DetailRow
                                        label="NIK"
                                        value={customer.nik}
                                        mono
                                    />
                                    <DetailRow
                                        label="Email"
                                        value={customer.email ?? '-'}
                                    />
                                    <DetailRow
                                        icon={MapPin}
                                        label="Alamat"
                                        value={
                                            [customer.address, customer.city]
                                                .filter(Boolean)
                                                .join(', ') || '-'
                                        }
                                    />
                                </dl>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Periode dan status pembayaran</CardTitle>
                            <CardDescription>
                                Durasi sewa dihitung dari tanggal mulai dan
                                tanggal selesai sewa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <dl className="grid gap-3 text-sm sm:grid-cols-3">
                                <DetailRow
                                    label="Mulai sewa"
                                    value={period.start_date_label}
                                />
                                <DetailRow
                                    label="Selesai sewa"
                                    value={period.end_date_label}
                                />
                                <DetailRow
                                    label="Durasi"
                                    value={period.total_days_label}
                                />
                            </dl>

                            {payment === null ? (
                                <p className="text-sm text-muted-foreground">
                                    Booking ini tidak punya record pembayaran.
                                </p>
                            ) : (
                                <dl className="grid gap-3 text-sm sm:grid-cols-3">
                                    <DetailRow
                                        label="Metode"
                                        value={payment.method_label}
                                    />
                                    <DetailRow
                                        label="Nominal"
                                        value={`Rp ${payment.amount_label}`}
                                    />
                                    <div className="flex flex-col gap-1">
                                        <dt className="text-muted-foreground">
                                            Status
                                        </dt>
                                        <dd>
                                            <PaymentStatusBadge
                                                status={payment.status}
                                                label={payment.status_label}
                                            />
                                        </dd>
                                    </div>

                                    {payment.proof_url !== null ? (
                                        <DetailRow
                                            icon={FileText}
                                            label="Bukti"
                                            value={
                                                <a
                                                    href={payment.proof_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="underline underline-offset-4"
                                                >
                                                    Lihat bukti
                                                </a>
                                            }
                                        />
                                    ) : null}

                                    {payment.verified_by !== null ? (
                                        <DetailRow
                                            label="Diverifikasi"
                                            value={`${payment.verified_by} · ${
                                                payment.verified_at_label ?? '-'
                                            }`}
                                        />
                                    ) : null}

                                    {payment.rejection_reason !== null ? (
                                        <DetailRow
                                            label="Alasan penolakan"
                                            value={payment.rejection_reason}
                                        />
                                    ) : null}
                                </dl>
                            )}
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.12 }}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Barang yang disewa</CardTitle>
                            <CardDescription>
                                Nama dan harga disalin dari produk saat booking
                                dibuat, jadi tetap terbaca walaupun produknya
                                sudah diubah atau dihapus.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead className="w-32 text-right">
                                            Harga
                                        </TableHead>
                                        <TableHead className="w-20 text-right">
                                            Jumlah
                                        </TableHead>
                                        <TableHead className="w-32 text-right">
                                            Subtotal
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {booking.items.map((item) => (
                                        <TableRow
                                            key={`${item.product_name}-${item.price}`}
                                        >
                                            <TableCell>
                                                {item.product_name}
                                                <span className="block text-xs text-muted-foreground">
                                                    {item.total_days} hari
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {item.price_label}
                                                <span className="block text-xs text-muted-foreground">
                                                    / {item.price_unit}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {item.quantity}
                                            </TableCell>
                                            <TableCell className="text-right font-medium tabular-nums">
                                                {item.subtotal_label}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            <dl className="mt-4 flex flex-col gap-2 border-t pt-4 text-sm sm:items-end">
                                <div className="flex justify-between gap-4 sm:w-64">
                                    <dt className="text-muted-foreground">
                                        Subtotal
                                    </dt>
                                    <dd className="tabular-nums">
                                        Rp {booking.subtotal_label}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4 text-base font-medium sm:w-64">
                                    <dt>Total</dt>
                                    <dd className="tabular-nums">
                                        Rp {booking.total_label}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Ringkasan</CardTitle>
                            <CardDescription>
                                Informasi tambahan dari formulir pemesanan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="flex flex-col gap-3 text-sm">
                                <DetailRow
                                    label="Jumlah penyewa"
                                    value={
                                        booking.renter_count === null
                                            ? '-'
                                            : `${booking.renter_count} orang`
                                    }
                                />
                                <DetailRow
                                    icon={Receipt}
                                    label="Catatan penyewa"
                                    value={booking.notes ?? '-'}
                                />

                                {booking.cancellation_reason !== null ? (
                                    <DetailRow
                                        icon={Ban}
                                        label="Alasan pembatalan"
                                        value={booking.cancellation_reason}
                                    />
                                ) : null}
                            </dl>
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.18 }}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Riwayat status</CardTitle>
                            <CardDescription>
                                Setiap perubahan status dicatat, termasuk siapa
                                yang melakukannya.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ol className="flex flex-col gap-0">
                                {history.map((entry, index) => (
                                    <HistoryEntry
                                        key={`${entry.to_status}-${index}`}
                                        entry={entry}
                                        isLast={index === history.length - 1}
                                    />
                                ))}
                            </ol>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Tanggal penting</CardTitle>
                            <CardDescription>
                                Waktu tahap booking yang sudah terjadi.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="flex flex-col gap-3 text-sm">
                                <TimestampRow
                                    icon={Clock}
                                    label="Dikonfirmasi"
                                    value={timestamps.confirmed_at_label}
                                />
                                <TimestampRow
                                    icon={Clock}
                                    label="Mulai disewa"
                                    value={timestamps.started_at_label}
                                />
                                <TimestampRow
                                    icon={CircleCheck}
                                    label="Selesai"
                                    value={timestamps.completed_at_label}
                                />
                                <TimestampRow
                                    icon={Ban}
                                    label="Dibatalkan"
                                    value={timestamps.cancelled_at_label}
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </motion.div>
            </div>

            <BookingCancelDialog
                bookingCode={booking.booking_code}
                payment={booking.payment}
                open={cancelOpen}
                onOpenChange={setCancelOpen}
            />
        </>
    );
}

/**
 * Breadcrumb memakai fungsi, bukan objek biasa, karena crumb terakhir berisi
 * kode booking yang hanya diketahui setelah props halaman dimuat.
 */
AdminBookingDetail.layout = ({ booking }: AdminBookingShowPageProps) => ({
    breadcrumbs: [
        {
            title: 'Booking',
            href: bookingRoutes.index(),
        },
        {
            title: booking.booking_code,
        },
    ],
});

/**
 * Satu baris label-isi.
 *
 * Baris yang tidak punya nilai tetap ditampilkan dengan tanda hubung. Daftar
 * penyewa dan pembayaran sengaja tidak disembunyikan sebagian supaya admin bisa
 * melihat bahwa datanya memang tidak ada, bukan gagal dimuat.
 */
function DetailRow({
    icon: Icon,
    label,
    value,
    mono = false,
}: {
    icon?: LucideIcon;
    label: string;
    value: string | ReactNode;
    mono?: boolean;
}) {
    return (
        <div className="flex flex-col gap-0.5">
            <dt className="flex items-center gap-1.5 text-muted-foreground">
                {Icon ? <Icon className="size-3.5" aria-hidden /> : null}
                {label}
            </dt>
            <dd className={mono ? 'font-mono text-xs' : undefined}>{value}</dd>
        </div>
    );
}

/**
 * Baris timestamp yang belum terjadi.
 */
function TimestampRow({
    icon: Icon,
    label,
    value,
}: {
    icon: LucideIcon;
    label: string;
    value: string | null;
}) {
    return (
        <DetailRow
            icon={Icon}
            label={label}
            value={value ?? 'Belum terjadi'}
            mono={value !== null}
        />
    );
}

/**
 * Satu entri riwayat status.
 *
 * Baris terakhir tidak diberi garis penghubung supaya daftar jelas berhenti di
 * status sekarang, bukan berlanjut ke tahap yang belum terjadi.
 */
function HistoryEntry({
    entry,
    isLast,
}: {
    entry: AdminBookingHistoryEntry;
    isLast: boolean;
}) {
    return (
        <li className="flex gap-3">
            <div className="flex flex-col items-center">
                <span className="mt-1.5 size-2.5 shrink-0 rounded-full bg-primary" />
                {isLast ? null : <span className="w-px flex-1 bg-border" />}
            </div>

            <div className="flex flex-1 flex-col gap-1 pb-6">
                <div className="flex flex-wrap items-center gap-2">
                    {entry.from_status_label !== null ? (
                        <span className="text-sm text-muted-foreground">
                            {entry.from_status_label} &rarr;
                        </span>
                    ) : null}
                    <BookingStatusBadge
                        status={entry.to_status}
                        label={entry.to_status_label}
                    />
                </div>

                <p className="text-xs text-muted-foreground">
                    {entry.created_at_label}
                    {entry.author !== null
                        ? ` · ${entry.author}`
                        : ' · Penyewa (booking masuk)'}
                </p>

                {entry.note !== null ? (
                    <p className="text-sm">{entry.note}</p>
                ) : null}
            </div>
        </li>
    );
}
