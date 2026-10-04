import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import PublicLayout from '@/layouts/public-layout';
import type { BookingSuccessPageProps } from '@/types';
import { ArrowLeft, CalendarClock, Receipt, Wallet } from 'lucide-react';

/**
 * Halaman konfirmasi booking (ROADMAP 3.11).
 *
 * Halaman ini menampilkan kode booking hasil generate `BookingCodeGenerator`
 * (BR-10) beserta status awal booking dan pembayarannya. Isinya dibaca dari
 * session, bukan dari URL, supaya kode booking tidak bisa dijebol orang lain
 * dengan menebak alamat halaman.
 *
 * Tombol WhatsApp untuk meneruskan detail ke admin dibangun di ROADMAP 3.12.
 */
export default function BookingSuccess({
    receipt,
    businesses,
}: BookingSuccessPageProps) {
    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={`Booking ${receipt.booking_code}`} />

            <section className="border-b">
                <div className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                            Booking tersimpan
                        </h1>
                        <p className="mt-3 text-muted-foreground">
                            Simpan kode booking ini. Admin akan menghubungi Anda
                            untuk konfirmasi.
                        </p>

                        <Card className="mt-8">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Kode booking
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <p className="font-mono text-2xl font-semibold tracking-tight sm:text-3xl">
                                    {receipt.booking_code}
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
                                        <dd className="text-right font-medium">
                                            {receipt.payment.status_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-muted-foreground">
                                            Status booking
                                        </dt>
                                        <dd className="text-right font-medium">
                                            {receipt.booking_status_label}
                                        </dd>
                                    </div>
                                </dl>

                                <Separator className="my-6" />

                                <div className="flex items-baseline justify-between gap-4">
                                    <p className="text-sm text-muted-foreground">
                                        Total
                                    </p>
                                    <p className="text-2xl font-semibold tracking-tight">
                                        Rp {receipt.total_label}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="mt-8 grid gap-4 sm:grid-cols-3">
                            <div className="flex items-start gap-3">
                                <Receipt className="size-5 shrink-0 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Kode booking dipakai admin untuk menemukan
                                    transaksi Anda.
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <CalendarClock className="size-5 shrink-0 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Booking menunggu konfirmasi admin sebelum
                                    barang dipakai.
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <Wallet className="size-5 shrink-0 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Bukti pembayaran sudah tersimpan dan akan
                                    diverifikasi admin.
                                </p>
                            </div>
                        </div>

                        <Button asChild variant="ghost" className="mt-8 -ml-3">
                            <Link href="/">
                                <ArrowLeft className="size-4" />
                                Kembali ke beranda
                            </Link>
                        </Button>
                    </motion.div>
                </div>
            </section>
        </PublicLayout>
    );
}
