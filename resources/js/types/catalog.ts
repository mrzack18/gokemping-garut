import type { LandingBusiness } from '@/types/landing';

export type CatalogSort =
    | 'terbaru'
    | 'harga_terendah'
    | 'harga_tertinggi'
    | 'nama';

export type CatalogCategory = {
    id: number;
    name: string;
    slug: string;
    products_count: number;
};

export type CatalogProduct = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    price_unit: string;
    stock: number;
    is_available: boolean;
    category: { id: number; name: string } | null;
    photo: string | null;
};

/**
 * State filter dinormalisasi oleh backend. Nilai `null` berarti filter tidak
 * aktif, bukan filter dengan nilai kosong.
 */
export type CatalogFilters = {
    q: string;
    category: string | null;
    min_price: number | null;
    max_price: number | null;
    sort: CatalogSort;
};

export type CatalogPaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type CatalogPaginator = {
    data: CatalogProduct[];
    links: CatalogPaginatorLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

export type CatalogPageProps = {
    business: LandingBusiness & { booking_code_prefix: string };
    businesses: LandingBusiness[];
    categories: CatalogCategory[];
    products: CatalogPaginator;
    filters: CatalogFilters;
    priceBounds: { min: number; max: number };
};
