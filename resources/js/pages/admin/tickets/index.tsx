import { Form, Head, router } from '@inertiajs/react';
import { Search, Ticket } from 'lucide-react';
import { useState } from 'react';
import AdminEmptyState from '@/components/admin/empty-state';
import AdminPageHeader from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import TicketResult from '@/components/ticket/ticket-result';
import TicketScanner from '@/components/ticket/ticket-scanner';
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
import { parseTicketScan } from '@/lib/ticket';
import bookingRoutes from '@/routes/admin/bookings';
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
    const [scanError, setScanError] = useState<string | null>(null);

    /**
     * QR tiket berisi URL pindai publik. Di panel admin, yang diambil hanya
     * kode booking dan tokennya, lalu pencarian diarahkan ke route admin
     * supaya tiket tetap tampil di dalam panel dan tetap ter-scope unit.
     */
    function handleScan(text: string) {
        const parsed = parseTicketScan(text);

        if (parsed === null) {
            setScanError(
                'QR tidak dikenali. Pastikan yang dipindai adalah QR tiket dari aplikasi ini.',
            );

            return;
        }

        setScanError(null);
        router.get(
            adminTicketRoutes.scan.url(
                { booking: parsed.booking },
                { query: { token: parsed.token } },
            ),
            { preserveScroll: false },
        );
    }

    return (
        <>
            <Head title="Cek Tiket" />

            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <AdminPageHeader
                    title="Cek Tiket"
                    description="Verifikasi tiket saat pengambilan barang. Masukkan kode booking dan nomor WhatsApp penyewa; hanya tiket unit ini yang bisa diperiksa dari halaman ini."
                />

                <Card className="rounded-lg shadow-none">
                    <CardHeader>
                        <CardTitle className="font-display text-base">
                            Verifikasi tiket
                        </CardTitle>
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
                            className="grid gap-5 sm:grid-cols-2"
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

                                    <div className="flex flex-col gap-2 border-t border-border pt-4 sm:col-span-2 sm:flex-row sm:items-center">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="sm:min-w-36"
                                        >
                                            <Search
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                            {processing
                                                ? 'Mencari...'
                                                : 'Cek Tiket'}
                                        </Button>

                                        <TicketScanner onScan={handleScan} />
                                    </div>

                                    {scanError !== null ? (
                                        <div className="sm:col-span-2">
                                            <InputError
                                                role="alert"
                                                message={scanError}
                                            />
                                        </div>
                                    ) : null}
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {ticket !== null ? (
                    <TicketResult
                        ticket={ticket}
                        resetUrl={adminTicketRoutes.index.url()}
                        detailUrl={bookingRoutes.show.url(ticket.booking_code)}
                    />
                ) : (
                    <AdminEmptyState
                        icon={Ticket}
                        title="Belum ada tiket diperiksa"
                        description="Hasil verifikasi tiket muncul di sini setelah kode booking dan nomor WhatsApp dicocokkan."
                    />
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
