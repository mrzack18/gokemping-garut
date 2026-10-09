import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

type PageHeaderProps = {
    title: string;
    description?: string;
    eyebrow?: string;
    backHref?: string;
    backLabel?: string;
    breadcrumb?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

export default function PageHeader({
    title,
    description,
    eyebrow,
    backHref,
    backLabel = 'Kembali',
    breadcrumb,
    actions,
    className,
}: PageHeaderProps) {
    return (
        <header className={cn('border-b border-border', className)}>
            <div className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-11">
                {backHref ? (
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="mb-3 -ml-3 text-muted-foreground"
                    >
                        <Link href={backHref}>
                            <ArrowLeft aria-hidden="true" className="size-4" />
                            {backLabel}
                        </Link>
                    </Button>
                ) : null}
                {breadcrumb ? (
                    <div className="mb-3 text-xs text-muted-foreground">
                        {breadcrumb}
                    </div>
                ) : null}
                <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="max-w-3xl">
                        {eyebrow ? (
                            <p className="text-xs font-semibold tracking-[0.16em] text-pine-600 uppercase">
                                {eyebrow}
                            </p>
                        ) : null}
                        <h1 className="mt-1 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                            {title}
                        </h1>
                        {description ? (
                            <p className="mt-3 max-w-2xl leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        ) : null}
                    </div>
                    {actions ? (
                        <div className="flex shrink-0 flex-wrap gap-2">
                            {actions}
                        </div>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
