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
import bannerRoutes from '@/routes/admin/content/banners';
import type { AdminBannerRow } from '@/types';

/**
 * Form tambah dan ubah banner (PRD section 28, ROADMAP 5.4).
 *
 * Gambar wajib saat menambah, opsional saat mengubah: membiarkannya kosong
 * mempertahankan gambar lama, jadi admin bisa memperbaiki judul atau tautan
 * tanpa mengunggah ulang. Urutan tayang yang lebih kecil tampil lebih dulu.
 */
type BannerFormDialogProps = {
    banner: AdminBannerRow | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function BannerFormDialog({
    banner,
    open,
    onOpenChange,
}: BannerFormDialogProps) {
    const isEditing = banner !== null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Ubah Banner' : 'Tambah Banner'}
                    </DialogTitle>
                    <DialogDescription>
                        Banner tampil di hero carousel landing page. Gambar
                        berformat JPG, PNG, atau WebP maksimal 5 MB dan
                        dikompresi otomatis.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(isEditing
                        ? bannerRoutes.update.form({ banner: banner.id })
                        : bannerRoutes.store.form())}
                    encType="multipart/form-data"
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={banner?.title ?? ''}
                                    placeholder="Contoh: Promo Sewa Tenda Akhir Pekan"
                                    maxLength={150}
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="subtitle">Subjudul</Label>
                                <Textarea
                                    id="subtitle"
                                    name="subtitle"
                                    defaultValue={banner?.subtitle ?? ''}
                                    placeholder="Keterangan singkat, opsional."
                                    rows={2}
                                    maxLength={255}
                                />
                                <InputError message={errors.subtitle} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="image">Gambar</Label>
                                {banner?.image_url ? (
                                    <img
                                        src={banner.image_url}
                                        alt={banner.title}
                                        className="h-32 w-full rounded-md border object-cover"
                                    />
                                ) : null}
                                <input
                                    id="image"
                                    name="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    className="text-sm"
                                    required={!isEditing}
                                />
                                <p className="text-sm text-muted-foreground">
                                    {isEditing
                                        ? 'Biarkan kosong untuk mempertahankan gambar yang sekarang.'
                                        : 'JPG, PNG, atau WebP, maksimal 5 MB.'}
                                </p>
                                <InputError message={errors.image} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="link_url">Tautan</Label>
                                <Input
                                    id="link_url"
                                    name="link_url"
                                    defaultValue={banner?.link_url ?? ''}
                                    placeholder="/gokemping atau https://..."
                                    maxLength={2048}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Opsional. Isi dengan alamat internal
                                    (diawali &quot;/&quot;) atau tautan luar.
                                </p>
                                <InputError message={errors.link_url} />
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
                                    defaultValue={banner?.sort_order ?? 0}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Angka kecil tampil lebih dulu.
                                </p>
                                <InputError message={errors.sort_order} />
                            </div>

                            <label className="flex items-start gap-3 rounded-lg border p-3 text-sm">
                                {/*
                                 * Hidden di depan checkbox membuat
                                 * `is_active` selalu terkirim walau tidak
                                 * dicentang.
                                 */}
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={banner?.is_active ?? true}
                                    className="mt-0.5 size-4"
                                />
                                <span className="grid gap-0.5">
                                    <span className="font-medium">
                                        Tampilkan banner
                                    </span>
                                    <span className="text-muted-foreground">
                                        Banner nonaktif tetap tersimpan tapi
                                        tidak tampil di landing page.
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
                                            : 'Tambah Banner'}
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
