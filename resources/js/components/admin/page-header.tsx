import { Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type AdminPageHeaderProps = {
    title: string;
    /** Kelas tambahan untuk judul, mis. `font-mono` untuk kode booking. */
    titleClassName?: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
    titleAdornment?: ReactNode;
    meta?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

/**
 * Header halaman admin: judul, deskripsi, aksi, dan tombol kembali.
 *
 * Dipakai semua halaman back-office supaya ritme judul, jarak, dan posisi
 * tombol aksi konsisten. Animasi masuk mengikuti preferensi reduced-motion.
 */
export default function AdminPageHeader({
    title,
    titleClassName,
    description,
    backHref,
    backLabel = 'Kembali',
    titleAdornment,
    meta,
    actions,
    className,
}: AdminPageHeaderProps) {
    const reducedMotion = useReducedMotion();

    return (
        <motion.div
            initial={reducedMotion ? false : { opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{
                duration: reducedMotion ? 0 : 0.25,
                ease: [0.22, 1, 0.36, 1],
            }}
            className={cn(
                'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between',
                className,
            )}
        >
            <div className="flex min-w-0 flex-col gap-1.5">
                {backHref ? (
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="mb-1 -ml-3 w-fit text-muted-foreground"
                    >
                        <Link href={backHref}>
                            <ArrowLeft aria-hidden="true" className="size-4" />
                            {backLabel}
                        </Link>
                    </Button>
                ) : null}

                <div className="flex flex-wrap items-center gap-3">
                    <h1
                        className={cn(
                            'font-display text-2xl font-semibold tracking-tight text-balance',
                            titleClassName,
                        )}
                    >
                        {title}
                    </h1>
                    {titleAdornment}
                </div>

                {description ? (
                    <p className="max-w-2xl text-sm leading-relaxed text-muted-foreground">
                        {description}
                    </p>
                ) : null}

                {meta ? (
                    <div className="flex flex-wrap items-center gap-2 pt-0.5">
                        {meta}
                    </div>
                ) : null}
            </div>

            {actions ? (
                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {actions}
                </div>
            ) : null}
        </motion.div>
    );
}
