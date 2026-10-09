import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Hitung nomor halaman yang ditampilkan: halaman pertama, terakhir, dan
 * sekitar halaman aktif. Sisanya diringkas menjadi `gap`.
 */
export function visiblePages(
    current: number,
    last: number,
): (number | 'gap')[] {
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

type AdminPaginationProps = {
    currentPage: number;
    lastPage: number;
    pageUrl: (page: number) => string;
    ariaLabel: string;
};

/**
 * Navigasi halaman daftar admin. URL dibangun pemanggil supaya filter yang
 * sedang aktif ikut terbawa, dan tautan memakai `preserveScroll` agar posisi
 * admin tidak melompat ke atas.
 */
export default function AdminPagination({
    currentPage,
    lastPage,
    pageUrl,
    ariaLabel,
}: AdminPaginationProps) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <nav
            aria-label={ariaLabel}
            className="flex flex-wrap items-center justify-center gap-1"
        >
            {currentPage > 1 ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={pageUrl(currentPage - 1)} preserveScroll>
                        <ChevronLeft aria-hidden="true" className="size-4" />
                        Sebelumnya
                    </Link>
                </Button>
            ) : null}

            {visiblePages(currentPage, lastPage).map((page, index) =>
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
                        variant={page === currentPage ? 'default' : 'outline'}
                    >
                        <Link
                            href={pageUrl(page)}
                            preserveScroll
                            aria-current={
                                page === currentPage ? 'page' : undefined
                            }
                        >
                            {page}
                        </Link>
                    </Button>
                ),
            )}

            {currentPage < lastPage ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={pageUrl(currentPage + 1)} preserveScroll>
                        Berikutnya
                        <ChevronRight aria-hidden="true" className="size-4" />
                    </Link>
                </Button>
            ) : null}
        </nav>
    );
}
