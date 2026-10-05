import { Link } from '@inertiajs/react';
import {
    CalendarRange,
    FolderTree,
    LayoutGrid,
    Package,
    Settings2,
    Users,
    Wallet,
} from 'lucide-react';
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
import bookingRoutes from '@/routes/admin/bookings';
import categoryRoutes from '@/routes/admin/categories';
import customerRoutes from '@/routes/admin/customers';
import paymentRoutes from '@/routes/admin/payments';
import paymentSettingRoutes from '@/routes/admin/payment-settings';
import productRoutes from '@/routes/admin/products';
import type { NavItem } from '@/types';

// Menu modul lain (laporan, pengaturan akun) sengaja belum ditambahkan dan akan
// menyusul sesuai fase pengerjaan pada ROADMAP.md.
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
    {
        title: 'Produk',
        href: productRoutes.index(),
        icon: Package,
    },
    {
        title: 'Booking',
        href: bookingRoutes.index(),
        icon: CalendarRange,
    },
    {
        title: 'Penyewa',
        href: customerRoutes.index(),
        icon: Users,
    },
    {
        title: 'Pembayaran',
        href: paymentRoutes.index(),
        icon: Wallet,
    },
    {
        title: 'Pengaturan Pembayaran',
        href: paymentSettingRoutes.index(),
        icon: Settings2,
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
