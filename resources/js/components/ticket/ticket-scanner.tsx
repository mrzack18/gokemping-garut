import {
    BrowserMultiFormatReader,
    type IScannerControls,
} from '@zxing/browser';
import { LoaderCircle, RefreshCw, ScanLine } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * Pemindai QR tiket memakai kamera perangkat (ROADMAP 5.5 lanjutan).
 *
 * Dipakai di landing page dan panel admin supaya staf tidak perlu mengetik
 * kode booking dan nomor WhatsApp saat pengambilan barang. Kamera hanya hidup
 * selama dialog terbuka, dan selalu dimatikan lewat cleanup saat dialog
 * ditutup atau komponen dilepas.
 *
 * `onScan` disimpan di ref supaya perubahan identitas fungsi dari induk tidak
 * memulai ulang kamera di tengah pemindaian.
 */
export default function TicketScanner({
    onScan,
    label = 'Scan QR',
    description = 'Arahkan kamera ke QR di tiket penyewa. Tiket akan terbuka otomatis tanpa mengetik kode.',
    variant = 'default',
}: {
    onScan: (text: string) => void;
    label?: string;
    description?: string;
    variant?: 'default' | 'public';
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const controlsRef = useRef<IScannerControls | null>(null);
    const onScanRef = useRef(onScan);
    const [open, setOpen] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [isStarting, setIsStarting] = useState(false);
    const [retryKey, setRetryKey] = useState(0);

    useEffect(() => {
        onScanRef.current = onScan;
    }, [onScan]);

    const video = (
        <video
            ref={videoRef}
            className={`aspect-square w-full object-cover ${variant === 'default' ? 'rounded-lg bg-black' : ''}`}
            muted
            playsInline
        />
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        const video = videoRef.current;

        if (video === null) {
            return;
        }

        let cancelled = false;
        setError(null);
        setIsStarting(true);

        const reader = new BrowserMultiFormatReader();

        reader
            .decodeFromConstraints(
                { video: { facingMode: 'environment' } },
                video,
                (result, scanError, controls) => {
                    if (
                        cancelled ||
                        result === undefined ||
                        scanError !== undefined
                    ) {
                        return;
                    }

                    controls.stop();
                    setOpen(false);
                    onScanRef.current(result.getText());
                },
            )
            .then((controls) => {
                if (cancelled) {
                    controls.stop();

                    return;
                }

                controlsRef.current = controls;
                setIsStarting(false);
            })
            .catch(() => {
                if (!cancelled) {
                    setIsStarting(false);
                    setError(
                        'Kamera tidak bisa diakses. Izinkan akses kamera di peramban, atau isi kode booking dan nomor WhatsApp secara manual.',
                    );
                }
            });

        return () => {
            cancelled = true;
            controlsRef.current?.stop();
            controlsRef.current = null;
        };
    }, [open, retryKey]);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(true)}
            >
                <ScanLine aria-hidden="true" className="size-4" />
                {label}
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Scan QR tiket</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    {error !== null ? (
                        variant === 'public' ? (
                            <div
                                role="status"
                                className="space-y-4 rounded-md border border-destructive/20 bg-destructive/5 p-4"
                            >
                                <p className="text-sm text-destructive">
                                    {error}
                                </p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setError(null);
                                        setRetryKey((key) => key + 1);
                                    }}
                                >
                                    <RefreshCw
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    Coba lagi
                                </Button>
                            </div>
                        ) : (
                            <p className="text-sm text-destructive">{error}</p>
                        )
                    ) : variant === 'public' ? (
                        <div className="relative overflow-hidden rounded-lg bg-black">
                            {video}
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute inset-0 grid place-items-center"
                            >
                                <div className="size-[58%] rounded-md border border-white/80 shadow-[0_0_0_999px_rgba(0,0,0,0.2)]" />
                            </div>
                            {isStarting ? (
                                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/55 text-sm text-white">
                                    <LoaderCircle
                                        aria-hidden="true"
                                        className="size-6 animate-spin"
                                    />
                                    Menyiapkan kamera…
                                </div>
                            ) : null}
                        </div>
                    ) : (
                        video
                    )}

                    <p className="text-xs text-muted-foreground">
                        Kamera aktif hanya selama dialog ini terbuka.
                    </p>
                </DialogContent>
            </Dialog>
        </>
    );
}
