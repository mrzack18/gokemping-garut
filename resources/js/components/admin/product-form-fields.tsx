import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { Textarea } from '@/components/ui/textarea';
import { Plus, X } from 'lucide-react';

const NO_CATEGORY = 'tanpa-kategori';
const DEFAULT_PRICE_UNIT = 'hari';

const PRICE_UNIT_LABELS: Record<string, string> = {
    hari: 'per hari',
    jam: 'per jam',
    paket: 'per paket',
    event: 'per event',
};

/**
 * Satu baris spesifikasi pada form produk (PRD section 23, ROADMAP 4.3).
 *
 * Spesifikasi disimpan sebagai objek JSON, jadi bentuknya selalu pasangan
 * label-isi. Form ini menampilkan sisinya sebagai baris yang bisa ditambah dan
 * dihapus, lalu dikirim sebagai `specification[][key]` dan
 * `specification[][value]`.
 */
export type SpecificationRow = {
    key: string;
    value: string;
};

/**
 * Kolom isian produk, dipakai bersama oleh halaman tambah dan edit.
 *
 * Komponen ini sengaja tidak punya tombol simpan. Tombolnya ada di halaman
 * induk supaya kedua halaman bisa punya aksi utama di bawah form.
 *
 * Dua field dikirim lewat input tersembunyi, bukan lewat komponen `Select`:
 * `Select` dibangun di atas Radix yang tidak punya elemen select asli, jadi
 * nilainya tidak ikut terkirim bersama form. Tanpa input tersembunyi, kategori
 * dan satuan harga selalu terkirim kosong padahal admin sudah memilihnya.
 */
type ProductFormFieldsProps = {
    categories: { id: number; name: string; products_count: number }[];
    priceUnits: string[];
    defaults: {
        name: string;
        categoryId: number | null;
        description: string;
        specification: SpecificationRow[];
        rentalTerms: string;
        price: string;
        priceUnit: string;
        stock: string;
        isActive: boolean;
    };
    /** Kategori boleh dikosongkan saat produk sudah pernah tersimpan. */
    categoryRequired: boolean;
    errors: Record<string, string>;
};

export default function ProductFormFields({
    categories,
    priceUnits,
    defaults,
    categoryRequired,
    errors,
}: ProductFormFieldsProps) {
    const [rows, setRows] = useState<SpecificationRow[]>(
        defaults.specification,
    );
    const [categoryId, setCategoryId] = useState(
        defaults.categoryId === null
            ? NO_CATEGORY
            : String(defaults.categoryId),
    );
    const [priceUnit, setPriceUnit] = useState(
        defaults.priceUnit === '' ? DEFAULT_PRICE_UNIT : defaults.priceUnit,
    );

    /**
     * Error spesifikasi punya satu pesan per baris, jadi kuncinya berbentuk
     * `specification.0.key`. Pesannya dikumpulkan supaya admin melihat alasan
     * penolakan untuk setiap baris yang bermasalah, bukan satu pesan umum untuk
     * seluruh tabel.
     */
    const specificationErrors = Object.entries(errors).filter(([key]) =>
        key.startsWith('specification.'),
    );

    /**
     * Ubah satu baris spesifikasi.
     *
     * Baris spesifikasi memakai input yang dikendalikan state, bukan
     * `defaultValue`. Dengan `defaultValue`, menghapus baris tengah hanya
     * memindahkan baris yang tersisa ke posisi yang lebih rendah, dan input lama
     * yang tidak di-unmount mempertahankan isinya sendiri. Akibatnya baris yang
     * sudah dihapus masih terlihat dengan isi baris sebelumnya. Dengan state,
     * isi baris selalu mengikuti data yang dikirim.
     */
    function updateRow(index: number, patch: Partial<SpecificationRow>) {
        setRows((current) =>
            current.map((row, position) =>
                position === index ? { ...row, ...patch } : row,
            ),
        );
    }

    function addRow() {
        setRows((current) => [...current, { key: '', value: '' }]);
    }

    /**
     * Hapus baris spesifikasi.
     *
     * Baris yang dihapus tidak digeser nilainya ke baris lain. Memindahkan isi
     * baris berarti admin harus mengetik ulang isi baris yang tersisa, jadi
     * tidak ada langkah yang bisa membuat ketikan admin hilang diam-diam.
     */
    function removeRow(index: number) {
        setRows((current) =>
            current.filter((_, position) => position !== index),
        );
    }

    return (
        <div className="flex flex-col gap-4">
            <Card className="rounded-lg shadow-none">
                <CardHeader>
                    <CardTitle className="font-display text-base">
                        Informasi Produk
                    </CardTitle>
                    <CardDescription>
                        Detail yang dilihat penyewa di katalog: nama, kategori,
                        harga, stok, dan ketentuan sewa.
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="name">Nama produk</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={defaults.name}
                            placeholder="Contoh: Tenda Dome 4 Person"
                            maxLength={100}
                            required
                            autoFocus
                        />
                        <p className="text-sm text-muted-foreground">
                            Nama ini muncul di katalog dan di detail produk. URL
                            produk dibuat otomatis dan tetap sama walau nama
                            diedit.
                        </p>
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category_id">Kategori</Label>
                        <input
                            type="hidden"
                            name="category_id"
                            value={categoryId === NO_CATEGORY ? '' : categoryId}
                        />
                        <Select
                            value={categoryId}
                            onValueChange={setCategoryId}
                        >
                            <SelectTrigger id="category_id" className="w-full">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                {categoryRequired ? null : (
                                    <SelectItem value={NO_CATEGORY}>
                                        Tanpa kategori
                                    </SelectItem>
                                )}
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
                        {categories.length === 0 && categoryRequired ? (
                            <p className="text-sm text-muted-foreground">
                                Unit ini belum punya kategori. Buat kategori
                                dulu di halaman Kategori, baru tambah produknya.
                            </p>
                        ) : null}
                        <InputError message={errors.category_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="price">Harga sewa</Label>
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            min={0}
                            inputMode="numeric"
                            defaultValue={defaults.price}
                            placeholder="75000"
                            className="tabular-nums"
                            required
                        />
                        <InputError message={errors.price} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="price_unit">Satuan harga</Label>
                        <input
                            type="hidden"
                            name="price_unit"
                            value={priceUnit}
                        />
                        <Select value={priceUnit} onValueChange={setPriceUnit}>
                            <SelectTrigger id="price_unit" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {priceUnits.map((unit) => (
                                    <SelectItem key={unit} value={unit}>
                                        {PRICE_UNIT_LABELS[unit] ?? unit}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.price_unit} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="stock">Stok tersedia</Label>
                        <Input
                            id="stock"
                            name="stock"
                            type="number"
                            min={0}
                            max={1000}
                            inputMode="numeric"
                            defaultValue={defaults.stock}
                            className="tabular-nums"
                            required
                        />
                        <p className="text-sm text-muted-foreground">
                            Stok 0 tidak menghilangkan produk dari katalog,
                            hanya menutup pemesanannya.
                        </p>
                        <InputError message={errors.stock} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            name="description"
                            defaultValue={defaults.description}
                            placeholder="Opsional. Kondisi barang, isi paket, dan hal yang perlu orang tahu sebelum menyewa."
                            rows={4}
                            maxLength={2000}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="rental_terms">Syarat sewa</Label>
                        <Textarea
                            id="rental_terms"
                            name="rental_terms"
                            defaultValue={defaults.rentalTerms}
                            placeholder="Opsional. Contoh: Wajib DP, foto bersama identitas saat ambil, dikembalikan paling lambat H+1."
                            rows={3}
                            maxLength={2000}
                        />
                        <InputError message={errors.rental_terms} />
                    </div>
                </CardContent>
            </Card>

            <Card className="rounded-lg shadow-none">
                <CardHeader className="flex flex-row items-start justify-between gap-2">
                    <div className="grid gap-1">
                        <CardTitle className="font-display text-base">
                            Spesifikasi
                        </CardTitle>
                        <CardDescription>
                            Pasangan label dan isi yang tampil sebagai tabel di
                            halaman detail produk, contoh: kapasitas 4 orang
                            atau berat 12 kg. Baris yang kosong tidak ikut
                            disimpan.
                        </CardDescription>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addRow}
                    >
                        <Plus aria-hidden="true" />
                        Tambah Baris
                    </Button>
                </CardHeader>
                <CardContent className="grid gap-4">
                    {rows.length === 0 ? (
                        <p className="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground">
                            Belum ada spesifikasi. Produk tanpa spesifikasi
                            tetap tampil di katalog.
                        </p>
                    ) : (
                        <div className="flex flex-col gap-3">
                            {rows.map((row, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-1 items-end gap-2 sm:grid-cols-[1fr_1fr_auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`specification_key_${index}`}
                                            className="sr-only"
                                        >
                                            Label spesifikasi {index + 1}
                                        </Label>
                                        <Input
                                            id={`specification_key_${index}`}
                                            name="specification[][key]"
                                            value={row.key}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    key: event.target.value,
                                                })
                                            }
                                            placeholder="Kapasitas"
                                            maxLength={60}
                                            aria-invalid={
                                                errors[
                                                    `specification.${index}.key`
                                                ] !== undefined
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`specification_value_${index}`}
                                            className="sr-only"
                                        >
                                            Isi spesifikasi {index + 1}
                                        </Label>
                                        <Input
                                            id={`specification_value_${index}`}
                                            name="specification[][value]"
                                            value={row.value}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    value: event.target.value,
                                                })
                                            }
                                            placeholder="4 orang"
                                            maxLength={200}
                                            aria-invalid={
                                                errors[
                                                    `specification.${index}.value`
                                                ] !== undefined
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeRow(index)}
                                        aria-label={`Hapus baris spesifikasi ${index + 1}`}
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}

                    {errors.specification !== undefined && (
                        <p className="text-sm text-destructive">
                            {errors.specification}
                        </p>
                    )}

                    {specificationErrors.map(([key, message]) => (
                        <p key={key} className="text-sm text-destructive">
                            Baris {specificationPosition(key)}: {message}
                        </p>
                    ))}
                </CardContent>
            </Card>

            <label className="flex items-start gap-3 rounded-lg border border-border bg-muted/30 p-4 text-sm">
                {/*
                 * Checkbox yang tidak dicentang tidak mengirim apa pun, jadi
                 * backend akan menganggap statusnya tidak berubah dan admin
                 * tidak bisa menonaktifkan produk dari form ini. Hidden ini
                 * membuat `is_active` selalu terkirim; nilai terakhir yang
                 * dibaca PHP adalah nilai checkbox kalau dicentang.
                 */}
                <input type="hidden" name="is_active" value="0" />
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    defaultChecked={defaults.isActive}
                    className="mt-0.5 size-4 accent-pine-700 dark:accent-pine-600"
                />
                <span className="grid gap-0.5">
                    <span className="font-medium">
                        Tampilkan di katalog publik
                    </span>
                    <span className="text-muted-foreground">
                        Produk nonaktif disembunyikan dari katalog, tapi datanya
                        tetap utuh dan bisa diaktifkan lagi kapan saja.
                    </span>
                </span>
            </label>

            <InputError message={errors.is_active} />
        </div>
    );
}

/**
 * Nomor baris dari kunci error spesifikasi.
 *
 * Backend menolak per baris, jadi nomor barisnya ikut ditampilkan: pesan
 * tanpa nomor baris hanya membuat admin menebak baris mana yang bermasalah.
 */
function specificationPosition(key: string): string {
    const match = /^specification\.(\d+)/.exec(key);

    return match === null ? '?' : String(Number(match[1]) + 1);
}
