/**
 * Angka dashboard admin (PRD section 22, ROADMAP 4.1).
 *
 * Seluruhnya sudah dihitung server per `business_id` admin yang login, jadi
 * frontend tidak pernah melakukan agregasi sendiri. Label uang dan tanggal juga
 * sudah siap tampil supaya angka yang dibaca admin sama dengan yang dihitung
 * database.
 */
export type DashboardRevenue = {
    /** Pembayaran lunas yang dikonfirmasi admin pada bulan berjalan. */
    paid: number;
    paid_label: string;
    /** Pembayaran yang masih menunggu atau belum lunas bulan berjalan. */
    pending: number;
    pending_label: string;
    period_label: string;
};

export type DashboardBookingChartPoint = {
    date: string;
    label: string;
    full_label: string;
    count: number;
};

export type DashboardRecentBooking = {
    booking_code: string;
    customer_name: string;
    product_name: string;
    quantity: number;
    period_label: string;
    total: number;
    total_label: string;
    status: string;
    status_label: string;
};

export type AdminDashboardPageProps = {
    business: {
        name: string;
        slug: string;
        bookingCodePrefix: string;
        whatsapp: string;
    };
    stats: {
        totalProducts: number;
        bookingsToday: number;
        rented: number;
        awaitingConfirmation: number;
        pendingPayments: number;
        revenue: DashboardRevenue;
        bookingChart: DashboardBookingChartPoint[];
        recentBookings: DashboardRecentBooking[];
    };
};
