import { Form, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import TicketResult from '@/components/ticket/ticket-result';
import TicketScanner from '@/components/ticket/ticket-scanner';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { parseTicketScan } from '@/lib/ticket';
import { home } from '@/routes';
import tickets from '@/routes/tickets';
import type { TicketDetail } from '@/types';

/**
 * Section cek tiket di landing page (ROADMAP 5.5).
 *
 * Sengaja tidak dibuat sebagai halaman terpisah: penyewa dan staf menemukannya
 * di tempat yang sama dengan informasi layanan, dan hasil pencariannya muncul
 * tepat di bawah formulir tanpa berpindah halaman.
 *
 * Tiket hanya terbuka dengan kombinasi kode booking dan nomor WhatsApp
 * penyewa, karena kode booking berurutan per hari dan bisa ditebak. NIK tidak
 * pernah ditampilkan di sini.
 */
export default function TicketCheck({
    ticket,
}: {
    ticket: TicketDetail | null;
}) {
    const [scanError, setScanError] = useState<string | null>(null);

    function handleScan(text: string) {
        const parsed = parseTicketScan(text);

        if (parsed === null) {
            setScanError(
                'QR tidak dikenali. Pastikan yang dipindai adalah QR tiket dari aplikasi ini.',
            );

            return;
        }

        setScanError(null);
        router.get(text, { preserveScroll: false });
    }

    return (
        <Section
            id="cek-tiket"
            eyebrow="Cek tiket"
            title="Lihat status booking-mu"
            description="Masukkan kode booking dan nomor WhatsApp yang dipakai saat memesan. Staf juga bisa memakai bagian ini untuk memeriksa tiket saat pengambilan barang."
        >
            <Reveal className="mx-auto max-w-3xl">
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            {...tickets.lookup.form()}
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

                                    <div className="flex flex-wrap items-center gap-2 sm:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Search className="size-4" />
                                            {processing
                                                ? 'Mencari...'
                                                : 'Cek Tiket'}
                                        </Button>

                                        <TicketScanner onScan={handleScan} />
                                    </div>

                                    {scanError !== null ? (
                                        <p className="text-sm text-destructive sm:col-span-2">
                                            {scanError}
                                        </p>
                                    ) : null}
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {ticket !== null ? (
                    <TicketResult
                        ticket={ticket}
                        resetUrl={`${home.url()}#cek-tiket`}
                    />
                ) : null}
            </Reveal>
        </Section>
    );
}
