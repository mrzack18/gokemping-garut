import { Link } from '@inertiajs/react';
import {
    BarChart3,
    CalendarRange,
    FolderTree,
    LayoutGrid,
    LayoutTemplate,
    Package,
    Settings2,
    Ticket,
    TrendingUp,
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
import contentRoutes from '@/routes/admin/content';
import customerRoutes from '@/routes/admin/customers';
import paymentRoutes from '@/routes/admin/payments';
import paymentSettingRoutes from '@/routes/admin/payment-settings';
import productRoutes from '@/routes/admin/products';
import reportRoutes from '@/routes/admin/reports';
import statisticRoutes from '@/routes/admin/statistics';
import adminTicketRoutes from '@/routes/admin/tickets';
import type { NavItem } from '@/types';

// Seluruh menu modul sesuai ROADMAP.md sudah tersedia di sidebar ini.
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
    {
        title: 'Laporan',
        href: reportRoutes.index(),
        icon: BarChart3,
    },
    {
        title: 'Statistik',
        href: statisticRoutes.index(),
        icon: TrendingUp,
    },
    {
        title: 'Konten',
        href: contentRoutes.index(),
        icon: LayoutTemplate,
    },
    {
        title: 'Cek Tiket',
        href: adminTicketRoutes.index(),
        icon: Ticket,
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
