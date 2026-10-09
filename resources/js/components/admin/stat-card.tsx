import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type StatTone = 'default' | 'success' | 'warning' | 'destructive' | 'ember';

const toneClasses: Record<StatTone, string> = {
    default:
        'border-pine-700/15 bg-pine-50 text-pine-700 dark:border-pine-100/20 dark:bg-pine-100/40 dark:text-pine-600',
    success: 'border-success/25 bg-success/10 text-success',
    warning: 'border-warning-border bg-warning-bg text-warning-text',
    destructive: 'border-destructive/20 bg-destructive/10 text-destructive',
    ember: 'border-ember-500/30 bg-ember-500/10 text-ember-600',
};

type AdminStatCardProps = {
    icon: LucideIcon;
    label: string;
    value: string | number;
    hint?: string;
    tone?: StatTone;
};

/**
 * Kartu angka ringkas untuk dashboard, laporan, dan ringkasan transaksi.
 * Semua halaman memakai komponen ini supaya ukuran angka dan ikonnya sama.
 */
export default function AdminStatCard({
    icon: Icon,
    label,
    value,
    hint,
    tone = 'default',
}: AdminStatCardProps) {
    return (
        <Card className="gap-3 rounded-lg py-5 shadow-none">
            <CardContent className="flex items-start justify-between gap-4 px-5">
                <div className="min-w-0">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        {label}
                    </p>
                    <p className="mt-1.5 font-display text-2xl font-semibold tracking-tight tabular-nums">
                        {value}
                    </p>
                    {hint ? (
                        <p className="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                            {hint}
                        </p>
                    ) : null}
                </div>
                <span
                    className={cn(
                        'flex size-9 shrink-0 items-center justify-center rounded-md border',
                        toneClasses[tone],
                    )}
                >
                    <Icon aria-hidden="true" className="size-5" />
                </span>
            </CardContent>
        </Card>
    );
}
