import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    CalendarRange,
    FilePlus,
    FolderTree,
    LayoutGrid,
    LayoutTemplate,
    Package,
    Settings2,
    Store,
    Ticket,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import type { NavSection } from '@/types';

// Menu dikelompokkan mengikuti alur kerja admin: operasional harian dulu,
// lalu katalog, laporan, dan terakhir pengaturan konten/pembayaran.
const navSections: NavSection[] = [
    {
        label: 'Operasional',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
                exact: true,
            },
            {
                title: 'Booking',
                href: bookingRoutes.index(),
                icon: CalendarRange,
                exclude: [bookingRoutes.manual.create()],
            },
            {
                title: 'Booking Manual',
                href: bookingRoutes.manual.create(),
                icon: FilePlus,
                exact: true,
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
                title: 'Cek Tiket',
                href: adminTicketRoutes.index(),
                icon: Ticket,
            },
        ],
    },
    {
        label: 'Katalog',
        items: [
            {
                title: 'Produk',
                href: productRoutes.index(),
                icon: Package,
            },
            {
                title: 'Kategori',
                href: categoryRoutes.index(),
                icon: FolderTree,
            },
        ],
    },
    {
        label: 'Laporan',
        items: [
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
        ],
    },
    {
        label: 'Pengaturan',
        items: [
            {
                title: 'Konten',
                href: contentRoutes.index(),
                icon: LayoutTemplate,
            },
            {
                title: 'Metode Bayar',
                href: paymentSettingRoutes.index(),
                icon: Settings2,
            },
        ],
    },
];

export function AppSidebar() {
    const { business } = usePage().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="gap-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <div className="flex items-center gap-2 rounded-md border border-sidebar-border/60 bg-sidebar-accent/40 px-2.5 py-2 text-xs group-data-[collapsible=icon]:hidden">
                    <Store
                        aria-hidden="true"
                        className="size-3.5 shrink-0 text-sidebar-primary"
                    />
                    <span className="min-w-0 truncate font-medium text-sidebar-foreground/85">
                        {business ? business.name : 'Unit usaha'}
                    </span>
                    {business ? (
                        <span className="ml-auto shrink-0 rounded-sm bg-sidebar-primary/15 px-1.5 py-0.5 font-mono text-[0.65rem] text-sidebar-primary">
                            {business.bookingCodePrefix}
                        </span>
                    ) : null}
                </div>
            </SidebarHeader>

            <SidebarContent>
                <NavMain sections={navSections} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
