import { cn } from '@/lib/utils';

type BrandMarkProps = {
    className?: string;
    inverse?: boolean;
};

/**
 * Mark GoKemping (tenda + aksen ember). Dipakai logo publik, panel admin,
 * dan halaman masuk supaya identitas visualnya sama di semua area.
 */
export function BrandMark({ className, inverse = false }: BrandMarkProps) {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 40 40"
            fill="none"
            className={cn('size-9 shrink-0', className)}
        >
            <path
                d="M5 32.5 20 7l15 25.5H5Z"
                className={inverse ? 'stroke-pine-50' : 'stroke-pine-700'}
                strokeWidth="2.4"
                strokeLinejoin="round"
            />
            <path
                d="m14.4 32.5 5.6-10 5.6 10M20 22.5v10"
                className={inverse ? 'stroke-pine-50' : 'stroke-pine-700'}
                strokeWidth="2.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="m26.4 17.8 2.2 3.8"
                className="stroke-ember-500"
                strokeWidth="2.6"
                strokeLinecap="round"
            />
        </svg>
    );
}

type LogoProps = {
    className?: string;
    inverse?: boolean;
    showTagline?: boolean;
};

export default function Logo({
    className,
    inverse = false,
    showTagline = true,
}: LogoProps) {
    return (
        <span className={cn('inline-flex items-center gap-2.5', className)}>
            <BrandMark inverse={inverse} />
            <span className="flex flex-col leading-none">
                <span
                    className={cn(
                        'font-display text-lg font-bold tracking-tight',
                        inverse ? 'text-pine-50' : 'text-foreground',
                    )}
                >
                    GoKemping
                </span>
                {showTagline ? (
                    <span
                        className={cn(
                            'mt-1 text-[0.68rem] tracking-wide',
                            inverse
                                ? 'text-pine-50/65'
                                : 'text-muted-foreground',
                        )}
                    >
                        Sewa camping &amp; sepeda Garut
                    </span>
                ) : null}
            </span>
        </span>
    );
}
