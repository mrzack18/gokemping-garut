import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, RotateCcw, Search, Users } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
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

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Penyewa"
                    description="Orang yang pernah booking di unit ini, beserta jumlah booking dan total transaksinya. Riwayat booking lengkap ada di halaman detail masing-masing penyewa."
                />

                <AdminFilterCard
                    title="Cari penyewa"
                    description="Cari dengan nama, nomor WhatsApp, atau NIK. NIK dicari dalam bentuk lengkap maupun sebagian, tetapi yang tampil di tabel tetap tersamar."
                    onSubmit={handleSubmit}
                    pending={pending}
                    hasFilters={hasFilters}
                    onReset={resetFilters}
                    submitLabel="Cari"
                    resetLabel="Reset"
                >
                    <div className="space-y-2 sm:col-span-2 lg:col-span-8">
                        <Label htmlFor="q">Kata kunci</Label>
                        <div className="relative">
                            <Search
                                aria-hidden="true"
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
                </AdminFilterCard>

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Daftar Penyewa
                        </CardTitle>
                        <CardDescription>
                            {total === 0
                                ? 'Tidak ada penyewa yang cocok dengan pencarian.'
                                : `Menampilkan ${customers.from ?? 0}-${customers.to ?? 0} dari ${total} penyewa.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {customers.data.length === 0 ? (
                            <AdminEmptyState
                                icon={Users}
                                title={
                                    hasFilters
                                        ? 'Tidak ada penyewa yang cocok'
                                        : 'Belum ada penyewa'
                                }
                                description={
                                    hasFilters
                                        ? 'Coba periksa ejaan nama, nomor WhatsApp, atau NIK-nya.'
                                        : 'Penyewa muncul di sini setelah booking pertamanya masuk lewat halaman pemesanan publik.'
                                }
                                action={
                                    hasFilters ? (
                                        <Button
                                            variant="outline"
                                            onClick={resetFilters}
                                            disabled={pending}
                                        >
                                            <RotateCcw aria-hidden="true" />
                                            Reset pencarian
                                        </Button>
                                    ) : null
                                }
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

                <AdminPagination
                    currentPage={currentPage}
                    lastPage={lastPage}
                    pageUrl={(page) => pageUrl(filters, page)}
                    ariaLabel="Navigasi halaman penyewa"
                />
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
