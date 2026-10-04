/**
 * Helper perhitungan periode booking yang dipakai bersama oleh form jadwal,
 * biodata, dan halaman review.
 *
 * Aturan durasi mengikuti keputusan ROADMAP 3.5: durasi adalah selisih tanggal
 * selesai dikurangi tanggal mulai, tanggal selesai adalah batas pengembalian
 * dan tidak ikut dihitung, dan periode satu hari tetap bernilai 1 hari supaya
 * tidak ada booking bernilai nol rupiah.
 */

const MS_PER_DAY = 86_400_000;

const longDateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

/**
 * Parse tanggal `YYYY-MM-DD` sebagai waktu lokal midnight.
 *
 * `new Date('2026-10-10')` ditafsirkan sebagai UTC, sehingga di timezone
 * negatif seperti WIB tanggalnya bisa mundur satu hari. Format eksplisit
 * `T00:00:00` menghindari masalah itu.
 */
export function parseBookingDate(value: string | null): Date | null {
    if (value === null || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * Tanggal panjang berbahasa Indonesia, contoh: `10 Oktober 2026`.
 */
export function formatBookingDate(value: string | null): string {
    const date = parseBookingDate(value);

    return date === null ? '-' : longDateFormatter.format(date);
}

/**
 * Durasi sewa dalam hari. Mengembalikan `0` kalau salah satu tanggal tidak
 * bisa dibaca, dan minimal `1` untuk periode satu hari atau tanggal sama.
 */
export function durationInDays(
    start: string | null,
    end: string | null,
): number {
    const startDate = parseBookingDate(start);
    const endDate = parseBookingDate(end);

    if (startDate === null || endDate === null) {
        return 0;
    }

    const diff = Math.round(
        (endDate.getTime() - startDate.getTime()) / MS_PER_DAY,
    );

    return diff > 0 ? diff : 1;
}
