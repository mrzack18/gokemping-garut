import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import BookingMobileBar from '@/components/booking/booking-mobile-bar';
import BookingSteps from '@/components/booking/booking-steps';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useClipboard } from '@/hooks/use-clipboard';
import { formatRupiah } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import bookingRoutes from '@/routes/booking';
import type { BookingPaymentPageProps } from '@/types';
import {
    AlertTriangle,
    ArrowLeft,
    Check,
    Copy,
    FileText,
    ImageUp,
    Trash2,
} from 'lucide-react';

/**
 * Batas ukuran yang sama dengan validasi server, diperiksa lebih awal supaya
 * penyewa tidak menunggu berkas 5 MB selesai terunggah sebelum tahu kalau
 * berkasnya ditolak.
 */
const MAX_PROOF_BYTES = 5 * 1024 * 1024;

const ACCEPTED_PROOF_TYPES = 'image/jpg,image/jpeg,image/png,image/webp';

/**
 * Halaman pembayaran sesuai metode yang dipilih di halaman review
 * (ROADMAP 3.9, PRD section 17).
 *
 * Nomor rekening, nama bank, dan gambar QRIS dibaca dari `payment_methods`
 * milik unit bisnis, jadi frontend tidak pernah menyimpan data pembayaran
 * secara hardcoded.
 *
 * Upload bukti pembayaran dibangun di ROADMAP 3.10 (BR-08). Berkas dikirim ke
 * server untuk disimpan di disk publik. Pratinjau lokal hanya dipakai selama
 * berkas belum tersimpan, lalu dibersihkan supaya `URL.createObjectURL` tidak
 * menahan memori browser.
 *
 * Tombol konfirmasi menyimpan booking di ROADMAP 3.11. Setelah booking
 * tersimpan, penyewa diarahkan ke halaman konfirmasi yang berisi kode booking;
 * tombol WhatsApp di halaman itu dibangun di ROADMAP 3.12.
 */
export default function BookingPayment({
    business,
    businesses,
    method,
    product,
    period,
    availability,
    pricing,
    proof,
}: BookingPaymentPageProps) {
    const [copiedText, copy] = useClipboard();
    const [localPreview, setLocalPreview] = useState<string | null>(null);
    const [showCopyFeedback, setShowCopyFeedback] = useState(false);
    const copyTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const proofForm = useForm<{ proof: File | null }>({ proof: null });
    const removeForm = useForm<{ proof?: string }>({});
    /**
     * Form konfirmasi tidak mengirim data apa pun, tetapi tetap punya dua key
     * error dari server: `proof` kalau bukti belum diunggah, dan `period` kalau
     * stok sudah habis saat booking disimpan.
     */
    const bookingForm = useForm<{ proof?: string; period?: string }>({});

    const selectedFile = proofForm.data.proof;

    /**
     * Pratinjau lokal hidup selama ada berkas yang belum diunggah. Setelah
     * server menyimpan berkas, halaman dimuat ulang dengan pratinjau dari disk
     * dan objek URL lokal sudah tidak dibutuhkan.
     */
    useEffect(() => {
        if (selectedFile === null) {
            setLocalPreview(null);

            return;
        }

        const objectUrl = URL.createObjectURL(selectedFile);
        setLocalPreview(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [selectedFile]);

    useEffect(
        () => () => {
            if (copyTimeoutRef.current !== null) {
                clearTimeout(copyTimeoutRef.current);
            }
        },
        [],
    );

    const routes = routesFor(business.slug);

    if (routes === null) {
        return null;
    }

    const isBankTransfer = method.type === 'bank_transfer';
    const isQris = method.type === 'qris';
    const isCopied = showCopyFeedback && copiedText === method.account_number;

    const proofStoreUrl = routes.payment.proof.store.url();
    const proofDestroyUrl = routes.payment.proof.destroy.url();
    const bookingStoreUrl = routes.store.url();

    const previewUrl = localPreview ?? proof?.url ?? null;
    const previewName = selectedFile?.name ?? proof?.name ?? null;
    const hasSavedProof = proof !== null;
    const isBusy =
        proofForm.processing || removeForm.processing || bookingForm.processing;

    /**
     * Booking tidak boleh disimpan sebelum bukti pembayaran tersimpan di
     * server. Berkas yang masih dipilih di form upload belum ada di disk, jadi
     * menyimpan booking akan menghasilkan pembayaran tanpa bukti.
     */
    const canConfirm =
        !isBusy &&
        availability.is_available &&
        (!method.requires_proof || hasSavedProof);

    function selectProof(event: React.ChangeEvent<HTMLInputElement>): void {
        const file = event.target.files?.[0] ?? null;

        /**
         * Memilih berkas yang sama dua kali tidak memicu event `change`, jadi
         * nilainya direset lebih dulu supaya penyewa tetap bisa memilih ulang
         * berkas yang sama setelah membatalkan pilihannya.
         */
        event.target.value = '';

        if (file === null) {
            proofForm.setData('proof', null);
            proofForm.clearErrors('proof');

            return;
        }

        /**
         * Ukuran diperiksa di browser supaya berkas 5 MB tidak perlu sampai ke
         * server dulu untuk ditolak. Server tetap memvalidasi ulang karena
         * pemeriksaan di browser hanya untuk kenyamanan.
         */
        if (file.size > MAX_PROOF_BYTES) {
            proofForm.setData('proof', null);
            proofForm.setError(
                'proof',
                'Ukuran bukti pembayaran maksimal 5 MB.',
            );

            return;
        }

        proofForm.setData('proof', file);
        proofForm.clearErrors('proof');
    }

    function submitProof(): void {
        proofForm.post(proofStoreUrl);
    }

    function destroyProof(): void {
        proofForm.setData('proof', null);
        proofForm.clearErrors('proof');
        removeForm.delete(proofDestroyUrl);
    }

    function confirmBooking(): void {
        bookingForm.post(bookingStoreUrl);
    }

    async function copyAccountNumber(): Promise<void> {
        const accountNumber = method.account_number;

        if (accountNumber === null || !(await copy(accountNumber))) {
            return;
        }

        setShowCopyFeedback(true);

        if (copyTimeoutRef.current !== null) {
            clearTimeout(copyTimeoutRef.current);
        }

        copyTimeoutRef.current = setTimeout(
            () => setShowCopyFeedback(false),
            2000,
        );
    }

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={`Pembayaran ${method.label}`} />

            <section className="border-b border-border bg-sand-50">
                <div className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                    >
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-3"
                        >
                            <Link href={routes.review.url()}>
                                <ArrowLeft className="size-4" />
                                Kembali ke review
                            </Link>
                        </Button>

                        <h1 className="mt-4 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
                            Pembayaran {method.label}
                        </h1>
                        <p className="mt-3 max-w-2xl text-muted-foreground">
                            {product.name} · {period.quantity} unit ·{' '}
                            {period.duration_label}
                        </p>
                    </motion.div>
                    <div className="mt-7 max-w-2xl">
                        <BookingSteps current={4} />
                    </div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 py-8 pb-28 sm:px-6 sm:py-10 sm:pb-28 lg:pb-14">
                <div className="grid gap-8 lg:grid-cols-3">
                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.45 }}
                        className="space-y-6 lg:col-span-2"
                    >
                        {!availability.is_available ? (
                            <div
                                role="alert"
                                className="flex items-start gap-3 rounded-md border border-warning-border bg-warning-bg p-4 text-sm text-warning-text"
                            >
                                <AlertTriangle className="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <p className="font-medium">
                                        Ketersediaan berubah
                                    </p>
                                    <p className="mt-1 text-warning-text/90">
                                        Unit yang tersisa hanya{' '}
                                        {availability.available}, sedangkan Anda
                                        memilih {availability.requested}.
                                        Kembali ke review untuk mengubah jadwal.
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="mt-3"
                                    >
                                        <Link href={routes.review.url()}>
                                            Kembali ke review
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ) : null}

                        {isQris ? (
                            <Card className="rounded-lg shadow-none">
                                <CardHeader>
                                    <CardTitle className="font-display text-base">
                                        Scan QRIS
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    {method.merchant_name !== null ? (
                                        <p className="text-sm text-muted-foreground">
                                            Merchant:{' '}
                                            <span className="font-medium text-foreground">
                                                {method.merchant_name}
                                            </span>
                                        </p>
                                    ) : null}

                                    {method.qris_image_url !== null ? (
                                        <div className="flex justify-center">
                                            <img
                                                src={method.qris_image_url}
                                                alt={`QRIS ${method.merchant_name ?? business.name}`}
                                                width={280}
                                                height={280}
                                                className="rounded-lg border bg-white p-2"
                                            />
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Gambar QRIS belum tersedia.
                                        </p>
                                    )}

                                    <p className="text-sm text-muted-foreground">
                                        Scan dengan aplikasi pembayaran Anda,
                                        lalu simpan bukti transfernya.
                                    </p>
                                </CardContent>
                            </Card>
                        ) : null}

                        {isBankTransfer ? (
                            <Card className="rounded-lg shadow-none">
                                <CardHeader>
                                    <CardTitle className="font-display text-base">
                                        Transfer Bank
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    <dl className="space-y-4">
                                        <div className="flex items-start justify-between gap-6">
                                            <dt className="text-sm text-muted-foreground">
                                                Bank
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {method.bank_name ?? '-'}
                                            </dd>
                                        </div>
                                        <div className="flex items-start justify-between gap-6">
                                            <dt className="text-sm text-muted-foreground">
                                                Nomor rekening
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {method.account_number ?? '-'}
                                            </dd>
                                        </div>
                                        <div className="flex items-start justify-between gap-6">
                                            <dt className="text-sm text-muted-foreground">
                                                Atas nama
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {method.account_name ?? '-'}
                                            </dd>
                                        </div>
                                    </dl>

                                    {method.account_number !== null ? (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                void copyAccountNumber()
                                            }
                                        >
                                            {isCopied ? (
                                                <Check className="size-4" />
                                            ) : (
                                                <Copy className="size-4" />
                                            )}
                                            {isCopied
                                                ? 'Nomor rekening tersalin'
                                                : 'Salin Nomor Rekening'}
                                        </Button>
                                    ) : null}
                                </CardContent>
                            </Card>
                        ) : null}

                        {!isQris && !isBankTransfer ? (
                            <Card className="rounded-lg shadow-none">
                                <CardHeader>
                                    <CardTitle className="font-display text-base">
                                        Pembayaran langsung
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">
                                        {method.instructions ??
                                            'Pembayaran dilakukan langsung kepada admin.'}
                                    </p>
                                </CardContent>
                            </Card>
                        ) : null}

                        {method.requires_proof ? (
                            <Card className="rounded-lg shadow-none">
                                <CardHeader>
                                    <CardTitle className="font-display text-base">
                                        Bukti Pembayaran
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    <p className="text-sm text-muted-foreground">
                                        Unggah bukti pembayaran setelah
                                        mentransfer. Bukti wajib untuk metode
                                        ini dan akan diperiksa admin sebelum
                                        booking dikonfirmasi.
                                    </p>

                                    {previewUrl !== null &&
                                    previewName !== null ? (
                                        <div className="space-y-3">
                                            <div className="flex items-center gap-3 rounded-lg border p-3">
                                                <FileText
                                                    aria-hidden="true"
                                                    className="size-5 shrink-0 text-muted-foreground"
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">
                                                        {previewName}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {hasSavedProof
                                                            ? 'Bukti sudah diunggah'
                                                            : 'Belum diunggah'}
                                                    </p>
                                                </div>
                                                {hasSavedProof ? (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={isBusy}
                                                        onClick={destroyProof}
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Hapus
                                                    </Button>
                                                ) : null}
                                            </div>

                                            <img
                                                src={previewUrl}
                                                alt={`Pratinjau bukti pembayaran ${previewName}`}
                                                className="max-h-96 w-full rounded-lg border object-contain"
                                            />
                                        </div>
                                    ) : null}

                                    <div className="space-y-2 rounded-md border border-dashed border-border bg-sand-50 p-4">
                                        <Label htmlFor="proof">
                                            Pilih berkas bukti
                                        </Label>
                                        <Input
                                            id="proof"
                                            name="proof"
                                            type="file"
                                            accept={ACCEPTED_PROOF_TYPES}
                                            onChange={selectProof}
                                            disabled={isBusy}
                                            aria-invalid={Boolean(
                                                proofForm.errors.proof,
                                            )}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Format JPG, JPEG, PNG, atau WebP.
                                            Maksimal 5 MB.
                                        </p>
                                        <InputError
                                            message={proofForm.errors.proof}
                                        />
                                    </div>

                                    {removeForm.errors.proof ? (
                                        <InputError
                                            message={removeForm.errors.proof}
                                        />
                                    ) : null}

                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={
                                            selectedFile === null || isBusy
                                        }
                                        onClick={submitProof}
                                    >
                                        <ImageUp className="size-4" />
                                        {proofForm.processing
                                            ? 'Mengunggah...'
                                            : hasSavedProof
                                              ? 'Ganti Bukti'
                                              : 'Unggah Bukti'}
                                    </Button>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card className="rounded-lg shadow-none">
                                <CardHeader>
                                    <CardTitle className="font-display text-base">
                                        Bukti Pembayaran
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">
                                        Bukti pembayaran tidak wajib untuk
                                        pembayaran cash (BR-08).
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                    >
                        <Card className="rounded-lg shadow-none lg:sticky lg:top-24">
                            <CardHeader>
                                <CardTitle className="font-display text-base">
                                    Ringkasan
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <dl className="space-y-4">
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Produk
                                        </dt>
                                        <dd className="text-right text-sm font-medium">
                                            {product.name}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Periode
                                        </dt>
                                        <dd className="text-right text-sm font-medium">
                                            {period.start_date_label} s/d{' '}
                                            {period.end_date_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Durasi
                                        </dt>
                                        <dd className="text-sm font-medium">
                                            {period.duration_label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Metode
                                        </dt>
                                        <dd className="text-sm font-medium">
                                            {method.label}
                                        </dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-6">
                                        <dt className="text-sm text-muted-foreground">
                                            Bukti
                                        </dt>
                                        <dd className="text-right text-sm font-medium">
                                            {method.requires_proof
                                                ? hasSavedProof
                                                    ? 'Sudah diunggah'
                                                    : 'Belum diunggah'
                                                : 'Tidak wajib'}
                                        </dd>
                                    </div>
                                </dl>

                                <Separator />

                                <div className="flex items-baseline justify-between gap-4">
                                    <p className="text-sm text-muted-foreground">
                                        Total pembayaran
                                    </p>
                                    <p className="text-2xl font-semibold tracking-tight">
                                        {formatRupiah(pricing.total)}
                                    </p>
                                </div>

                                {method.requires_proof && !hasSavedProof ? (
                                    <p className="text-xs text-warning-text">
                                        Unggah bukti pembayaran dulu sebelum
                                        menyimpan booking.
                                    </p>
                                ) : null}

                                {bookingForm.errors.proof ? (
                                    <InputError
                                        message={bookingForm.errors.proof}
                                    />
                                ) : null}

                                {bookingForm.errors.period ? (
                                    <InputError
                                        message={bookingForm.errors.period}
                                    />
                                ) : null}

                                <Button
                                    type="button"
                                    size="lg"
                                    className="w-full"
                                    disabled={!canConfirm}
                                    onClick={confirmBooking}
                                >
                                    {bookingForm.processing
                                        ? 'Menyimpan...'
                                        : 'Konfirmasi Booking'}
                                </Button>
                                <p className="text-xs text-muted-foreground">
                                    Booking akan tersimpan dengan status
                                    menunggu konfirmasi admin.
                                </p>
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>
            </section>
            <BookingMobileBar
                amount={formatRupiah(pricing.total)}
                label={bookingForm.processing ? 'Menyimpan…' : 'Konfirmasi'}
                disabled={!canConfirm}
                onClick={confirmBooking}
            />
        </PublicLayout>
    );
}

/**
 * Route Wayfinder untuk halaman booking satu unit. `null` kalau slug unit tidak
 * terdaftar, supaya halaman tidak pernah merender tautan ke URL yang tidak ada.
 */
function routesFor(slug: string) {
    if (slug === 'gokemping') {
        return bookingRoutes.gokemping;
    }

    if (slug === 'sewa-sepeda-garut') {
        return bookingRoutes.sewaSepedaGarut;
    }

    return null;
}
