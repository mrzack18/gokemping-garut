import { Form, Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import BannerDeleteDialog from '@/components/admin/banner-delete-dialog';
import BannerFormDialog from '@/components/admin/banner-form-dialog';
import FaqDeleteDialog from '@/components/admin/faq-delete-dialog';
import FaqFormDialog from '@/components/admin/faq-form-dialog';
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
import contentRoutes from '@/routes/admin/content';
import type {
    AdminBannerRow,
    AdminContentPageProps,
    AdminFaqRow,
} from '@/types';

/**
 * Manajemen konten unit (PRD section 28, ROADMAP 5.4).
 *
 * Tiga kelompok konten di halaman ini semuanya tampil di halaman publik:
 * informasi layanan dan kontak di halaman pilih layanan serta section kontak,
 * banner di hero carousel landing page, dan FAQ di section FAQ. Semuanya milik
 * unit admin yang login, jadi tidak ada konten yang bocor antar unit.
 */
export default function AdminContent({
    content,
    banners,
    faqs,
}: AdminContentPageProps) {
    const [bannerForm, setBannerForm] = useState<AdminBannerRow | null>(null);
    const [bannerFormOpen, setBannerFormOpen] = useState(false);
    const [bannerToDelete, setBannerToDelete] = useState<AdminBannerRow | null>(
        null,
    );
    const [faqForm, setFaqForm] = useState<AdminFaqRow | null>(null);
    const [faqFormOpen, setFaqFormOpen] = useState(false);
    const [faqToDelete, setFaqToDelete] = useState<AdminFaqRow | null>(null);

    function openBannerForm(banner: AdminBannerRow | null) {
        setBannerForm(banner);
        setBannerFormOpen(true);
    }

    function openFaqForm(faq: AdminFaqRow | null) {
        setFaqForm(faq);
        setFaqFormOpen(true);
    }

    return (
        <>
            <Head title="Konten" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Konten
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Atur informasi layanan, kontak, banner, dan FAQ yang
                            tampil di halaman publik unit ini.
                        </p>
                    </div>

                    <Button asChild variant="outline">
                        <Link href="/">
                            <ExternalLink />
                            Lihat halaman publik
                        </Link>
                    </Button>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.06 }}
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi Layanan & Kontak</CardTitle>
                            <CardDescription>
                                Tampil di halaman pilih layanan dan section
                                kontak landing page. Ketentuan sewa di sini
                                berlaku untuk unit, bukan per produk.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...contentRoutes.profile.form()}
                                options={{ preserveScroll: true }}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="whatsapp">
                                                    Nomor WhatsApp
                                                </Label>
                                                <Input
                                                    id="whatsapp"
                                                    name="whatsapp"
                                                    defaultValue={
                                                        content.whatsapp
                                                    }
                                                    placeholder="6281234567890"
                                                    maxLength={25}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.whatsapp}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="phone">
                                                    Telepon
                                                </Label>
                                                <Input
                                                    id="phone"
                                                    name="phone"
                                                    defaultValue={
                                                        content.phone ?? ''
                                                    }
                                                    placeholder="Opsional"
                                                    maxLength={25}
                                                />
                                                <InputError
                                                    message={errors.phone}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="email">
                                                    Email
                                                </Label>
                                                <Input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    defaultValue={
                                                        content.email ?? ''
                                                    }
                                                    placeholder="Opsional"
                                                    maxLength={255}
                                                />
                                                <InputError
                                                    message={errors.email}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="address">
                                                    Alamat
                                                </Label>
                                                <Input
                                                    id="address"
                                                    name="address"
                                                    defaultValue={
                                                        content.address ?? ''
                                                    }
                                                    placeholder="Alamat unit"
                                                    maxLength={255}
                                                />
                                                <InputError
                                                    message={errors.address}
                                                />
                                            </div>
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="service_intro">
                                                Informasi layanan
                                            </Label>
                                            <Textarea
                                                id="service_intro"
                                                name="service_intro"
                                                defaultValue={
                                                    content.service_intro ?? ''
                                                }
                                                placeholder="Deskripsi singkat layanan unit ini."
                                                rows={3}
                                                maxLength={500}
                                            />
                                            <InputError
                                                message={errors.service_intro}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="service_highlights">
                                                Poin informasi layanan
                                            </Label>
                                            <Textarea
                                                id="service_highlights"
                                                name="service_highlights"
                                                defaultValue={content.service_highlights.join(
                                                    '\n',
                                                )}
                                                placeholder={
                                                    'Satu poin per baris, maksimal 6 baris.\nContoh: Tenda, sleeping bag, dan matras tersedia.'
                                                }
                                                rows={4}
                                                maxLength={1000}
                                            />
                                            <InputError
                                                message={
                                                    errors.service_highlights
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="rental_terms">
                                                Ketentuan sewa
                                            </Label>
                                            <Textarea
                                                id="rental_terms"
                                                name="rental_terms"
                                                defaultValue={
                                                    content.rental_terms ?? ''
                                                }
                                                placeholder="Ketentuan umum unit, mis. wajib membawa KTP."
                                                rows={4}
                                                maxLength={2000}
                                            />
                                            <InputError
                                                message={errors.rental_terms}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="maps_embed_url">
                                                URL embed lokasi
                                            </Label>
                                            <Input
                                                id="maps_embed_url"
                                                name="maps_embed_url"
                                                defaultValue={
                                                    content.maps_embed_url ?? ''
                                                }
                                                placeholder="https://www.google.com/maps/embed?..."
                                                maxLength={2048}
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                Buka Google Maps &rarr; Bagikan
                                                &rarr; Sematkan peta, lalu
                                                tempel URL di dalam atribut
                                                &quot;src&quot;. Hanya tautan
                                                embed Google Maps yang diterima.
                                            </p>
                                            <InputError
                                                message={errors.maps_embed_url}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Menyimpan...'
                                                : 'Simpan Informasi'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.12 }}
                >
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="flex flex-col gap-1">
                                    <CardTitle>Banner Hero</CardTitle>
                                    <CardDescription>
                                        Banner tampil di carousel landing page
                                        sesuai urutan tayang.
                                    </CardDescription>
                                </div>
                                <Button
                                    onClick={() => openBannerForm(null)}
                                    disabled={banners.length >= 5}
                                >
                                    <Plus />
                                    Tambah Banner
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {banners.length === 0 ? (
                                <p className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground">
                                    Belum ada banner. Tambahkan banner supaya
                                    hero carousel landing page terisi.
                                </p>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {banners.map((banner) => (
                                        <div
                                            key={banner.id}
                                            className="flex flex-col overflow-hidden rounded-lg border"
                                        >
                                            {banner.image_url ? (
                                                <img
                                                    src={banner.image_url}
                                                    alt={banner.title}
                                                    className="h-32 w-full object-cover"
                                                />
                                            ) : null}
                                            <div className="flex flex-1 flex-col gap-2 p-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge
                                                        variant={
                                                            banner.is_active
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {banner.is_active
                                                            ? 'Aktif'
                                                            : 'Nonaktif'}
                                                    </Badge>
                                                    <span className="text-xs text-muted-foreground">
                                                        Urutan{' '}
                                                        {banner.sort_order}
                                                    </span>
                                                </div>
                                                <p className="font-medium">
                                                    {banner.title}
                                                </p>
                                                {banner.subtitle ? (
                                                    <p className="text-sm text-muted-foreground">
                                                        {banner.subtitle}
                                                    </p>
                                                ) : null}
                                                <div className="mt-auto flex items-center gap-2 pt-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openBannerForm(
                                                                banner,
                                                            )
                                                        }
                                                    >
                                                        <Pencil />
                                                        Edit
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            setBannerToDelete(
                                                                banner,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 />
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: 0.18 }}
                >
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="flex flex-col gap-1">
                                    <CardTitle>FAQ</CardTitle>
                                    <CardDescription>
                                        Pertanyaan dan jawaban yang tampil di
                                        section FAQ landing page.
                                    </CardDescription>
                                </div>
                                <Button onClick={() => openFaqForm(null)}>
                                    <Plus />
                                    Tambah FAQ
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {faqs.length === 0 ? (
                                <p className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground">
                                    Belum ada FAQ. Selama kosong, landing page
                                    memakai daftar bawaan.
                                </p>
                            ) : (
                                <ul className="flex flex-col divide-y">
                                    {faqs.map((faq) => (
                                        <li
                                            key={faq.id}
                                            className="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div className="flex flex-col gap-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge
                                                        variant={
                                                            faq.is_active
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {faq.is_active
                                                            ? 'Aktif'
                                                            : 'Nonaktif'}
                                                    </Badge>
                                                    <span className="text-xs text-muted-foreground">
                                                        Urutan {faq.sort_order}
                                                    </span>
                                                </div>
                                                <p className="font-medium">
                                                    {faq.question}
                                                </p>
                                                <p className="max-w-2xl text-sm text-muted-foreground">
                                                    {faq.answer}
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 items-center gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openFaqForm(faq)
                                                    }
                                                >
                                                    <Pencil />
                                                    Edit
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setFaqToDelete(faq)
                                                    }
                                                >
                                                    <Trash2 />
                                                    Hapus
                                                </Button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </motion.div>
            </div>

            <BannerFormDialog
                key={
                    bannerForm === null
                        ? 'banner-baru'
                        : `banner-${bannerForm.id}`
                }
                banner={bannerForm}
                open={bannerFormOpen}
                onOpenChange={setBannerFormOpen}
            />

            <BannerDeleteDialog
                banner={bannerToDelete}
                open={bannerToDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setBannerToDelete(null);
                    }
                }}
            />

            <FaqFormDialog
                key={faqForm === null ? 'faq-baru' : `faq-${faqForm.id}`}
                faq={faqForm}
                open={faqFormOpen}
                onOpenChange={setFaqFormOpen}
            />

            <FaqDeleteDialog
                faq={faqToDelete}
                open={faqToDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setFaqToDelete(null);
                    }
                }}
            />
        </>
    );
}

AdminContent.layout = {
    breadcrumbs: [
        {
            title: 'Konten',
            href: contentRoutes.index(),
        },
    ],
};
