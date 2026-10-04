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
import categoryRoutes from '@/routes/admin/categories';
import type { AdminCategory } from '@/types';

/**
 * Konfirmasi hapus kategori (PRD section 23, ROADMAP 4.2).
 *
 * Kategori yang masih punya produk tidak offer dihapus lewat dialog ini.
 * Server juga menolaknya, jadi membiarkan tombol aktif hanya membuang satu
 * klik dan memunculkan pesan yang sama lagi. Alasan penolakan ditulis sebagai
 * teks di dalam dialog, bukan tooltip, supaya terbaca tanpa harus hover.
 */
type CategoryDeleteDialogProps = {
    category: AdminCategory | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function CategoryDeleteDialog({
    category,
    open,
    onOpenChange,
}: CategoryDeleteDialogProps) {
    if (category === null) {
        return null;
    }

    const productsCount = category.products_count;
    const isBlocked = productsCount > 0;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Hapus kategori &quot;{category.name}&quot;?
                    </DialogTitle>
                    <DialogDescription>
                        {isBlocked
                            ? `Kategori ini masih dipakai ${productsCount} produk, jadi belum bisa dihapus. Pindahkan produknya ke kategori lain, atau nonaktifkan kategori ini kalau produknya masih disewakan.`
                            : 'Kategori ini belum dipakai produk apa pun dan akan dihapus permanen.'}
                    </DialogDescription>
                </DialogHeader>

                {isBlocked ? (
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => onOpenChange(false)}
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                ) : (
                    <Form
                        {...categoryRoutes.destroy.form({
                            category: category.slug,
                        })}
                        options={{ preserveScroll: true }}
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
                                        <button type="submit">
                                            Hapus Kategori
                                        </button>
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}
