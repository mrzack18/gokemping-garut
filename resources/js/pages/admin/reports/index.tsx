import { Head, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    CalendarRange,
    CircleCheck,
    Download,
    FileText,
    RotateCcw,
    Search,
    TrendingUp,
    Users,
    Wallet,
    XCircle,
} from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import RevenueChart from '@/components/admin/revenue-chart';
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
import reportRoutes from '@/routes/admin/reports';
import exportRoutes from '@/routes/admin/exports';
import type { AdminReportFilters, AdminReportsPageProps } from '@/types';

/**
 * Laporan periode admin (PRD section 29, ROADMAP 5.1).
 *
 * Halaman ini adalah tempat admin menjawab pertanyaan "bagaimana bisnis
 * berjalan bulan ini". Angkanya dihitung server untuk unit bisnis admin, dan
 * filter tanggalnya tersimpan di URL supaya periode yang sedang dibaca bisa
 * dibagikan apa adanya.
 */
export default function AdminReports({
    filters,
    report,
}: AdminReportsPageProps) {
    const [draft, setDraft] = useState<AdminReportFilters>(filters);
    const [pending, setPending] = useState(false);

    useEffect(() => {
        setDraft(filters);
        setPending(false);
    }, [filters]);

    function apply(next: AdminReportFilters) {
        setPending(true);
        router.get(reportRoutes.index.url({ query: next }), {
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
        setPending(true);
        router.get(reportRoutes.index.url(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const { stats, period } = report;

    return (
        <>
            <Head title="Laporan" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Laporan
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Ringkasan operasional dan pendapatan pada periode
                            yang dipilih. Booking dihitung dari kapan
                            transaksinya masuk, sedangkan pendapatan dihitung
                            dari kapan pembayarannya diverifikasi.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/*
                         * Unduhan memakai anchor biasa, bukan Link Inertia:
                         * responsnya berkas biner, bukan halaman Inertia.
                         */}
                        <Button asChild variant="outline">
                            <a
                                href={exportRoutes.report.url({
                                    query: {
                                        from: filters.from,
                                        to: filters.to,
                                    },
                                })}
                            >
                                <Download />
                                Excel
                            </a>
                        </Button>
                        <Button asChild variant="outline">
                            <a
                                href={exportRoutes.reportPdf.url({
                                    query: {
                                        from: filters.from,
                                        to: filters.to,
                                    },
                                })}
                            >
                                <FileText />
                                PDF
                            </a>
                        </Button>
                    </div>
                </motion.div>

                <Card>
                    <CardHeader>
                        <CardTitle>Periode</CardTitle>
                        <CardDescription>
                            Default-nya bulan berjalan sampai hari ini. Kalau
                            tanggalnya tertukar, keduanya ditukar otomatis.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={handleSubmit}
                            className="grid gap-4 sm:grid-cols-2 lg:grid-cols-12"
                        >
                            <div className="space-y-2 lg:col-span-4">
                                <Label htmlFor="from">Tanggal mulai</Label>
                                <Input
                                    id="from"
                                    name="from"
                                    type="date"
                                    value={draft.from}
                                    onChange={(event) =>
                                        setDraft({
                                            ...draft,
                                            from: event.target.value,
                                        })
                                    }
                                    required
                                />
                            </div>

                            <div className="space-y-2 lg:col-span-4">
                                <Label htmlFor="to">Tanggal akhir</Label>
                                <Input
                                    id="to"
                                    name="to"
                                    type="date"
                                    value={draft.to}
                                    onChange={(event) =>
                                        setDraft({
                                            ...draft,
                                            to: event.target.value,
                                        })
                                    }
                                    required
                                />
                            </div>

                            <div className="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-4">
                                <Button type="submit" disabled={pending}>
                                    <Search className="size-4" />
                                    Terapkan
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={resetFilters}
                                    disabled={pending}
                                >
                                    <RotateCcw className="size-4" />
                                    Bulan ini
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.06 }}
                >
                    <p className="mb-3 flex items-center gap-2 text-sm text-muted-foreground">
                        <CalendarRange className="size-4" aria-hidden />
                        {period.label} · {period.days} hari
                    </p>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        <StatCard
                            icon={CalendarRange}
                            label="Jumlah Booking"
                            value={String(stats.bookings)}
                        />
                        <StatCard
                            icon={CircleCheck}
                            label="Booking Selesai"
                            value={String(stats.finished)}
                        />
                        <StatCard
                            icon={XCircle}
                            label="Booking Dibatalkan"
                            value={String(stats.cancelled)}
                        />
                        <StatCard
                            icon={Users}
                            label="Jumlah Penyewa"
                            value={String(stats.customers)}
                        />
                        <StatCard
                            icon={Wallet}
                            label="Total Pendapatan"
                            value={`Rp ${stats.revenue_label}`}
                        />
                    </div>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.12 }}
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Grafik Pendapatan</CardTitle>
                            <CardDescription>
                                Pendapatan dari pembayaran lunas yang
                                diverifikasi pada periode ini.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <RevenueChart
                                points={report.revenue_chart.points}
                                granularity={report.revenue_chart.granularity}
                                totalLabel={report.revenue_chart.total_label}
                            />
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.18 }}
                    className="grid gap-6 xl:grid-cols-2"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Produk Paling Banyak Disewa</CardTitle>
                            <CardDescription>
                                Lima teratas berdasarkan jumlah unit. Booking
                                yang dibatalkan tidak ikut dihitung.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {report.top_products.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    Belum ada produk tersewa pada periode ini.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Produk</TableHead>
                                            <TableHead className="w-24 text-right">
                                                Unit
                                            </TableHead>
                                            <TableHead className="w-36 text-right">
                                                Nilai Sewa
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {report.top_products.map(
                                            (product, index) => (
                                                <TableRow
                                                    key={product.product_name}
                                                >
                                                    <TableCell>
                                                        <span className="flex items-center gap-2">
                                                            <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium tabular-nums">
                                                                {index + 1}
                                                            </span>
                                                            {
                                                                product.product_name
                                                            }
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {product.quantity}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        Rp{' '}
                                                        {product.revenue_label}
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Rekap Metode Pembayaran</CardTitle>
                            <CardDescription>
                                Nominal transaksi yang dibuat pada periode ini,
                                dan berapa yang sudah lunas.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Metode</TableHead>
                                        <TableHead className="w-24 text-right">
                                            Transaksi
                                        </TableHead>
                                        <TableHead className="w-36 text-right">
                                            Nominal
                                        </TableHead>
                                        <TableHead className="w-36 text-right">
                                            Lunas
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report.payment_methods.map((method) => (
                                        <TableRow key={method.method}>
                                            <TableCell>
                                                <span className="flex items-center gap-2">
                                                    <TrendingUp
                                                        className="size-3.5 text-muted-foreground"
                                                        aria-hidden
                                                    />
                                                    {method.method_label}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {method.transactions}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                Rp {method.amount_label}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                Rp {method.paid_label}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </motion.div>
            </div>
        </>
    );
}

AdminReports.layout = {
    breadcrumbs: [
        {
            title: 'Laporan',
            href: reportRoutes.index(),
        },
    ],
};

/**
 * Kartu satu angka laporan.
 */
function StatCard({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof Wallet;
    label: string;
    value: string;
}) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 pt-6">
                <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                    <Icon
                        className="size-5 text-muted-foreground"
                        aria-hidden
                    />
                </div>
                <div className="flex flex-col gap-0.5">
                    <span className="text-xs text-muted-foreground">
                        {label}
                    </span>
                    <span className="text-lg font-semibold tabular-nums">
                        {value}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}
