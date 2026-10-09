import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type StatusPillProps = {
    children: ReactNode;
    tone?: 'available' | 'unavailable' | 'pending' | 'neutral';
    className?: string;
};

const tones = {
    available: 'border-success/25 bg-success/10 text-success',
    unavailable: 'border-destructive/20 bg-destructive/10 text-destructive',
    pending: 'border-warning-border bg-warning-bg text-warning-text',
    neutral: 'border-border bg-muted text-muted-foreground',
};

export default function StatusPill({
    children,
    tone = 'neutral',
    className,
}: StatusPillProps) {
    return (
        <span
            className={cn(
                'inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs leading-none font-medium',
                tones[tone],
                className,
            )}
        >
            <span
                aria-hidden="true"
                className={cn(
                    'size-1.5 rounded-full',
                    tone === 'available' && 'bg-success',
                    tone === 'unavailable' && 'bg-destructive',
                    tone === 'pending' && 'bg-warning-text',
                    tone === 'neutral' && 'bg-muted-foreground',
                )}
            />
            {children}
        </span>
    );
}
