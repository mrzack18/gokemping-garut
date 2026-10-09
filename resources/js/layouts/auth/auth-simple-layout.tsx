import { Link } from '@inertiajs/react';
import { BrandMark } from '@/components/brand/logo';
import { useThemeScope } from '@/hooks/use-theme-scope';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    useThemeScope('admin-theme');

    return (
        <div className="admin-theme flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2.5"
                        >
                            <span className="flex size-12 items-center justify-center rounded-lg border border-pine-700/20 bg-pine-50">
                                <BrandMark className="size-7" />
                            </span>
                            <span className="font-display text-lg font-bold tracking-tight">
                                GoKemping
                            </span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="font-display text-2xl font-semibold tracking-tight">
                                {title}
                            </h1>
                            {description ? (
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            ) : null}
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
