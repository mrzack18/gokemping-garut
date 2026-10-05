import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

/**
 * Satu anak remah navigasi.
 *
 * `href` boleh kosong untuk halaman yang sedang dibuka: halaman edit produk
 * baru tahu URL-nya setelah produknya dimuat, jadi remah terakhirnya tidak
 * punya tautan untuk diikuti.
 */
export type BreadcrumbItem = {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};
