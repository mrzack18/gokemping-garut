import { Form, Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    CalendarRange,
    MessageCircle,
    Search,
    Ticket,
    User,
} from 'lucide-react';
import {
    BookingStatusBadge,
    PaymentStatusBadge,
} from '@/components/admin/booking-status-badge';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import PublicLayout from '@/layouts/public-layout';
import { whatsappLink } from '@/lib/format';
import tickets from '@/routes/tickets';
import type { TicketCheckPageProps } from '@/types';

/**
 * Cek tiket publik (ROADMAP lanjutan).
 *
 * Penyewa memakai halaman ini untuk melihat status booking-nya, dan staf
 * memakainya untuk memverifikasi tiket saat barang diambil. Tiket hanya
 * terbuka dengan kombinasi kode booking dan nomor WhatsApp penyewa, karena
 * kode booking berurutan per hari dan bisa ditebak.
 *
 * NIK tidak pernah ditampilkan di sini; halaman ini milik publik.
 */
export default function TicketCheck({
    ticket,
    businesses,
}: TicketCheckPageProps) {
    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title="Cek Tiket" />

            <section className="border-b">
                <div className="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
                            Cek tiket
                        </p>
                        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Lihat status booking-mu
                        </h1>
                        <p className="mt-3 text-muted-foreground">
                            Masukkan kode booking dan nomor WhatsApp yang
                            dipakai saat memesan. Staf juga bisa memakai halaman
                            ini untuk memeriksa tiket saat pengambilan barang.
                        </p>
                    </motion.div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Cari tiket</CardTitle>
                        <CardDescription>
                            Kode booking ada di pesan konfirmasi WhatsApp,
                            contoh GK-20261015-001.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...tickets.lookup.form()}
                            options={{
                                preserveScroll: true,
                                preserveState: true,
                            }}
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="booking_code">
                                            Kode booking
                                        </Label>
                                        <Input
                                            id="booking_code"
                                            name="booking_code"
                                            placeholder="GK-20261015-001"
                                            maxLength={20}
                                            required
                                            autoFocus
                                        />
                                        <InputError
                                            message={errors.booking_code}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="whatsapp">
                                            Nomor WhatsApp
                                        </Label>
                                        <Input
                                            id="whatsapp"
                                            name="whatsapp"
                                            placeholder="081234567890"
                                            maxLength={25}
                                            required
                                        />
                                        <InputError message={errors.whatsapp} />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full sm:w-auto"
                                        >
                                            <Search className="size-4" />
                                            {processing
                                                ? 'Mencari...'
                                                : 'Cek Tiket'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {ticket !== null ? <TicketDetailCard ticket={ticket} /> : null}
            </section>
        </PublicLayout>
    );
}

/**
 * Detail tiket yang sudah ditemukan.
 */
function TicketDetailCard({
    ticket,
}: {
    ticket: NonNullable<TicketCheckPageProps['ticket']>;
}) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
            className="mt-6"
        >
            <Card>
                <CardHeader>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex flex-col gap-1">
                            <CardDescription>
                                {ticket.business.name} · booking{' '}
                                <span className="font-mono">
                                    {ticket.booking_code}
                                </span>
                            </CardDescription>
                            <CardTitle className="text-xl">
                                Tiket {ticket.customer_name}
                            </CardTitle>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <BookingStatusBadge
                                status={ticket.status}
                                label={ticket.status_label}
                            />
                            <PaymentStatusBadge
                                status={ticket.payment_status}
                                label={ticket.payment_status_label}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="flex flex-col gap-5">
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div className="flex gap-2">
                            <User
                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                aria-hidden
                            />
                            <div>
                                <dt className="text-muted-foreground">
                                    Penyewa
                                </dt>
                                <dd className="font-medium">
                                    {ticket.customer_name}
                                </dd>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <CalendarRange
                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                aria-hidden
                            />
                            <div>
                                <dt className="text-muted-foreground">
                                    Periode sewa
                                </dt>
                                <dd className="font-medium">
                                    {ticket.period.start_date_label} &ndash;{' '}
                                    {ticket.period.end_date_label} (
                                    {ticket.period.total_days_label})
                                </dd>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <Ticket
                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                aria-hidden
                            />
                            <div>
                                <dt className="text-muted-foreground">
                                    Metode pembayaran
                                </dt>
                                <dd className="font-medium">
                                    {ticket.payment_method_label}
                                </dd>
                            </div>
                        </div>

                        <div>
                            <dt className="text-muted-foreground">Dibuat</dt>
                            <dd className="font-medium">
                                {ticket.created_at_label}
                            </dd>
                        </div>
                    </dl>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead className="w-20 text-right">
                                    Jumlah
                                </TableHead>
                                <TableHead className="w-32 text-right">
                                    Subtotal
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {ticket.items.map((item) => (
                                <TableRow
                                    key={`${item.product_name}-${item.quantity}`}
                                >
                                    <TableCell>{item.product_name}</TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {item.quantity}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        Rp {item.subtotal_label}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <div className="flex items-center justify-between border-t pt-4">
                        <span className="text-sm text-muted-foreground">
                            Total
                        </span>
                        <span className="text-lg font-semibold tabular-nums">
                            Rp {ticket.total_label}
                        </span>
                    </div>

                    {ticket.cancellation_reason !== null ? (
                        <Alert variant="destructive">
                            <AlertTitle>Booking dibatalkan</AlertTitle>
                            <AlertDescription>
                                {ticket.cancellation_reason}
                            </AlertDescription>
                        </Alert>
                    ) : null}

                    <div className="flex flex-wrap items-center gap-2">
                        {ticket.business.whatsapp !== null ? (
                            <Button asChild>
                                <a
                                    href={whatsappLink(
                                        ticket.business.whatsapp,
                                        `Halo ${ticket.business.name}, saya mau menanyakan tiket ${ticket.booking_code}.`,
                                    )}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <MessageCircle className="size-4" />
                                    Tanya admin
                                </a>
                            </Button>
                        ) : null}

                        <Button asChild variant="outline">
                            <Link href={tickets.check()}>Cek tiket lain</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </motion.div>
    );
}
