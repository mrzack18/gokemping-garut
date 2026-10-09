import { Form } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import ActiveBadge from '@/components/admin/active-badge';
import FileInput from '@/components/admin/file-input';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import paymentSettingRoutes from '@/routes/admin/payment-settings';
import type { AdminPaymentMethodSetting } from '@/types';

/**
 * Satu kartu pengaturan metode pembayaran (PRD section 27, ROADMAP 4.7).
 *
 * Setiap metode punya form dan endpoint sendiri supaya menyimpan satu kartu
 * tidak memvalidasi atau mengirim ulang field kartu lain: data rekening yang
 * salah tidak boleh menghalangi admin memperbaiki keterangan cash.
 *
 * Metode yang aktif tetapi datanya belum lengkap diberi badge "Belum lengkap",
 * bukan diblokir saat disimpan. Halaman pembayaran publik tetap menampilkan
 * metode seperti itu tanpa `is_ready`, dan admin perlu melihat peringatannya di
 * tempat ia bisa memperbaikinya.
 */
export default function PaymentSettingCard({
    method,
}: {
    method: AdminPaymentMethodSetting;
}) {
    return (
        <Card className="flex flex-col rounded-lg shadow-none">
            <CardHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <CardTitle className="font-display text-base">
                        {method.label}
                    </CardTitle>
                    <ActiveBadge isActive={method.is_active} />
                    {method.is_active && !method.is_ready ? (
                        <Badge variant="destructive">Belum lengkap</Badge>
                    ) : null}
                </div>
                <CardDescription>{description(method)}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col">
                <Form
                    {...paymentSettingRoutes.update.form({
                        type: method.type,
                    })}
                    encType="multipart/form-data"
                    options={{ preserveScroll: true }}
                    className="flex flex-1 flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex flex-1 flex-col gap-4">
                                {method.type === 'cash' ? (
                                    <div className="grid gap-2">
                                        <Label htmlFor="instructions">
                                            Keterangan pembayaran
                                        </Label>
                                        <Textarea
                                            id="instructions"
                                            name="instructions"
                                            defaultValue={
                                                method.instructions ?? ''
                                            }
                                            placeholder="Contoh: Pembayaran dilakukan langsung di lokasi saat pengambilan barang."
                                            rows={4}
                                            maxLength={500}
                                        />
                                        <InputError
                                            message={errors.instructions}
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            Keterangan ini tampil di halaman
                                            pembayaran saat penyewa memilih
                                            cash.
                                        </p>
                                    </div>
                                ) : null}

                                {method.type === 'qris' ? (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="merchant_name">
                                                Nama merchant
                                            </Label>
                                            <Input
                                                id="merchant_name"
                                                name="merchant_name"
                                                defaultValue={
                                                    method.merchant_name ?? ''
                                                }
                                                placeholder="Nama yang terdaftar di QRIS"
                                                maxLength={255}
                                            />
                                            <InputError
                                                message={errors.merchant_name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="qris_image">
                                                Gambar QRIS
                                            </Label>
                                            {method.qris_image_url === null ? (
                                                <div className="flex items-center gap-3 rounded-lg border border-dashed border-border bg-muted/30 p-3 text-sm text-muted-foreground">
                                                    <ImageOff
                                                        aria-hidden="true"
                                                        className="size-5 shrink-0"
                                                    />
                                                    Belum ada gambar. QRIS tidak
                                                    bisa dipilih penyewa sebelum
                                                    gambarnya diunggah.
                                                </div>
                                            ) : (
                                                <img
                                                    src={method.qris_image_url}
                                                    alt={`QRIS ${method.merchant_name ?? method.label}`}
                                                    width={160}
                                                    height={160}
                                                    className="h-40 w-40 rounded-md border border-border bg-white object-contain p-1"
                                                />
                                            )}
                                            <FileInput
                                                id="qris_image"
                                                name="qris_image"
                                                accept="image/jpeg,image/png,image/webp"
                                            />
                                            <InputError
                                                message={errors.qris_image}
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                JPG, PNG, atau WebP, maksimal 5
                                                MB. Unggah berkas baru untuk
                                                menggantikan gambar yang
                                                sekarang.
                                            </p>
                                        </div>
                                    </>
                                ) : null}

                                {method.type === 'bank_transfer' ? (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="bank_name">
                                                Nama bank
                                            </Label>
                                            <Input
                                                id="bank_name"
                                                name="bank_name"
                                                defaultValue={
                                                    method.bank_name ?? ''
                                                }
                                                placeholder="Contoh: BCA"
                                                maxLength={255}
                                            />
                                            <InputError
                                                message={errors.bank_name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="account_number">
                                                Nomor rekening
                                            </Label>
                                            <Input
                                                id="account_number"
                                                name="account_number"
                                                defaultValue={
                                                    method.account_number ?? ''
                                                }
                                                placeholder="Contoh: 1234567890"
                                                maxLength={50}
                                            />
                                            <InputError
                                                message={errors.account_number}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="account_name">
                                                Nama pemilik rekening
                                            </Label>
                                            <Input
                                                id="account_name"
                                                name="account_name"
                                                defaultValue={
                                                    method.account_name ?? ''
                                                }
                                                placeholder="Nama sesuai buku rekening"
                                                maxLength={255}
                                            />
                                            <InputError
                                                message={errors.account_name}
                                            />
                                        </div>
                                    </>
                                ) : null}

                                <label className="flex items-start gap-3 rounded-lg border border-border bg-muted/30 p-3 text-sm">
                                    {/*
                                     * Checkbox yang tidak dicentang tidak
                                     * mengirim apa pun, jadi hidden di depannya
                                     * membuat `is_active` selalu terkirim.
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
                                        defaultChecked={method.is_active}
                                        className="mt-0.5 size-4 accent-pine-700 dark:accent-pine-600"
                                    />
                                    <span className="grid gap-0.5">
                                        <span className="font-medium">
                                            Tampilkan metode ini
                                        </span>
                                        <span className="text-muted-foreground">
                                            Metode nonaktif tidak muncul di
                                            halaman pembayaran, tapi datanya
                                            tetap tersimpan.
                                        </span>
                                    </span>
                                </label>
                                <InputError message={errors.is_active} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="self-start"
                            >
                                {processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

/**
 * Penjelasan singkat per metode, memakai aturan yang sama dengan halaman
 * pembayaran publik: QRIS dan transfer memerlukan bukti, cash tidak.
 */
function description(method: AdminPaymentMethodSetting): string {
    switch (method.type) {
        case 'cash':
            return 'Pembayaran di lokasi. Cash tidak memerlukan bukti unggahan, jadi selalu siap dipakai.';
        case 'qris':
            return 'Gambar QRIS dan nama merchant yang tampil saat penyewa memilih QRIS. Gambar wajib ada supaya metode ini bisa dipilih.';
        case 'bank_transfer':
            return 'Rekening tujuan transfer. Nama bank dan nomor rekening wajib ada supaya metode ini bisa dipilih.';
        default:
            return 'Konfigurasi metode pembayaran.';
    }
}
