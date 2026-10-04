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
    /**
     * Nilai awal dari draft session supaya tombol "Kembali" dari halaman
     * biodata tidak menghapus jadwal yang sudah dipilih.
     */
    initial: {
        start_date: string;
        end_date: string;
        quantity: number;
    };
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

/**
 * Data penyewa yang dikembalikan endpoint `booking.customerLookup` untuk
 * prefill otomatis (ROADMAP 3.7, PRD section 25).
 */
export type CustomerLookupCustomer = {
    name: string;
    whatsapp: string;
    email: string | null;
    nik: string;
    address: string;
    city: string | null;
    notes: string | null;
};

export type CustomerLookupResponse = {
    found: boolean;
    customer: CustomerLookupCustomer | null;
};

export type BookingCustomerDraft = {
    name: string;
    whatsapp: string;
    email: string;
    nik: string;
    address: string;
    city: string;
    notes: string;
    renter_count: number | string | null;
};

export type BookingBiodataPageProps = {
    business: LandingBusiness;
    businesses: LandingBusiness[];
    product: {
        id: number;
        name: string;
        slug: string;
        price: number;
        price_unit: string;
        stock: number;
    };
    draft: {
        start_date: string | null;
        end_date: string | null;
        quantity: number;
    };
    customer: BookingCustomerDraft;
    isBikeRental: boolean;
};

/**
 * Opsi metode pembayaran aktif untuk satu unit bisnis (ROADMAP 3.9).
 *
 * Datanya dibaca dari `payment_methods` per `business_id`, jadi nomor rekening
 * dan gambar QRIS tidak pernah ditulis langsung di frontend.
 */
export type BookingPaymentMethod = {
    type: 'cash' | 'qris' | 'bank_transfer';
    label: string;
    description: string;
    requires_proof: boolean;
    is_ready: boolean;
    instructions: string | null;
    merchant_name: string | null;
    qris_image_url: string | null;
    bank_name: string | null;
    account_number: string | null;
    account_name: string | null;
};

/**
 * Halaman review booking (ROADMAP 3.8, PRD section 15).
 *
 * Durasi, subtotal, dan total dihitung di server lewat `BookingPeriod` dan
 * dikirim sebagai angka siap tampil, supaya total yang dibaca penyewa sama
 * dengan total yang akan disimpan di ROADMAP 3.11.
 */
export type BookingReviewPageProps = {
    business: LandingBusiness;
    businesses: LandingBusiness[];
    product: {
        id: number;
        name: string;
        slug: string;
        price: number;
        price_unit: string;
        stock: number;
    };
    period: {
        start_date: string;
        end_date: string;
        start_date_label: string;
        end_date_label: string;
        duration: number;
        duration_label: string;
        quantity: number;
    };
    availability: {
        available: number;
        requested: number;
        is_available: boolean;
    };
    pricing: {
        price: number;
        price_label: string;
        subtotal: number;
        total: number;
    };
    customer: {
        name: string;
        whatsapp: string;
        email: string;
        nik: string;
        address: string;
        city: string;
        notes: string;
        renter_count: string;
    };
    paymentMethods: BookingPaymentMethod[];
    isBikeRental: boolean;
};

/**
 * Bukti pembayaran yang sudah tersimpan (ROADMAP 3.10, BR-08).
 *
 * `url` dibaca dari disk publik oleh server, `name` hanya untuk ditampilkan.
 * Path relatif ikut dikirim supaya frontend tidak perlu menebak nama berkas
 * saat penyewa menggantinya.
 */
export type BookingPaymentProof = {
    path: string;
    name: string;
    url: string;
};

/**
 * Halaman pembayaran per metode (ROADMAP 3.9, PRD section 17).
 */
export type BookingPaymentPageProps = {
    business: LandingBusiness;
    businesses: LandingBusiness[];
    method: BookingPaymentMethod;
    product: {
        name: string;
        slug: string;
    };
    period: {
        start_date_label: string;
        end_date_label: string;
        duration_label: string;
        quantity: number;
    };
    availability: {
        available: number;
        requested: number;
        is_available: boolean;
    };
    pricing: {
        total: number;
    };
    proof: BookingPaymentProof | null;
};
