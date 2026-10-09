import { Award, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AdminReportPaymentMethod, AdminReportTopProduct } from '@/types';

/**
 * Dua kartu peringkat yang dipakai bersama oleh halaman Laporan dan
 * Statistik. Isinya identik di kedua halaman, jadi bentuknya disatukan agar
 * kolom dan penomorannya tidak pernah berbeda.
 */
export function TopProductsCard({
    products,
}: {
    products: AdminReportTopProduct[];
}) {
    return (
        <Card className="rounded-lg shadow-none">
            <CardHeader>
                <CardTitle className="font-display text-base">
                    Produk Paling Banyak Disewa
                </CardTitle>
                <CardDescription>
                    Lima teratas berdasarkan jumlah unit. Booking yang
                    dibatalkan tidak ikut dihitung.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {products.length === 0 ? (
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
                            {products.map((product, index) => (
                                <TableRow key={product.product_name}>
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
    );
}

type PaymentMethodsCardProps = {
    methods: AdminReportPaymentMethod[];
    title?: string;
    description?: string;
    /** Tampilkan penanda metode terbanyak (dipakai halaman Statistik). */
    highlightTop?: boolean;
};

export function PaymentMethodsCard({
    methods,
    title = 'Rekap Metode Pembayaran',
    description = 'Nominal transaksi yang dibuat pada periode ini, dan berapa yang sudah lunas.',
    highlightTop = false,
}: PaymentMethodsCardProps) {
    const busiest =
        highlightTop && (methods[0]?.transactions ?? 0) > 0
            ? methods[0].method_label
            : null;

    return (
        <Card className="rounded-lg shadow-none">
            <CardHeader>
                <CardTitle className="font-display text-base">
                    {title}
                </CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                {busiest !== null ? (
                    <p className="mb-4 flex items-center gap-2 text-sm">
                        <Award
                            aria-hidden="true"
                            className="size-4 text-pine-700 dark:text-pine-600"
                        />
                        Paling banyak dipakai:{' '}
                        <span className="font-medium">{busiest}</span>
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
                        {methods.map((method) => (
                            <TableRow key={method.method}>
                                <TableCell>
                                    <span className="flex items-center gap-2">
                                        <TrendingUp
                                            aria-hidden="true"
                                            className="size-3.5 text-muted-foreground"
                                        />
                                        {method.method_label}
                                        {methods[0]?.method === method.method &&
                                        busiest !== null ? (
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
    );
}
