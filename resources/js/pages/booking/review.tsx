import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { formatRupiah } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import bookingRoutes from '@/routes/booking';
import type { BookingReviewPageProps } from '@/types';
import {
    AlertTriangle,
    ArrowLeft,
    CalendarClock,
    Pencil,
    UserRound,
} from 'lucide-react';

/**
 * Review booking (ROADMAP 3.8, PRD section 15).
 *
 * Halaman ini menampilkan rekap detail, data penyewa, dan total, lalu meminta
 * konfirmasi sebelum lanjut ke pembayaran sesuai PRD section 15.
 *
 * Total sudah dihitung server lewat `BookingPeriod` dan dikirim sebagai angka
 * siap tampil, sehingga angka yang dibaca penyewa sama dengan yang akan
 * disimpan di ROADMAP 3.11.
 *
 * Ketersediaan dicek ulang oleh `BookingReviewController` (BR-04). Kalau stok
 * sudah tidak cukup, tombol konfirmasi dan pilihan metode dimatikan, dan
 * penyewa diminta mengubah tanggal.
 *
 * Metode pembayaran dipilih di sini (ROADMAP 3.9), lalu dikirim ke
 * `booking.gokemping.payment.store` atau `booking.sewaSepedaGarut.payment.store`.
 * Server yang memutuskan metode mana yang boleh dipakai, jadi pilihan di sini
 * hanya menentukan kemana penyewa diarahkan, bukan apa yang tersimpan.
 */
export default function BookingReview({
    business,
    businesses,
    product,
    period,
    availability,
    pricing,
    customer,
    paymentMethods,
    isBikeRental,
}: BookingReviewPageProps) {
    const [isConfirmed, setIsConfirmed] = useState(false);
    const [method, setMethod] = useState('');

    const paymentForm = useForm({ method: '' });

    const routes = routesFor(business.slug);

    if (routes === null) {
        return null;
    }

    const isAvailable = availability.is_available;
    const readyMethods = paymentMethods.filter((item) => item.is_ready);

    const detailRows = [
        { label: 'Produk', value: product.name },
        { label: 'Jumlah', value: `${period.quantity} unit` },
        {
            label: 'Tanggal',
            value: `${period.start_date_label} s/d ${period.end_date_label}`,
        },
        { label: 'Durasi', value: period.duration_label },
        {
            label: 'Harga',
            value: `${formatRupiah(pricing.price)} / ${product.price_unit}`,
        },
    ];

    const customerRows = [
        { label: 'Nama', value: customer.name },
        { label: 'WhatsApp', value: customer.whatsapp },
        { label: 'NIK', value: customer.nik },
        { label: 'Alamat', value: customer.address },
    ];

    const optionalCustomerRows = [
        customer.city === ''
            ? null
            : { label: 'Kota/Kabupaten', value: customer.city },
        customer.email === ''
            ? null
            : { label: 'Email', value: customer.email },
        customer.notes === ''
            ? null
            : { label: 'Catatan', value: customer.notes },
        isBikeRental && customer.renter_count !== ''
            ? {
                  label: 'Jumlah Penyewa',
                  value: `${customer.renter_count} orang`,
              }
            : null,
    ].filter((row): row is { label: string; value: string } => row !== null);

    const paymentStoreUrl = routes.payment.store.url();

    function chooseMethod(value: string): void {
        setMethod(value);
        paymentForm.setData('method', value);
        paymentForm.clearErrors('method');
    }

    function submitPaymentMethod(): void {
        paymentForm.post(paymentStoreUrl);
    }

    const canPay =
        isAvailable &&
        isConfirmed &&
        method !== '' &&
        readyMethods.length > 0 &&
        !paymentForm.processing;

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title="Review Booking" />

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
                            <Link href={routes.biodata.url()}>
                                <ArrowLeft className="size-4" />
                                Kembali ke biodata
                            </Link>
                        </Button>

                        <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Review Booking
                        </h1>
                        <p className="mt-3 max-w-2xl text-muted-foreground">
                            Periksa kembali detail booking dan data penyewa
                            sebelum melanjutkan ke pembayaran.
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
                        {!isAvailable ? (
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
                                        Unit yang tersisa untuk periode ini
                                        hanya {availability.available},
                                        sedangkan Anda memilih{' '}
                                        {availability.requested}. Ubah tanggal
                                        sewa untuk melanjutkan.
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="mt-3"
                                    >
                                        <Link
                                            href={routes.create.url(
                                                product.slug,
                                            )}
                                        >
                                            <CalendarClock className="size-4" />
                                            Ubah jadwal sewa
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ) : null}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Detail Booking
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="space-y-4">
                                    {detailRows.map((row) => (
                                        <div
                                            key={row.label}
                                            className="flex items-start justify-between gap-6"
                                        >
                                            <dt className="text-sm text-muted-foreground">
                                                {row.label}
                                            </dt>
                                            <dd className="text-right text-sm font-medium">
                                                {row.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>

                                <Separator className="my-5" />

                                <div className="flex items-center justify-between gap-6">
                                    <p className="text-sm text-muted-foreground">
                                        Subtotal
                                    </p>
                                    <p className="text-sm font-semibold">
                                        {formatRupiah(pricing.subtotal)}
                                    </p>
                                </div>

                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="mt-5 -ml-3"
                                >
                                    <Link
                                        href={routes.create.url(product.slug)}
                                    >
                                        <Pencil className="size-4" />
                                        Ubah jadwal atau jumlah
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Data Penyewa
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <UserRound className="size-4" />
                                    <span className="text-sm">
                                        Diisi tanpa akun, memakai data dari
                                        langkah biodata.
                                    </span>
                                </div>

                                <dl className="mt-5 space-y-4">
                                    {customerRows.map((row) => (
                                        <div
                                            key={row.label}
                                            className="flex items-start justify-between gap-6"
                                        >
                                            <dt className="text-sm text-muted-foreground">
                                                {row.label}
                                            </dt>
                                            <dd className="max-w-sm text-right text-sm font-medium break-words">
                                                {row.value}
                                            </dd>
                                        </div>
                                    ))}

                                    {optionalCustomerRows.map((row) => (
                                        <div
                                            key={row.label}
                                            className="flex items-start justify-between gap-6"
                                        >
                                            <dt className="text-sm text-muted-foreground">
                                                {row.label}
                                            </dt>
                                            <dd className="max-w-sm text-right text-sm font-medium break-words">
                                                {row.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>

                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="mt-5 -ml-3"
                                >
                                    <Link href={routes.biodata.url()}>
                                        <Pencil className="size-4" />
                                        Ubah data penyewa
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                    >
                        <Card className="lg:sticky lg:top-24">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Total
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex items-baseline justify-between gap-4">
                                    <p className="text-sm text-muted-foreground">
                                        Total pembayaran
                                    </p>
                                    <p className="text-2xl font-semibold tracking-tight">
                                        {formatRupiah(pricing.total)}
                                    </p>
                                </div>

                                <Separator />

                                <div className="space-y-3">
                                    <p className="text-sm font-medium">
                                        Metode pembayaran
                                    </p>

                                    {paymentMethods.length > 0 ? (
                                        <RadioGroup
                                            value={method}
                                            onValueChange={chooseMethod}
                                            className="gap-3"
                                        >
                                            {paymentMethods.map((item) => (
                                                <div
                                                    key={item.type}
                                                    className="flex items-start gap-3"
                                                >
                                                    <RadioGroupItem
                                                        value={item.type}
                                                        id={`method-${item.type}`}
                                                        disabled={
                                                            !item.is_ready ||
                                                            !isAvailable
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={`method-${item.type}`}
                                                        className="flex-1 text-sm leading-snug font-normal"
                                                    >
                                                        <span className="font-medium">
                                                            {item.label}
                                                        </span>
                                                        <span className="mt-0.5 block text-muted-foreground">
                                                            {item.description}
                                                        </span>
                                                        {!item.is_ready ? (
                                                            <span className="mt-0.5 block text-amber-700 dark:text-amber-400">
                                                                Belum bisa
                                                                dipilih karena
                                                                data pembayaran
                                                                belum lengkap.
                                                            </span>
                                                        ) : null}
                                                    </Label>
                                                </div>
                                            ))}
                                        </RadioGroup>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Belum ada metode pembayaran yang
                                            tersedia untuk unit ini.
                                        </p>
                                    )}

                                    {paymentForm.errors.method ? (
                                        <p className="text-sm text-red-600 dark:text-red-400">
                                            {paymentForm.errors.method}
                                        </p>
                                    ) : null}
                                </div>

                                <Separator />

                                <div className="space-y-3">
                                    <div className="flex items-start gap-3">
                                        <Checkbox
                                            id="review-confirmation"
                                            checked={isConfirmed}
                                            onCheckedChange={(checked) =>
                                                setIsConfirmed(checked === true)
                                            }
                                            disabled={!isAvailable}
                                        />
                                        <Label
                                            htmlFor="review-confirmation"
                                            className="text-sm leading-snug font-normal"
                                        >
                                            Saya pastikan detail booking dan
                                            data penyewa di atas sudah benar.
                                        </Label>
                                    </div>

                                    {!isConfirmed ? (
                                        <p className="text-xs text-muted-foreground">
                                            {isAvailable
                                                ? 'Centang konfirmasi dulu sebelum memilih metode pembayaran.'
                                                : 'Konfirmasi tidak bisa diberikan karena ketersediaan berubah.'}
                                        </p>
                                    ) : null}
                                </div>

                                <Button
                                    type="button"
                                    size="lg"
                                    className="w-full"
                                    disabled={!canPay}
                                    onClick={submitPaymentMethod}
                                >
                                    {paymentForm.processing
                                        ? 'Menyimpan...'
                                        : 'Lanjut Pembayaran'}
                                </Button>
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
