import { Form, Head } from '@inertiajs/react';
import { motion } from 'motion/react';
import { FolderTree, Pencil, Plus, Power, Trash2 } from 'lucide-react';
import { useState } from 'react';
import CategoryDeleteDialog from '@/components/admin/category-delete-dialog';
import CategoryFormDialog from '@/components/admin/category-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import categoryRoutes from '@/routes/admin/categories';
import type { AdminCategoriesPageProps, AdminCategory } from '@/types';

type DialogState = {
    open: boolean;
    category: AdminCategory | null;
};

/**
 * Daftar kategori (PRD section 23, ROADMAP 4.2).
 *
 * Urutan tampil diatur lewat `sort_order`, bukan lewat nama. Dua kategori bisa
 * saja punya urutan sama, jadi pengurutan di server memakai nama sebagai
 * pemutus terakhir supaya daftar tidak berganti posisi tak terduga.
 */
export default function AdminCategories({
    categories,
}: AdminCategoriesPageProps) {
    const [formDialog, setFormDialog] = useState<DialogState>({
        open: false,
        category: null,
    });
    const [deleteDialog, setDeleteDialog] = useState<DialogState>({
        open: false,
        category: null,
    });

    const closeFormDialog = (open: boolean) =>
        setFormDialog((state) => ({ ...state, open }));
    const closeDeleteDialog = (open: boolean) =>
        setDeleteDialog((state) => ({ ...state, open }));

    return (
        <>
            <Head title="Kategori" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Kategori
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kategori mengelompokkan produk yang bisa disewa.
                            Urutan di sini sama dengan urutan filter di katalog
                            publik.
                        </p>
                    </div>

                    <Button
                        onClick={() =>
                            setFormDialog({ open: true, category: null })
                        }
                    >
                        <Plus />
                        Tambah Kategori
                    </Button>
                </motion.div>

                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Kategori</CardTitle>
                        <CardDescription>
                            {categories.length === 0
                                ? 'Belum ada kategori.'
                                : `${categories.length} kategori terdaftar.`}
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        {categories.length === 0 ? (
                            <EmptyState
                                onCreate={() =>
                                    setFormDialog({
                                        open: true,
                                        category: null,
                                    })
                                }
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-20">
                                            Urutan
                                        </TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead className="w-28 text-right">
                                            Produk
                                        </TableHead>
                                        <TableHead className="w-32">
                                            Status
                                        </TableHead>
                                        <TableHead className="w-44 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.map((category) => (
                                        <CategoryRow
                                            key={category.id}
                                            category={category}
                                            onEdit={() =>
                                                setFormDialog({
                                                    open: true,
                                                    category,
                                                })
                                            }
                                            onDelete={() =>
                                                setDeleteDialog({
                                                    open: true,
                                                    category,
                                                })
                                            }
                                        />
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            <CategoryFormDialog
                key={formDialog.category?.id ?? 'new'}
                category={formDialog.category}
                open={formDialog.open}
                onOpenChange={closeFormDialog}
            />

            <CategoryDeleteDialog
                category={deleteDialog.category}
                open={deleteDialog.open}
                onOpenChange={closeDeleteDialog}
            />
        </>
    );
}

AdminCategories.layout = {
    breadcrumbs: [
        {
            title: 'Kategori',
            href: categoryRoutes.index(),
        },
    ],
};

/**
 * Baris kategori.
 *
 * Aksi status dikirim ke endpoint status sendiri, bukan lewat form edit,
 * karena yang diubah hanya satu nilai boolean. Nilainya dikirim sebagai input
 * tersembunyi di dalam form supaya tidak ikut hilang saat baris di-render ulang.
 */
function CategoryRow({
    category,
    onEdit,
    onDelete,
}: {
    category: AdminCategory;
    onEdit: () => void;
    onDelete: () => void;
}) {
    return (
        <TableRow>
            <TableCell className="text-muted-foreground tabular-nums">
                {category.sort_order}
            </TableCell>
            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span className="font-medium">{category.name}</span>
                    <span className="text-xs text-muted-foreground">
                        /{category.slug}
                    </span>
                    {category.description !== null && (
                        <span className="line-clamp-2 text-xs text-muted-foreground">
                            {category.description}
                        </span>
                    )}
                </div>
            </TableCell>
            <TableCell className="text-right tabular-nums">
                {category.products_count}
            </TableCell>
            <TableCell>
                <div className="flex flex-col items-start gap-2">
                    <Badge
                        variant={category.is_active ? 'default' : 'secondary'}
                    >
                        {category.is_active ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                    <Form
                        {...categoryRoutes.status.form({
                            category: category.slug,
                        })}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value={category.is_active ? '0' : '1'}
                                />
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex items-center gap-1 text-xs text-muted-foreground underline-offset-4 hover:underline disabled:opacity-50"
                                >
                                    <Power className="size-3" />
                                    {category.is_active
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
                    <Button variant="outline" size="sm" onClick={onEdit}>
                        <Pencil />
                        Edit
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
 * Empty state untuk unit yang belum punya kategori.
 *
 * Produk baru bisa dikelompokkan setelah ada kategori, jadi satu-satunya
 * tindakan yang berguna di halaman kosong ini adalah membuat kategori pertama.
 */
function EmptyState({ onCreate }: { onCreate: () => void }) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-10 text-center">
            <FolderTree className="size-8 text-muted-foreground" />
            <div className="flex flex-col gap-1">
                <p className="font-medium">Belum ada kategori</p>
                <p className="max-w-sm text-sm text-muted-foreground">
                    Buat kategori dulu supaya produk bisa dikelompokkan dan
                    penyewa bisa memfilter barang yang mereka cari.
                </p>
            </div>
            <Button variant="outline" onClick={onCreate}>
                <Plus />
                Tambah Kategori
            </Button>
        </div>
    );
}
