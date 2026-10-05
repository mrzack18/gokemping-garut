import { Badge } from '@/components/ui/badge';

/**
 * Badge status booking dan pembayaran (PRD section 24, ROADMAP 4.4).
 *
 * Warna badge ikut dipisah dari halaman supaya daftar, detail, dan dashboard
 * memberi pembacaan yang sama. Admin memindai banyak booking dalam satu
 * layar, jadi warna harus bisa dipercaya tanpa membaca labelnya.
 *
 * Booking yang dibatalkan memakai `destructive`, bukan `outline` seperti
 * `selesai`: yang selesai adalah akhir yang wajar, sedangkan yang dibatalkan
 * berarti ada masalah yang perlu ditindaklanjuti.
 */

type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive';

/**
 * Varian badge untuk status booking.
 */
export function bookingStatusVariant(status: string): BadgeVariant {
    switch (status) {
        case 'menunggu_konfirmasi':
            return 'secondary';
        case 'dikonfirmasi':
            return 'default';
        case 'sedang_disewa':
            return 'default';
        case 'selesai':
            return 'outline';
        case 'dibatalkan':
            return 'destructive';
        default:
            return 'secondary';
    }
}

/**
 * Varian badge untuk status pembayaran.
 */
export function paymentStatusVariant(status: string): BadgeVariant {
    switch (status) {
        case 'belum_dibayar':
            return 'outline';
        case 'menunggu_verifikasi':
            return 'secondary';
        case 'lunas':
            return 'default';
        case 'ditolak':
            return 'destructive';
        default:
            return 'outline';
    }
}

/**
 * Badge status booking.
 */
export function BookingStatusBadge({
    status,
    label,
}: {
    status: string;
    label: string;
}) {
    return <Badge variant={bookingStatusVariant(status)}>{label}</Badge>;
}

/**
 * Badge status pembayaran.
 */
export function PaymentStatusBadge({
    status,
    label,
}: {
    status: string;
    label: string;
}) {
    return <Badge variant={paymentStatusVariant(status)}>{label}</Badge>;
}
