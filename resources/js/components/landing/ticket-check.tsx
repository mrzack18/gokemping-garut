import { Form, Link } from '@inertiajs/react';
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
import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { whatsappLink } from '@/lib/format';
import { home } from '@/routes';
import tickets from '@/routes/tickets';
import type { TicketDetail } from '@/types';

/**
 * Section cek tiket di landing page (ROADMAP 5.5).
 *
 * Sengaja tidak dibuat sebagai halaman terpisah: penyewa dan staf menemukannya
 * di tempat yang sama dengan informasi layanan, dan hasil pencariannya muncul
 * tepat di bawah formulir tanpa berpindah halaman.
 *
 * Tiket hanya terbuka dengan kombinasi kode booking dan nomor WhatsApp
 * penyewa, karena kode booking berurutan per hari dan bisa ditebak. NIK tidak
 * pernah ditampilkan di sini.
 */
export default function TicketCheck({
    ticket,
}: {
    ticket: TicketDetail | null;
}) {
    return (
        <Section
            id="cek-tiket"
            eyebrow="Cek tiket"
            title="Lihat status booking-mu"
            description="Masukkan kode booking dan nomor WhatsApp yang dipakai saat memesan. Staf juga bisa memakai bagian ini untuk memeriksa tiket saat pengambilan barang."
        >
            <Reveal className="mx-auto max-w-3xl">
                <Card>
                    <CardContent className="pt-6">
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

                {ticket !== null ? <TicketResult ticket={ticket} /> : null}
            </Reveal>
        </Section>
    );
}

/**
 * Ringkasan tiket yang ditemukan.
 */
function TicketResult({ ticket }: { ticket: TicketDetail }) {
    return (
        <Card className="mt-6">
            <CardContent className="flex flex-col gap-5 pt-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <p className="text-sm text-muted-foreground">
                            {ticket.business.name} &middot; booking{' '}
                            <span className="font-mono">
                                {ticket.booking_code}
                            </span>
                        </p>
                        <p className="text-xl font-semibold">
                            Tiket {ticket.customer_name}
                        </p>
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

                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                    <div className="flex gap-2">
                        <User
                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden
                        />
                        <div>
                            <dt className="text-muted-foreground">Penyewa</dt>
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
                    <span className="text-sm text-muted-foreground">Total</span>
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
                        <Link href={`${home.url()}#cek-tiket`}>
                            Cek tiket lain
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
