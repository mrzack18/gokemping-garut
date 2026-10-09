import { Head, router } from '@inertiajs/react';
import {
    CalendarRange,
    CircleCheck,
    Download,
    FileText,
    Users,
    Wallet,
    XCircle,
} from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import AdminFilterCard from '@/components/admin/filter-card';
import AdminPageHeader from '@/components/admin/page-header';
import AdminStatCard from '@/components/admin/stat-card';
import BarChart from '@/components/admin/bar-chart';
import {
    PaymentMethodsCard,
    TopProductsCard,
} from '@/components/admin/ranked-tables';
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

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Laporan"
                    description="Ringkasan operasional dan pendapatan pada periode yang dipilih. Booking dihitung dari kapan transaksinya masuk, sedangkan pendapatan dihitung dari kapan pembayarannya diverifikasi."
                    actions={
                        <>
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
                                    <Download aria-hidden="true" />
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
                                    <FileText aria-hidden="true" />
                                    PDF
                                </a>
                            </Button>
                        </>
                    }
                />

                <AdminFilterCard
                    title="Periode"
                    description="Default-nya bulan berjalan sampai hari ini. Kalau tanggalnya tertukar, keduanya ditukar otomatis."
                    onSubmit={handleSubmit}
                    pending={pending}
                    hasFilters
                    onReset={resetFilters}
                    resetLabel="Bulan ini"
                    showReset
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
                </AdminFilterCard>

                <div>
                    <p className="mb-3 flex items-center gap-2 text-sm text-muted-foreground">
                        <CalendarRange aria-hidden="true" className="size-4" />
                        {period.label} · {period.days} hari
                    </p>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        <AdminStatCard
                            icon={CalendarRange}
                            label="Jumlah Booking"
                            value={stats.bookings}
                        />
                        <AdminStatCard
                            icon={CircleCheck}
                            label="Booking Selesai"
                            value={stats.finished}
                            tone="success"
                        />
                        <AdminStatCard
                            icon={XCircle}
                            label="Booking Dibatalkan"
                            value={stats.cancelled}
                            tone="destructive"
                        />
                        <AdminStatCard
                            icon={Users}
                            label="Jumlah Penyewa"
                            value={stats.customers}
                        />
                        <AdminStatCard
                            icon={Wallet}
                            label="Total Pendapatan"
                            value={`Rp ${stats.revenue_label}`}
                        />
                    </div>
                </div>

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Grafik Pendapatan
                        </CardTitle>
                        <CardDescription>
                            Pendapatan dari pembayaran lunas yang diverifikasi
                            pada periode ini.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            points={report.revenue_chart.points.map(
                                (point) => ({
                                    key: point.key,
                                    label: point.label,
                                    full_label: point.full_label,
                                    value: point.amount,
                                    value_label: point.amount_label,
                                }),
                            )}
                            tooltipPrefix="Rp "
                            summary={`Total pendapatan Rp ${report.revenue_chart.total_label} pada periode ini, digambar per ${report.revenue_chart.granularity}.`}
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-2">
                    <TopProductsCard products={report.top_products} />
                    <PaymentMethodsCard methods={report.payment_methods} />
                </div>
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
