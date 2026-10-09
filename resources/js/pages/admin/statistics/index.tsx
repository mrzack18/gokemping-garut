import { Head, router } from '@inertiajs/react';
import { CalendarRange } from 'lucide-react';
import AdminPageHeader from '@/components/admin/page-header';
import BarChart from '@/components/admin/bar-chart';
import {
    PaymentMethodsCard,
    TopProductsCard,
} from '@/components/admin/ranked-tables';
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
import statisticRoutes from '@/routes/admin/statistics';
import type { AdminStatisticsPageProps } from '@/types';

/**
 * Statistik tahunan admin (ROADMAP 5.3).
 *
 * Berbeda dari laporan yang memakai periode bebas, halaman ini selalu satu
 * tahun penuh supaya semua bagiannya menjawab periode yang sama: tren bulanan,
 * produk terlaris, dan metode pembayaran paling banyak dipakai pada tahun itu.
 *
 * Angka produk dan metode memakai sumber yang sama dengan laporan periode
 * setahun, jadi tidak mungkin berbeda dari laporan yang memakai rentang yang
 * sama.
 */
export default function AdminStatistics({
    year,
    years,
    booking_chart,
    revenue_chart,
    top_products,
    payment_methods,
}: AdminStatisticsPageProps) {
    const totalBookings = booking_chart.points.reduce(
        (sum, point) => sum + point.value,
        0,
    );

    return (
        <>
            <Head title="Statistik" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Statistik"
                    description="Tren dan peringkat satu tahun penuh. Booking dihitung dari kapan transaksinya masuk, pendapatan dari kapan pembayarannya diverifikasi."
                    actions={
                        <div className="flex w-full flex-col gap-2 sm:w-44">
                            <Label htmlFor="year">Tahun</Label>
                            <Select
                                value={String(year)}
                                onValueChange={(value) =>
                                    router.get(
                                        statisticRoutes.index.url({
                                            query: { year: value },
                                        }),
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                            replace: true,
                                        },
                                    )
                                }
                            >
                                <SelectTrigger id="year" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {years.map((option) => (
                                        <SelectItem
                                            key={option}
                                            value={String(option)}
                                        >
                                            {option}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="rounded-lg shadow-none">
                        <CardHeader>
                            <CardTitle className="font-display text-base">
                                Booking per Bulan
                            </CardTitle>
                            <CardDescription>
                                Jumlah booking yang masuk tiap bulan pada tahun
                                yang dipilih.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                points={booking_chart.points}
                                summary={
                                    totalBookings === 0
                                        ? `Belum ada booking masuk pada tahun ${year}.`
                                        : `${totalBookings} booking masuk sepanjang tahun ${year}.`
                                }
                            />
                        </CardContent>
                    </Card>

                    <Card className="rounded-lg shadow-none">
                        <CardHeader>
                            <CardTitle className="font-display text-base">
                                Pendapatan per Bulan
                            </CardTitle>
                            <CardDescription>
                                Pendapatan dari pembayaran lunas yang
                                diverifikasi pada bulan tersebut.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                points={revenue_chart.points}
                                tooltipPrefix="Rp "
                                summary={`Total pendapatan Rp ${revenue_chart.total_label} pada tahun ${year}.`}
                            />
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <TopProductsCard products={top_products} />
                    <PaymentMethodsCard
                        methods={payment_methods}
                        title="Metode Pembayaran Paling Banyak Dipakai"
                        description="Diurutkan dari yang paling sering dipakai pada tahun ini. Nominal transaksi dan yang sudah lunas ditampilkan terpisah."
                        highlightTop
                    />
                </div>

                <p className="flex items-center gap-2 text-xs text-muted-foreground">
                    <CalendarRange aria-hidden="true" className="size-3.5" />
                    Data dihitung dari transaksi tahun {year} di unit ini.
                </p>
            </div>
        </>
    );
}

AdminStatistics.layout = {
    breadcrumbs: [
        {
            title: 'Statistik',
            href: statisticRoutes.index(),
        },
    ],
};
