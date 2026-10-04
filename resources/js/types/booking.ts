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

/**
 * Respons endpoint `booking.*.availability` (ROADMAP 3.6).
 */
export type BookingAvailability = {
    product_id: number;
    start_date: string;
    end_date: string;
    stock: number;
    used: number;
    available: number;
    requested: number;
    is_available: boolean;
    message: string;
    holding_statuses: string[];
};
