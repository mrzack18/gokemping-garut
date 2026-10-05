import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import paymentRoutes from '@/routes/admin/payments';
import type { AdminPaymentRow } from '@/types';

/**
 * Konfirmasi tandai lunas (PRD section 26, ROADMAP 4.6).
 *
 * Verifikasi pembayaran adalah keputusan uang, jadi tidak dijalankan langsung
 * dari tombol di tabel: admin melihat ulang nominal, metode, dan penyewanya
 * dulu. Untuk QRIS dan transfer, pengingat untuk memeriksa bukti ditulis di
 * dialog karena itu langkah yang mudah terlewat.
 */
type PaymentVerifyDialogProps = {
    payment: AdminPaymentRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function PaymentVerifyDialog({
    payment,
    open,
    onOpenChange,
}: PaymentVerifyDialogProps) {
    if (payment === null) {
        return null;
    }

    const isCash = payment.method === 'cash';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Tandai pembayaran {payment.booking_code} lunas?
                    </DialogTitle>
                    <DialogDescription>
                        Pembayaran {payment.method_label} sebesar Rp{' '}
                        {payment.amount_label} atas nama {payment.customer_name}{' '}
                        akan dicatat lunas.{' '}
                        {isCash
                            ? 'Pastikan uangnya sudah diterima di lokasi.'
                            : 'Pastikan bukti transfernya sudah diperiksa.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...paymentRoutes.verify.form({ payment: payment.id })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => onOpenChange(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={processing} asChild>
                                <button type="submit">Tandai Lunas</button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
