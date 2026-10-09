import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type SectionProps = {
    id: string;
    eyebrow?: string;
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
    tone?: 'default' | 'sand' | 'dark' | 'bare';
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
    tone = 'default',
}: SectionProps) {
    const isDark = tone === 'dark';

    return (
        <section
            id={id}
            className={cn(
                'scroll-mt-20 py-14 sm:py-20',
                tone === 'default' && 'border-t',
                tone === 'sand' && 'bg-sand-50',
                tone === 'dark' && 'bg-pine-950 text-pine-50',
                className,
            )}
        >
            <div className="mx-auto w-full max-w-6xl px-4 sm:px-6">
                <div className="max-w-2xl">
                    {eyebrow ? (
                        <p
                            className={cn(
                                'text-xs font-semibold tracking-[0.16em] uppercase',
                                isDark ? 'text-ember-500' : 'text-pine-600',
                            )}
                        >
                            {eyebrow}
                        </p>
                    ) : null}
                    <h2 className="mt-2 font-display text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
                        {title}
                    </h2>
                    {description ? (
                        <p
                            className={cn(
                                'mt-3 leading-relaxed',
                                isDark
                                    ? 'text-pine-50/75'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {description}
                        </p>
                    ) : null}
                </div>

                <div className="mt-8">{children}</div>
            </div>
        </section>
    );
}
