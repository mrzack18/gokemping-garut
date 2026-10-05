import { motion } from 'motion/react';
import type { AdminReportRevenuePoint } from '@/types';

/**
 * Grafik batang pendapatan laporan (ROADMAP 5.1).
 *
 * Sama seperti grafik dashboard, dibuat dengan elemen biasa karena
 * kebutuhannya hanya batang sederhana dan titiknya sudah lengkap dari server,
 * termasuk periode tanpa pendapatan. Tinggi batang memakai rasio terhadap
 * titik tertinggi, sedangkan nominalnya dibaca lewat tooltip supaya label
 * angka tidak saling bertabrakan saat titiknya banyak.
 */
export default function RevenueChart({
    points,
    granularity,
    totalLabel,
}: {
    points: AdminReportRevenuePoint[];
    granularity: 'harian' | 'bulanan';
    totalLabel: string;
}) {
    const max = Math.max(...points.map((point) => point.amount), 1);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex h-44 items-end gap-1">
                {points.map((point, index) => {
                    const height =
                        point.amount === 0
                            ? 2
                            : Math.max(
                                  10,
                                  Math.round((point.amount / max) * 100),
                              );

                    return (
                        <div
                            key={point.key}
                            className="flex h-full flex-1 flex-col items-center justify-end gap-2"
                        >
                            <motion.div
                                initial={{ height: 0 }}
                                animate={{ height: `${height}%` }}
                                transition={{
                                    duration: 0.4,
                                    delay: index * 0.02,
                                    ease: 'easeOut',
                                }}
                                className={
                                    point.amount === 0
                                        ? 'w-full rounded-t-sm bg-muted'
                                        : 'w-full rounded-t-sm bg-primary'
                                }
                                title={`${point.full_label}: Rp ${point.amount_label}`}
                            />
                        </div>
                    );
                })}
            </div>

            {points.length <= 16 ? (
                <div className="flex gap-1 border-t pt-3">
                    {points.map((point) => (
                        <p
                            key={point.key}
                            className="flex-1 text-center text-[10px] text-muted-foreground"
                        >
                            {point.label}
                        </p>
                    ))}
                </div>
            ) : (
                <div className="flex justify-between border-t pt-3 text-[10px] text-muted-foreground">
                    <span>{points[0]?.label}</span>
                    <span>{points[points.length - 1]?.label}</span>
                </div>
            )}

            <p className="text-xs text-muted-foreground">
                Total pendapatan Rp {totalLabel} pada periode ini, digambar per{' '}
                {granularity}.
            </p>
        </div>
    );
}
