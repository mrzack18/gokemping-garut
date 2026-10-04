import { motion } from 'motion/react';
import type { DashboardBookingChartPoint } from '@/types';

/**
 * Grafik batang booking tujuh hari terakhir (ROADMAP 4.1).
 *
 * Dibuat dengan elemen biasa, bukan library chart, karena kebutuhannya hanya
 * tujuh batang: menambah dependensi chart untuk ini tidak sebanding dengan
 * bobotnya. Kalau laporan Fase 3 butuh grafik yang lebih rumit, library chart
 * baru bisa ditambahkan di situ.
 *
 * Titik data sudah lengkap dari server, termasuk hari tanpa booking, supaya
 * batang kosong terlihat sebagai "tidak ada booking" dan bukan tanggal yang
 * hilang dari grafik.
 */
export default function BookingChart({
    points,
}: {
    points: DashboardBookingChartPoint[];
}) {
    const max = Math.max(...points.map((point) => point.count), 1);
    const total = points.reduce((sum, point) => sum + point.count, 0);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex h-44 items-end gap-2">
                {points.map((point, index) => {
                    const height =
                        point.count === 0
                            ? 2
                            : Math.max(
                                  10,
                                  Math.round((point.count / max) * 100),
                              );

                    return (
                        <div
                            key={point.date}
                            className="flex h-full flex-1 flex-col items-center justify-end gap-2"
                        >
                            <span className="text-xs font-medium text-muted-foreground tabular-nums">
                                {point.count > 0 ? point.count : ''}
                            </span>
                            <motion.div
                                initial={{ height: 0 }}
                                animate={{ height: `${height}%` }}
                                transition={{
                                    duration: 0.4,
                                    delay: index * 0.04,
                                    ease: 'easeOut',
                                }}
                                className={
                                    point.count === 0
                                        ? 'w-full rounded-t-sm bg-muted'
                                        : 'w-full rounded-t-sm bg-primary'
                                }
                                title={`${point.full_label}: ${point.count} booking`}
                            />
                        </div>
                    );
                })}
            </div>

            <div className="flex gap-2 border-t pt-3">
                {points.map((point) => (
                    <p
                        key={point.date}
                        className="flex-1 text-center text-[10px] text-muted-foreground"
                    >
                        {point.label}
                    </p>
                ))}
            </div>

            <p className="text-xs text-muted-foreground">
                {total === 0
                    ? 'Belum ada booking yang masuk pada tujuh hari terakhir.'
                    : `${total} booking masuk pada tujuh hari terakhir.`}
            </p>
        </div>
    );
}
