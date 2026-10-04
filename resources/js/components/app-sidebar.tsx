import { Link } from '@inertiajs/react';
import { FolderTree, LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes/admin';
import categoryRoutes from '@/routes/admin/categories';
import type { NavItem } from '@/types';

// Menu modul lain (produk, booking, penyewa, pembayaran, laporan, pengaturan)
// sengaja belum ditambahkan dan akan menyusul sesuai fase pengerjaan pada
// ROADMAP.md.
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Kategori',
        href: categoryRoutes.index(),
        icon: FolderTree,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={[]} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
