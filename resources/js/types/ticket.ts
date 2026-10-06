import type { BookingStatusValue, PaymentStatusValue } from './admin';
import type { LandingBusiness } from './landing';

/**
 * Cek tiket publik (kode booking + nomor WhatsApp).
 *
 * Payload tiket sengaja tanpa NIK dan tanpa alamat: halaman ini milik publik.
 */
export type TicketItem = {
    product_name: string;
    quantity: number;
    subtotal_label: string;
};

export type TicketDetail = {
    booking_code: string;
    business: {
        name: string;
        whatsapp: string | null;
    };
    customer_name: string;
    status: BookingStatusValue;
    status_label: string;
    payment_status: PaymentStatusValue;
    payment_status_label: string;
    payment_method_label: string;
    items: TicketItem[];
    period: {
        start_date_label: string;
        end_date_label: string;
        total_days_label: string;
    };
    total: number;
    total_label: string;
    cancellation_reason: string | null;
    created_at_label: string;
};

export type TicketCheckPageProps = {
    ticket: TicketDetail | null;
    businesses: LandingBusiness[];
};
