import type { LandingBusiness } from '@/types';

type ServiceCopy = {
    cta: string;
    highlights: string[];
};

/**
 * Copy marketing per unit bisnis. Label CTA mengikuti PRD section 8, sedangkan
 * deskripsi panjang diambil dari kolom `businesses.description`.
 */
const serviceCopy: Record<string, ServiceCopy> = {
    gokemping: {
        cta: 'Sewa Alat Camping',
        highlights: [
            'Tenda, sleeping bag, matras, dan kursi lipat',
            'Kompor camping serta carrier siap pakai',
            'Perlengkapan dibersihkan sebelum disewakan',
        ],
    },
    'sewa-sepeda-garut': {
        cta: 'Sewa Sepeda',
        highlights: [
            'MTB, city bike, dan sepeda anak',
            'Wajib membawa KTP dan memakai helm',
            'Sepeda dicek sebelum dan sesudah disewa',
        ],
    },
};

export function serviceCopyFor(business: LandingBusiness): ServiceCopy {
    return (
        serviceCopy[business.slug] ?? {
            cta: 'Lihat Katalog',
            highlights: [],
        }
    );
}

export function serviceUrl(business: LandingBusiness): string {
    return `/${business.slug}`;
}
