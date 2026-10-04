import type { LandingBusiness } from '@/types/landing';

export type BookingFormProduct = {
    id: number;
    name: string;
    slug: string;
    price: number;
    price_unit: string;
    stock: number;
    photo: string | null;
    category: { id: number; name: string } | null;
};

/**
 * Tanggal dikirim backend dalam format `YYYY-MM-DD` supaya frontend tidak
 * perlu menghitung ulang zona waktu hari ini.
 */
export type BookingFormPageProps = {
    business: LandingBusiness;
    businesses: LandingBusiness[];
    product: BookingFormProduct;
    minDate: string;
};
