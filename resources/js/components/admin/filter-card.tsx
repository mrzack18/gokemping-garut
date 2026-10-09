import { RotateCcw, Search } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type AdminFilterCardProps = {
    title?: string;
    description?: string;
    children: ReactNode;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    pending?: boolean;
    hasFilters: boolean;
    onReset: () => void;
    submitLabel?: string;
    resetLabel?: string;
    showReset?: boolean;
    formClassName?: string;
    className?: string;
};

/**
 * Kartu filter standar halaman admin.
 *
 * Grid form mengikuti pola dua belas kolom agar field pencarian, kategori,
 * dan tanggal bisa disusun konsisten di semua halaman daftar.
 */
export default function AdminFilterCard({
    title = 'Cari dan filter',
    description,
    children,
    onSubmit,
    pending = false,
    hasFilters,
    onReset,
    submitLabel = 'Terapkan',
    resetLabel = 'Reset filter',
    showReset,
    formClassName,
    className,
}: AdminFilterCardProps) {
    const displayReset = showReset ?? hasFilters;

    return (
        <Card className={cn('rounded-lg shadow-none', className)}>
            {title ? (
                <CardHeader>
                    <CardTitle className="font-display text-base">
                        {title}
                    </CardTitle>
                    {description ? (
                        <CardDescription>{description}</CardDescription>
                    ) : null}
                </CardHeader>
            ) : null}
            <CardContent className={title ? undefined : 'pt-6'}>
                <form
                    onSubmit={onSubmit}
                    className={cn(
                        'grid gap-4 sm:grid-cols-2 lg:grid-cols-12',
                        formClassName,
                    )}
                >
                    {children}

                    <div className="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-12">
                        <Button type="submit" disabled={pending}>
                            <Search aria-hidden="true" className="size-4" />
                            {submitLabel}
                        </Button>
                        {displayReset ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onReset}
                                disabled={pending}
                            >
                                <RotateCcw
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {resetLabel}
                            </Button>
                        ) : null}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
