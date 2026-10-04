import { Head, Link, router, usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import PublicLayout from '@/layouts/public-layout';
import { durationInDays, formatBookingDate } from '@/lib/booking';
import { formatRupiah } from '@/lib/format';
import bookingRoutes from '@/routes/booking';
import catalogRoutes from '@/routes/catalog';
import type { BookingAvailability, BookingFormPageProps } from '@/types';
import {
    AlertCircle,
    ArrowLeft,
    ImageOff,
    Loader2,
    Minus,
    Plus,
} from 'lucide-react';

function productDetailUrlBySlug(slug: string, productSlug: string): string {
    if (slug === 'gokemping') {
        return catalogRoutes.gokemping.show.url(productSlug);
    }

    if (slug === 'sewa-sepeda-garut') {
        return catalogRoutes.sewaSepedaGarut.show.url(productSlug);
    }

    return `/${slug}/${productSlug}`;
}

/**
 * Route Wayfinder untuk endpoint ketersediaan (ROADMAP 3.6). `null` kalau unit
 * bisnis tidak punya route terdaftar, supaya pemanggilan endpoint dilewati
 * alih-alih menembak URL yang tidak ada.
 */
function availabilityRouteBySlug(slug: string) {
    if (slug === 'gokemping') {
        return bookingRoutes.gokemping.availability;
    }

    if (slug === 'sewa-sepeda-garut') {
        return bookingRoutes.sewaSepedaGarut.availability;
    }

    return null;
}

/**
 * Route Wayfinder untuk menyimpan draft booking (ROADMAP 3.7).
 */
function draftRouteBySlug(slug: string) {
    if (slug === 'gokemping') {
        return bookingRoutes.gokemping.draft;
    }

    if (slug === 'sewa-sepeda-garut') {
        return bookingRoutes.sewaSepedaGarut.draft;
    }

    return null;
}

export default function BookingForm({
    business,
    businesses,
    product,
    minDate,
    initial,
}: BookingFormPageProps) {
    const serverErrors = usePage().props.errors as Record<string, string>;

    const [startDate, setStartDate] = useState(initial.start_date);
    const [endDate, setEndDate] = useState(initial.end_date);
    const [quantity, setQuantity] = useState(initial.quantity);
    const [availability, setAvailability] =
        useState<BookingAvailability | null>(null);
    const [isChecking, setIsChecking] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [availabilityError, setAvailabilityError] = useState<string | null>(
        null,
    );

    const productDetailUrl = productDetailUrlBySlug(
        business.slug,
        product.slug,
    );

    const availabilityRoute = availabilityRouteBySlug(business.slug);
    const draftRoute = draftRouteBySlug(business.slug);

    const startError =
        serverErrors.start_date ??
        (startDate === ''
            ? 'Pilih tanggal mulai.'
            : startDate < minDate
              ? 'Tanggal mulai tidak boleh di masa lalu.'
              : null);

    const endError =
        serverErrors.end_date ??
        (endDate === ''
            ? 'Pilih tanggal selesai.'
            : startError !== null
              ? null
              : endDate < startDate
                ? 'Tanggal selesai harus tanggal mulai atau setelahnya.'
                : null);

    const quantityError = serverErrors.quantity ?? null;

    const hasValidPeriod = startError === null && endError === null;

    /**
     * Endpoint ketersediaan dipanggil setiap kali periode berubah, sesuai
     * ROADMAP 3.6. Jumlah barang tidak ikut memicu request karena hanya perlu
     * dibandingkan dengan `available` yang sudah diterima.
     */
    useEffect(() => {
        if (availabilityRoute === null || !hasValidPeriod) {
            setAvailability(null);
            setAvailabilityError(null);
            setIsChecking(false);

            return;
        }

        const controller = new AbortController();

        setIsChecking(true);

        fetch(
            availabilityRoute.url(
                { product: product.slug },
                {
                    query: {
                        start_date: startDate,
                        end_date: endDate,
                    },
                },
            ),
            {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            },
        )
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Permintaan gagal');
                }

                return (await response.json()) as BookingAvailability;
            })
            .then((data) => {
                setAvailability(data);
                setAvailabilityError(null);
                setQuantity((current) =>
                    Math.min(current, Math.max(data.available, 1)),
                );
            })
            .catch((error: unknown) => {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                setAvailability(null);
                setAvailabilityError(
                    'Ketersediaan tidak bisa diperiksa. Muat ulang halaman untuk mencoba lagi.',
                );
            })
            .finally(() => setIsChecking(false));

        return () => controller.abort();
    }, [availabilityRoute, endDate, hasValidPeriod, product.slug, startDate]);

    const duration = hasValidPeriod ? durationInDays(startDate, endDate) : 0;

    /**
     * Batas stepper ikut Availability Checking: begitu respons diterima,
     * jumlah tidak boleh melebihi unit yang benar-benar tersedia.
     */
    const maxQuantity =
        availability === null
            ? product.stock
            : Math.max(availability.available, 1);

    const total = product.price * quantity * duration;
    const isOutOfStock = product.stock < 1;
    const canEstimate = !isOutOfStock && duration > 0 && quantity > 0;
    const isQuantityAvailable =
        availability !== null && quantity <= availability.available;
    const isBlocked =
        availabilityError !== null ||
        (availability !== null && !isQuantityAvailable);

    /**
     * Lanjut hanya aktif kalau periode valid, ketersediaan sudah dicek, dan
     * jumlah benar-benar tersedia. Server tetap mengecek ulang saat menyimpan
     * draft, jadi nilai di sini hanya untuk kenyamanan pengguna, bukan sumber
     * kebenaran.
     */
    const canContinue =
        !isOutOfStock &&
        hasValidPeriod &&
        quantityError === null &&
        !isChecking &&
        !isBlocked &&
        isQuantityAvailable;

    function continueToBiodata(): void {
        if (!canContinue || draftRoute === null) {
            return;
        }

        router.post(
            draftRoute.store.url(product.slug),
            {
                start_date: startDate,
                end_date: endDate,
                quantity,
            },
            {
                onStart: () => setIsSubmitting(true),
                onFinish: () => setIsSubmitting(false),
            },
        );
    }

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={`Booking ${product.name}`} />

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
                            <Link href={productDetailUrl}>
                                <ArrowLeft className="size-4" />
                                Kembali ke detail produk
                            </Link>
                        </Button>

                        <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Form Booking
                        </h1>
                        <p className="mt-3 max-w-2xl text-muted-foreground">
                            Tentukan jadwal sewa dan jumlah barang. Estimasi
                            total dihitung otomatis dan bisa berubah saat
                            ketersediaan diperiksa pada langkah berikutnya.
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
                        className="space-y-4 lg:col-span-2"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Produk yang disewa
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex gap-4">
                                    <div className="aspect-square w-24 shrink-0 overflow-hidden rounded-lg bg-muted">
                                        {product.photo ? (
                                            <img
                                                src={product.photo}
                                                alt={product.name}
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex size-full items-center justify-center text-muted-foreground">
                                                <ImageOff className="size-5" />
                                            </div>
                                        )}
                                    </div>
                                    <div className="space-y-1">
                                        {product.category ? (
                                            <Badge
                                                variant="secondary"
                                                className="font-normal"
                                            >
                                                {product.category.name}
                                            </Badge>
                                        ) : null}
                                        <p className="font-medium">
                                            {product.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatRupiah(product.price)} /{' '}
                                            {product.price_unit}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {isOutOfStock ? (
                            <Card>
                                <CardContent className="flex items-start gap-3 py-6">
                                    <AlertCircle className="mt-0.5 size-5 shrink-0 text-destructive" />
                                    <div className="space-y-1">
                                        <p className="font-medium">
                                            Stok produk sedang kosong
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Produk ini tidak bisa dipesan untuk
                                            sementara. Hubungi admin unit{' '}
                                            {business.name} untuk informasi
                                            ketersediaan.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Jadwal dan jumlah
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="start_date">
                                                Tanggal mulai
                                            </Label>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                                min={minDate}
                                                value={startDate}
                                                onChange={(event) =>
                                                    setStartDate(
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={
                                                    startError !== null
                                                }
                                            />
                                            {startError !== null ? (
                                                <p className="text-xs text-destructive">
                                                    {startError}
                                                </p>
                                            ) : null}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="end_date">
                                                Tanggal selesai
                                            </Label>
                                            <Input
                                                id="end_date"
                                                name="end_date"
                                                type="date"
                                                min={
                                                    startError === null &&
                                                    startDate !== ''
                                                        ? startDate
                                                        : minDate
                                                }
                                                value={endDate}
                                                onChange={(event) =>
                                                    setEndDate(
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={endError !== null}
                                            />
                                            {endError !== null ? (
                                                <p className="text-xs text-destructive">
                                                    {endError}
                                                </p>
                                            ) : null}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="quantity">
                                            Jumlah barang
                                        </Label>
                                        <div className="flex items-center gap-3">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                aria-label="Kurangi jumlah"
                                                disabled={quantity <= 1}
                                                onClick={() =>
                                                    setQuantity(
                                                        Math.max(
                                                            1,
                                                            quantity - 1,
                                                        ),
                                                    )
                                                }
                                            >
                                                <Minus />
                                            </Button>
                                            <input
                                                id="quantity"
                                                name="quantity"
                                                type="number"
                                                min={1}
                                                max={maxQuantity}
                                                value={quantity}
                                                onChange={(event) => {
                                                    const parsed = Number(
                                                        event.target.value,
                                                    );
                                                    setQuantity(
                                                        Number.isFinite(parsed)
                                                            ? Math.min(
                                                                  Math.max(
                                                                      Math.trunc(
                                                                          parsed,
                                                                      ),
                                                                      1,
                                                                  ),
                                                                  maxQuantity,
                                                              )
                                                            : 1,
                                                    );
                                                }}
                                                className="h-9 w-16 rounded-md border bg-background text-center text-sm tabular-nums"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                aria-label="Tambah jumlah"
                                                disabled={
                                                    quantity >= maxQuantity
                                                }
                                                onClick={() =>
                                                    setQuantity(
                                                        Math.min(
                                                            maxQuantity,
                                                            quantity + 1,
                                                        ),
                                                    )
                                                }
                                            >
                                                <Plus />
                                            </Button>
                                            <span className="text-sm text-muted-foreground">
                                                Maksimal {maxQuantity} unit
                                            </span>
                                        </div>

                                        {quantityError !== null ? (
                                            <p className="text-xs text-destructive">
                                                {quantityError}
                                            </p>
                                        ) : null}

                                        <div className="flex items-start gap-2 pt-1 text-sm">
                                            {isChecking ? (
                                                <>
                                                    <Loader2 className="mt-0.5 size-4 shrink-0 animate-spin" />
                                                    <span className="text-muted-foreground">
                                                        Memeriksa
                                                        ketersediaan...
                                                    </span>
                                                </>
                                            ) : availabilityError !== null ? (
                                                <>
                                                    <AlertCircle className="mt-0.5 size-4 shrink-0 text-destructive" />
                                                    <span className="text-destructive">
                                                        {availabilityError}
                                                    </span>
                                                </>
                                            ) : availability === null ? (
                                                <span className="text-muted-foreground">
                                                    Ketersediaan unit akan
                                                    diperiksa otomatis setelah
                                                    tanggal diisi.
                                                </span>
                                            ) : isQuantityAvailable ? (
                                                <>
                                                    <Badge variant="secondary">
                                                        Tersedia{' '}
                                                        {availability.available}{' '}
                                                        unit
                                                    </Badge>
                                                    <span className="text-muted-foreground">
                                                        dari{' '}
                                                        {availability.stock}{' '}
                                                        unit, sisa{' '}
                                                        {availability.used} unit
                                                        sedang tersewa.
                                                    </span>
                                                </>
                                            ) : (
                                                <>
                                                    <AlertCircle className="mt-0.5 size-4 shrink-0 text-destructive" />
                                                    <span className="text-destructive">
                                                        Stok tidak mencukupi
                                                        pada periode tersebut.
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    <p className="text-xs text-muted-foreground">
                                        Tanggal selesai diperlakukan sebagai
                                        batas pengembalian, bukan hari sewa.
                                        Sewa 10 sampai 12 Oktober dihitung 2
                                        hari, dan sewa pada tanggal yang sama
                                        dihitung 1 hari.
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45, delay: 0.1 }}
                    >
                        <Card className="lg:sticky lg:top-24">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Estimasi biaya
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Harga {product.price_unit}
                                    </span>
                                    <span className="tabular-nums">
                                        {formatRupiah(product.price)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Jumlah
                                    </span>
                                    <span className="tabular-nums">
                                        {quantity}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Durasi
                                    </span>
                                    <span className="tabular-nums">
                                        {duration > 0
                                            ? `${duration} hari`
                                            : '-'}
                                    </span>
                                </div>

                                {startDate !== '' && endDate !== '' ? (
                                    <p className="text-xs text-muted-foreground">
                                        {formatBookingDate(startDate)} sampai{' '}
                                        {formatBookingDate(endDate)}
                                    </p>
                                ) : null}

                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Ketersediaan
                                    </span>
                                    <span className="tabular-nums">
                                        {availability === null
                                            ? '-'
                                            : `${availability.available} dari ${availability.stock} unit`}
                                    </span>
                                </div>

                                <Separator />

                                <div className="flex items-center justify-between gap-4">
                                    <span className="font-medium">Total</span>
                                    <span className="text-xl font-semibold tabular-nums">
                                        {canEstimate
                                            ? formatRupiah(total)
                                            : '-'}
                                    </span>
                                </div>

                                {canEstimate ? (
                                    <p className="text-xs text-muted-foreground tabular-nums">
                                        {formatRupiah(product.price)} x{' '}
                                        {quantity} x {duration} hari
                                    </p>
                                ) : null}

                                <Separator />

                                <Button
                                    type="button"
                                    className="w-full"
                                    size="lg"
                                    onClick={continueToBiodata}
                                    disabled={!canContinue || isSubmitting}
                                >
                                    {isSubmitting ? 'Menyimpan...' : 'Lanjut'}
                                </Button>
                                <p className="text-xs text-muted-foreground">
                                    {availability !== null &&
                                    !isQuantityAvailable
                                        ? 'Jumlah barang melebihi unit yang tersedia pada periode ini.'
                                        : isBlocked
                                          ? 'Ketersediaan belum bisa dipastikan. Isi tanggal yang valid lalu coba lagi.'
                                          : !hasValidPeriod
                                            ? 'Isi tanggal sewa dengan benar sebelum melanjutkan.'
                                            : 'Data penyewa diminta pada langkah berikutnya.'}
                                </p>
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>
            </section>
        </PublicLayout>
    );
}
