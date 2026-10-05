import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import bookingRoutes from '@/routes/admin/bookings';
import type { AdminBookingPayment } from '@/types';

/**
 * Konfirmasi pembatalan booking (PRD section 24, ROADMAP 4.4).
 *
 * Pembatalan tidak menghapus booking, hanya menutupnya: barang yang sedang
 * ditahan otomatis terbaca tersedia lagi untuk tanggal tersebut, dan riwayat
 * statusnya tetap utuh. Karena dampaknya tidak bisa dibatalkan begitu saja,
 * dialog ini menanyakan alasannya dan menyimpan jawaban itu di riwayat.
 *
 * Dampaknya ke pembayaran disebutkan terbuka karena tidak sama untuk semua
 * kasus. Pembayaran yang sudah `lunas` tidak diubah: uang sudah masuk, jadi
 * pengembaliannya keputusan manual di luar sistem, bukan efek samping dari
 * menekan tombol pembatalan.
 */
type BookingCancelDialogProps = {
    bookingCode: string;
    open: boolean;
    payment: AdminBookingPayment | null;
    onOpenChange: (open: boolean) => void;
};

export default function BookingCancelDialog({
    bookingCode,
    open,
    payment,
    onOpenChange,
}: BookingCancelDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Batalkan booking {bookingCode}?</DialogTitle>
                    <DialogDescription>
                        Barang yang di-booking langsung tersedia lagi untuk
                        tanggal sewa tersebut. Booking tidak dihapus, dan
                        riwayatnya tetap bisa dibaca.
                    </DialogDescription>
                </DialogHeader>

                {payment !== null && payment.status !== 'lunas' ? (
                    <Alert>
                        <AlertTitle>Pembayaran ikut ditutup</AlertTitle>
                        <AlertDescription>
                            Pembayaran saat ini &quot;
                            {payment.status_label}&quot; akan menjadi
                            &quot;Ditolak&quot; dengan alasan pembatalan. Bukti
                            pembayaran tetap disimpan.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {payment?.status === 'lunas' ? (
                    <Alert>
                        <AlertTitle>Pembayaran sudah lunas</AlertTitle>
                        <AlertDescription>
                            Pembayaran tetap tercatat lunas karena uangnya sudah
                            masuk. Kalau perlu dikembalikan, lakukan manual di
                            luar sistem ini.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <Form
                    {...bookingRoutes.cancel.form({
                        booking: bookingCode,
                    })}
                    options={{
                        preserveScroll: true,
                    }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="cancellation_reason">
                                    Alasan pembatalan
                                </Label>
                                <Textarea
                                    id="cancellation_reason"
                                    name="cancellation_reason"
                                    placeholder="Contoh: Penyewa tidak muncul di lokasi pengambilan."
                                    rows={3}
                                    maxLength={200}
                                    required
                                    autoFocus
                                />
                                <p className="text-sm text-muted-foreground">
                                    Alasan ini tersimpan di riwayat status dan
                                    dibaca penyewa lewat pesan pembatalan.
                                </p>
                                <InputError
                                    message={errors.cancellation_reason}
                                />
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
                                        Batalkan Booking
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
