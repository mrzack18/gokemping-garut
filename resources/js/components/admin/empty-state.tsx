import { cn } from '@/lib/utils';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type AdminEmptyStateProps = {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
    className?: string;
};

/**
 * Empty state daftar admin. Dipakai semua halaman list supaya pesan kosong
 * selalu tampil dengan bentuk yang sama.
 */
export default function AdminEmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: AdminEmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 rounded-lg border border-dashed px-5 py-10 text-center',
                className,
            )}
        >
            <span className="flex size-11 items-center justify-center rounded-md border border-border bg-muted text-muted-foreground">
                <Icon aria-hidden="true" className="size-5" />
            </span>
            <p className="font-medium">{title}</p>
            <p className="max-w-sm text-sm leading-relaxed text-muted-foreground">
                {description}
            </p>
            {action ? <div className="pt-1">{action}</div> : null}
        </div>
    );
}
