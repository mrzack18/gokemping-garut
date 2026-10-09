import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarRange,
    MessageCircle,
    Ticket,
    User,
} from 'lucide-react';
import {
    BookingStatusBadge,
    PaymentStatusBadge,
} from '@/components/admin/booking-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
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
 * pembayaran, produk, periode, total, serta aksi yang sesuai konteks: WhatsApp
 * untuk penyewa publik, detail booking untuk staf admin. NIK dan alamat tidak
 * pernah ikut karena hasil tiket ini juga dipakai di halaman publik.
 */
export default function TicketResult({
    ticket,
    resetUrl,
    detailUrl,
    variant = 'admin',
}: {
    ticket: TicketDetail;
    /** Tautan "cek tiket lain", beda antara landing page dan panel admin. */
    resetUrl: string;
    /** Halaman detail internal yang dipakai staf setelah menemukan tiket. */
    detailUrl?: string;
    variant?: 'public' | 'admin';
}) {
    const isPublic = variant === 'public';

    return (
        <Card className={cn('mt-6', isPublic && 'rounded-lg shadow-none')}>
            <CardContent
                className={cn(
                    'flex flex-col gap-5 pt-6',
                    isPublic && 'p-5 sm:p-6',
                )}
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <p
                            className={cn(
                                'text-sm text-muted-foreground',
                                isPublic && 'text-xs',
                            )}
                        >
                            {ticket.business.name} &middot; booking{' '}
                            <span className="font-mono">
                                {ticket.booking_code}
                            </span>
                        </p>
                        <p
                            className={cn(
                                'text-xl font-semibold',
                                isPublic && 'font-display tracking-tight',
                            )}
                        >
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

                <dl
                    className={cn(
                        'grid gap-4 text-sm sm:grid-cols-2',
                        isPublic && 'border-y border-border py-5',
                    )}
                >
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

                {isPublic ? (
                    <div className="divide-y divide-border border-b border-border">
                        {ticket.items.map((item, index) => (
                            <div
                                key={`${item.product_name}-${item.quantity}-${index}`}
                                className="flex items-start justify-between gap-4 py-3 text-sm"
                            >
                                <div className="min-w-0">
                                    <p className="font-medium">
                                        {item.product_name}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Jumlah {item.quantity}
                                    </p>
                                </div>
                                <p className="shrink-0 text-right font-medium tabular-nums">
                                    Rp {item.subtotal_label}
                                </p>
                            </div>
                        ))}
                    </div>
                ) : (
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
                )}

                <div
                    className={cn(
                        'flex items-center justify-between border-t pt-4',
                        isPublic && 'border-0 pt-0',
                    )}
                >
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
                    {isPublic && ticket.business.whatsapp !== null ? (
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

                    {!isPublic && detailUrl ? (
                        <Button asChild>
                            <Link href={detailUrl}>
                                Detail booking
                                <ArrowRight aria-hidden="true" />
                            </Link>
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
