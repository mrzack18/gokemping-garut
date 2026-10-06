import {
    BrowserMultiFormatReader,
    type IScannerControls,
} from '@zxing/browser';
import { ScanLine } from 'lucide-react';
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
}: {
    onScan: (text: string) => void;
    label?: string;
    description?: string;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const controlsRef = useRef<IScannerControls | null>(null);
    const onScanRef = useRef(onScan);
    const [open, setOpen] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        onScanRef.current = onScan;
    }, [onScan]);

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
            })
            .catch(() => {
                if (!cancelled) {
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
    }, [open]);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(true)}
            >
                <ScanLine className="size-4" />
                {label}
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Scan QR tiket</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    {error !== null ? (
                        <p className="text-sm text-destructive">{error}</p>
                    ) : (
                        <video
                            ref={videoRef}
                            className="aspect-square w-full rounded-lg bg-black object-cover"
                            muted
                            playsInline
                        />
                    )}

                    <p className="text-xs text-muted-foreground">
                        Kamera aktif hanya selama dialog ini terbuka.
                    </p>
                </DialogContent>
            </Dialog>
        </>
    );
}
