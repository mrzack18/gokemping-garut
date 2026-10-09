import { Head, Link, router } from '@inertiajs/react';
import {
    BadgeCheck,
    Ban,
    FileText,
    RotateCcw,
    Settings2,
    Wallet,
} from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminFilterCard from '@/components/admin/filter-card';
import AdminPageHeader from '@/components/admin/page-header';
import AdminPagination from '@/components/admin/pagination';
import PaymentProofDialog from '@/components/admin/payment-proof-dialog';
import PaymentRejectDialog from '@/components/admin/payment-reject-dialog';
import PaymentVerifyDialog from '@/components/admin/payment-verify-dialog';
import { PaymentStatusBadge } from '@/components/admin/booking-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import paymentRoutes from '@/routes/admin/payments';
import paymentSettingRoutes from '@/routes/admin/payment-settings';
import type {
    AdminPaymentFilters,
    AdminPaymentRow,
    AdminPaymentsPageProps,
} from '@/types';

const ALL = 'semua';

/**
 * Daftar pembayaran admin (PRD section 26, ROADMAP 4.6).
 *
 * Halaman ini adalah antrean verifikasi. Pembayaran terbaru tampil lebih dulu,
 * dan filter statusnya memudahkan admin memfokuskan diri ke antrean
 * `menunggu_verifikasi` tanpa terganggu riwayat yang sudah final.
 *
 * Aksi yang tersedia dihitung server: cash yang belum dibayar bisa langsung
 * ditandai lunas, sedangkan tombol tolak hanya muncul untuk pembayaran yang
 * sedang menunggu verifikasi. Frontend tidak menebak aturan transisinya
 * sendiri.
 */
export default function AdminPayments({
    payments,
    filters,
    statusOptions,
    methodOptions,
}: AdminPaymentsPageProps) {
    const [draft, setDraft] = useState<AdminPaymentFilters>(filters);
    const [pending, setPending] = useState(false);
    const [proofTarget, setProofTarget] = useState<AdminPaymentRow | null>(
        null,
    );
    const [verifyTarget, setVerifyTarget] = useState<AdminPaymentRow | null>(
        null,
    );
    const [rejectTarget, setRejectTarget] = useState<AdminPaymentRow | null>(
        null,
    );

    useEffect(() => {
        setDraft(filters);
        setPending(false);
    }, [filters]);

    const hasFilters = filters.status !== null || filters.method !== null;

    function apply(next: AdminPaymentFilters) {
        setPending(true);
        router.get(paymentRoutes.index.url({ query: buildQuery(next) }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        apply(draft);
    }

    function resetFilters() {
        const cleared: AdminPaymentFilters = { status: null, method: null };

        setDraft(cleared);
        apply(cleared);
    }

    const { current_page: currentPage, last_page: lastPage, total } = payments;

    return (
        <>
            <Head title="Pembayaran" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Pembayaran"
                    description="Antrean verifikasi pembayaran. Periksa bukti transfer sebelum menandai lunas, dan tulis alasan yang jelas saat menolak supaya penyewa tahu apa yang harus diperbaiki."
                    actions={
                        <Button asChild variant="outline">
                            <Link href={paymentSettingRoutes.index()}>
                                <Settings2 aria-hidden="true" />
                                Atur Metode
                            </Link>
                        </Button>
                    }
                />

                <AdminFilterCard
                    title="Filter"
                    description="Filter tersimpan di alamat halaman, jadi tautan yang disalin membuka tampilan yang sama."
                    onSubmit={handleSubmit}
                    pending={pending}
                    hasFilters={hasFilters}
                    onReset={resetFilters}
                >
                    <div className="space-y-2 lg:col-span-4">
                        <Label htmlFor="status">Status</Label>
                        <Select
                            value={draft.status ?? ALL}
                            onValueChange={(value) =>
                                setDraft({
                                    ...draft,
                                    status:
                                        value === ALL
                                            ? null
                                            : (value as AdminPaymentFilters['status']),
                                })
                            }
                        >
                            <SelectTrigger id="status" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
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

                    <div className="space-y-2 lg:col-span-4">
                        <Label htmlFor="method">Metode</Label>
                        <Select
                            value={draft.method ?? ALL}
                            onValueChange={(value) =>
                                setDraft({
                                    ...draft,
                                    method: value === ALL ? null : value,
                                })
                            }
                        >
                            <SelectTrigger id="method" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    Semua metode
                                </SelectItem>
                                {methodOptions.map((option) => (
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
                </AdminFilterCard>

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Daftar Pembayaran
                        </CardTitle>
                        <CardDescription>
                            {total === 0
                                ? 'Tidak ada pembayaran yang cocok dengan filter.'
                                : `Menampilkan ${payments.from ?? 0}-${payments.to ?? 0} dari ${total} pembayaran.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {payments.data.length === 0 ? (
                            <AdminEmptyState
                                icon={Wallet}
                                title={
                                    hasFilters
                                        ? 'Tidak ada pembayaran yang cocok'
                                        : 'Belum ada pembayaran'
                                }
                                description={
                                    hasFilters
                                        ? 'Coba longgarkan filter status atau metode untuk melihat pembayaran lain.'
                                        : 'Pembayaran muncul di sini setelah booking pertama dibuat lewat halaman pemesanan publik.'
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
                                        <TableHead>Booking</TableHead>
                                        <TableHead className="w-32">
                                            Metode
                                        </TableHead>
                                        <TableHead className="w-32 text-right">
                                            Nominal
                                        </TableHead>
                                        <TableHead className="w-32">
                                            Bukti
                                        </TableHead>
                                        <TableHead className="w-48">
                                            Status
                                        </TableHead>
                                        <TableHead className="w-56 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.data.map((payment) => (
                                        <PaymentRow
                                            key={payment.id}
                                            payment={payment}
                                            onShowProof={() =>
                                                setProofTarget(payment)
                                            }
                                            onVerify={() =>
                                                setVerifyTarget(payment)
                                            }
                                            onReject={() =>
                                                setRejectTarget(payment)
                                            }
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
                    ariaLabel="Navigasi halaman pembayaran"
                />
            </div>

            <PaymentProofDialog
                payment={proofTarget}
                open={proofTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setProofTarget(null);
                    }
                }}
            />

            <PaymentVerifyDialog
                payment={verifyTarget}
                open={verifyTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setVerifyTarget(null);
                    }
                }}
            />

            <PaymentRejectDialog
                payment={rejectTarget}
                open={rejectTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRejectTarget(null);
                    }
                }}
            />
        </>
    );
}

AdminPayments.layout = {
    breadcrumbs: [
        {
            title: 'Pembayaran',
            href: paymentRoutes.index(),
        },
    ],
};

/**
 * Baris pembayaran.
 *
 * Bukti selalu bisa dilihat, termasuk yang sudah final, karena admin kadang
 * perlu membuka ulang bukti lama saat menyelesaikan sengketa.
 */
function PaymentRow({
    payment,
    onShowProof,
    onVerify,
    onReject,
}: {
    payment: AdminPaymentRow;
    onShowProof: () => void;
    onVerify: () => void;
    onReject: () => void;
}) {
    return (
        <TableRow>
            <TableCell>
                <Link
                    href={bookingRoutes.show(payment.booking_code)}
                    className="font-mono text-xs underline underline-offset-4"
                >
                    {payment.booking_code}
                </Link>
                <span className="mt-0.5 block text-xs text-muted-foreground">
                    {payment.customer_name} · masuk {payment.created_at_label}
                </span>
            </TableCell>
            <TableCell className="text-sm">{payment.method_label}</TableCell>
            <TableCell className="text-right font-medium tabular-nums">
                Rp {payment.amount_label}
            </TableCell>
            <TableCell>
                <Button variant="outline" size="sm" onClick={onShowProof}>
                    <FileText />
                    {payment.proof_url === null ? 'Tanpa bukti' : 'Lihat'}
                </Button>
            </TableCell>
            <TableCell>
                <div className="flex flex-col items-start gap-1">
                    <PaymentStatusBadge
                        status={payment.status}
                        label={payment.status_label}
                    />
                    {payment.verified_by !== null ? (
                        <span className="text-xs text-muted-foreground">
                            {payment.status === 'ditolak'
                                ? 'Ditolak'
                                : 'Diverifikasi'}{' '}
                            {payment.verified_by} ·{' '}
                            {payment.verified_at_label ?? '-'}
                        </span>
                    ) : null}
                    {payment.rejection_reason !== null ? (
                        <span className="text-xs text-muted-foreground">
                            {payment.rejection_reason}
                        </span>
                    ) : null}
                </div>
            </TableCell>
            <TableCell>
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {payment.can_verify ? (
                        <Button size="sm" onClick={onVerify}>
                            <BadgeCheck />
                            Verifikasi
                        </Button>
                    ) : null}
                    {payment.can_reject ? (
                        <Button variant="outline" size="sm" onClick={onReject}>
                            <Ban />
                            Tolak
                        </Button>
                    ) : null}
                    {!payment.can_verify && !payment.can_reject ? (
                        <span className="text-xs text-muted-foreground">
                            Final
                        </span>
                    ) : null}
                </div>
            </TableCell>
        </TableRow>
    );
}

/**
 * Query string filter. Nilai default tidak ikut ditulis supaya URL tetap
 * pendek; semua nilai sudah dinormalisasi server.
 */
function buildQuery(filters: AdminPaymentFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.status !== null) {
        query.status = filters.status;
    }

    if (filters.method !== null) {
        query.method = filters.method;
    }

    return query;
}

function pageUrl(filters: AdminPaymentFilters, page: number): string {
    return paymentRoutes.index.url({
        query: {
            ...buildQuery(filters),
            page: String(page),
        },
    });
}
