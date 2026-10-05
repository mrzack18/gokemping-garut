import { Head, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { Award, CalendarRange, Wallet } from 'lucide-react';
import BarChart from '@/components/admin/bar-chart';
import { Badge } from '@/components/ui/badge';
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

    const busiest = payment_methods[0] ?? null;
    const busiestLabel =
        busiest !== null && busiest.transactions > 0
            ? busiest.method_label
            : null;

    return (
        <>
            <Head title="Statistik" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Statistik
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Tren dan peringkat satu tahun penuh. Booking
                            dihitung dari kapan transaksinya masuk, pendapatan
                            dari kapan pembayarannya diverifikasi.
                        </p>
                    </div>

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
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.06 }}
                    className="grid gap-6 lg:grid-cols-2"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Booking per Bulan</CardTitle>
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

                    <Card>
                        <CardHeader>
                            <CardTitle>Pendapatan per Bulan</CardTitle>
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
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.12 }}
                    className="grid gap-6 lg:grid-cols-2"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Produk Terlaris</CardTitle>
                            <CardDescription>
                                Lima teratas berdasarkan jumlah unit pada tahun
                                ini. Booking yang dibatalkan tidak ikut
                                dihitung.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {top_products.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    Belum ada produk tersewa pada tahun ini.
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
                                        {top_products.map((product, index) => (
                                            <TableRow
                                                key={product.product_name}
                                            >
                                                <TableCell>
                                                    <span className="flex items-center gap-2">
                                                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium tabular-nums">
                                                            {index + 1}
                                                        </span>
                                                        {product.product_name}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {product.quantity}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    Rp {product.revenue_label}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Metode Pembayaran Paling Banyak Dipakai
                            </CardTitle>
                            <CardDescription>
                                Diurutkan dari yang paling sering dipakai pada
                                tahun ini. Nominal transaksi dan yang sudah
                                lunas ditampilkan terpisah.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {busiestLabel !== null ? (
                                <p className="mb-4 flex items-center gap-2 text-sm">
                                    <Award
                                        className="size-4 text-muted-foreground"
                                        aria-hidden
                                    />
                                    Paling banyak dipakai:{' '}
                                    <span className="font-medium">
                                        {busiestLabel}
                                    </span>
                                </p>
                            ) : null}

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
                                    {payment_methods.map((method, index) => (
                                        <TableRow key={method.method}>
                                            <TableCell>
                                                <span className="flex items-center gap-2">
                                                    <Wallet
                                                        className="size-3.5 text-muted-foreground"
                                                        aria-hidden
                                                    />
                                                    {method.method_label}
                                                    {index === 0 &&
                                                    busiestLabel !== null ? (
                                                        <Badge variant="secondary">
                                                            Terbanyak
                                                        </Badge>
                                                    ) : null}
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

                <p className="flex items-center gap-2 text-xs text-muted-foreground">
                    <CalendarRange className="size-3.5" aria-hidden />
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
