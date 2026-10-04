const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

/**
 * Format harga sewa menjadi mata uang rupiah tanpa desimal, contoh: Rp75.000.
 */
export function formatRupiah(value: number): string {
    return rupiahFormatter.format(value);
}

/**
 * Link WhatsApp resmi (BR-06). Nomor disimpan tanpa tanda "+" atau spasi.
 */
export function whatsappLink(whatsapp: string, message?: string): string {
    const base = `https://wa.me/${whatsapp.replace(/[^0-9]/g, '')}`;

    return message ? `${base}?text=${encodeURIComponent(message)}` : base;
}
