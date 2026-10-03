import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import { CalendarCheck, LayoutGrid, ShieldCheck, Wallet } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';
import { dashboard } from '@/routes/admin';

type PageProps = {
    auth: {
        user: { name: string } | null;
    };
};

const features = [
    {
        icon: LayoutGrid,
        title: 'Katalog Terstruktur',
        description:
            'Tenda, tenda’agung, dan promocional beserta harga serta ketersediaan stok per unit.',
    },
    {
        icon: CalendarCheck,
        title: 'Booking Tanpa Konflik',
        description:
            'Tanggal sewa dan jumlah unit divalidasi otomatis agar tidak terjadi overbooking.',
    },
    {
        icon: Wallet,
        title: 'Pembayaran Terverifikasi',
        description:
            'Transfer bank dan QRIS dengan bukti pembayaran yang diverifikasi admin.',
    },
    {
        icon: ShieldCheck,
        title: 'Unit Terpisah',
        description:
            'Setiap usaha hanya melihat data sendiri; data penyewa disimpan lintas unit.',
    },
];

export default function Welcome() {
    const { auth } = usePage<PageProps>().props;
    const isAuthenticated = Boolean(auth.user);

    return (
        <>
            <Head title="GoKemping" />

            <div className="flex min-h-screen flex-col bg-background">
                <header className="border-b">
                    <div className="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-6 py-4">
                        <span className="text-lg font-semibold">GoKemping</span>

                        {isAuthenticated ? (
                            <Button asChild size="sm">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <Button asChild size="sm" variant="outline">
                                <Link href={login()}>Log in Admin</Link>
                            </Button>
                        )}
                    </div>
                </header>

                <main className="mx-auto w-full max-w-5xl flex-1 px-6">
                    <motion.section
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                        className="flex flex-col items-start gap-4 py-16 md:py-24"
                    >
                        <h1 className="text-3xl font-semibold tracking-tight md:text-5xl">
                            Sewa tenda tanpa ribet
                        </h1>
                        <p className="max-w-2xl text-muted-foreground md:text-lg">
                            GoKemping menyediakan katalog tenda event di Garut.
                            Pilih unit, tentukan tanggal, lalu selesaikan
                            pembayaran dengan konfirmasi admin.
                        </p>

                        <div className="flex flex-wrap gap-3 pt-2">
                            {isAuthenticated ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Buka Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <Button asChild disabled>
                                    <span>Katalog segera hadir</span>
                                </Button>
                            )}
                        </div>
                    </motion.section>

                    <section className="grid gap-4 pb-16 sm:grid-cols-2">
                        {features.map((feature, index) => (
                            <motion.article
                                key={feature.title}
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{
                                    duration: 0.3,
                                    delay: index * 0.08,
                                }}
                                className="rounded-xl border p-5"
                            >
                                <feature.icon className="size-5 text-muted-foreground" />
                                <h2 className="mt-3 font-medium">
                                    {feature.title}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {feature.description}
                                </p>
                            </motion.article>
                        ))}
                    </section>
                </main>

                <footer className="border-t">
                    <div className="mx-auto w-full max-w-5xl px-6 py-6 text-sm text-muted-foreground">
                        GoKemping — Fase 0 Foundation. Modul katalog, booking,
                        dan pembayaran menyusul.
                    </div>
                </footer>
            </div>
        </>
    );
}
