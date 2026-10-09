import { Form, Head } from '@inertiajs/react';
import { FolderTree, Pencil, Plus, Power, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ActiveBadge from '@/components/admin/active-badge';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminPageHeader from '@/components/admin/page-header';
import CategoryDeleteDialog from '@/components/admin/category-delete-dialog';
import CategoryFormDialog from '@/components/admin/category-form-dialog';
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

    const openCreateDialog = () =>
        setFormDialog({ open: true, category: null });

    return (
        <>
            <Head title="Kategori" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Kategori"
                    description="Kategori mengelompokkan produk yang bisa disewa. Urutan di sini sama dengan urutan filter di katalog publik."
                    actions={
                        <Button onClick={openCreateDialog}>
                            <Plus aria-hidden="true" />
                            Tambah Kategori
                        </Button>
                    }
                />

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Daftar Kategori
                        </CardTitle>
                        <CardDescription>
                            {categories.length === 0
                                ? 'Belum ada kategori.'
                                : `${categories.length} kategori terdaftar.`}
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        {categories.length === 0 ? (
                            <AdminEmptyState
                                icon={FolderTree}
                                title="Belum ada kategori"
                                description="Buat kategori dulu supaya produk bisa dikelompokkan dan penyewa bisa memfilter barang yang mereka cari."
                                action={
                                    <Button
                                        variant="outline"
                                        onClick={openCreateDialog}
                                    >
                                        <Plus aria-hidden="true" />
                                        Tambah Kategori
                                    </Button>
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
                                        <TableHead className="w-36">
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
                    <span className="font-mono text-xs text-muted-foreground">
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
                    <ActiveBadge isActive={category.is_active} />
                    <Form
                        {...categoryRoutes.status.form({
                            category: category.slug,
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
                                    value={category.is_active ? '0' : '1'}
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
                        <Pencil aria-hidden="true" />
                        Edit
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
