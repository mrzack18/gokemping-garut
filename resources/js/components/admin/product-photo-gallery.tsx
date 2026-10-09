import { Form } from '@inertiajs/react';
import { useRef, useState } from 'react';
import FileInput from '@/components/admin/file-input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import imageRoutes from '@/routes/admin/products/images';
import type { AdminProductDetail, AdminProductPhoto } from '@/types';
import { ImageOff, Star, Trash2, Upload } from 'lucide-react';

/**
 * Panel galeri foto produk (PRD section 23 & 33, ROADMAP 4.3).
 *
 * Panel ini formnya terpisah dari form produk. Kalau foto ikut dikirim bersama
 * produk, mengunggah satu foto akan mengirim ulang seluruh isian produk yang
 * belum disimpan, dan Inertia akan merender ulang form itu.
 *
 * Batas jumlah foto ditegakkan di backend. Sisa kapasitas yang dikirim ke sini
 * hanya supaya tombol unggah dimatikan lebih dulu: lebih baik tombolnya terlihat
 * tidak bisa dipakai daripada membiarkan admin memilih berkas lalu mendapat
 * penolakan.
 *
 * Hapus foto memakai dialog konfirmasi. Berkas foto dihapus permanen dari disk
 * tanpa ada tempat lain yang menyimpan salinannya, jadi salah klik tidak bisa
 * diperbaiki dengan memuat ulang halaman.
 */
type ProductPhotoGalleryProps = {
    product: AdminProductDetail;
    maxImages: number;
    remainingImages: number;
};

export default function ProductPhotoGallery({
    product,
    maxImages,
    remainingImages,
}: ProductPhotoGalleryProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [photoToDelete, setPhotoToDelete] =
        useState<AdminProductPhoto | null>(null);

    const isFull = remainingImages <= 0;

    return (
        <Card className="rounded-lg shadow-none">
            <CardHeader>
                <CardTitle className="font-display text-base">
                    Foto Produk
                </CardTitle>
                <CardDescription>
                    {product.photos.length === 0
                        ? 'Belum ada foto. Produk tanpa foto tetap tampil di katalog dengan gambar cadangan.'
                        : `${product.photos.length} dari ${maxImages} foto terpakai.` +
                          (isFull ? '' : ` Sisa ${remainingImages} foto.`)}
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-col gap-6">
                {product.photos.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 rounded-lg border border-dashed py-10 text-center">
                        <ImageOff
                            aria-hidden="true"
                            className="size-8 text-muted-foreground"
                        />
                        <p className="font-medium">Belum ada foto</p>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Foto pertama yang diunggah otomatis menjadi foto
                            utama produk ini.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {product.photos.map((photo) => (
                            <div
                                key={photo.id}
                                className="flex flex-col gap-2 rounded-lg border p-2"
                            >
                                <div className="aspect-square overflow-hidden rounded-md bg-muted">
                                    <img
                                        src={photo.url}
                                        alt={`Foto produk ${product.name}`}
                                        loading="lazy"
                                        className="size-full object-cover"
                                    />
                                </div>

                                <div className="flex items-center justify-between gap-1">
                                    {photo.is_primary ? (
                                        <span className="flex items-center gap-1 rounded-full border border-success/25 bg-success/10 px-2 py-0.5 text-xs font-medium text-success">
                                            <Star
                                                aria-hidden="true"
                                                className="size-3"
                                            />
                                            Utama
                                        </span>
                                    ) : (
                                        <Form
                                            {...imageRoutes.update.form({
                                                product: product.slug,
                                                image: photo.id,
                                            })}
                                            options={{
                                                preserveScroll: true,
                                            }}
                                        >
                                            {({ processing }) => (
                                                <button
                                                    type="submit"
                                                    disabled={processing}
                                                    className="flex items-center gap-1 text-xs font-medium text-pine-700 underline-offset-4 hover:underline disabled:opacity-50 dark:text-pine-600"
                                                >
                                                    <Star
                                                        aria-hidden="true"
                                                        className="size-3"
                                                    />
                                                    Jadikan utama
                                                </button>
                                            )}
                                        </Form>
                                    )}

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setPhotoToDelete(photo)}
                                        aria-label={`Hapus foto ${photo.id}`}
                                        className="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 aria-hidden="true" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Form
                    {...imageRoutes.store.form({
                        product: product.slug,
                    })}
                    encType="multipart/form-data"
                    options={{
                        preserveScroll: true,
                    }}
                    onSuccess={() => {
                        if (inputRef.current !== null) {
                            inputRef.current.value = '';
                        }
                    }}
                    className="flex flex-col gap-2 border-t pt-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex flex-wrap items-end justify-between gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="images">Unggah foto</Label>
                                    <FileInput
                                        ref={inputRef}
                                        id="images"
                                        name="images[]"
                                        accept="image/jpeg,image/png,image/webp"
                                        multiple={remainingImages > 1}
                                        disabled={isFull}
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        JPG, PNG, atau WebP, maksimal 5 MB per
                                        foto. Foto dikompresi ulang jadi WebP
                                        dengan sisi terpanjang 1200 piksel.
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing || isFull}
                                >
                                    <Upload aria-hidden="true" />
                                    {processing ? 'Mengunggah...' : 'Unggah'}
                                </Button>
                            </div>

                            {isFull ? (
                                <p className="text-sm text-muted-foreground">
                                    Galeri sudah penuh. Hapus salah satu foto
                                    untuk menambah foto baru.
                                </p>
                            ) : null}

                            {errors.images ? (
                                <p className="text-sm text-destructive">
                                    {errors.images}
                                </p>
                            ) : null}
                        </>
                    )}
                </Form>
            </CardContent>

            <Dialog
                open={photoToDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPhotoToDelete(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus foto produk?</DialogTitle>
                        <DialogDescription>
                            Berkas fotonya dihapus permanen dari server. Kalau
                            foto ini sedang jadi foto utama, foto berikutnya
                            otomatis menggantikannya.
                        </DialogDescription>
                    </DialogHeader>

                    {photoToDelete !== null ? (
                        <Form
                            {...imageRoutes.destroy.form({
                                product: product.slug,
                                image: photoToDelete.id,
                            })}
                            options={{
                                preserveScroll: true,
                            }}
                            onSuccess={() => setPhotoToDelete(null)}
                        >
                            {({ processing }) => (
                                <>
                                    <DialogFooter className="gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() =>
                                                setPhotoToDelete(null)
                                            }
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button type="submit">
                                                Hapus Foto
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    ) : null}
                </DialogContent>
            </Dialog>
        </Card>
    );
}
