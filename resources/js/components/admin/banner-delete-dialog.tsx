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
import bannerRoutes from '@/routes/admin/content/banners';
import type { AdminBannerRow } from '@/types';

/**
 * Konfirmasi hapus banner (PRD section 28, ROADMAP 5.4).
 *
 * Menghapus banner juga menghapus berkas gambarnya dari disk, jadi tindakan
 * ini disebutkan terbuka di dialog. Banner yang hanya ingin disembunyikan
 * sementara bisa dinonaktifkan lewat form ubah.
 */
type BannerDeleteDialogProps = {
    banner: AdminBannerRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function BannerDeleteDialog({
    banner,
    open,
    onOpenChange,
}: BannerDeleteDialogProps) {
    if (banner === null) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Hapus banner &quot;{banner.title}&quot;?
                    </DialogTitle>
                    <DialogDescription>
                        Banner dan berkas gambarnya langsung dihapus. Kalau
                        hanya ingin menyembunyikannya sementara, ubah banner ini
                        dan matikan status tampilnya.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...bannerRoutes.destroy.form({ banner: banner.id })}
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
                            <Button
                                variant="destructive"
                                disabled={processing}
                                asChild
                            >
                                <button type="submit">Hapus Banner</button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
