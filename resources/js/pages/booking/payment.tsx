import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useClipboard } from '@/hooks/use-clipboard';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatRupiah } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import bookingRoutes from '@/routes/booking';
import type { BookingPaymentPageProps } from '@/types';
import { AlertTriangle, ArrowLeft, Check, Copy } from 'lucide-react';

/**
 * Halaman pembayaran sesuai metode yang dipilih di halaman review
 * (ROADMAP 3.9, PRD section 17).
 *
 * Nomor rekening, nama bank, dan gambar QRIS dibaca dari `payment_methods`
 * milik unit bisnis, jadi frontend tidak pernah menyimpan data pembayaran
 * secara hardcoded.
 *
 * Upload bukti pembayaran belum ada pada tahap ini. Validasi, preview, dan
 * hapus ganti bukti dibangun di ROADMAP 3.10, jadi QRIS dan transfer baru
 * menampilkan keterangan tempat bukti, bukan input upload.
 *
 * Tombol "Lanjut Pesan via WhatsApp" tampil sesuai PRD section 17 tetapi
 * masih nonaktif karena pembuatannya ada di ROADMAP 3.12.
 */
export default function BookingPayment({
    business,
    businesses,
    method,
    product,
    period,
    availability,
    pricing,
}: BookingPaymentPageProps) {
    const [copiedText, copy] = useClipboard();

    const routes = routesFor(business.slug);

    if (routes === null) {
        return null;
    }

    const isBankTransfer = method.type === 'bank_transfer';
    const isQris = method.type === 'qris';
    const isCopied = copiedText === method.account_number;

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={`Pembayaran ${method.label}`} />

            <section className="border-b">
                <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-3"
                        >
                            <Link href={routes.review.url()}>
                                <ArrowLeft className="size-4" />
                                Kembali ke review
                            </Link>
                        </Button>

                        <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Pembayaran {method.label}
                        </h1>
                        <p className="mt-3 max-w-2xl text-muted-foreground">
                            {product.name} · {period.quantity} unit ·{' '}
                            {period.duration_label}
                        </p>
                    </motion.div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                <div className="grid gap-8 lg:grid-cols-3">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45 }}
                        className="space-y-6 lg:col-span-2"
                    >
                        {!availability.is_available ? (
                            <div
                                role="alert"
                                className="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100"
                            >
                                <AlertTriangle className="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <p className="font-medium">
                                        Ketersediaan berubah
                                    </p>
                                    <p className="mt-1 text-amber-800 dark:text-amber-200">
                                        Unit yang tersisa hanya{' '}
                                        {availability.available}, sedangkan Anda
                                        memilih {availability.requested}.
                                        Kembali ke review untuk mengubah jadwal.
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="mt-3"
                                    >
                                        <Link href={routes.review.url()}>
                                            Kembali ke review
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ) : null}

                        {isQris ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Scan QRIS
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    {method.merchant_name !== null ? (
                                        <p className="text-sm text-muted-foreground">
                                            Merchant:{' '}
                                            <span className="font-medium text-foreground">
                                                {method.merchant_name}
                                            </span>
                                        </p>
                                    ) : null}

                                    {method.qris_image_url !== null ? (
                                        <div className="flex justify-center">
                                            <img
                                                src={method.qris_image_url}
                                                alt={`QRIS ${method.merchant_name ?? business.name}`}
                                                width={280}
                                                height={280}
                                                className="rounded-lg border bg-white p-2"
                                            />
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Gambar QRIS belum tersedia.
                                        </p>
                                    )}

                                    <p className="text-sm text-muted-foreground">
                                        Scan dengan aplikasi pembayaran Anda,
                                        lalu simpan bukti transfernya.
                                    </p>
                                </CardContent>
                            </Card>
                        ) : null}

                        {isBankTransfer ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Transfer Bank
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    <dl className="space-y-4">
                                        <div className="flex items-start justify-between gap-6">
                                            <dt className="text-sm text-muted-foreground">
                                                Bank
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {method.bank_name ?? '-'}
                                            </dd>
                                        </div>
                                        <div className="flex items-start justify-between gap-6">
                                            <dt className="text-sm text-muted-foreground">
                                                Nomor rekening
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {method.account_number ?? '-'}
                                            </dd>
                                        </div>
                                        <div className="flex items-start justify-between gap-6">
                                            <dt className="text-sm text-muted-foreground">
                                                Atas nama
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {method.account_name ?? '-'}
                                            </dd>
                                        </div>
                                    </dl>

                                    {method.account_number !== null ? (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                void copy(
                                                    method.account_number ?? '',
                                                )
                                            }
                                        >
                                            {isCopied ? (
                                                <Check className="size-4" />
                                            ) : (
                                                <Copy className="size-4" />
                                            )}
                                            {isCopied
                                                ? 'Nomor rekening tersalin'
                                                : 'Salin Nomor Rekening'}
                                        </Button>
                                    ) : null}
                                </CardContent>
                            </Card>
                        ) : null}

                        {!isQris && !isBankTransfer ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Pembayaran langsung
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">
                                        {method.instructions ??
                                            'Pembayaran dilakukan langsung kepada admin.'}
                                    </p>
                                </CardContent>
                            </Card>
                        ) : null}

                        {method.requires_proof ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Bukti Pembayaran
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">
                                        Unggah bukti pembayaran setelah
                                        mentransfer. Form upload, validasi, dan
                                        pratinjau bildir dibangun pada ROADMAP
                                        3.10.
                                    </p>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Bukti Pembayaran
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">
                                        Bukti pembayaran tidak wajib untuk
                                        pembayaran cash (BR-08).
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                    >
                        <Card className="lg:sticky lg:top-24">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Ringkasan
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <dl className="space-y-4">
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Produk
                                        </dt>
                                        <dd className="text-right text-sm font-medium">
                                            {product.name}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Periode
                                        </dt>
                                        <dd className="text-right text-sm font-medium">
                                            {period.start_date_label} s/d{' '}
                                            {period.end_date_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Durasi
                                        </dt>
                                        <dd className="text-sm font-medium">
                                            {period.duration_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Metode
                                        </dt>
                                        <dd className="text-sm font-medium">
                                            {method.label}
                                        </dd>
                                    </div>
                                </dl>

                                <Separator />

                                <div className="flex items-baseline justify-between gap-4">
                                    <p className="text-sm text-muted-foreground">
                                        Total pembayaran
                                    </p>
                                    <p className="text-2xl font-semibold tracking-tight">
                                        {formatRupiah(pricing.total)}
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    size="lg"
                                    className="w-full"
                                    disabled
                                >
                                    Lanjut Pesan via WhatsApp
                                </Button>
                                <p className="text-xs text-muted-foreground">
                                    Tombol WhatsApp dibangun pada ROADMAP 3.12,
                                    jadi tombol ini belum aktif.
                                </p>
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>
            </section>
        </PublicLayout>
    );
}

/**
 * Route Wayfinder untuk halaman booking satu unit. `null` kalau slug unit tidak
 * terdaftar, supaya halaman tidak pernah merender tautan ke URL yang tidak ada.
 */
function routesFor(slug: string) {
    if (slug === 'gokemping') {
        return bookingRoutes.gokemping;
    }

    if (slug === 'sewa-sepeda-garut') {
        return bookingRoutes.sewaSepedaGarut;
    }

    return null;
}
