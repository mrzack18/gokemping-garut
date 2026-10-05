import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import paymentRoutes from '@/routes/admin/payments';
import type { AdminPaymentRow } from '@/types';

/**
 * Konfirmasi tolak pembayaran (PRD section 26, ROADMAP 4.6).
 *
 * Penolakan wajib memakai alasan karena status `ditolak` dikirim balik ke
 * penyewa lewat pesan pembayaran: tanpa alasan, penyewa tidak tahu apakah harus
 * mengunggah ulang bukti, memperbaiki nominal, atau menghubungi admin. Alasan
 * ini juga tersimpan di `payments.rejection_reason` dan ikut tampil di detail
 * booking.
 */
type PaymentRejectDialogProps = {
    payment: AdminPaymentRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function PaymentRejectDialog({
    payment,
    open,
    onOpenChange,
}: PaymentRejectDialogProps) {
    if (payment === null) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Tolak pembayaran {payment.booking_code}?
                    </DialogTitle>
                    <DialogDescription>
                        Pembayaran {payment.method_label} sebesar Rp{' '}
                        {payment.amount_label} atas nama {payment.customer_name}{' '}
                        akan ditandai ditolak. Alasan di bawah ikut tersimpan di
                        detail booking.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...paymentRoutes.reject.form({ payment: payment.id })}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="rejection_reason">
                                    Alasan penolakan
                                </Label>
                                <Textarea
                                    id="rejection_reason"
                                    name="rejection_reason"
                                    placeholder="Contoh: Nominal transfer tidak sesuai dengan total booking."
                                    rows={3}
                                    maxLength={255}
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.rejection_reason} />
                            </div>

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                    asChild
                                >
                                    <button type="submit">
                                        Tolak Pembayaran
                                    </button>
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
