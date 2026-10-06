import { Form, Head } from '@inertiajs/react';
import { Search, Ticket } from 'lucide-react';
import InputError from '@/components/input-error';
import TicketResult from '@/components/ticket/ticket-result';
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
import adminTicketRoutes from '@/routes/admin/tickets';
import type { AdminTicketPageProps } from '@/types';

/**
 * Cek tiket panel admin (ROADMAP 5.5).
 *
 * Dipakai staf untuk memverifikasi tiket saat barang diambil. Pencariannya
 * dibatasi ke unit admin yang login, berbeda dari section cek tiket di landing
 * page yang lintas unit. Verifikasinya tetap kode booking + nomor WhatsApp
 * penyewa supaya kode booking yang berurutan tidak bisa dipakai menebak tiket
 * orang lain.
 */
export default function AdminTicketCheck({ ticket }: AdminTicketPageProps) {
    return (
        <>
            <Head title="Cek Tiket" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Cek Tiket
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Verifikasi tiket saat pengambilan barang. Masukkan kode
                        booking dan nomor WhatsApp penyewa; hanya tiket unit ini
                        yang bisa diperiksa dari halaman ini.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Verifikasi tiket</CardTitle>
                        <CardDescription>
                            Kode booking ada di pesan konfirmasi penyewa, contoh
                            GK-20261015-001.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...adminTicketRoutes.lookup.form()}
                            options={{
                                preserveScroll: true,
                                preserveState: true,
                            }}
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="booking_code">
                                            Kode booking
                                        </Label>
                                        <Input
                                            id="booking_code"
                                            name="booking_code"
                                            placeholder="GK-20261015-001"
                                            maxLength={20}
                                            required
                                            autoFocus
                                        />
                                        <InputError
                                            message={errors.booking_code}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="whatsapp">
                                            Nomor WhatsApp
                                        </Label>
                                        <Input
                                            id="whatsapp"
                                            name="whatsapp"
                                            placeholder="081234567890"
                                            maxLength={25}
                                            required
                                        />
                                        <InputError message={errors.whatsapp} />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Search className="size-4" />
                                            {processing
                                                ? 'Mencari...'
                                                : 'Cek Tiket'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {ticket !== null ? (
                    <TicketResult
                        ticket={ticket}
                        resetUrl={adminTicketRoutes.index.url()}
                    />
                ) : (
                    <div className="flex flex-col items-center gap-2 rounded-lg border border-dashed py-10 text-center">
                        <Ticket className="size-8 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            Hasil verifikasi tiket muncul di sini setelah kode
                            booking dan nomor WhatsApp dicocokkan.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

AdminTicketCheck.layout = {
    breadcrumbs: [
        {
            title: 'Cek Tiket',
            href: adminTicketRoutes.index(),
        },
    ],
};
