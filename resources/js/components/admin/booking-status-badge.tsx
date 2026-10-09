import { cn } from '@/lib/utils';

/**
 * Badge status booking dan pembayaran (PRD section 24, ROADMAP 4.4).
 *
 * Setiap status punya warna, titik penanda, dan bobot berbeda supaya admin
 * bisa memindai banyak baris tanpa membaca labelnya. `dikonfirmasi` dan
 * `sedang_disewa` sengaja dibedakan: yang pertama menunggu pengambilan,
 * yang kedua barang sedang di tangan penyewa.
 */

type StatusTone = {
    className: string;
    dot: string;
};

const neutralTone: StatusTone = {
    className: 'border-border bg-muted text-muted-foreground',
    dot: 'bg-muted-foreground',
};

const warningTone: StatusTone = {
    className: 'border-warning-border bg-warning-bg text-warning-text',
    dot: 'bg-warning-text',
};

const pineTone: StatusTone = {
    className:
        'border-pine-700/20 bg-pine-50 text-pine-700 dark:border-pine-100/20 dark:bg-pine-100/30 dark:text-pine-600',
    dot: 'bg-pine-600',
};

const successTone: StatusTone = {
    className: 'border-success/25 bg-success/10 text-success',
    dot: 'bg-success',
};

const destructiveTone: StatusTone = {
    className: 'border-destructive/20 bg-destructive/10 text-destructive',
    dot: 'bg-destructive',
};

const bookingTones: Record<string, StatusTone> = {
    menunggu_konfirmasi: warningTone,
    dikonfirmasi: pineTone,
    sedang_disewa: successTone,
    selesai: neutralTone,
    dibatalkan: destructiveTone,
};

const paymentTones: Record<string, StatusTone> = {
    belum_dibayar: neutralTone,
    menunggu_verifikasi: warningTone,
    lunas: successTone,
    ditolak: destructiveTone,
};

function StatusBadge({
    tone,
    label,
    className,
}: {
    tone: StatusTone;
    label: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                tone.className,
                className,
            )}
        >
            <span
                aria-hidden="true"
                className={cn('size-1.5 rounded-full', tone.dot)}
            />
            {label}
        </span>
    );
}

export function BookingStatusBadge({
    status,
    label,
    className,
}: {
    status: string;
    label: string;
    className?: string;
}) {
    return (
        <StatusBadge
            tone={bookingTones[status] ?? neutralTone}
            label={label}
            className={className}
        />
    );
}

export function PaymentStatusBadge({
    status,
    label,
    className,
}: {
    status: string;
    label: string;
    className?: string;
}) {
    return (
        <StatusBadge
            tone={paymentTones[status] ?? neutralTone}
            label={label}
            className={className}
        />
    );
}
