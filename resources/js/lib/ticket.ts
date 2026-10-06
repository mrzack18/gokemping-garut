/**
 * Pembantu QR tiket (ROADMAP 5.5 lanjutan).
 *
 * QR tiket berisi URL pindai bertanda tangan dari server:
 * `/cek-tiket/{booking_code}?token=...`. Halaman publik maupun panel admin
 * perlu membaca isi QR yang sama, jadi penguraiannya dipusatkan di sini.
 */

export type ScannedTicket = {
    booking: string;
    token: string;
};

/**
 * Uraikan URL pindai tiket dari teks QR.
 *
 * Hanya menerima URL dengan origin yang sama; QR dari domain lain tidak
 * pernah membawa tiket aplikasi ini, dan menavigasi ke domain asing dari
 * halaman cek tiket bukan hal yang diinginkan. Teks yang bukan URL, atau URL
 * dengan bentuk path yang salah, dikembalikan sebagai null supaya pemanggil
 * bisa menampilkan pesan yang jelas.
 */
export function parseTicketScan(text: string): ScannedTicket | null {
    let url: URL;

    try {
        url = new URL(text, window.location.origin);
    } catch {
        return null;
    }

    if (url.origin !== window.location.origin) {
        return null;
    }

    const match = url.pathname.match(/^\/cek-tiket\/([^/]+)\/?$/);
    const token = url.searchParams.get('token');

    if (match === null || token === null || token === '') {
        return null;
    }

    return {
        booking: decodeURIComponent(match[1]),
        token,
    };
}
