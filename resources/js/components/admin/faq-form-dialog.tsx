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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import faqRoutes from '@/routes/admin/content/faqs';
import type { AdminFaqRow } from '@/types';

/**
 * Form tambah dan ubah FAQ (PRD section 28, ROADMAP 5.4).
 *
 * Urutan tayang mengikuti angka `sort_order`, dan FAQ yang belum siap
 * ditayangkan bisa disimpan dengan status nonaktif lalu dinyalakan lagi tanpa
 * mengetik ulang.
 */
type FaqFormDialogProps = {
    faq: AdminFaqRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function FaqFormDialog({
    faq,
    open,
    onOpenChange,
}: FaqFormDialogProps) {
    const isEditing = faq !== null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah FAQ' : 'Tambah FAQ'}
                    </DialogTitle>
                    <DialogDescription>
                        FAQ tampil di landing page dengan nama unit ini sebagai
                        penanda, dan jawabannya bisa berbeda dari unit lain.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(isEditing
                        ? faqRoutes.update.form({ faq: faq.id })
                        : faqRoutes.store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="question">Pertanyaan</Label>
                                <Input
                                    id="question"
                                    name="question"
                                    defaultValue={faq?.question ?? ''}
                                    placeholder="Contoh: Apakah harus membawa KTP?"
                                    maxLength={255}
                                    required
                                />
                                <InputError message={errors.question} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="answer">Jawaban</Label>
                                <Textarea
                                    id="answer"
                                    name="answer"
                                    defaultValue={faq?.answer ?? ''}
                                    placeholder="Jawaban singkat yang bisa ditindaklanjuti."
                                    rows={4}
                                    maxLength={2000}
                                    required
                                />
                                <InputError message={errors.answer} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">
                                    Urutan tayang
                                </Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    min={0}
                                    max={9999}
                                    defaultValue={faq?.sort_order ?? 0}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Angka kecil tampil lebih dulu.
                                </p>
                                <InputError message={errors.sort_order} />
                            </div>

                            <label className="flex items-start gap-3 rounded-lg border border-border bg-muted/30 p-3 text-sm">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={faq?.is_active ?? true}
                                    className="mt-0.5 size-4 accent-pine-700 dark:accent-pine-600"
                                />
                                <span className="grid gap-0.5">
                                    <span className="font-medium">
                                        Tampilkan FAQ
                                    </span>
                                    <span className="text-muted-foreground">
                                        FAQ nonaktif tetap tersimpan tapi tidak
                                        tampil di landing page.
                                    </span>
                                </span>
                            </label>
                            <InputError message={errors.is_active} />

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
                                    disabled={processing}
                                    asChild
                                >
                                    <button type="submit">
                                        {isEditing
                                            ? 'Simpan Perubahan'
                                            : 'Tambah FAQ'}
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
