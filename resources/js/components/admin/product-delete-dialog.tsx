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
import productRoutes from '@/routes/admin/products';
import type { AdminProductRow } from '@/types';

/**
 * Konfirmasi hapus produk (PRD section 23, ROADMAP 4.3).
 *
 * Produk dihapus dengan cara menonaktifkan lalu soft delete, bukan menghapus
 * baris permanen. Booking yang sudah pernah memakai produk itu tetap bisa dibaca
 * karena nama dan harga produk disalin ke detail booking saat transaksi dibuat.
 *
 * Karena itu kalimat di dialog menyebut dampaknya secara terbuka: admin perlu
 * tahu produk ini akan hilang dari daftar dan katalog, bukan hilang dari riwayat
 * booking. Halaman edit yang menampilkan jumlah booking memakai angka yang
 * jujur, jadi dialog ini tidak perlu mengarang jumlah.
 */
type ProductDeleteDialogProps = {
    product: AdminProductRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function ProductDeleteDialog({
    product,
    open,
    onOpenChange,
}: ProductDeleteDialogProps) {
    if (product === null) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Hapus produk &quot;{product.name}&quot;?
                    </DialogTitle>
                    <DialogDescription>
                        Produk ini langsung hilang dari katalog publik dan dari
                        daftar admin. Booking yang pernah memakai produk ini
                        tetap tersimpan dan tidak ikut terhapus.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...productRoutes.destroy.form({
                        product: product.slug,
                    })}
                    options={{
                        preserveScroll: true,
                    }}
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ processing }) => (
                        <>
                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                    asChild
                                >
                                    <button type="submit">Hapus Produk</button>
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
