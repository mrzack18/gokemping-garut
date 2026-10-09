import { Form, Head, Link, router } from '@inertiajs/react';
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
import ActiveBadge from '@/components/admin/active-badge';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminFilterCard from '@/components/admin/filter-card';
import AdminPageHeader from '@/components/admin/page-header';
import AdminPagination from '@/components/admin/pagination';
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

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Produk"
                    description="Produk aktif saja yang tampil di katalog publik. Produk nonaktif tetap ada di sini supaya bisa dinyalakan lagi tanpa mengisi ulang datanya."
                    actions={
                        <Button asChild>
                            <Link href={productRoutes.create()}>
                                <Plus aria-hidden="true" />
                                Tambah Produk
                            </Link>
                        </Button>
                    }
                />

                <AdminFilterCard
                    description="Filter tersimpan di alamat halaman, jadi tautan yang disalin membuka tampilan yang sama."
                    onSubmit={handleSubmit}
                    pending={pending}
                    hasFilters={hasFilters}
                    onReset={resetFilters}
                >
                    <div className="space-y-2 sm:col-span-2 lg:col-span-6">
                        <Label htmlFor="q">Cari produk</Label>
                        <div className="relative">
                            <Search
                                aria-hidden="true"
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
                            <SelectTrigger id="category" className="w-full">
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
                            <SelectTrigger id="status" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL_STATUS}>
                                    Semua status
                                </SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">
                                    Nonaktif
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </AdminFilterCard>

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Daftar Produk
                        </CardTitle>
                        <CardDescription>
                            {total === 0
                                ? 'Tidak ada produk yang cocok dengan filter.'
                                : `Menampilkan ${products.from ?? 0}-${products.to ?? 0} dari ${total} produk.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {products.data.length === 0 ? (
                            <AdminEmptyState
                                icon={Package}
                                title={
                                    hasFilters
                                        ? 'Tidak ada produk yang cocok'
                                        : 'Belum ada produk'
                                }
                                description={
                                    hasFilters
                                        ? 'Coba longgarkan pencarian atau reset filter untuk melihat semua produk.'
                                        : 'Tambahkan produk pertama supaya penyewa bisa mulai melihat barang yang bisa disewa.'
                                }
                                action={
                                    hasFilters ? (
                                        <Button
                                            variant="outline"
                                            onClick={resetFilters}
                                            disabled={pending}
                                        >
                                            <RotateCcw aria-hidden="true" />
                                            Reset filter
                                        </Button>
                                    ) : (
                                        <Button asChild>
                                            <Link href={productRoutes.create()}>
                                                <Plus aria-hidden="true" />
                                                Tambah Produk
                                            </Link>
                                        </Button>
                                    )
                                }
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
                                        <TableHead className="w-40">
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

                <AdminPagination
                    currentPage={currentPage}
                    lastPage={lastPage}
                    pageUrl={(page) => pageUrl(filters, page)}
                    ariaLabel="Navigasi halaman produk"
                />
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
                                <ImageOff
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </div>
                        )}
                    </div>
                    <div className="flex flex-col gap-0.5">
                        <span className="font-medium">{product.name}</span>
                        <span className="font-mono text-xs text-muted-foreground">
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
                        <ActiveBadge isActive={product.is_active} />
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
                                    className="flex items-center gap-1 text-xs font-medium text-pine-700 underline-offset-4 hover:underline disabled:opacity-50 dark:text-pine-600"
                                >
                                    <Power
                                        aria-hidden="true"
                                        className="size-3"
                                    />
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
                            <Pencil aria-hidden="true" />
                            Edit
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onDelete}
                        className="text-destructive hover:text-destructive"
                    >
                        <Trash2 aria-hidden="true" />
                        Hapus
                    </Button>
                </div>
            </TableCell>
        </TableRow>
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
 * hanya karena menekan tombol halaman berikutnya.
 */
function pageUrl(filters: AdminProductFilters, page: number): string {
    return productRoutes.index.url({
        query: {
            ...buildQuery(filters),
            page: String(page),
        },
    });
}
