import type { LandingBusiness } from '@/types';

type ServiceCopy = {
    /** Label CTA di landing page, mengikuti PRD section 7. */
    cta: string;

    /** Label tombol di halaman pilih layanan, mengikuti PRD section 8. */
    buttonLabel: string;

    /** Deskripsi singkat sesuai PRD section 8. */
    intro: string;

    highlights: string[];

    /** Nama route katalog unit, mengikuti PRD section 34. */
    catalogUrl: string;
};

/**
 * Copy marketing per unit bisnis. Copy diambil dari PRD section 7 dan 8
 * karena belum ada content management (Fase 3, PRD section 28).
 */
const serviceCopy: Record<string, ServiceCopy> = {
    gokemping: {
        cta: 'Sewa Alat Camping',
        buttonLabel: 'Lihat Peralatan',
        intro: 'Sewa perlengkapan camping lengkap untuk kebutuhan camping dan outdoor.',
        highlights: [
            'Tenda, sleeping bag, matras, dan kursi lipat',
            'Kompor camping serta carrier siap pakai',
            'Perlengkapan dibersihkan sebelum disewakan',
        ],
        catalogUrl: '/gokemping',
    },
    'sewa-sepeda-garut': {
        cta: 'Sewa Sepeda',
        buttonLabel: 'Lihat Sepeda',
        intro: 'Sewa sepeda untuk gowes, rekreasi, maupun aktivitas outdoor di Garut.',
        highlights: [
            'MTB, city bike, dan sepeda anak',
            'Wajib membawa KTP dan memakai helm',
            'Sepeda dicek sebelum dan sesudah disewa',
        ],
        catalogUrl: '/sewa-sepeda-garut',
    },
};

const fallback: ServiceCopy = {
    cta: 'Lihat Katalog',
    buttonLabel: 'Lihat Katalog',
    intro: 'Katalog barang siap sewa untuk kebutuhan outdoor Anda.',
    highlights: [],
    catalogUrl: '/',
};

export function serviceCopyFor(business: LandingBusiness): ServiceCopy {
    return (
        serviceCopy[business.slug] ?? {
            ...fallback,
            catalogUrl: `/${business.slug}`,
        }
    );
}

export function serviceUrl(business: LandingBusiness): string {
    return serviceCopyFor(business).catalogUrl;
}
