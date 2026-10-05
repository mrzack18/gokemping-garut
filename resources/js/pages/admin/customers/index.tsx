import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowRight, RotateCcw, Search, Users } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import customerRoutes from '@/routes/admin/customers';
import type {
    AdminCustomerFilters,
    AdminCustomerRow,
    AdminCustomersPageProps,
} from '@/types';

/**
 * Daftar penyewa admin (PRD section 25, ROADMAP 4.5).
 *
 * Penyewa tidak punya akun dan tidak terikat satu unit bisnis, jadi daftar ini
 * berisi orang-orang yang punya booking di unit ini. Penyewa yang hanya pernah
 * menyewa di unit lain tidak ikut, walau barisnya ada di database yang sama.
 *
 * NIK tampil tersamar di seluruh halaman (PRD section 33). Nilai aslinya tetap
 * bisa dicari lewat kotak pencarian, karena hanya petugas yang tahu NIK-nya
 * yang bisa memakai kata kunci itu.
 */
export default function AdminCustomers({
    customers,
    filters,
}: AdminCustomersPageProps) {
    const [draft, setDraft] = useState<AdminCustomerFilters>(filters);
    const [pending, setPending] = useState(false);

    useEffect(() => {
        setDraft(filters);
        setPending(false);
    }, [filters]);

    const hasFilters = filters.q !== '';

    function apply(next: AdminCustomerFilters) {
        setPending(true);
        router.get(customerRoutes.index.url({ query: buildQuery(next) }), {
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
        const cleared: AdminCustomerFilters = { q: '' };

        setDraft(cleared);
        apply(cleared);
    }

    const { current_page: currentPage, last_page: lastPage, total } = customers;

    return (
        <>
            <Head title="Penyewa" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-1"
                >
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Penyewa
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Orang yang pernah booking di unit ini, beserta jumlah
                        booking dan total transaksinya. Riwayat booking lengkap
                        ada di halaman detail masing-masing penyewa.
                    </p>
                </motion.div>

                <Card>
                    <CardHeader>
                        <CardTitle>Cari penyewa</CardTitle>
                        <CardDescription>
                            Cari dengan nama, nomor WhatsApp, atau NIK. NIK
                            dicari dalam bentuk lengkap maupun sebagian, tetapi
                            yang tampil di tabel tetap tersamar.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={handleSubmit}
                            className="flex flex-col gap-4 sm:flex-row sm:items-end"
                        >
                            <div className="flex-1 space-y-2">
                                <Label htmlFor="q">Kata kunci</Label>
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
                                        placeholder="Nama, WhatsApp, atau NIK"
                                        className="pl-9"
                                        maxLength={100}
                                    />
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <Button type="submit" disabled={pending}>
                                    <Search className="size-4" />
                                    Cari
                                </Button>
                                {hasFilters ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetFilters}
                                        disabled={pending}
                                    >
                                        <RotateCcw className="size-4" />
                                        Reset
                                    </Button>
                                ) : null}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Penyewa</CardTitle>
                        <CardDescription>
                            {total === 0
                                ? 'Tidak ada penyewa yang cocok dengan pencarian.'
                                : `Menampilkan ${customers.from ?? 0}-${customers.to ?? 0} dari ${total} penyewa.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {customers.data.length === 0 ? (
                            <CustomerEmptyState
                                hasFilters={hasFilters}
                                onReset={resetFilters}
                                pending={pending}
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Penyewa</TableHead>
                                        <TableHead className="w-44">
                                            Kontak
                                        </TableHead>
                                        <TableHead className="w-40">
                                            NIK
                                        </TableHead>
                                        <TableHead>Alamat</TableHead>
                                        <TableHead className="w-28 text-right">
                                            Booking
                                        </TableHead>
                                        <TableHead className="w-36 text-right">
                                            Total Transaksi
                                        </TableHead>
                                        <TableHead className="w-28 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.data.map((customer) => (
                                        <CustomerRow
                                            key={customer.id}
                                            customer={customer}
                                        />
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {lastPage > 1 ? (
                    <nav
                        aria-label="Navigasi halaman penyewa"
                        className="flex flex-wrap items-center justify-center gap-1"
                    >
                        {currentPage > 1 ? (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={pageUrl(filters, currentPage - 1)}
                                    preserveScroll
                                >
                                    Sebelumnya
                                </Link>
                            </Button>
                        ) : null}

                        {visiblePages(currentPage, lastPage).map((page) =>
                            page === 'gap' ? (
                                <span
                                    key={`gap-${page}`}
                                    className="px-2 text-sm text-muted-foreground"
                                >
                                    ...
                                </span>
                            ) : (
                                <Button
                                    key={page}
                                    asChild
                                    size="sm"
                                    variant={
                                        page === currentPage
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    <Link
                                        href={pageUrl(filters, page)}
                                        preserveScroll
                                        aria-current={
                                            page === currentPage
                                                ? 'page'
                                                : undefined
                                        }
                                    >
                                        {page}
                                    </Link>
                                </Button>
                            ),
                        )}

                        {currentPage < lastPage ? (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={pageUrl(filters, currentPage + 1)}
                                    preserveScroll
                                >
                                    Berikutnya
                                </Link>
                            </Button>
                        ) : null}
                    </nav>
                ) : null}
            </div>
        </>
    );
}

AdminCustomers.layout = {
    breadcrumbs: [
        {
            title: 'Penyewa',
            href: customerRoutes.index(),
        },
    ],
};

/**
 * Baris penyewa.
 */
function CustomerRow({ customer }: { customer: AdminCustomerRow }) {
    return (
        <TableRow>
            <TableCell>
                <span className="font-medium">{customer.name}</span>
            </TableCell>
            <TableCell className="tabular-nums">{customer.whatsapp}</TableCell>
            <TableCell className="font-mono text-xs">{customer.nik}</TableCell>
            <TableCell className="text-sm">
                {customer.address}
                {customer.city ? (
                    <span className="block text-xs text-muted-foreground">
                        {customer.city}
                    </span>
                ) : null}
            </TableCell>
            <TableCell className="text-right tabular-nums">
                {customer.bookings_count}
            </TableCell>
            <TableCell className="text-right font-medium tabular-nums">
                Rp {customer.total_transaction_label}
            </TableCell>
            <TableCell>
                <div className="flex justify-end">
                    <Button asChild variant="outline" size="sm">
                        <Link href={customerRoutes.show(customer.id)}>
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
 * Empty state daftar penyewa.
 *
 * Dua kondisi dibedakan karena tindakan yang berguna berbeda: tanpa pencarian,
 * memang belum ada booking yang masuk; dengan pencarian aktif, penyewanya
 * mungkin ada tapi tidak cocok.
 */
function CustomerEmptyState({
    hasFilters,
    onReset,
    pending,
}: {
    hasFilters: boolean;
    onReset: () => void;
    pending: boolean;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-10 text-center">
            <Users className="size-8 text-muted-foreground" />
            <div className="flex flex-col gap-1">
                <p className="font-medium">
                    {hasFilters
                        ? 'Tidak ada penyewa yang cocok'
                        : 'Belum ada penyewa'}
                </p>
                <p className="max-w-sm text-sm text-muted-foreground">
                    {hasFilters
                        ? 'Coba periksa ejaan nama, nomor WhatsApp, atau NIK-nya.'
                        : 'Penyewa muncul di sini setelah booking pertamanya masuk lewat halaman pemesanan publik.'}
                </p>
            </div>
            {hasFilters ? (
                <Button variant="outline" onClick={onReset} disabled={pending}>
                    <RotateCcw />
                    Reset pencarian
                </Button>
            ) : null}
        </div>
    );
}

/**
 * Query string pencarian.
 *
 * Nilai yang kosong tidak ikut ditulis supaya URL tetap pendek dan mudah
 * dibaca.
 */
function buildQuery(filters: AdminCustomerFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.q !== '') {
        query.q = filters.q;
    }

    return query;
}

function pageUrl(filters: AdminCustomerFilters, page: number): string {
    return customerRoutes.index.url({
        query: {
            ...buildQuery(filters),
            page: String(page),
        },
    });
}

/**
 * Nomor halaman yang ditampilkan, dengan celah `...` di antara halaman yang
 * dilewati. Pola yang sama dipakai daftar booking dan produk.
 */
function visiblePages(current: number, last: number): (number | 'gap')[] {
    const wanted = new Set<number>([
        1,
        last,
        current - 1,
        current,
        current + 1,
    ]);
    const pages: (number | 'gap')[] = [];
    let previous = 0;

    for (let page = 1; page <= last; page += 1) {
        if (!wanted.has(page)) {
            continue;
        }

        if (previous > 0 && page - previous > 1) {
            pages.push('gap');
        }

        pages.push(page);
        previous = page;
    }

    return pages;
}
