import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import Logo from '@/components/brand/logo';
import TopographyPattern from '@/components/brand/topography-pattern';
import { whatsappLink } from '@/lib/format';
import { login } from '@/routes';
import { dashboard } from '@/routes/admin';
import catalogRoutes from '@/routes/catalog';
import services from '@/routes/services';
import type { LandingBusiness } from '@/types';
import { ArrowRight, Menu } from 'lucide-react';
import { useEffect, useState, type PropsWithChildren } from 'react';

type PublicLayoutProps = PropsWithChildren<{
    businesses: LandingBusiness[];
    /** Prefix link anchor untuk halaman selain landing page. */
    anchorBase?: string;
}>;

const navigation = [
    { label: 'Layanan', href: '#layanan' },
    { label: 'Produk', href: '#produk' },
    { label: 'Cara sewa', href: '#cara-sewa' },
    { label: 'Tentang', href: '#tentang' },
    { label: 'Cek tiket', href: '#cek-tiket' },
    { label: 'FAQ', href: '#faq' },
    { label: 'Kontak', href: '#kontak' },
];

function catalogHref(slug: string): string {
    if (slug === 'gokemping') {
        return catalogRoutes.gokemping.url();
    }

    if (slug === 'sewa-sepeda-garut') {
        return catalogRoutes.sewaSepedaGarut.url();
    }

    return `/${slug}`;
}

export default function PublicLayout({
    businesses,
    anchorBase = '',
    children,
}: PublicLayoutProps) {
    const { auth } = usePage<{ auth: { user: { name: string } | null } }>()
        .props;
    const [open, setOpen] = useState(false);

    useEffect(() => {
        document.body.classList.add('public-theme');

        return () => document.body.classList.remove('public-theme');
    }, []);

    return (
        <div className="public-theme flex min-h-screen flex-col bg-background">
            <a
                href="#main-content"
                className="sr-only z-[60] rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
            >
                Lewati ke konten
            </a>

            <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur">
                <div className="mx-auto flex min-h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                    <Link
                        href="/"
                        aria-label="GoKemping — kembali ke beranda"
                        className="shrink-0 rounded-sm focus-visible:ring-[3px] focus-visible:ring-primary/40 focus-visible:outline-none"
                    >
                        <Logo className="gap-2" showTagline={false} />
                    </Link>

                    <nav
                        aria-label="Navigasi utama"
                        className="hidden items-center gap-0.5 xl:flex"
                    >
                        {navigation.map((item) => (
                            <a
                                key={item.href}
                                href={`${anchorBase}${item.href}`}
                                className="rounded-md px-2 py-2 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                            >
                                {item.label}
                            </a>
                        ))}
                    </nav>

                    <div className="flex shrink-0 items-center gap-2">
                        {auth.user ? (
                            <Button asChild size="sm">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button
                                    asChild
                                    size="sm"
                                    variant="ghost"
                                    className="hidden sm:inline-flex"
                                >
                                    <a href={`${anchorBase}#cek-tiket`}>
                                        Cek tiket
                                    </a>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href={services.index()}>
                                        Mulai sewa
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </Link>
                                </Button>
                            </>
                        )}

                        <Sheet open={open} onOpenChange={setOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="outline"
                                    className="xl:hidden"
                                    aria-label="Buka menu navigasi"
                                >
                                    <Menu
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="right"
                                className="flex w-[min(88vw,360px)] flex-col overflow-y-auto"
                            >
                                <SheetHeader className="text-left">
                                    <SheetTitle asChild>
                                        <div>
                                            <Logo />
                                        </div>
                                    </SheetTitle>
                                </SheetHeader>
                                <nav
                                    aria-label="Navigasi mobile"
                                    className="flex flex-col gap-1 px-4 py-2"
                                >
                                    {navigation.map((item) => (
                                        <a
                                            key={item.href}
                                            href={`${anchorBase}${item.href}`}
                                            onClick={() => setOpen(false)}
                                            className="rounded-md px-3 py-3 text-sm font-medium text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                                        >
                                            {item.label}
                                        </a>
                                    ))}
                                </nav>
                                <Separator className="my-3" />
                                <div className="grid gap-2 px-4 pb-5">
                                    <Button asChild>
                                        <Link
                                            href={services.index()}
                                            onClick={() => setOpen(false)}
                                        >
                                            Mulai sewa
                                            <ArrowRight
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <a
                                            href={`${anchorBase}#cek-tiket`}
                                            onClick={() => setOpen(false)}
                                        >
                                            Cek tiket
                                        </a>
                                    </Button>
                                    {businesses.map((business) => (
                                        <Link
                                            key={business.id}
                                            href={catalogHref(business.slug)}
                                            onClick={() => setOpen(false)}
                                            className="px-1 py-2 text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Katalog {business.name}
                                        </Link>
                                    ))}
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>

            <main
                id="main-content"
                tabIndex={-1}
                className="flex-1 outline-none"
            >
                {children}
            </main>

            <footer className="relative isolate overflow-hidden bg-pine-950 text-pine-50">
                <TopographyPattern className="pointer-events-none absolute inset-0 -z-10 size-full text-pine-50 opacity-[0.08]" />
                <div className="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 sm:py-14">
                    <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_0.8fr_1.2fr]">
                        <div className="space-y-4">
                            <Logo inverse />
                            <p className="max-w-xs text-sm leading-relaxed text-pine-50/70">
                                Penyewaan perlengkapan camping dan sepeda untuk
                                menikmati kegiatan outdoor di Garut.
                            </p>
                        </div>

                        <div className="space-y-4">
                            <h2 className="text-xs font-semibold tracking-[0.14em] text-pine-50/55 uppercase">
                                Layanan
                            </h2>
                            <ul className="space-y-2 text-sm">
                                {businesses.map((business) => (
                                    <li key={business.id}>
                                        <Link
                                            href={catalogHref(business.slug)}
                                            className="text-pine-50/80 transition-colors hover:text-white"
                                        >
                                            {business.name}
                                        </Link>
                                    </li>
                                ))}
                                <li>
                                    <Link
                                        href={services.index()}
                                        className="text-pine-50/80 transition-colors hover:text-white"
                                    >
                                        Bandingkan layanan
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div className="space-y-4">
                            <h2 className="text-xs font-semibold tracking-[0.14em] text-pine-50/55 uppercase">
                                Navigasi
                            </h2>
                            <ul className="space-y-2 text-sm">
                                {navigation
                                    .filter((item) =>
                                        [
                                            '#cara-sewa',
                                            '#cek-tiket',
                                            '#faq',
                                        ].includes(item.href),
                                    )
                                    .map((item) => (
                                        <li key={item.href}>
                                            <a
                                                href={`${anchorBase}${item.href}`}
                                                className="text-pine-50/80 transition-colors hover:text-white"
                                            >
                                                {item.label}
                                            </a>
                                        </li>
                                    ))}
                                <li>
                                    <Link
                                        href={services.index()}
                                        className="text-pine-50/80 transition-colors hover:text-white"
                                    >
                                        Pilih layanan
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div className="space-y-4">
                            <h2 className="text-xs font-semibold tracking-[0.14em] text-pine-50/55 uppercase">
                                Kontak
                            </h2>
                            <ul className="space-y-4">
                                {businesses.map((business) => (
                                    <li
                                        key={business.id}
                                        className="space-y-1.5"
                                    >
                                        <p className="text-sm font-medium">
                                            {business.name}
                                        </p>
                                        {business.address ? (
                                            <p className="text-xs leading-relaxed text-pine-50/65">
                                                {business.address}
                                            </p>
                                        ) : null}
                                        {business.phone ? (
                                            <a
                                                href={`tel:${business.phone}`}
                                                className="block text-xs text-pine-50/75 hover:text-white"
                                            >
                                                {business.phone}
                                            </a>
                                        ) : null}
                                        {business.email ? (
                                            <a
                                                href={`mailto:${business.email}`}
                                                className="block text-xs break-all text-pine-50/75 hover:text-white"
                                            >
                                                {business.email}
                                            </a>
                                        ) : null}
                                        <a
                                            href={whatsappLink(
                                                business.whatsapp,
                                                `Halo ${business.name}, saya mau tanya soal sewa.`,
                                            )}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex text-xs font-medium text-pine-50 underline decoration-pine-50/35 underline-offset-4 hover:decoration-pine-50"
                                        >
                                            Chat WhatsApp
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>

                    <Separator className="my-8 bg-pine-50/15" />
                    <div className="flex flex-col gap-3 text-xs text-pine-50/60 sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            &copy; {new Date().getFullYear()} GoKemping.
                            Pembayaran dilakukan manual dan dikonfirmasi admin.
                        </p>
                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="w-fit underline underline-offset-4 hover:text-white"
                        >
                            {auth.user ? 'Dashboard admin' : 'Masuk admin'}
                        </Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
