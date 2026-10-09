import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarRange,
    Download,
    Plus,
    RotateCcw,
    Search,
    Ticket,
} from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import {
    BookingStatusBadge,
    PaymentStatusBadge,
} from '@/components/admin/booking-status-badge';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminFilterCard from '@/components/admin/filter-card';
import AdminPageHeader from '@/components/admin/page-header';
import AdminPagination from '@/components/admin/pagination';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import bookingRoutes from '@/routes/admin/bookings';
import exportRoutes from '@/routes/admin/exports';
import type {
    AdminBookingFilters,
    AdminBookingRow,
    AdminBookingsPageProps,
} from '@/types';

const ALL_STATUSES = 'semua';

/**
 * Daftar booking admin (PRD section 24, ROADMAP 4.4).
 *
 * Daftar ini dibaca dari sudut admin: yang dicari bukan produknya, tapi siapa
 * yang menyewa, barang apa, dan tanggal berapa. Karena itu pencarian bebas
 * mencakup kode booking, nama dan WhatsApp penyewa, serta nama produk yang
 * tercatat di booking itu sendiri.
 *
 * Booking yang dibatalkan juga tetap tampil. Booking lama yang produknya sudah
 * dihapus tetap terbaca karena nama dan harga produk disalin ke `booking_items`
 * saat transaksi dibuat (BR-09).
 */
export default function AdminBookings({
    bookings,
    filters,
    statusOptions,
}: AdminBookingsPageProps) {
    const [draft, setDraft] = useState<AdminBookingFilters>(filters);
    const [pending, setPending] = useState(false);

    useEffect(() => {
        setDraft(filters);
        setPending(false);
    }, [filters]);

    const hasFilters =
        filters.q !== '' ||
        filters.status !== null ||
        filters.from !== null ||
        filters.to !== null;

    function apply(next: AdminBookingFilters) {
        setPending(true);
        router.get(bookingRoutes.index.url({ query: buildQuery(next) }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        apply({
            ...draft,
            q: draft.q.trim(),
        });
    }

    function resetFilters() {
        const cleared: AdminBookingFilters = {
            q: '',
            status: null,
            from: null,
            to: null,
        };

        setDraft(cleared);
        apply(cleared);
    }

    const { current_page: currentPage, last_page: lastPage, total } = bookings;

    return (
        <>
            <Head title="Booking" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Booking"
                    description="Semua booking yang masuk di unit ini, dari yang masih menunggu konfirmasi sampai yang sudah selesai atau dibatalkan."
                    actions={
                        <>
                            <Button asChild>
                                <Link href={bookingRoutes.manual.create()}>
                                    <Plus aria-hidden="true" />
                                    Booking Manual
                                </Link>
                            </Button>
                            {/* Unduhan tetap anchor biasa karena responsnya berkas biner. */}
                            <Button asChild variant="outline">
                                <a
                                    href={exportRoutes.bookings.url({
                                        query: buildQuery(filters),
                                    })}
                                >
                                    <Download aria-hidden="true" />
                                    Unduh Excel
                                </a>
                            </Button>
                        </>
                    }
                />

                <AdminFilterCard
                    description="Filter tanggal memakai tanggal mulai sewa, bukan tanggal booking dibuat, karena booking untuk bulan depan bisa dibuat lebih awal."
                    onSubmit={handleSubmit}
                    pending={pending}
                    hasFilters={hasFilters}
                    onReset={resetFilters}
                >
                    <div className="space-y-2 sm:col-span-2 lg:col-span-4">
                        <Label htmlFor="q">Cari booking</Label>
                        <div className="relative">
                            <Search
                                aria-hidden
                                className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="q"
                                name="q"
                                value={draft.q}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        q: event.target.value,
                                    })
                                }
                                placeholder="Kode booking, penyewa, atau produk"
                                className="pl-9"
                                maxLength={100}
                            />
                        </div>
                    </div>

                    <div className="space-y-2 lg:col-span-3">
                        <Label htmlFor="status">Status</Label>
                        <Select
                            value={
                                draft.status === null
                                    ? ALL_STATUSES
                                    : draft.status
                            }
                            onValueChange={(value) =>
                                setDraft({
                                    ...draft,
                                    status:
                                        value === ALL_STATUSES
                                            ? null
                                            : (value as AdminBookingFilters['status']),
                                })
                            }
                        >
                            <SelectTrigger id="status" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL_STATUSES}>
                                    Semua status
                                </SelectItem>
                                {statusOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2 lg:col-span-2">
                        <Label htmlFor="from">Mulai dari</Label>
                        <Input
                            id="from"
                            name="from"
                            type="date"
                            value={draft.from ?? ''}
                            onChange={(event) =>
                                setDraft({
                                    ...draft,
                                    from: event.target.value || null,
                                })
                            }
                        />
                    </div>

                    <div className="space-y-2 lg:col-span-3">
                        <Label htmlFor="to">Sampai</Label>
                        <Input
                            id="to"
                            name="to"
                            type="date"
                            value={draft.to ?? ''}
                            onChange={(event) =>
                                setDraft({
                                    ...draft,
                                    to: event.target.value || null,
                                })
                            }
                        />
                    </div>
                </AdminFilterCard>

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Daftar Booking
                        </CardTitle>
                        <CardDescription>
                            {total === 0
                                ? 'Tidak ada booking yang cocok dengan filter.'
                                : `Menampilkan ${bookings.from ?? 0}-${bookings.to ?? 0} dari ${total} booking.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {bookings.data.length === 0 ? (
                            <AdminEmptyState
                                icon={Ticket}
                                title={
                                    hasFilters
                                        ? 'Tidak ada booking yang cocok'
                                        : 'Belum ada booking masuk'
                                }
                                description={
                                    hasFilters
                                        ? 'Coba longgarkan pencarian atau reset filter untuk melihat semua booking.'
                                        : 'Booking dari halaman pemesanan publik akan muncul di sini setelah penyewa mengirimnya.'
                                }
                                action={
                                    hasFilters ? (
                                        <Button
                                            variant="outline"
                                            onClick={resetFilters}
                                            disabled={pending}
                                        >
                                            <RotateCcw aria-hidden="true" />
                                            Reset filter
                                        </Button>
                                    ) : null
                                }
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Penyewa</TableHead>
                                        <TableHead>Produk</TableHead>
                                        <TableHead className="w-52">
                                            Periode
                                        </TableHead>
                                        <TableHead className="w-40 text-right">
                                            Total
                                        </TableHead>
                                        <TableHead className="w-40">
                                            Pembayaran
                                        </TableHead>
                                        <TableHead className="w-44">
                                            Status
                                        </TableHead>
                                        <TableHead className="w-32 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bookings.data.map((booking) => (
                                        <BookingRow
                                            key={booking.booking_code}
                                            booking={booking}
                                        />
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <AdminPagination
                    currentPage={currentPage}
                    lastPage={lastPage}
                    pageUrl={(page) => pageUrl(filters, page)}
                    ariaLabel="Navigasi halaman booking"
                />
            </div>
        </>
    );
}

AdminBookings.layout = {
    breadcrumbs: [
        {
            title: 'Booking',
            href: bookingRoutes.index(),
        },
    ],
};

/**
 * Baris booking.
 *
 * Baris ini hanya menampilkan kode, penyewa, dan periodenya. Aksi mengubah
 * status tidak diletakkan di sini: satu booking punya satu tahap berikutnya,
 * dan tindakan itu perlu melihat detail pembayaran serta produknya dulu sebelum
 * ditekan.
 */
function BookingRow({ booking }: { booking: AdminBookingRow }) {
    return (
        <TableRow>
            <TableCell className="font-mono text-xs">
                {booking.booking_code}
                <span className="mt-0.5 block font-sans text-xs font-normal text-muted-foreground">
                    Masuk {booking.created_at_label}
                </span>
            </TableCell>
            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span className="font-medium">{booking.customer_name}</span>
                    {booking.customer_whatsapp ? (
                        <span className="text-xs text-muted-foreground tabular-nums">
                            {booking.customer_whatsapp}
                        </span>
                    ) : null}
                </div>
            </TableCell>
            <TableCell className="text-sm">
                {booking.product_label}
                <span className="block text-xs text-muted-foreground">
                    {booking.quantity} unit
                </span>
            </TableCell>
            <TableCell className="text-sm">
                <span className="flex items-center gap-1.5">
                    <CalendarRange
                        aria-hidden
                        className="size-4 shrink-0 text-muted-foreground"
                    />
                    {booking.period_label}
                </span>
            </TableCell>
            <TableCell className="text-right font-medium tabular-nums">
                {booking.total_label}
            </TableCell>
            <TableCell>
                <div className="flex flex-col items-start gap-1">
                    <PaymentStatusBadge
                        status={booking.payment_status}
                        label={booking.payment_status_label}
                    />
                    <span className="text-xs text-muted-foreground">
                        {booking.payment_method_label}
                    </span>
                </div>
            </TableCell>
            <TableCell>
                <BookingStatusBadge
                    status={booking.status}
                    label={booking.status_label}
                />
            </TableCell>
            <TableCell>
                <div className="flex justify-end">
                    <Button asChild variant="outline" size="sm">
                        <Link href={bookingRoutes.show(booking.booking_code)}>
                            Detail
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </TableCell>
        </TableRow>
    );
}

/**
 * Query string filter.
 *
 * Filter default tidak ikut ditulis supaya URL tetap pendek dan mudah dibaca.
 * Semua nilai sudah dinormalisasi server, jadi form dan URL tidak pernah
 * berbeda, termasuk saat admin membuka tautan yang disalin dengan filter tidak
 * lengkap.
 */
function buildQuery(filters: AdminBookingFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.q !== '') {
        query.q = filters.q;
    }

    if (filters.status !== null) {
        query.status = filters.status;
    }

    if (filters.from !== null) {
        query.from = filters.from;
    }

    if (filters.to !== null) {
        query.to = filters.to;
    }

    return query;
}

/**
 * Query string untuk pindah halaman.
 *
 * Filter ikut dibawa supaya admin tidak kehilangan filter yang sedang aktif
 * hanya karena menekan tombol halaman berikutnya.
 */
function pageUrl(filters: AdminBookingFilters, page: number): string {
    return bookingRoutes.index.url({
        query: {
            ...buildQuery(filters),
            page: String(page),
        },
    });
}
