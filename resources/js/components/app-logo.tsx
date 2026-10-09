import { usePage } from '@inertiajs/react';
import { BrandMark } from '@/components/brand/logo';

/**
 * Identitas panel admin: mark GoKemping + nama aplikasi + unit yang dikelola.
 * Nama unit dibaca dari shared prop `business` (HandleInertiaRequests), jadi
 * admin selalu tahu sedang berada di unit mana.
 */
export default function AppLogo() {
    const { name, business } = usePage().props;

    return (
        <>
            <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary/15 ring-1 ring-sidebar-primary/30 ring-inset">
                <BrandMark inverse className="size-5" />
            </span>
            <div className="grid min-w-0 flex-1 text-left leading-tight group-data-[collapsible=icon]:hidden">
                <span className="truncate text-sm font-semibold">{name}</span>
                <span className="truncate text-xs text-sidebar-foreground/60">
                    {business ? `Panel ${business.name}` : 'Panel admin'}
                </span>
            </div>
        </>
    );
}
