import { Form, Head, Link, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    ImageOff,
    Package,
    Pencil,
    Plus,
    Power,
    RotateCcw,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import ProductDeleteDialog from '@/components/admin/product-delete-dialog';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import productRoutes from '@/routes/admin/products';
import type {
    AdminProductFilters,
    AdminProductRow,
    AdminProductsPageProps,
} from '@/types';

const ALL_CATEGORIES = 'semua';
const ALL_STATUS = 'semua';

/**
 * Daftar produk admin (PRD section 23, ROADMAP 4.3).
 *
 * Daftar ini sengaja lebih luas dari katalog publik: produk nonaktif ikut
 * tampil karena tugas utama admin di halaman ini justru mengelola produk yang
 * sedang tidak bisa disewakan, dan produk yang sudah di-soft-delete tidak ikut
 * tampil karena memang sudah dihapus.
 */
export default function AdminProducts({
    products,
    categories,
    filters,
}: AdminProductsPageProps) {
    const [draft, setDraft] = useState<AdminProductFilters>(filters);
    const [pending, setPending] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<AdminProductRow | null>(
        null,
    );

    useEffect(() => {
        setDraft(filters);
        setPending(false);
    }, [filters]);

    const hasFilters =
        filters.q !== '' ||
        filters.category !== null ||
        filters.status !== ALL_STATUS;

    function apply(next: AdminProductFilters) {
        setPending(true);
        router.get(productRoutes.index.url({ query: buildQuery(next) }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        apply({
            ...draft,
            q: draft.q.trim(),
        });
    }

    function resetFilters() {
        const cleared: AdminProductFilters = {
            q: '',
            category: null,
            status: ALL_STATUS,
        };

        setDraft(cleared);
        apply(cleared);
    }

    const { current_page: currentPage, last_page: lastPage, total } = products;

    return (
        <>
            <Head title="Produk" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Produk
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Produk aktif saja yang tampil di katalog publik.
                            Produk nonaktif tetap ada di sini supaya bisa
                            dinyalakan lagi tanpa mengisi ulang datanya.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={productRoutes.create()}>
                            <Plus />
                            Tambah Produk
                        </Link>
                    </Button>
                </motion.div>

                <Card>
                    <CardHeader>
                        <CardTitle>Cari dan filter</CardTitle>
                        <CardDescription>
                            Filter tersimpan di alamat halaman, jadi tautan yang
                            disalin membuka tampilan yang sama.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={handleSubmit}
                            className="grid gap-4 sm:grid-cols-2 lg:grid-cols-12"
                        >
                            <div className="space-y-2 sm:col-span-2 lg:col-span-6">
                                <Label htmlFor="q">Cari produk</Label>
                                <div className="relative">
                                    <Search
                                        aria-hidden
                                        className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    />
                                    <Input
                                        id="q"
                                        name="q"
                                        value={draft.q}
                                        onChange={(event) =>
                                            setDraft({
                                                ...draft,
                                                q: event.target.value,
                                            })
                                        }
                                        placeholder="Nama atau deskripsi produk"
                                        className="pl-9"
                                        maxLength={100}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2 lg:col-span-3">
                                <Label htmlFor="category">Kategori</Label>
                                <Select
                                    value={
                                        draft.category === null
                                            ? ALL_CATEGORIES
                                            : String(draft.category)
                                    }
                                    onValueChange={(value) =>
                                        setDraft({
                                            ...draft,
                                            category:
                                                value === ALL_CATEGORIES
                                                    ? null
                                                    : Number(value),
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        id="category"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Semua kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL_CATEGORIES}>
                                            Semua kategori
                                        </SelectItem>
                                        {categories.map((category) => (
                                            <SelectItem
                                                key={category.id}
                                                value={String(category.id)}
                                            >
                                                {category.name} (
                                                {category.products_count})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2 lg:col-span-3">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={draft.status}
                                    onValueChange={(value) =>
                                        setDraft({
                                            ...draft,
                                            status: value as AdminProductFilters['status'],
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        id="status"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL_STATUS}>
                                            Semua status
                                        </SelectItem>
                                        <SelectItem value="aktif">
                                            Aktif
                                        </SelectItem>
                                        <SelectItem value="nonaktif">
                                            Nonaktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-12">
                                <Button type="submit" disabled={pending}>
                                    <Search className="size-4" />
                                    Terapkan
                                </Button>
                                {hasFilters ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetFilters}
                                        disabled={pending}
                                    >
                                        <RotateCcw className="size-4" />
                                        Reset filter
                                    </Button>
                                ) : null}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Produk</CardTitle>
                        <CardDescription>
                            {total === 0
                                ? 'Tidak ada produk yang cocok dengan filter.'
                                : `Menampilkan ${products.from ?? 0}-${products.to ?? 0} dari ${total} produk.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {products.data.length === 0 ? (
                            <ProductEmptyState
                                hasFilters={hasFilters}
                                onReset={resetFilters}
                                pending={pending}
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead className="w-32">
                                            Kategori
                                        </TableHead>
                                        <TableHead className="w-40">
                                            Harga
                                        </TableHead>
                                        <TableHead className="w-24 text-right">
                                            Stok
                                        </TableHead>
                                        <TableHead className="w-36">
                                            Status
                                        </TableHead>
                                        <TableHead className="w-44 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.data.map((product) => (
                                        <ProductRow
                                            key={product.id}
                                            product={product}
                                            onDelete={() =>
                                                setDeleteTarget(product)
                                            }
                                        />
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {lastPage > 1 ? (
                    <nav
                        aria-label="Navigasi halaman produk"
                        className="flex flex-wrap items-center justify-center gap-1"
                    >
                        {currentPage > 1 ? (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={pageUrl(filters, currentPage - 1)}
                                    preserveScroll
                                >
                                    Sebelumnya
                                </Link>
                            </Button>
                        ) : null}

                        {visiblePages(currentPage, lastPage).map((page) =>
                            page === 'gap' ? (
                                <span
                                    key={`gap-${page}`}
                                    className="px-2 text-sm text-muted-foreground"
                                >
                                    ...
                                </span>
                            ) : (
                                <Button
                                    key={page}
                                    asChild
                                    size="sm"
                                    variant={
                                        page === currentPage
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    <Link
                                        href={pageUrl(filters, page)}
                                        preserveScroll
                                        aria-current={
                                            page === currentPage
                                                ? 'page'
                                                : undefined
                                        }
                                    >
                                        {page}
                                    </Link>
                                </Button>
                            ),
                        )}

                        {currentPage < lastPage ? (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={pageUrl(filters, currentPage + 1)}
                                    preserveScroll
                                >
                                    Berikutnya
                                </Link>
                            </Button>
                        ) : null}
                    </nav>
                ) : null}
            </div>

            <ProductDeleteDialog
                product={deleteTarget}
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteTarget(null);
                    }
                }}
            />
        </>
    );
}

AdminProducts.layout = {
    breadcrumbs: [
        {
            title: 'Produk',
            href: productRoutes.index(),
        },
    ],
};

/**
 * Baris produk.
 *
 * Aksi status dikirim ke endpoint status sendiri, bukan lewat form edit,
 * karena yang diubah hanya satu nilai boolean. Nilainya dikirim sebagai input
 * tersembunyi supaya tidak ikut hilang saat baris dirender ulang.
 */
function ProductRow({
    product,
    onDelete,
}: {
    product: AdminProductRow;
    onDelete: () => void;
}) {
    return (
        <TableRow>
            <TableCell>
                <div className="flex items-center gap-3">
                    <div className="size-12 shrink-0 overflow-hidden rounded-md bg-muted">
                        {product.photo ? (
                            <img
                                src={product.photo}
                                alt={product.name}
                                loading="lazy"
                                className="size-full object-cover"
                            />
                        ) : (
                            <div className="flex size-full items-center justify-center text-muted-foreground">
                                <ImageOff className="size-4" />
                            </div>
                        )}
                    </div>
                    <div className="flex flex-col gap-0.5">
                        <span className="font-medium">{product.name}</span>
                        <span className="text-xs text-muted-foreground">
                            /{product.slug}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            Diperbarui {product.updated_at_label}
                            {product.images_count > 0
                                ? ` · ${product.images_count} foto`
                                : ''}
                        </span>
                    </div>
                </div>
            </TableCell>
            <TableCell className="text-sm">
                {product.category ?? (
                    <span className="text-muted-foreground">
                        Tanpa kategori
                    </span>
                )}
            </TableCell>
            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span className="font-medium tabular-nums">
                        {product.price_label}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        / {product.price_unit}
                    </span>
                </div>
            </TableCell>
            <TableCell className="text-right">
                <span
                    className={
                        product.is_available
                            ? 'tabular-nums'
                            : 'text-destructive tabular-nums'
                    }
                >
                    {product.stock}
                </span>
            </TableCell>
            <TableCell>
                <div className="flex flex-col items-start gap-2">
                    <div className="flex flex-wrap gap-1">
                        <Badge
                            variant={
                                product.is_active ? 'default' : 'secondary'
                            }
                        >
                            {product.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                        {!product.is_available ? (
                            <Badge variant="destructive">Stok Habis</Badge>
                        ) : null}
                    </div>
                    <Form
                        {...productRoutes.status.form({
                            product: product.slug,
                        })}
                        options={{
                            preserveScroll: true,
                            preserveState: true,
                        }}
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value={product.is_active ? '0' : '1'}
                                />
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex items-center gap-1 text-xs text-muted-foreground underline-offset-4 hover:underline disabled:opacity-50"
                                >
                                    <Power className="size-3" />
                                    {product.is_active
                                        ? 'Nonaktifkan'
                                        : 'Aktifkan'}
                                </button>
                            </>
                        )}
                    </Form>
                </div>
            </TableCell>
            <TableCell>
                <div className="flex items-center justify-end gap-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={productRoutes.edit(product.slug)}>
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" onClick={onDelete}>
                        <Trash2 />
                        Hapus
                    </Button>
                </div>
            </TableCell>
        </TableRow>
    );
}

/**
 * Empty state daftar produk.
 *
 * Dua kondisi dibedakan karena tindakan yang berguna berbeda: tanpa filter yang
 * aktif, produk pertama memang belum ada; dengan filter aktif, produknya ada
 * tapi tidak cocok, jadi yang perlu diubah adalah filternya.
 */
function ProductEmptyState({
    hasFilters,
    onReset,
    pending,
}: {
    hasFilters: boolean;
    onReset: () => void;
    pending: boolean;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-10 text-center">
            <Package className="size-8 text-muted-foreground" />
            <div className="flex flex-col gap-1">
                <p className="font-medium">
                    {hasFilters
                        ? 'Tidak ada produk yang cocok'
                        : 'Belum ada produk'}
                </p>
                <p className="max-w-sm text-sm text-muted-foreground">
                    {hasFilters
                        ? 'Coba longgarkan pencarian atau reset filter untuk melihat semua produk.'
                        : 'Tambahkan produk pertama supaya penyewa bisa mulai melihat barang yang bisa disewa.'}
                </p>
            </div>
            {hasFilters ? (
                <Button variant="outline" onClick={onReset} disabled={pending}>
                    <RotateCcw />
                    Reset filter
                </Button>
            ) : (
                <Button asChild>
                    <Link href={productRoutes.create()}>
                        <Plus />
                        Tambah Produk
                    </Link>
                </Button>
            )}
        </div>
    );
}

/**
 * Query string filter.
 *
 * Filter default tidak ikut ditulis supaya URL tetap pendek dan mudah dibaca.
 * `status` dan `q` datang dari server dalam bentuk ternormalisasi, jadi form
 * dan URL tidak pernah berbeda.
 */
function buildQuery(filters: AdminProductFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.q !== '') {
        query.q = filters.q;
    }

    if (filters.category !== null) {
        query.category = String(filters.category);
    }

    if (filters.status !== ALL_STATUS) {
        query.status = filters.status;
    }

    return query;
}

/**
 * Query string untuk pindah halaman.
 *
 * Filter ikut dibawa supaya admin tidak kehilangan filter yang sedang aktif
 * hanya karena menekan tombol halaman berikutnya. `links` bawaan Laravel
 * tidak dipakai di sini karena labelnya berisi markup navigasi, sedangkan
 * katalog publik sudah memakai daftar nomor halaman sendiri.
 */
function pageUrl(filters: AdminProductFilters, page: number): string {
    return productRoutes.index.url({
        query: {
            ...buildQuery(filters),
            page: String(page),
        },
    });
}

/**
 * Nomor halaman yang ditampilkan, dengan celah `...` di antara halaman yang
 * dilewati. Halaman pertama, terakhir, dan halaman di sekitar posisi sekarang
 * selalu ikut ditampilkan supaya admin tidak kehilangan akses ke bagian awal
 * dan akhir daftar.
 */
function visiblePages(current: number, last: number): (number | 'gap')[] {
    const wanted = new Set<number>([
        1,
        last,
        current - 1,
        current,
        current + 1,
    ]);
    const pages: (number | 'gap')[] = [];
    let previous = 0;

    for (let page = 1; page <= last; page += 1) {
        if (!wanted.has(page)) {
            continue;
        }

        if (previous > 0 && page - previous > 1) {
            pages.push('gap');
        }

        pages.push(page);
        previous = page;
    }

    return pages;
}
