import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import PublicLayout from '@/layouts/public-layout';
import { durationInDays, formatBookingDate } from '@/lib/booking';
import { formatRupiah } from '@/lib/format';
import bookingRoutes from '@/routes/booking';
import type { BookingBiodataPageProps, CustomerLookupResponse } from '@/types';
import { ArrowLeft, CheckCircle2, Loader2, Search } from 'lucide-react';

function routesFor(slug: string) {
    if (slug === 'gokemping') {
        return bookingRoutes.gokemping;
    }

    if (slug === 'sewa-sepeda-garut') {
        return bookingRoutes.sewaSepedaGarut;
    }

    return null;
}

export default function BookingBiodata({
    business,
    businesses,
    product,
    draft,
    customer,
    isBikeRental,
}: BookingBiodataPageProps) {
    const routes = routesFor(business.slug);

    const { data, setData, post, processing, errors } = useForm({
        name: customer.name,
        whatsapp: customer.whatsapp,
        email: customer.email,
        nik: customer.nik,
        address: customer.address,
        city: customer.city,
        notes: customer.notes,
        renter_count: customer.renter_count ?? '',
    });

    const [isChecking, setIsChecking] = useState(false);
    const [isFound, setIsFound] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);
    const controllerRef = useRef<AbortController | null>(null);

    const duration = durationInDays(draft.start_date, draft.end_date);
    const quantity = draft.quantity;
    const subtotal = product.price * quantity * duration;

    useEffect(() => {
        return () => controllerRef.current?.abort();
    }, []);

    /**
     * Kedua unit bisnis punya route biodata, jadi slug di luar daftar itu
     * tidak pernah muncul dari backend. Halaman tidak dirender kalau ada
     * slug yang tidak dikenal.
     */
    if (routes === null) {
        return null;
    }

    /**
     * Prefill dari data penyewa lama (PRD section 25). Dipanggil saat pengguna
     * selesai mengisi WhatsApp atau NIK. Endpoint publik ini hanya mengembalikan
     * data milik nomor yang diberikan, tidak pernah daftar pelanggan.
     */
    function lookupExistingCustomer(identifier: {
        whatsapp?: string;
        nik?: string;
    }): void {
        if (isChecking) {
            return;
        }

        controllerRef.current?.abort();

        const controller = new AbortController();

        controllerRef.current = controller;
        setIsChecking(true);
        setLookupError(null);

        fetch(bookingRoutes.customerLookup.url({ query: identifier }), {
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Permintaan gagal');
                }

                return (await response.json()) as CustomerLookupResponse;
            })
            .then((result) => {
                setIsFound(result.found);

                if (!result.found || result.customer === null) {
                    return;
                }

                setData((current) => ({
                    ...current,
                    name: result.customer?.name || current.name,
                    whatsapp:
                        identifier.whatsapp ??
                        result.customer?.whatsapp ??
                        current.whatsapp,
                    email: result.customer?.email ?? current.email,
                    nik: result.customer?.nik ?? current.nik,
                    address: result.customer?.address ?? current.address,
                    city: result.customer?.city ?? current.city,
                    notes: result.customer?.notes ?? current.notes,
                }));
            })
            .catch((error: unknown) => {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                setLookupError(
                    'Data penyewa lama tidak bisa dicek. Anda tetap bisa mengisi form secara manual.',
                );
            })
            .finally(() => setIsChecking(false));
    }

    const lookupStatus = (): ReactNode => {
        if (isChecking) {
            return (
                <>
                    <Loader2 className="size-4 animate-spin" />
                    <span className="text-muted-foreground">
                        Mencari data penyewa lama...
                    </span>
                </>
            );
        }

        if (isFound) {
            return (
                <Badge variant="secondary">
                    <CheckCircle2 className="mr-1 size-3.5" />
                    Data lama ditemukan, mohon periksa kembali
                </Badge>
            );
        }

        return (
            <>
                <Search className="size-4 text-muted-foreground" />
                <span className="text-muted-foreground">
                    Selesai mengisi WhatsApp atau NIK, data penyewa lama akan
                    diisi otomatis.
                </span>
            </>
        );
    };

    const summaryRows: {
        label: string;
        value: string;
        emphasis?: boolean;
    }[] = [
        { label: 'Produk', value: product.name },
        {
            label: 'Periode',
            value: `${formatBookingDate(draft.start_date)} sampai ${formatBookingDate(draft.end_date)}`,
        },
        { label: 'Durasi', value: `${duration} hari` },
        { label: 'Jumlah', value: String(quantity) },
        {
            label: `Harga ${product.price_unit}`,
            value: formatRupiah(product.price),
        },
    ];

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title="Biodata Penyewa" />

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
                            <Link href={routes.create.url(product.slug)}>
                                <ArrowLeft className="size-4" />
                                Kembali ke form booking
                            </Link>
                        </Button>

                        <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Biodata Penyewa
                        </h1>
                        <p className="mt-3 max-w-2xl text-muted-foreground">
                            Isi data penyewa tanpa membuat akun. Nomor WhatsApp
                            dan NIK dipakai untuk mengenali penyewa lama dan
                            mengisi form otomatis.
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
                        className="lg:col-span-2"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Data Penyewa
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        post(routes.biodata.store.url());
                                    }}
                                    className="space-y-6"
                                >
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">
                                                Nama lengkap *
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                value={data.name}
                                                onChange={(event) =>
                                                    setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                                autoComplete="name"
                                                aria-invalid={Boolean(
                                                    errors.name,
                                                )}
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="whatsapp">
                                                Nomor WhatsApp *
                                            </Label>
                                            <Input
                                                id="whatsapp"
                                                name="whatsapp"
                                                value={data.whatsapp}
                                                onChange={(event) =>
                                                    setData(
                                                        'whatsapp',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="081234567890"
                                                inputMode="tel"
                                                required
                                                aria-invalid={Boolean(
                                                    errors.whatsapp,
                                                )}
                                                onBlur={(event) =>
                                                    lookupExistingCustomer({
                                                        whatsapp:
                                                            event.target.value,
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={errors.whatsapp}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="email">
                                                Email (opsional)
                                            </Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                value={data.email}
                                                onChange={(event) =>
                                                    setData(
                                                        'email',
                                                        event.target.value,
                                                    )
                                                }
                                                autoComplete="email"
                                                aria-invalid={Boolean(
                                                    errors.email,
                                                )}
                                            />
                                            <InputError
                                                message={errors.email}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="nik">NIK *</Label>
                                            <Input
                                                id="nik"
                                                name="nik"
                                                value={data.nik}
                                                onChange={(event) =>
                                                    setData(
                                                        'nik',
                                                        event.target.value,
                                                    )
                                                }
                                                inputMode="numeric"
                                                maxLength={16}
                                                required
                                                aria-invalid={Boolean(
                                                    errors.nik,
                                                )}
                                                onBlur={(event) =>
                                                    lookupExistingCustomer({
                                                        nik: event.target.value,
                                                    })
                                                }
                                            />
                                            <InputError message={errors.nik} />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="address">
                                            Alamat *
                                        </Label>
                                        <Textarea
                                            id="address"
                                            name="address"
                                            rows={3}
                                            value={data.address}
                                            onChange={(event) =>
                                                setData(
                                                    'address',
                                                    event.target.value,
                                                )
                                            }
                                            required
                                            aria-invalid={Boolean(
                                                errors.address,
                                            )}
                                        />
                                        <InputError message={errors.address} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="city">
                                                Kota/Kabupaten
                                            </Label>
                                            <Input
                                                id="city"
                                                name="city"
                                                value={data.city}
                                                onChange={(event) =>
                                                    setData(
                                                        'city',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={Boolean(
                                                    errors.city,
                                                )}
                                            />
                                            <InputError message={errors.city} />
                                        </div>

                                        {isBikeRental ? (
                                            <div className="space-y-2">
                                                <Label htmlFor="renter_count">
                                                    Jumlah penyewa
                                                </Label>
                                                <Input
                                                    id="renter_count"
                                                    name="renter_count"
                                                    type="number"
                                                    min={1}
                                                    max={100}
                                                    value={data.renter_count}
                                                    onChange={(event) =>
                                                        setData(
                                                            'renter_count',
                                                            event.target.value,
                                                        )
                                                    }
                                                    aria-invalid={Boolean(
                                                        errors.renter_count,
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        errors.renter_count
                                                    }
                                                />
                                            </div>
                                        ) : null}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="notes">Catatan</Label>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            rows={3}
                                            value={data.notes}
                                            onChange={(event) =>
                                                setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Permintaan khusus, lokasi pickup, atau keterangan lain."
                                            aria-invalid={Boolean(errors.notes)}
                                        />
                                        <InputError message={errors.notes} />
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        {lookupStatus()}
                                    </div>

                                    {lookupError !== null ? (
                                        <p className="text-sm text-destructive">
                                            {lookupError}
                                        </p>
                                    ) : null}

                                    <Separator />

                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button
                                            type="submit"
                                            size="lg"
                                            disabled={processing}
                                        >
                                            Simpan Data Penyewa
                                        </Button>
                                        <p className="text-xs text-muted-foreground">
                                            Belum ada data yang disimpan ke
                                            database. Penyimpanan booking
                                            dibangun pada ROADMAP 3.11.
                                        </p>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45, delay: 0.1 }}
                    >
                        <Card className="lg:sticky lg:top-24">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Ringkasan booking
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {summaryRows.map((row) => (
                                    <div
                                        key={row.label}
                                        className="flex items-start justify-between gap-4"
                                    >
                                        <span className="text-muted-foreground">
                                            {row.label}
                                        </span>
                                        <span className="text-right tabular-nums">
                                            {row.value}
                                        </span>
                                    </div>
                                ))}

                                <Separator />

                                <div className="flex items-center justify-between gap-4">
                                    <span className="font-medium">
                                        Subtotal
                                    </span>
                                    <span className="text-xl font-semibold tabular-nums">
                                        {formatRupiah(subtotal)}
                                    </span>
                                </div>
                                <p className="text-xs text-muted-foreground tabular-nums">
                                    {formatRupiah(product.price)} x {quantity} x{' '}
                                    {duration} hari
                                </p>
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>
            </section>
        </PublicLayout>
    );
}
