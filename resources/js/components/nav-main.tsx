import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavSection } from '@/types';

export function NavMain({ sections }: { sections: NavSection[] }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <>
            {sections.map((section) => (
                <SidebarGroup key={section.label} className="px-2 py-0">
                    <SidebarGroupLabel className="text-sidebar-foreground/50">
                        {section.label}
                    </SidebarGroupLabel>
                    <SidebarMenu>
                        {section.items.map((item) => {
                            // Dashboard dicocokkan persis karena `/admin`
                            // adalah prefix dari semua halaman admin lainnya.
                            const isExcluded = item.exclude?.some((href) =>
                                isCurrentOrParentUrl(href),
                            );
                            const isActive =
                                !isExcluded &&
                                (item.exact
                                    ? isCurrentUrl(item.href)
                                    : isCurrentOrParentUrl(item.href));

                            return (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive}
                                        tooltip={{ children: item.title }}
                                        className="transition-colors data-[active=true]:ring-1 data-[active=true]:ring-sidebar-primary/25 data-[active=true]:ring-inset"
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
