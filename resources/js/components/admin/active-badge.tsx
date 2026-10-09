import { cn } from '@/lib/utils';

type ActiveBadgeProps = {
    isActive: boolean;
    activeLabel?: string;
    inactiveLabel?: string;
    className?: string;
};

/**
 * Badge status aktif/nonaktif untuk produk, kategori, banner, FAQ, dan
 * metode pembayaran. Memakai bentuk dan warna yang sama di semua halaman.
 */
export default function ActiveBadge({
    isActive,
    activeLabel = 'Aktif',
    inactiveLabel = 'Nonaktif',
    className,
}: ActiveBadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                isActive
                    ? 'border-success/25 bg-success/10 text-success'
                    : 'border-border bg-muted text-muted-foreground',
                className,
            )}
        >
            <span
                aria-hidden="true"
                className={cn(
                    'size-1.5 rounded-full',
                    isActive ? 'bg-success' : 'bg-muted-foreground',
                )}
            />
            {isActive ? activeLabel : inactiveLabel}
        </span>
    );
}
