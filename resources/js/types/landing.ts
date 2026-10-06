export type LandingBusiness = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    service_intro: string | null;
    service_highlights: string[] | null;
    rental_terms: string | null;
    whatsapp: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    maps_embed_url: string | null;
};

export type LandingProduct = {
    id: number;
    name: string;
    slug: string;
    price: number;
    price_unit: string;
    stock: number;
    business: Pick<LandingBusiness, 'id' | 'name' | 'slug'>;
    category: { id: number; name: string } | null;
};

/**
 * Banner hero milik satu unit (ROADMAP 5.4).
 *
 * Landing page menampilkan banner dari semua unit aktif, jadi nama unitnya
 * ikut dikirim supaya pengunjung tahu banner itu milik layanan yang mana.
 */
export type LandingBanner = {
    id: number;
    title: string;
    subtitle: string | null;
    image_url: string;
    link_url: string | null;
    business: Pick<LandingBusiness, 'name' | 'slug'>;
};

export type LandingFaq = {
    id: number;
    question: string;
    answer: string;
    business: Pick<LandingBusiness, 'name' | 'slug'>;
};

export type LandingPageProps = {
    businesses: LandingBusiness[];
    featuredProducts: LandingProduct[];
    banners: LandingBanner[];
    faqs: LandingFaq[];
};

export type ServiceSelectionProduct = {
    id: number;
    name: string;
    price: number;
    price_unit: string;
    stock: number;
};

export type ServiceSelectionPreview = {
    business_id: number;
    products: ServiceSelectionProduct[];
};

export type ServiceSelectionPageProps = {
    businesses: (LandingBusiness & { booking_code_prefix: string })[];
    previewProducts: ServiceSelectionPreview[];
};
