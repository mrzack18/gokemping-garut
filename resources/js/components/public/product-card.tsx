import { Link } from '@inertiajs/react';
import { formatRupiah } from '@/lib/format';
import type { CatalogProduct, LandingProduct } from '@/types';
import { ImageOff } from 'lucide-react';
import StatusPill from '@/components/public/status-pill';

type ProductCardProps = {
    product: CatalogProduct | LandingProduct;
    href: string;
};

export default function ProductCard({ product, href }: ProductCardProps) {
    const hasStock =
        'is_available' in product ? product.is_available : product.stock > 0;
    const hasLiveAvailability = 'available_now' in product;
    const availableNow = hasLiveAvailability
        ? product.available_now
        : product.stock;
    const photo = 'photo' in product ? product.photo : null;
    const category = product.category?.name;
    const description = 'description' in product ? product.description : null;
    const bookedPeriods =
        'booked_periods' in product ? product.booked_periods : [];
    const bookedPeriodsCount =
        'booked_periods_count' in product ? product.booked_periods_count : 0;

    return (
        <Link
            href={href}
            aria-label={`Lihat detail ${product.name}`}
            className="group block h-full rounded-lg focus-visible:ring-[3px] focus-visible:ring-primary/40 focus-visible:outline-none"
        >
            <article className="flex h-full flex-col overflow-hidden rounded-lg border border-border bg-card transition-[border-color,box-shadow,transform] duration-200 group-hover:-translate-y-0.5 group-hover:border-pine-600/40 group-hover:shadow-sm">
                <div className="aspect-4/3 overflow-hidden bg-muted">
                    {photo ? (
                        <img
                            src={photo}
                            alt={product.name}
                            loading="lazy"
                            width={640}
                            height={480}
                            className="size-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                        />
                    ) : (
                        <div className="flex size-full flex-col items-center justify-center gap-2 text-muted-foreground">
                            <ImageOff aria-hidden="true" className="size-7" />
                            <span className="text-xs">Foto belum tersedia</span>
                        </div>
                    )}
                </div>
                <div className="flex flex-1 flex-col p-5">
                    <div className="flex min-h-5 items-center justify-between gap-2">
                        {category ? (
                            <span className="text-xs font-medium tracking-wide text-pine-700 uppercase dark:text-pine-600">
                                {category}
                            </span>
                        ) : (
                            <span />
                        )}
                        <StatusPill
                            tone={
                                !hasStock
                                    ? 'unavailable'
                                    : availableNow > 0
                                      ? 'available'
                                      : 'pending'
                            }
                        >
                            {!hasStock
                                ? 'Stok habis'
                                : hasLiveAvailability
                                  ? availableNow > 0
                                      ? `${availableNow} tersedia hari ini`
                                      : 'Terbooking hari ini'
                                  : `Sisa ${product.stock}`}
                        </StatusPill>
                    </div>
                    <h3 className="mt-2 line-clamp-2 min-h-12 font-display text-base leading-snug font-semibold tracking-tight">
                        {product.name}
                    </h3>
                    {'business' in product ? (
                        <p className="mt-1 text-xs text-muted-foreground">
                            {product.business.name}
                        </p>
                    ) : null}
                    {description ? (
                        <p className="mt-2 line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                    {bookedPeriods.length > 0 ? (
                        <div className="mt-3 space-y-1 border-t border-border pt-3">
                            {bookedPeriods.slice(0, 2).map((period, index) => (
                                <p
                                    key={`${period.period_label}-${index}`}
                                    className="line-clamp-1 text-[0.68rem] leading-relaxed text-muted-foreground"
                                >
                                    <span className="font-medium text-foreground">
                                        {period.status_label}
                                        {period.is_overdue
                                            ? ' · terlambat'
                                            : ''}
                                        :
                                    </span>{' '}
                                    {period.period_label} · {period.quantity}{' '}
                                    unit
                                </p>
                            ))}
                            {bookedPeriodsCount > bookedPeriods.length ? (
                                <p className="text-[0.68rem] text-muted-foreground">
                                    +{bookedPeriodsCount - bookedPeriods.length}{' '}
                                    periode booking lainnya
                                </p>
                            ) : null}
                        </div>
                    ) : null}
                    <div className="mt-auto pt-4">
                        <p className="font-semibold text-foreground tabular-nums">
                            {formatRupiah(product.price)}
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                / {product.price_unit}
                            </span>
                        </p>
                        <span className="mt-3 inline-flex items-center border-t border-border pt-3 text-sm font-medium text-pine-700 dark:text-pine-600">
                            Lihat detail
                            <span aria-hidden="true" className="ml-1.5">
                                →
                            </span>
                        </span>
                    </div>
                </div>
            </article>
        </Link>
    );
}
