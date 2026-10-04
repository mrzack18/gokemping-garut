import { motion } from 'motion/react';
import Section from '@/components/landing/section';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatRupiah } from '@/lib/format';
import type { LandingProduct } from '@/types';
import { ArrowRight, Boxes } from 'lucide-react';

type FeaturedProductsProps = {
    products: LandingProduct[];
};

export default function FeaturedProducts({ products }: FeaturedProductsProps) {
    return (
        <Section
            id="produk"
            eyebrow="Produk unggulan"
            title="Barang yang paling sering disewa"
            description="Harga tertera per hari dan sudah termasuk pengecekan kondisi sebelum pengembalian."
        >
            {products.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Belum ada produk aktif. Admin dapat menambah produk melalui
                    dashboard.
                </p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {products.map((product, index) => (
                        <motion.article
                            key={product.id}
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.2 }}
                            transition={{
                                duration: 0.4,
                                delay: (index % 4) * 0.08,
                            }}
                            className="group"
                        >
                            <Card className="h-full overflow-hidden transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-lg">
                                <div className="flex h-32 items-center justify-center bg-muted">
                                    <Boxes className="size-10 text-muted-foreground transition-colors duration-300 group-hover:text-primary" />
                                </div>
                                <CardContent className="space-y-3 p-5">
                                    <div className="space-y-1">
                                        <p className="text-xs text-muted-foreground">
                                            {product.category
                                                ? `${product.business.name} / ${product.category.name}`
                                                : product.business.name}
                                        </p>
                                        <h3 className="leading-snug font-medium">
                                            {product.name}
                                        </h3>
                                    </div>

                                    <div className="flex items-end justify-between gap-2">
                                        <p className="text-lg font-semibold">
                                            {formatRupiah(product.price)}
                                            <span className="text-xs font-normal text-muted-foreground">
                                                /{product.price_unit}
                                            </span>
                                        </p>
                                        <Badge
                                            variant={
                                                product.stock > 0
                                                    ? 'secondary'
                                                    : 'destructive'
                                            }
                                        >
                                            {product.stock > 0
                                                ? `Sisa ${product.stock}`
                                                : 'Habis'}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </motion.article>
                    ))}
                </div>
            )}

            <p className="mt-6 flex items-center gap-2 text-sm text-muted-foreground">
                <ArrowRight className="size-4" />
                Katalog lengkap dengan filter dan pencarian tersedia di halaman
                katalog masing-masing unit.
            </p>
        </Section>
    );
}
