import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import { useThemeScope } from '@/hooks/use-theme-scope';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;
    useThemeScope('admin-theme');

    if (variant === 'header') {
        return (
            <div className="admin-theme flex min-h-screen w-full flex-col bg-background">
                {children}
            </div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen} className="admin-theme">
            {children}
        </SidebarProvider>
    );
}
