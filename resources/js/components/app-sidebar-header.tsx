import { usePage } from '@inertiajs/react';
import { ExternalLink, Store } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Badge } from '@/components/ui/badge';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { business } = usePage().props;

    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-border bg-background/95 px-4 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 sm:px-6">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex shrink-0 items-center gap-3">
                {business ? (
                    <Badge
                        variant="outline"
                        className="hidden gap-1.5 border-pine-700/25 bg-pine-50 font-medium text-pine-700 sm:inline-flex dark:border-pine-100/20 dark:bg-pine-100/30 dark:text-pine-600"
                    >
                        <Store aria-hidden="true" className="size-3.5" />
                        {business.name}
                    </Badge>
                ) : null}
                <a
                    href="/"
                    target="_blank"
                    rel="noreferrer"
                    className="hidden items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground sm:inline-flex"
                >
                    <ExternalLink aria-hidden="true" className="size-3.5" />
                    Lihat situs
                </a>
            </div>
        </header>
    );
}
