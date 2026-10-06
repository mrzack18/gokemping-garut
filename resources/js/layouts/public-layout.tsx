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
import { login } from '@/routes';
import { dashboard } from '@/routes/admin';
import tickets from '@/routes/tickets';
import type { LandingBusiness } from '@/types';
import { Menu } from 'lucide-react';
import { useState, type PropsWithChildren } from 'react';

type PublicLayoutProps = PropsWithChildren<{
    businesses: LandingBusiness[];
    /**
     * Prefix untuk link navigasi anchor. Halaman selain landing page perlu
     * menyetel `"/"` supaya menu tetap mengarah ke section landing page.
     */
    anchorBase?: string;
}>;

const navigation = [
    { label: 'Tentang', href: '#tentang' },
    { label: 'Layanan', href: '#layanan' },
    { label: 'Produk', href: '#produk' },
    { label: 'Cara Sewa', href: '#cara-sewa' },
    { label: 'Keunggulan', href: '#keunggulan' },
    { label: 'FAQ', href: '#faq' },
    { label: 'Kontak', href: '#kontak' },
];

export default function PublicLayout({
    businesses,
    anchorBase = '',
    children,
}: PublicLayoutProps) {
    const { auth } = usePage<{ auth: { user: { name: string } | null } }>()
        .props;
    const [open, setOpen] = useState(false);

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header className="sticky top-0 z-40 border-b bg-background/85 backdrop-blur">
                <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <Link href="/" className="flex flex-col leading-tight">
                        <span className="text-base font-semibold tracking-tight">
                            GoKemping
                        </span>
                        <span className="text-xs text-muted-foreground">
                            Sewa camping &amp; sepeda Garut
                        </span>
                    </Link>

                    <nav className="hidden items-center gap-1 lg:flex">
                        {navigation.map((item) => (
                            <a
                                key={item.href}
                                href={`${anchorBase}${item.href}`}
                                className="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            >
                                {item.label}
                            </a>
                        ))}

                        <Link
                            href={tickets.check()}
                            className="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            Cek Tiket
                        </Link>
                    </nav>

                    <div className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild size="sm">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <Button asChild size="sm" variant="outline">
                                <Link href={login()}>Masuk Admin</Link>
                            </Button>
                        )}

                        <Sheet open={open} onOpenChange={setOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="outline"
                                    className="lg:hidden"
                                    aria-label="Buka menu navigasi"
                                >
                                    <Menu className="size-4" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="w-72">
                                <SheetHeader>
                                    <SheetTitle>GoKemping</SheetTitle>
                                </SheetHeader>
                                <nav className="flex flex-col gap-1 px-4">
                                    {navigation.map((item) => (
                                        <a
                                            key={item.href}
                                            href={`${anchorBase}${item.href}`}
                                            onClick={() => setOpen(false)}
                                            className="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                        >
                                            {item.label}
                                        </a>
                                    ))}

                                    <Link
                                        href={tickets.check()}
                                        onClick={() => setOpen(false)}
                                        className="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    >
                                        Cek Tiket
                                    </Link>
                                </nav>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t bg-muted/30">
                <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
                    <div className="grid gap-8 md:grid-cols-3">
                        <div className="space-y-2">
                            <p className="font-semibold">GoKemping</p>
                            <p className="text-sm text-muted-foreground">
                                Penyewaan perlengkapan camping dan sepeda untuk
                                kebutuhan outdoor di Garut.
                            </p>
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-medium">Unit usaha</p>
                            <ul className="space-y-1 text-sm text-muted-foreground">
                                {businesses.map((business) => (
                                    <li key={business.id}>{business.name}</li>
                                ))}
                            </ul>
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-medium">Kontak</p>
                            <ul className="space-y-1 text-sm text-muted-foreground">
                                {businesses.map((business) => (
                                    <li key={business.id}>
                                        {business.address}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>

                    <Separator className="my-6" />

                    <p className="text-xs text-muted-foreground">
                        &copy; {new Date().getFullYear()} GoKemping. Pembayaran
                        dilakukan manual dan dikonfirmasi admin.
                    </p>
                </div>
            </footer>
        </div>
    );
}
