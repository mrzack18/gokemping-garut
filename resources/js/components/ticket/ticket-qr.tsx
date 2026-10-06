import { Download } from 'lucide-react';
import { useEffect, useState } from 'react';
import QRCode from 'qrcode';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

/**
 * QR tiket yang bisa diunduh (ROADMAP 5.5 lanjutan).
 *
 * QR dibuat di browser dari URL pindai bertanda tangan yang dikirim server,
 * jadi nominal dan kode booking tidak perlu dikirim ke layanan gambar
 * mana pun. Tombol unduh memakai data URL yang sama, sehingga berkas yang
 * diunduh persis QR yang tampil di layar.
 */
export default function TicketQr({
    url,
    fileName,
    size = 200,
}: {
    url: string;
    fileName: string;
    size?: number;
}) {
    const [dataUrl, setDataUrl] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let active = true;

        setDataUrl(null);
        setFailed(false);

        QRCode.toDataURL(url, {
            width: 512,
            margin: 1,
            errorCorrectionLevel: 'M',
        })
            .then((result) => {
                if (active) {
                    setDataUrl(result);
                }
            })
            .catch(() => {
                if (active) {
                    setFailed(true);
                }
            });

        return () => {
            active = false;
        };
    }, [url]);

    return (
        <div className="flex flex-col items-center gap-3">
            <div
                className="flex items-center justify-center rounded-lg border bg-white p-3"
                style={{ width: size + 24, height: size + 24 }}
            >
                {failed ? (
                    <p className="px-4 text-center text-sm text-muted-foreground">
                        QR gagal dibuat. Gunakan kode booking di atas untuk cek
                        tiket manual.
                    </p>
                ) : dataUrl === null ? (
                    <Spinner />
                ) : (
                    <img
                        src={dataUrl}
                        alt="QR tiket"
                        width={size}
                        height={size}
                    />
                )}
            </div>

            <Button asChild variant="outline" disabled={dataUrl === null}>
                <a
                    href={dataUrl ?? '#'}
                    download={fileName}
                    aria-disabled={dataUrl === null}
                    onClick={(event) => {
                        if (dataUrl === null) {
                            event.preventDefault();
                        }
                    }}
                >
                    <Download className="size-4" />
                    Unduh QR
                </a>
            </Button>
        </div>
    );
}
