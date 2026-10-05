import { motion } from 'motion/react';
import type { AdminStatisticChartPoint } from '@/types';

/**
 * Grafik batang generik untuk laporan dan statistik (ROADMAP 5.1 & 5.3).
 *
 * Dibuat dengan elemen biasa, bukan library chart, karena kebutuhannya hanya
 * batang sederhana. Titik data selalu lengkap dari server, termasuk periode
 * tanpa nilai, supaya batang kosong terlihat sebagai "tidak ada data" dan
 * bukan periode yang hilang. Nominal dibaca lewat tooltip supaya label angka
 * tidak saling bertabrakan saat titiknya banyak.
 */
export default function BarChart({
    points,
    summary,
    tooltipPrefix = '',
}: {
    points: AdminStatisticChartPoint[];
    summary: string;
    tooltipPrefix?: string;
}) {
    const max = Math.max(...points.map((point) => point.value), 1);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex h-44 items-end gap-1">
                {points.map((point, index) => {
                    const height =
                        point.value === 0
                            ? 2
                            : Math.max(
                                  10,
                                  Math.round((point.value / max) * 100),
                              );

                    return (
                        <div
                            key={point.key}
                            className="flex h-full flex-1 flex-col items-center justify-end"
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
                                    point.value === 0
                                        ? 'w-full rounded-t-sm bg-muted'
                                        : 'w-full rounded-t-sm bg-primary'
                                }
                                title={`${point.full_label}: ${tooltipPrefix}${point.value_label}`}
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

            <p className="text-xs text-muted-foreground">{summary}</p>
        </div>
    );
}
