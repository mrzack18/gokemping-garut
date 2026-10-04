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
import categoryRoutes from '@/routes/admin/categories';
import type { AdminCategory } from '@/types';

/**
 * Satu dialog untuk tambah dan edit kategori (PRD section 23, ROADMAP 4.2).
 *
 * Status kategori sengaja tidak ada di sini. Status punya endpoint sendiri
 * karena yang diubah cuma satu nilai, sedangkan dialog ini mengubah nama,
 * deskripsi, dan urutan sekaligus.
 *
 * Slug juga tidak diedit. Nama kategori menghasilkan slug baru kalau kategori
 * baru ditambahkan, sedangkan kategori yang sudah ada mempertahankan slug
 * lamanya supaya filter kategori di URL katalog publik tidak ikut putus.
 */
type CategoryFormDialogProps = {
    category: AdminCategory | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function CategoryFormDialog({
    category,
    open,
    onOpenChange,
}: CategoryFormDialogProps) {
    const isEditing = category !== null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit Kategori' : 'Tambah Kategori'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Perubahan langsung dipakai katalog untuk kategori ini.'
                            : 'Kategori baru langsung bisa dipilih saat menambah produk.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(isEditing
                        ? categoryRoutes.update.form({
                              category: category.slug,
                          })
                        : categoryRoutes.store.form())}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ resetAndClearErrors, processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama kategori</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={category?.name ?? ''}
                                    placeholder="Contoh: Sewa Sepeda MTB"
                                    maxLength={100}
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">
                                    Urutan tampil
                                </Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    min={0}
                                    max={9999}
                                    defaultValue={category?.sort_order ?? 0}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Angka kecil tampil lebih dulu di katalog.
                                </p>
                                <InputError message={errors.sort_order} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Deskripsi</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    defaultValue={category?.description ?? ''}
                                    placeholder="Opsional. Memberi petunjuk singkat untuk penyewa."
                                    rows={3}
                                    maxLength={1000}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        resetAndClearErrors();
                                        onOpenChange(false);
                                    }}
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
                                            : 'Tambah Kategori'}
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
