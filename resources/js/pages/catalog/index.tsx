import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useEffect, useState, type FormEvent } from 'react';
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
import PublicLayout from '@/layouts/public-layout';
import { formatRupiah } from '@/lib/format';
import catalogRoutes from '@/routes/catalog';
import services from '@/routes/services';
import type {
    CatalogFilters,
    CatalogPageProps,
    CatalogProduct,
    CatalogSort,
} from '@/types';
import {
    ArrowLeft,
    ArrowRight,
    ImageOff,
    Package,
    RotateCcw,
    Search,
} from 'lucide-react';

const ALL_CATEGORIES = 'semua';

/**
 * Path katalog diambil dari route yang benar-benar terdaftar agar halaman ini
 * tidak pernah menulis URL yang tidak ada. Unit tanpa entri di sini
 * (mis. unit yang ditambahkan setelah katalog ini dibuat) tetap mendapat
 * fallback yang mengikuti slug-nya.
 */
const catalogUrlBySlug: Record<string, string> = {
    gokemping: catalogRoutes.gokemping.url(),
    'sewa-sepeda-garut': catalogRoutes.sewaSepedaGarut.url(),
};

/**
 * URL detail produk memakai route yang terdaftar agar card tidak pernah
 * menulis path yang tidak ada.
 */
const productUrlBuilders: Record<string, (product: CatalogProduct) => string> =
    {
        gokemping: (product) => catalogRoutes.gokemping.show.url(product.slug),
        'sewa-sepeda-garut': (product) =>
            catalogRoutes.sewaSepedaGarut.show.url(product.slug),
    };

const sortOptions: { value: CatalogSort; label: string }[] = [
    { value: 'terbaru', label: 'Terbaru' },
    { value: 'harga_terendah', label: 'Harga terendah' },
    { value: 'harga_tertinggi', label: 'Harga tertinggi' },
    { value: 'nama', label: 'Nama produk (A-Z)' },
];

type CatalogDraft = {
    q: string;
    category: string;
    min_price: string;
    max_price: string;
    sort: CatalogSort;
};

function draftFromFilters(filters: CatalogFilters): CatalogDraft {
    return {
        q: filters.q,
        category: filters.category ?? ALL_CATEGORIES,
        min_price: filters.min_price === null ? '' : String(filters.min_price),
        max_price: filters.max_price === null ? '' : String(filters.max_price),
        sort: filters.sort,
    };
}

/**
 * Membangun query string dari state filter. Filter default tidak ikut
 * ditulis supaya URL tetap pendek dan mudah dibaca.
 */
function buildQuery(filters: CatalogFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.q !== '') {
        query.q = filters.q;
    }

    if (filters.category !== null) {
        query.category = filters.category;
    }

    if (filters.min_price !== null) {
        query.min_price = String(filters.min_price);
    }

    if (filters.max_price !== null) {
        query.max_price = String(filters.max_price);
    }

    if (filters.sort !== 'terbaru') {
        query.sort = filters.sort;
    }

    return query;
}

function parsePrice(value: string): number | null {
    if (value.trim() === '') {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed >= 0 ? Math.floor(parsed) : null;
}

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

export default function Catalog({
    business,
    businesses,
    categories,
    products,
    filters,
    priceBounds,
}: CatalogPageProps) {
    const catalogUrl = catalogUrlBySlug[business.slug] ?? `/${business.slug}`;
    const [draft, setDraft] = useState<CatalogDraft>(() =>
        draftFromFilters(filters),
    );
    const [pending, setPending] = useState(false);

    useEffect(() => {
        setDraft(draftFromFilters(filters));
        setPending(false);
    }, [filters]);

    const hasFilters =
        filters.q !== '' ||
        filters.category !== null ||
        filters.min_price !== null ||
        filters.max_price !== null;

    function apply(next: CatalogFilters) {
        const query = buildQuery(next);
        const search = new URLSearchParams(query).toString();

        setPending(true);
        router.get(search === '' ? catalogUrl : `${catalogUrl}?${search}`);
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        apply({
            q: draft.q.trim(),
            category: draft.category === ALL_CATEGORIES ? null : draft.category,
            min_price: parsePrice(draft.min_price),
            max_price: parsePrice(draft.max_price),
            sort: draft.sort,
        });
    }

    function resetFilters() {
        setDraft(
            draftFromFilters({
                ...filters,
                q: '',
                category: null,
                min_price: null,
                max_price: null,
            }),
        );
        apply({
            ...filters,
            q: '',
            category: null,
            min_price: null,
            max_price: null,
        });
    }

    const { current_page: currentPage, last_page: lastPage, total } = products;
    const pageUrl = (page: number) => {
        const query = {
            ...buildQuery(filters),
            ...(page > 1 ? { page: String(page) } : {}),
        };
        const search = new URLSearchParams(query).toString();

        return search === '' ? catalogUrl : `${catalogUrl}?${search}`;
    };

    return (
        <PublicLayout businesses={businesses} anchorBase="/">
            <Head title={`Katalog ${business.name}`} />

            <section className="border-b">
                <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
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
                            <Link href={services.index()}>
                                <ArrowLeft className="size-4" />
                                Pilih layanan
                            </Link>
                        </Button>

                        <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Katalog {business.name}
                        </h1>
                        {business.description ? (
                            <p className="mt-3 max-w-2xl text-muted-foreground">
                                {business.description}
                            </p>
                        ) : null}
                    </motion.div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Cari dan filter
                        </CardTitle>
                        <CardDescription>
                            Filter tersimpan di alamat halaman, jadi tautan yang
                            kamu salin akan membuka tampilan yang sama.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={handleSubmit}
                            className="grid gap-4 sm:grid-cols-2 lg:grid-cols-12"
                        >
                            <div className="space-y-2 lg:col-span-4">
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
                                        placeholder="Contoh: tenda"
                                        className="pl-9"
                                        maxLength={100}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2 lg:col-span-3">
                                <Label htmlFor="category">Kategori</Label>
                                <Select
                                    value={draft.category}
                                    onValueChange={(value) =>
                                        setDraft({
                                            ...draft,
                                            category: value,
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
                                                value={category.slug}
                                            >
                                                {category.name} (
                                                {category.products_count})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2 lg:col-span-3">
                                <Label htmlFor="sort">Urutkan</Label>
                                <Select
                                    value={draft.sort}
                                    onValueChange={(value) =>
                                        setDraft({
                                            ...draft,
                                            sort: value as CatalogSort,
                                        })
                                    }
                                >
                                    <SelectTrigger id="sort" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sortOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid grid-cols-2 gap-3 lg:col-span-2">
                                <div className="space-y-2">
                                    <Label htmlFor="min_price">Harga min</Label>
                                    <Input
                                        id="min_price"
                                        name="min_price"
                                        type="number"
                                        min={0}
                                        inputMode="numeric"
                                        value={draft.min_price}
                                        onChange={(event) =>
                                            setDraft({
                                                ...draft,
                                                min_price: event.target.value,
                                            })
                                        }
                                        placeholder={String(priceBounds.min)}
                                        className="tabular-nums"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="max_price">
                                        Harga maks
                                    </Label>
                                    <Input
                                        id="max_price"
                                        name="max_price"
                                        type="number"
                                        min={0}
                                        inputMode="numeric"
                                        value={draft.max_price}
                                        onChange={(event) =>
                                            setDraft({
                                                ...draft,
                                                max_price: event.target.value,
                                            })
                                        }
                                        placeholder={String(priceBounds.max)}
                                        className="tabular-nums"
                                    />
                                </div>
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

                <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        {total === 0
                            ? 'Tidak ada produk yang cocok.'
                            : `Menampilkan ${products.from ?? 0}-${products.to ?? 0} dari ${total} produk`}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Kode booking unit ini dimulai dengan{' '}
                        <span className="font-medium">
                            {business.booking_code_prefix}
                        </span>
                    </p>
                </div>

                {products.data.length === 0 ? (
                    <Card className="mt-4">
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Package className="size-8 text-muted-foreground" />
                            <p className="font-medium">Katalog kosong</p>
                            <p className="max-w-md text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Coba longgarkan filter pencarian, atau reset filter untuk melihat semua produk.'
                                    : 'Belum ada produk aktif di unit ini.'}
                            </p>
                            {hasFilters ? (
                                <Button
                                    variant="outline"
                                    onClick={resetFilters}
                                    disabled={pending}
                                >
                                    <RotateCcw className="size-4" />
                                    Reset filter
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {products.data.map((product, index) => {
                            const detailUrlBuilder =
                                productUrlBuilders[business.slug];
                            const detailUrl = detailUrlBuilder
                                ? detailUrlBuilder(product)
                                : null;

                            return (
                                <motion.div
                                    key={product.id}
                                    initial={{ opacity: 0, y: 16 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.35,
                                        delay: Math.min(index, 8) * 0.06,
                                    }}
                                >
                                    <Card className="flex h-full flex-col overflow-hidden">
                                        <div className="aspect-4/3 w-full overflow-hidden bg-muted">
                                            {product.photo ? (
                                                <img
                                                    src={product.photo}
                                                    alt={product.name}
                                                    loading="lazy"
                                                    className="size-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-full flex-col items-center justify-center gap-2 text-muted-foreground">
                                                    <ImageOff className="size-6" />
                                                    <span className="text-xs">
                                                        Foto belum tersedia
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                        <CardHeader className="gap-2">
                                            {product.category ? (
                                                <Badge
                                                    variant="secondary"
                                                    className="w-fit font-normal"
                                                >
                                                    {product.category.name}
                                                </Badge>
                                            ) : null}
                                            <CardTitle className="text-base leading-snug">
                                                {product.name}
                                            </CardTitle>
                                        </CardHeader>

                                        <CardContent className="mt-auto space-y-3">
                                            <div className="flex flex-wrap items-baseline gap-x-2">
                                                <span className="text-lg font-semibold tabular-nums">
                                                    {formatRupiah(
                                                        product.price,
                                                    )}
                                                </span>
                                                <span className="text-sm text-muted-foreground">
                                                    / {product.price_unit}
                                                </span>
                                            </div>

                                            <div className="flex items-center justify-between gap-2">
                                                <Badge
                                                    variant={
                                                        product.is_available
                                                            ? 'default'
                                                            : 'destructive'
                                                    }
                                                >
                                                    {product.is_available
                                                        ? 'Tersedia'
                                                        : 'Tidak tersedia'}
                                                </Badge>
                                                {product.is_available ? (
                                                    <span className="text-xs text-muted-foreground tabular-nums">
                                                        Sisa {product.stock}
                                                    </span>
                                                ) : null}
                                            </div>

                                            {product.description ? (
                                                <p className="line-clamp-3 text-sm text-muted-foreground">
                                                    {product.description}
                                                </p>
                                            ) : null}

                                            {detailUrl ? (
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    className="mt-auto w-full"
                                                >
                                                    <Link href={detailUrl}>
                                                        Lihat Detail
                                                        <ArrowRight className="size-4" />
                                                    </Link>
                                                </Button>
                                            ) : null}
                                        </CardContent>
                                    </Card>
                                </motion.div>
                            );
                        })}
                    </div>
                )}

                {lastPage > 1 ? (
                    <nav
                        aria-label="Navigasi halaman katalog"
                        className="mt-8 flex flex-wrap items-center justify-center gap-1"
                    >
                        {lastPage > 1 && currentPage > 1 ? (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={pageUrl(currentPage - 1)}
                                    preserveScroll
                                >
                                    Sebelumnya
                                </Link>
                            </Button>
                        ) : null}

                        {visiblePages(currentPage, lastPage).map(
                            (page, index) =>
                                page === 'gap' ? (
                                    <span
                                        key={`gap-${index}`}
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
                                            href={pageUrl(page)}
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
                                    href={pageUrl(currentPage + 1)}
                                    preserveScroll
                                >
                                    Berikutnya
                                </Link>
                            </Button>
                        ) : null}
                    </nav>
                ) : null}
            </section>
        </PublicLayout>
    );
}
