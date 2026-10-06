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
import faqRoutes from '@/routes/admin/content/faqs';
import type { AdminFaqRow } from '@/types';

/**
 * Konfirmasi hapus FAQ (PRD section 28, ROADMAP 5.4).
 *
 * FAQ yang hanya ingin disembunyikan sementara bisa dinonaktifkan lewat form
 * ubah, jadi dialog ini khusus untuk penghapusan permanen.
 */
type FaqDeleteDialogProps = {
    faq: AdminFaqRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function FaqDeleteDialog({
    faq,
    open,
    onOpenChange,
}: FaqDeleteDialogProps) {
    if (faq === null) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus FAQ ini?</DialogTitle>
                    <DialogDescription>
                        &quot;{faq.question}&quot; akan dihapus permanen dari
                        landing page. Kalau hanya ingin menyembunyikannya,
                        matikan status tampilnya lewat form ubah.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...faqRoutes.destroy.form({ faq: faq.id })}
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
                                <button type="submit">Hapus FAQ</button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
