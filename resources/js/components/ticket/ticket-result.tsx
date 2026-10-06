import { Link } from '@inertiajs/react';
import { CalendarRange, MessageCircle, Ticket, User } from 'lucide-react';
import {
    BookingStatusBadge,
    PaymentStatusBadge,
} from '@/components/admin/booking-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { whatsappLink } from '@/lib/format';
import type { TicketDetail } from '@/types';

/**
 * Ringkasan tiket hasil pencarian, dipakai bersama oleh section cek tiket di
 * landing page dan halaman cek tiket panel admin (ROADMAP 5.5).
 *
 * Yang ditampilkan sama di dua tempat: identitas tiket, status booking dan
 * pembayaran, produk, periode, total, serta tombol WhatsApp admin unit. NIK
 * dan alamat tidak pernah ikut karena tiket ini juga halaman publik.
 */
export default function TicketResult({
    ticket,
    resetUrl,
}: {
    ticket: TicketDetail;
    /** Tautan "cek tiket lain", beda antara landing page dan panel admin. */
    resetUrl: string;
}) {
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
                        <Link href={resetUrl}>Cek tiket lain</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
