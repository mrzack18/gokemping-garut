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

/**
 * Kategori di daftar kategori admin (PRD section 23, ROADMAP 4.2).
 *
 * `products_count` menghitung seluruh produk, termasuk yang nonaktif, karena
 * itulah yang menentukan apakah kategori boleh dihapus.
 */
export type AdminCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    products_count: number;
};

export type AdminCategoriesPageProps = {
    categories: AdminCategory[];
};

/**
 * Opsi kategori untuk form dan filter produk (PRD section 23, ROADMAP 4.3).
 *
 * `products_count` diikutkan supaya form bisa memberi tahu admin berapa produk
 * yang ikut bergerak kalau kategorinya diganti.
 */
export type AdminCategoryOption = {
    id: number;
    name: string;
    products_count: number;
};

/**
 * Satuan harga sewa. Nilainya berasal dari daftar yang sama dengan validasi
 * backend (`StoreProductRequest::PRICE_UNITS`) supaya pilihan di form dan
 * nilai yang disimpan tidak mungkin berbeda.
 */
export type ProductPriceUnit = 'hari' | 'jam' | 'paket' | 'event';

/**
 * Baris daftar produk admin.
 *
 * `price` dipakai form edit sebagai angka, `price_label` dipakai tabel sebagai
 * teks siap tampil. Keduanya dikirim supaya daftar dan form tidak bisa
 * menampilkan angka yang berbeda.
 *
 * `is_available` memakai definisi yang sama dengan katalog: stok lebih dari nol.
 * Admin perlu tahu status yang dilihat pengunjung, bukan hanya angka stok.
 */
export type AdminProductRow = {
    id: number;
    name: string;
    slug: string;
    category: string | null;
    price: number;
    price_label: string;
    price_unit: string;
    stock: number;
    is_active: boolean;
    is_available: boolean;
    photo: string | null;
    images_count: number;
    updated_at_label: string;
};

export type AdminProductFilters = {
    q: string;
    category: number | null;
    status: 'semua' | 'aktif' | 'nonaktif';
};

export type AdminProductPaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type AdminProductPaginator = {
    data: AdminProductRow[];
    links: AdminProductPaginatorLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

export type AdminProductsPageProps = {
    products: AdminProductPaginator;
    categories: AdminCategoryOption[];
    filters: AdminProductFilters;
};

export type AdminProductPhoto = {
    id: number;
    url: string;
    is_primary: boolean;
};

/**
 * Produk pada halaman edit.
 *
 * Spesifikasi dikirim sebagai baris label-isi, bukan objek, karena form
 * menampilkan baris yang bisa ditambah dan dihapus admin. Baris kosong yang
 * tidak ikut dikirim tidak akan pernah sampai ke backend.
 */
export type AdminProductDetail = {
    id: number;
    name: string;
    slug: string;
    category_id: number | null;
    description: string | null;
    specification: { key: string; value: string }[];
    rental_terms: string | null;
    price: number;
    price_unit: string;
    stock: number;
    is_active: boolean;
    photos: AdminProductPhoto[];
    bookingCount: number;
};

export type AdminProductFormPageProps = {
    categories: AdminCategoryOption[];
    priceUnits: ProductPriceUnit[];
    maxImages: number;
};

/**
 * Halaman edit menambah sisa kapasitas foto. Halaman tambah tidak punya sisa
 * kapasitas karena produknya belum ada, jadi tidak ada foto yang bisa terpakai.
 */
export type AdminProductEditPageProps = AdminProductFormPageProps & {
    product: AdminProductDetail;
    remainingImages: number;
};

/**
 * Status booking. Nilainya sama persis dengan enum `BookingStatus` di backend,
 * jadi filter dan label status di frontend memakai sumber yang sama dengan
 * validasi transisi.
 */
export type BookingStatusValue =
    | 'menunggu_konfirmasi'
    | 'dikonfirmasi'
    | 'sedang_disewa'
    | 'selesai'
    | 'dibatalkan';

/**
 * Baris daftar booking admin.
 *
 * Nama dan harga produk dibaca dari `booking_items`, bukan dari tabel produk,
 * jadi daftar ini tetap menampilkan booking lama walaupun produknya sudah
 * diubah atau dihapus.
 */
export type AdminBookingRow = {
    booking_code: string;
    customer_name: string;
    customer_whatsapp: string | null;
    product_label: string;
    quantity: number;
    period_label: string;
    total: number;
    total_label: string;
    payment_method_label: string;
    payment_status: string;
    payment_status_label: string;
    status: BookingStatusValue;
    status_label: string;
    created_at_label: string;
};

export type AdminBookingFilters = {
    q: string;
    status: BookingStatusValue | null;
    from: string | null;
    to: string | null;
};

/**
 * Paginator daftar booking.
 *
 * `links` bawaan Laravel tidak dikirim karena labelnya berisi markup navigasi
 * yang sudah dirender server. Halaman ini memakai daftar nomor halaman sendiri
 * supaya filter tetap ikut di URL.
 */
export type AdminBookingPaginator = {
    data: AdminBookingRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

export type AdminBookingsPageProps = {
    bookings: AdminBookingPaginator;
    filters: AdminBookingFilters;
    statusOptions: { value: BookingStatusValue; label: string }[];
};

export type AdminBookingItem = {
    product_name: string;
    price: number;
    price_label: string;
    price_unit: string;
    quantity: number;
    total_days: number;
    subtotal: number;
    subtotal_label: string;
};

/**
 * Penyewa pada detail booking.
 *
 * `nik` selalu berasal dari `Customer::maskedNik()`, jadi NIK penuh tidak pernah
 * dikirim ke browser halaman ini. NIK penuh hanya bisa dibaca di Manajemen
 * Penyewa (ROADMAP 4.5).
 */
export type AdminBookingCustomer = {
    name: string;
    whatsapp: string;
    email: string | null;
    nik: string;
    address: string | null;
    city: string | null;
};

/**
 * Pembayaran pada detail booking.
 *
 * `needs_verification` menandai pembayaran yang masih menunggu keputusan admin.
 * Verifikasi pembayaran sendiri dikerjakan di ROADMAP 4.6, jadi halaman ini
 * hanya menampilkan statusnya dan bukti transfernya.
 */
export type AdminBookingPayment = {
    method_label: string;
    amount: number;
    amount_label: string;
    status: string;
    status_label: string;
    proof_url: string | null;
    rejection_reason: string | null;
    verified_at_label: string | null;
    verified_by: string | null;
    needs_verification: boolean;
};

export type AdminBookingDetail = {
    booking_code: string;
    status: BookingStatusValue;
    status_label: string;
    /** Tahap berikutnya, null kalau booking sudah selesai atau dibatalkan. */
    next_status: BookingStatusValue | null;
    next_status_label: string | null;
    is_cancellable: boolean;
    period: {
        start_date: string;
        end_date: string;
        start_date_label: string;
        end_date_label: string;
        total_days: number;
        total_days_label: string;
    };
    items: AdminBookingItem[];
    total: number;
    total_label: string;
    subtotal: number;
    subtotal_label: string;
    notes: string | null;
    renter_count: number | null;
    cancellation_reason: string | null;
    timestamps: {
        created_at_label: string;
        confirmed_at_label: string | null;
        started_at_label: string | null;
        completed_at_label: string | null;
        cancelled_at_label: string | null;
    };
    customer: AdminBookingCustomer | null;
    payment: AdminBookingPayment | null;
};

/**
 * Satu baris riwayat status, dari yang paling lama.
 *
 * `author` null untuk baris pertama yang dibuat sistem saat penyewa mengirim
 * booking, karena saat itu belum ada admin yang bertindak.
 */
export type AdminBookingHistoryEntry = {
    from_status: BookingStatusValue | null;
    from_status_label: string | null;
    to_status: BookingStatusValue;
    to_status_label: string;
    note: string | null;
    author: string | null;
    created_at_label: string;
};

export type AdminBookingShowPageProps = {
    booking: AdminBookingDetail;
    history: AdminBookingHistoryEntry[];
};
