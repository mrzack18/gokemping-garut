import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useClipboard } from '@/hooks/use-clipboard';
import BookingSteps from '@/components/booking/booking-steps';
import StatusPill from '@/components/public/status-pill';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import TicketQr from '@/components/ticket/ticket-qr';
import PublicLayout from '@/layouts/public-layout';
import { formatRupiah } from '@/lib/format';
import { home } from '@/routes';
import type { BookingSuccessPageProps } from '@/types';
import {
    ArrowLeft,
    CalendarClock,
    Check,
    CheckCircle2,
    Copy,
    MessageCircle,
    Receipt,
    Ticket,
} from 'lucide-react';

/**
 * Halaman konfirmasi booking (ROADMAP 3.11 dan 3.12).
 *
 * Halaman ini menampilkan kode booking hasil generate `BookingCodeGenerator`
 * (BR-10) beserta status awal booking dan pembayarannya. Isinya dibaca dari
 * session, bukan dari URL, supaya kode booking tidak bisa dijebol orang lain
 * dengan menebak alamat halaman.
 *
 * Tombol "Lanjut Pesan via WhatsApp" meneruskan detail booking ke admin sesuai
 * template PRD section 19. Tautan dan isi pesannya sudah dirakit di server,
 * dan nomornya mengikuti unit penyewa (BR-06).
 */
export default function BookingSuccess({
    receipt,
    businesses,
}: BookingSuccessPageProps) {
    const { whatsapp } = receipt;
    const [copiedText, copy] = useClipboard();

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={`Booking ${receipt.booking_code}`} />

            <section className="border-b border-border bg-sand-50">
                <div className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="mb-4 -ml-3"
                        >
                            <Link href="/">
                                <ArrowLeft
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                Kembali ke beranda
                            </Link>
                        </Button>

                        <div className="flex items-center gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center border border-success/20 bg-success/10 text-success">
                                <CheckCircle2
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            </span>
                            <h1 className="font-display text-3xl font-semibold tracking-tight sm:text-4xl">
                                Booking tersimpan
                            </h1>
                        </div>
                        <p className="mt-3 text-muted-foreground">
                            Kirim detail booking ini ke admin lewat WhatsApp
                            supaya pesanan Anda lebih cepat diproses. Kode
                            booking di bawah tetap dipakai admin untuk menemukan
                            transaksi Anda.
                        </p>

                        <div className="mt-7 max-w-2xl">
                            <BookingSteps current={4} complete />
                        </div>

                        <div className="mt-8 flex flex-col gap-3">
                            {whatsapp.url ? (
                                <Button asChild size="lg" className="w-full">
                                    <a
                                        href={whatsapp.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <MessageCircle />
                                        Lanjut Pesan via WhatsApp
                                    </a>
                                </Button>
                            ) : (
                                <Alert variant="destructive">
                                    <MessageCircle />
                                    <AlertTitle>
                                        Nomor WhatsApp admin belum tersedia
                                    </AlertTitle>
                                    <AlertDescription>
                                        Salin detail booking di bawah lalu
                                        kirimkan ke admin lewat WhatsApp secara
                                        manual.
                                    </AlertDescription>
                                </Alert>
                            )}

                            {whatsapp.number ? (
                                <p className="text-center text-xs text-muted-foreground">
                                    Pesan akan dibuka di WhatsApp Admin{' '}
                                    {receipt.business_name} ({whatsapp.number}).
                                </p>
                            ) : null}

                            <Button asChild variant="outline" size="lg">
                                <Link href={`${home.url()}#cek-tiket`}>
                                    <Ticket />
                                    Cek Status Tiket
                                </Link>
                            </Button>
                        </div>

                        <Card className="mt-8 rounded-lg shadow-none">
                            <CardHeader>
                                <CardTitle className="font-display text-base">
                                    Kode booking
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <p className="font-mono text-xl font-semibold tracking-tight sm:text-2xl">
                                        {receipt.booking_code}
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            void copy(receipt.booking_code)
                                        }
                                    >
                                        {copiedText === receipt.booking_code ? (
                                            <Check
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        ) : (
                                            <Copy
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        )}
                                        {copiedText === receipt.booking_code
                                            ? 'Tersalin'
                                            : 'Salin kode'}
                                    </Button>
                                </div>
                                <p aria-live="polite" className="sr-only">
                                    {copiedText === receipt.booking_code
                                        ? 'Kode booking tersalin.'
                                        : ''}
                                </p>

                                <Separator className="my-6" />

                                <dl className="space-y-4 text-sm">
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Produk
                                        </dt>
                                        <dd className="text-right font-medium">
                                            {receipt.product_name}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Periode
                                        </dt>
                                        <dd className="text-right font-medium">
                                            {receipt.period.start_date_label}{' '}
                                            s/d {receipt.period.end_date_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Durasi
                                        </dt>
                                        <dd className="font-medium">
                                            {receipt.period.duration} hari ·{' '}
                                            {receipt.period.quantity} unit
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Metode pembayaran
                                        </dt>
                                        <dd className="text-right font-medium">
                                            {receipt.payment.method_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Status pembayaran
                                        </dt>
                                        <dd className="text-right">
                                            <StatusPill tone="pending">
                                                {receipt.payment.status_label}
                                            </StatusPill>
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Status booking
                                        </dt>
                                        <dd className="text-right">
                                            <StatusPill tone="pending">
                                                {receipt.booking_status_label}
                                            </StatusPill>
                                        </dd>
                                    </div>
                                </dl>

                                <Separator className="my-6" />

                                <div className="flex items-baseline justify-between gap-4">
                                    <p className="text-sm text-muted-foreground">
                                        Total
                                    </p>
                                    <p className="font-display text-2xl font-semibold tracking-tight tabular-nums">
                                        {formatRupiah(receipt.total)}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="mt-6 rounded-lg shadow-none">
                            <CardHeader>
                                <CardTitle className="font-display text-base">
                                    QR tiket
                                </CardTitle>
                            </CardHeader>

                            <CardContent className="flex flex-col items-center gap-3">
                                <TicketQr
                                    url={receipt.ticket_url}
                                    fileName={`tiket-${receipt.booking_code}.png`}
                                />
                                <p className="max-w-sm text-center text-sm text-muted-foreground">
                                    Tunjukkan atau pindai QR ini saat
                                    pengambilan barang. Staf bisa memindainya
                                    untuk membuka status tiket tanpa mengetik
                                    kode.
                                </p>
                            </CardContent>
                        </Card>

                        <Accordion
                            type="single"
                            collapsible
                            className="mt-6 rounded-lg border px-4"
                        >
                            <AccordionItem value="message">
                                <AccordionTrigger>
                                    Lihat isi pesan WhatsApp
                                </AccordionTrigger>
                                <AccordionContent>
                                    <pre className="overflow-x-auto text-xs leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {whatsapp.message}
                                    </pre>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>

                        <div className="mt-8 grid gap-4 sm:grid-cols-2">
                            <div className="flex items-start gap-3">
                                <Receipt className="size-5 shrink-0 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Kode booking dipakai admin untuk menemukan
                                    transaksi Anda, jadi sebutkan kode ini di
                                    pesan pertama.
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <CalendarClock className="size-5 shrink-0 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Booking menunggu konfirmasi admin sebelum
                                    barang dipakai.
                                </p>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </section>
        </PublicLayout>
    );
}
