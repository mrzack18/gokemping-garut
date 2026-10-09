import { Head, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { CheckCircle2, ImageOff, Package, ReceiptText } from 'lucide-react';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminPageHeader from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { durationInDays } from '@/lib/booking';
import { formatRupiah } from '@/lib/format';
import bookingRoutes from '@/routes/admin/bookings';
import productRoutes from '@/routes/admin/products';
import type { AdminManualBookingPageProps } from '@/types';

type ManualBookingData = {
    product_id: string;
    start_date: string;
    end_date: string;
    quantity: string;
    name: string;
    whatsapp: string;
    email: string;
    nik: string;
    address: string;
    city: string;
    notes: string;
    renter_count: string;
    payment_method: 'cash' | 'qris' | 'bank_transfer';
    is_paid: boolean;
    start_rental_now: boolean;
};

type Availability = {
    stock: number;
    used: number;
    available: number;
    requested: number;
    is_available: boolean;
};

export default function AdminManualBooking({
    products,
    minDate,
    isBikeRental,
    paymentMethods,
}: AdminManualBookingPageProps) {
    const form = useForm<ManualBookingData>({
        product_id: '',
        start_date: minDate,
        end_date: minDate,
        quantity: '1',
        name: '',
        whatsapp: '',
        email: '',
        nik: '',
        address: '',
        city: '',
        notes: '',
        renter_count: isBikeRental ? '1' : '',
        payment_method: 'cash',
        is_paid: true,
        start_rental_now: true,
    });

    const [availability, setAvailability] = useState<Availability | null>(null);
    const [isChecking, setIsChecking] = useState(false);
    const [availabilityError, setAvailabilityError] = useState<string | null>(
        null,
    );

    const product = products.find(
        (item) => String(item.id) === form.data.product_id,
    );
    const quantity = Math.max(1, Number.parseInt(form.data.quantity, 10) || 1);
    const validPeriod =
        form.data.start_date !== '' &&
        form.data.end_date !== '' &&
        form.data.end_date >= form.data.start_date;
    const duration = validPeriod
        ? durationInDays(form.data.start_date, form.data.end_date)
        : 0;
    const total = product ? product.price * quantity * duration : 0;
    const paymentMethod = paymentMethods.find(
        (method) => method.value === form.data.payment_method,
    );

    useEffect(() => {
        if (!product || !validPeriod || quantity < 1) {
            setAvailability(null);
            setAvailabilityError(null);
            setIsChecking(false);

            return;
        }

        const controller = new AbortController();
        let active = true;

        setIsChecking(true);
        setAvailability(null);
        setAvailabilityError(null);

        fetch(
            bookingRoutes.manual.availability.url({
                query: {
                    product_id: String(product.id),
                    start_date: form.data.start_date,
                    end_date: form.data.end_date,
                    quantity: String(quantity),
                },
            }),
            {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            },
        )
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Permintaan ketersediaan gagal.');
                }

                return (await response.json()) as Availability;
            })
            .then((data) => {
                if (active) {
                    setAvailability(data);
                    setAvailabilityError(null);
                }
            })
            .catch((error: unknown) => {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                if (active) {
                    setAvailability(null);
                    setAvailabilityError(
                        'Ketersediaan tidak dapat dimuat. Sistem tetap mengecek stok secara aman saat booking disimpan.',
                    );
                }
            })
            .finally(() => {
                if (active) {
                    setIsChecking(false);
                }
            });

        return () => {
            active = false;
            controller.abort();
        };
    }, [
        form.data.end_date,
        form.data.start_date,
        form.data.product_id,
        product,
        quantity,
        validPeriod,
    ]);

    const availabilityMessage = useMemo(() => {
        if (isChecking) {
            return 'Memeriksa stok untuk periode ini…';
        }

        if (availabilityError !== null) {
            return availabilityError;
        }

        if (availability === null) {
            return product
                ? 'Stok periode ini akan diperiksa sebelum booking disimpan.'
                : 'Pilih produk dan periode sewa untuk melihat stok.';
        }

        return availability.is_available
            ? `${availability.available} dari ${availability.stock} unit tersedia untuk periode ini.`
            : `Stok tidak cukup: tersisa ${availability.available} unit, sementara diminta ${availability.requested}.`;
    }, [availability, availabilityError, isChecking, product]);

    function handleSubmit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        form.post(bookingRoutes.manual.store.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Booking Manual" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Booking Manual"
                    description="Catat penyewaan yang dibuat langsung di lokasi. Booking ini akan masuk ke laporan dan perhitungan stok unit yang sama."
                    backHref={bookingRoutes.index.url()}
                    backLabel="Daftar booking"
                />

                {products.length === 0 ? (
                    <AdminEmptyState
                        icon={Package}
                        title="Belum ada produk siap disewa"
                        description="Aktifkan produk dan pastikan stoknya lebih dari nol sebelum mencatat booking manual."
                        action={
                            <Button asChild>
                                <a href={productRoutes.index.url()}>
                                    Kelola produk
                                </a>
                            </Button>
                        }
                    />
                ) : (
                    <form onSubmit={handleSubmit} className="min-w-0">
                        <div className="grid gap-6 xl:grid-cols-3">
                            <div className="flex min-w-0 flex-col gap-6 xl:col-span-2">
                                <Card className="rounded-lg shadow-none">
                                    <CardHeader>
                                        <CardTitle className="font-display text-base">
                                            Barang dan periode sewa
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-5">
                                        <div className="grid gap-2">
                                            <Label htmlFor="product_id">
                                                Produk
                                            </Label>
                                            <Select
                                                value={form.data.product_id}
                                                onValueChange={(value) => {
                                                    form.setData(
                                                        'product_id',
                                                        value,
                                                    );
                                                    form.clearErrors(
                                                        'product_id',
                                                    );
                                                    form.clearErrors(
                                                        'quantity',
                                                    );
                                                }}
                                            >
                                                <SelectTrigger
                                                    id="product_id"
                                                    className="w-full"
                                                    aria-invalid={Boolean(
                                                        form.errors.product_id,
                                                    )}
                                                >
                                                    <SelectValue placeholder="Pilih produk" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {products.map((item) => (
                                                        <SelectItem
                                                            key={item.id}
                                                            value={String(
                                                                item.id,
                                                            )}
                                                        >
                                                            {item.name} · Rp{' '}
                                                            {item.price_label}/
                                                            {item.price_unit}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={form.errors.product_id}
                                            />
                                        </div>

                                        {product ? (
                                            <div className="flex items-center gap-3 rounded-md border border-border bg-muted/30 p-3">
                                                <div className="size-14 shrink-0 overflow-hidden rounded-md bg-muted">
                                                    {product.photo ? (
                                                        <img
                                                            src={product.photo}
                                                            alt={product.name}
                                                            className="size-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex size-full items-center justify-center text-muted-foreground">
                                                            <ImageOff
                                                                aria-hidden="true"
                                                                className="size-5"
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate font-medium">
                                                        {product.name}
                                                    </p>
                                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                                        {product.category ??
                                                            'Tanpa kategori'}
                                                        {' · '}Stok katalog{' '}
                                                        {product.stock} unit
                                                    </p>
                                                </div>
                                                <p className="shrink-0 text-right text-sm font-semibold tabular-nums">
                                                    Rp {product.price_label}
                                                    <span className="block text-xs font-normal text-muted-foreground">
                                                        / {product.price_unit}
                                                    </span>
                                                </p>
                                            </div>
                                        ) : null}

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="start_date">
                                                    Tanggal mulai
                                                </Label>
                                                <Input
                                                    id="start_date"
                                                    name="start_date"
                                                    type="date"
                                                    min={minDate}
                                                    value={form.data.start_date}
                                                    required
                                                    aria-invalid={Boolean(
                                                        form.errors.start_date,
                                                    )}
                                                    onChange={(event) => {
                                                        const value =
                                                            event.target.value;
                                                        form.setData(
                                                            'start_date',
                                                            value,
                                                        );
                                                        form.clearErrors(
                                                            'start_date',
                                                        );
                                                        form.clearErrors(
                                                            'end_date',
                                                        );
                                                        form.clearErrors(
                                                            'start_rental_now',
                                                        );

                                                        if (value !== minDate) {
                                                            form.setData(
                                                                'start_rental_now',
                                                                false,
                                                            );
                                                        }
                                                    }}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.start_date
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="end_date">
                                                    Tanggal selesai
                                                </Label>
                                                <Input
                                                    id="end_date"
                                                    name="end_date"
                                                    type="date"
                                                    min={
                                                        form.data.start_date ||
                                                        minDate
                                                    }
                                                    value={form.data.end_date}
                                                    required
                                                    aria-invalid={Boolean(
                                                        form.errors.end_date,
                                                    )}
                                                    onChange={(event) => {
                                                        form.setData(
                                                            'end_date',
                                                            event.target.value,
                                                        );
                                                        form.clearErrors(
                                                            'end_date',
                                                        );
                                                    }}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.end_date
                                                    }
                                                />
                                            </div>
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="quantity">
                                                    Jumlah barang
                                                </Label>
                                                <Input
                                                    id="quantity"
                                                    name="quantity"
                                                    type="number"
                                                    min={1}
                                                    max={1000}
                                                    inputMode="numeric"
                                                    value={form.data.quantity}
                                                    required
                                                    aria-invalid={Boolean(
                                                        form.errors.quantity,
                                                    )}
                                                    onChange={(event) => {
                                                        form.setData(
                                                            'quantity',
                                                            event.target.value,
                                                        );
                                                        form.clearErrors(
                                                            'quantity',
                                                        );
                                                    }}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.quantity
                                                    }
                                                />
                                            </div>

                                            <div className="flex items-end">
                                                <p
                                                    role="status"
                                                    aria-live="polite"
                                                    className={`pb-2 text-sm ${
                                                        availability?.is_available ===
                                                        false
                                                            ? 'text-destructive'
                                                            : 'text-muted-foreground'
                                                    }`}
                                                >
                                                    {availabilityMessage}
                                                </p>
                                            </div>
                                        </div>

                                        {isBikeRental ? (
                                            <div className="grid gap-2 sm:max-w-xs">
                                                <Label htmlFor="renter_count">
                                                    Jumlah penyewa sepeda
                                                </Label>
                                                <Input
                                                    id="renter_count"
                                                    name="renter_count"
                                                    type="number"
                                                    min={1}
                                                    max={100}
                                                    inputMode="numeric"
                                                    value={
                                                        form.data.renter_count
                                                    }
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'renter_count',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.renter_count
                                                    }
                                                />
                                            </div>
                                        ) : null}

                                        <div className="grid gap-2 border-t border-border pt-4">
                                            <Label htmlFor="notes">
                                                Catatan
                                            </Label>
                                            <Textarea
                                                id="notes"
                                                name="notes"
                                                rows={3}
                                                maxLength={1000}
                                                value={form.data.notes}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'notes',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Contoh: barang diserahkan di loket depan."
                                            />
                                            <InputError
                                                message={form.errors.notes}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="rounded-lg shadow-none">
                                    <CardHeader>
                                        <CardTitle className="font-display text-base">
                                            Data penyewa
                                        </CardTitle>
                                        <p className="text-sm text-muted-foreground">
                                            Catat data orang yang menerima
                                            barang supaya tiket dan riwayat
                                            sewanya tetap bisa dilacak.
                                        </p>
                                    </CardHeader>
                                    <CardContent className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Nama lengkap
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                value={form.data.name}
                                                autoComplete="name"
                                                required
                                                aria-invalid={Boolean(
                                                    form.errors.name,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.name}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="whatsapp">
                                                Nomor WhatsApp
                                            </Label>
                                            <Input
                                                id="whatsapp"
                                                name="whatsapp"
                                                value={form.data.whatsapp}
                                                placeholder="081234567890"
                                                inputMode="tel"
                                                autoComplete="tel"
                                                required
                                                aria-invalid={Boolean(
                                                    form.errors.whatsapp,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'whatsapp',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.whatsapp}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="nik">NIK</Label>
                                            <Input
                                                id="nik"
                                                name="nik"
                                                value={form.data.nik}
                                                inputMode="numeric"
                                                maxLength={16}
                                                required
                                                aria-invalid={Boolean(
                                                    form.errors.nik,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'nik',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.nik}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">
                                                Email (opsional)
                                            </Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                value={form.data.email}
                                                autoComplete="email"
                                                aria-invalid={Boolean(
                                                    form.errors.email,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'email',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.email}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="address">
                                                Alamat
                                            </Label>
                                            <Textarea
                                                id="address"
                                                name="address"
                                                rows={2}
                                                required
                                                value={form.data.address}
                                                aria-invalid={Boolean(
                                                    form.errors.address,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'address',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.address}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2 sm:max-w-xs">
                                            <Label htmlFor="city">
                                                Kota/Kabupaten (opsional)
                                            </Label>
                                            <Input
                                                id="city"
                                                name="city"
                                                value={form.data.city}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'city',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.city}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="rounded-lg shadow-none">
                                    <CardHeader>
                                        <CardTitle className="font-display text-base">
                                            Pembayaran dan penyerahan
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid gap-2 sm:max-w-md">
                                            <Label htmlFor="payment_method">
                                                Metode pembayaran
                                            </Label>
                                            <Select
                                                value={form.data.payment_method}
                                                onValueChange={(value) =>
                                                    form.setData(
                                                        'payment_method',
                                                        value as ManualBookingData['payment_method'],
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    id="payment_method"
                                                    className="w-full"
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {paymentMethods.map(
                                                        (method) => (
                                                            <SelectItem
                                                                key={
                                                                    method.value
                                                                }
                                                                value={
                                                                    method.value
                                                                }
                                                            >
                                                                {method.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    form.errors.payment_method
                                                }
                                            />
                                        </div>

                                        <div className="flex items-start gap-3 rounded-md border border-border bg-muted/30 p-3 text-sm">
                                            <Checkbox
                                                id="is_paid"
                                                checked={form.data.is_paid}
                                                onCheckedChange={(checked) =>
                                                    form.setData(
                                                        'is_paid',
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span className="grid gap-0.5">
                                                <Label
                                                    htmlFor="is_paid"
                                                    className="font-medium"
                                                >
                                                    Pembayaran sudah diterima
                                                </Label>
                                                <span className="text-muted-foreground">
                                                    Jika dicentang, pembayaran
                                                    dicatat lunas dan
                                                    terverifikasi oleh admin
                                                    yang sedang login.
                                                </span>
                                            </span>
                                        </div>

                                        <div className="flex items-start gap-3 rounded-md border border-border bg-muted/30 p-3 text-sm">
                                            <Checkbox
                                                id="start_rental_now"
                                                checked={
                                                    form.data.start_rental_now
                                                }
                                                disabled={
                                                    form.data.start_date !==
                                                    minDate
                                                }
                                                onCheckedChange={(checked) =>
                                                    form.setData(
                                                        'start_rental_now',
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span className="grid gap-0.5">
                                                <Label
                                                    htmlFor="start_rental_now"
                                                    className="font-medium"
                                                >
                                                    Barang sudah diserahkan hari
                                                    ini
                                                </Label>
                                                <span className="text-muted-foreground">
                                                    Booking langsung berstatus
                                                    sedang disewa. Untuk jadwal
                                                    mendatang, status awalnya
                                                    dikonfirmasi.
                                                </span>
                                            </span>
                                        </div>
                                        {form.errors.start_rental_now ? (
                                            <InputError
                                                role="alert"
                                                message={
                                                    form.errors.start_rental_now
                                                }
                                            />
                                        ) : null}
                                    </CardContent>
                                </Card>
                            </div>

                            <aside className="xl:sticky xl:top-24 xl:h-fit">
                                <Card className="rounded-lg shadow-none">
                                    <CardHeader>
                                        <CardTitle className="font-display text-base">
                                            Ringkasan booking
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-start gap-3">
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-md border border-pine-700/15 bg-pine-50 text-pine-700 dark:border-pine-100/20 dark:bg-pine-100/40 dark:text-pine-600">
                                                <ReceiptText
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium">
                                                    {product?.name ??
                                                        'Pilih produk'}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {form.data.start_date ||
                                                        'Tanggal mulai'}
                                                    {' – '}
                                                    {form.data.end_date ||
                                                        'Tanggal selesai'}
                                                </p>
                                            </div>
                                        </div>

                                        <dl className="space-y-2 border-y border-border py-4 text-sm">
                                            <div className="flex justify-between gap-4">
                                                <dt className="text-muted-foreground">
                                                    Jumlah
                                                </dt>
                                                <dd className="tabular-nums">
                                                    {quantity} unit
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <dt className="text-muted-foreground">
                                                    Durasi
                                                </dt>
                                                <dd className="tabular-nums">
                                                    {duration > 0
                                                        ? `${duration} hari`
                                                        : '—'}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <dt className="text-muted-foreground">
                                                    Pembayaran
                                                </dt>
                                                <dd>
                                                    {paymentMethod?.label ??
                                                        '—'}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <dt className="text-muted-foreground">
                                                    Status bayar
                                                </dt>
                                                <dd>
                                                    {form.data.is_paid
                                                        ? 'Lunas'
                                                        : 'Belum dibayar'}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <dt className="text-muted-foreground">
                                                    Status booking
                                                </dt>
                                                <dd>
                                                    {form.data.start_rental_now
                                                        ? 'Sedang disewa'
                                                        : 'Dikonfirmasi'}
                                                </dd>
                                            </div>
                                        </dl>

                                        <div className="flex items-baseline justify-between gap-4">
                                            <span className="text-sm font-medium">
                                                Total
                                            </span>
                                            <span className="font-display text-xl font-semibold tabular-nums">
                                                {product && duration > 0
                                                    ? formatRupiah(total)
                                                    : '—'}
                                            </span>
                                        </div>
                                        {product && duration > 0 ? (
                                            <p className="text-xs text-muted-foreground tabular-nums">
                                                {formatRupiah(product.price)} ×{' '}
                                                {quantity} × {duration} hari
                                            </p>
                                        ) : null}

                                        <InputError
                                            role="alert"
                                            message={form.errors.quantity}
                                        />
                                        <p className="text-xs leading-relaxed text-muted-foreground">
                                            Stok diperiksa ulang di server saat
                                            booking disimpan.
                                        </p>
                                        <Button
                                            type="submit"
                                            size="lg"
                                            className="w-full"
                                            disabled={
                                                form.processing ||
                                                isChecking ||
                                                availability?.is_available ===
                                                    false ||
                                                !form.data.product_id
                                            }
                                        >
                                            <CheckCircle2
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                            {form.processing
                                                ? 'Menyimpan…'
                                                : 'Simpan Booking Manual'}
                                        </Button>
                                    </CardContent>
                                </Card>
                            </aside>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}

AdminManualBooking.layout = {
    breadcrumbs: [
        {
            title: 'Booking',
            href: bookingRoutes.index(),
        },
        {
            title: 'Booking Manual',
            href: bookingRoutes.manual.create(),
        },
    ],
};
