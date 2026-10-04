import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type SectionProps = {
    id: string;
    eyebrow?: string;
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
};

/**
 * Kerangka section landing page: judul, deskripsi, dan kontennya.
 */
export default function Section({
    id,
    eyebrow,
    title,
    description,
    children,
    className,
}: SectionProps) {
    return (
        <section
            id={id}
            className={cn('scroll-mt-20 border-t py-14 sm:py-20', className)}
        >
            <div className="mx-auto w-full max-w-6xl px-4 sm:px-6">
                <div className="max-w-2xl">
                    {eyebrow ? (
                        <p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
                            {eyebrow}
                        </p>
                    ) : null}
                    <h2 className="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                        {title}
                    </h2>
                    {description ? (
                        <p className="mt-3 text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                </div>

                <div className="mt-8">{children}</div>
            </div>
        </section>
    );
}
