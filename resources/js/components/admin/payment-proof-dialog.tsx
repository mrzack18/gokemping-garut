import { ImageOff } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { AdminPaymentRow } from '@/types';

/**
 * Pratinjau bukti pembayaran (PRD section 26, ROADMAP 4.6).
 *
 * Bukti dibuka di modal supaya admin bisa memutuskan tanpa meninggalkan daftar
 * verifikasi. Gambar ditautkan ke berkas aslinya, jadi admin yang perlu
 * memperbesar atau mengunduh tetap bisa membukanya di tab lain.
 */
type PaymentProofDialogProps = {
    payment: AdminPaymentRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function PaymentProofDialog({
    payment,
    open,
    onOpenChange,
}: PaymentProofDialogProps) {
    if (payment === null) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        Bukti pembayaran {payment.booking_code}
                    </DialogTitle>
                    <DialogDescription>
                        {payment.customer_name} · {payment.method_label} · Rp{' '}
                        {payment.amount_label}
                    </DialogDescription>
                </DialogHeader>

                {payment.proof_url === null ? (
                    <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-10 text-center">
                        <ImageOff className="size-8 text-muted-foreground" />
                        <p className="max-w-sm text-sm text-muted-foreground">
                            {payment.method === 'cash'
                                ? 'Pembayaran cash tidak memerlukan bukti unggahan karena uang diterima langsung di lokasi.'
                                : 'Belum ada bukti unggahan. Untuk booking manual, konfirmasikan pembayaran langsung kepada penyewa sebelum menandainya lunas.'}
                        </p>
                    </div>
                ) : (
                    <a
                        href={payment.proof_url}
                        target="_blank"
                        rel="noreferrer"
                        className="block"
                    >
                        <img
                            src={payment.proof_url}
                            alt={`Bukti pembayaran ${payment.booking_code}`}
                            className="max-h-[70vh] w-full rounded-md border object-contain"
                        />
                    </a>
                )}
            </DialogContent>
        </Dialog>
    );
}
