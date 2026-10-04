export type LandingBusiness = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    whatsapp: string;
    email: string | null;
    address: string | null;
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

export type LandingPageProps = {
    businesses: LandingBusiness[];
    featuredProducts: LandingProduct[];
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
