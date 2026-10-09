import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import type { LandingFaq } from '@/types';

/**
 * FAQ landing page (PRD section 28, ROADMAP 5.4).
 *
 * FAQ berasal dari tabel `faqs` per unit. Selama admin belum mengisi FAQ-nya,
 * daftar bawaan dari PRD tetap dipakai supaya section ini tidak kosong di
 * halaman publik. Nama unitnya ditampilkan karena satu halaman memuat FAQ dari
 * beberapa unit.
 */
const fallbackFaqs: { question: string; answer: string }[] = [
    {
        question: 'Apakah harus membuat akun sebelum menyewa?',
        answer: 'Tidak. GoKemping tidak memakai pendaftaran akun. Anda cukup memilih barang, mengisi data penyewa, lalu konfirmasi pesanan melalui WhatsApp admin.',
    },
    {
        question: 'Bagaimana cara membayar?',
        answer: 'Pembayaran dilakukan manual setelah pesanan dibuat. Pilihan yang tersedia adalah cash saat pengambilan barang, QRIS toko, atau transfer bank. Admin akan mengonfirmasi status pembayaran.',
    },
    {
        question: 'Apakah barang dijamin tersedia?',
        answer: 'Ya. Halaman booking menghitung stok terpakai dari booking yang sudah dikonfirmasi atau sedang disewa pada tanggal yang dipilih. Jika stok kurang, proses booking akan dihentikan.',
    },
    {
        question: 'Boleh sewa untuk beberapa hari?',
        answer: 'Bisa. Harga dihitung per hari sesuai satuan pada katalog, jadi total bertambah otomatis mengikuti jumlah hari sewa.',
    },
    {
        question: 'Bagaimana bila barang rusak saat disewa?',
        answer: 'Setiap barang punya ketentuan penyewaan sendiri yang ditampilkan di halaman detail. Kerusakan akibat penyewa menjadi tanggung jawab penyewa.',
    },
    {
        question: 'Apakah ada jam operasional?',
        answer: 'Hubungi admin lewat WhatsApp untuk memastikan jam pengambilan dan pengembalian pada tanggal yang Anda pilih.',
    },
];

export default function Faq({ faqs }: { faqs: LandingFaq[] }) {
    const items =
        faqs.length > 0
            ? faqs.map((faq) => ({
                  key: `faq-${faq.id}`,
                  question: faq.question,
                  answer: faq.answer,
                  business: faq.business.name,
              }))
            : fallbackFaqs.map((faq, index) => ({
                  key: `fallback-${index}`,
                  question: faq.question,
                  answer: faq.answer,
                  business: null,
              }));

    return (
        <Section
            id="faq"
            eyebrow="FAQ"
            title="Pertanyaan yang sering diajukan"
            description="Kalau jawabannya belum ada di sini, langsung tanya admin lewat WhatsApp."
            tone="sand"
        >
            <Reveal className="mx-auto max-w-3xl">
                <Accordion type="single" collapsible className="w-full">
                    {items.map((item) => (
                        <AccordionItem key={item.key} value={item.key}>
                            <AccordionTrigger>
                                <span className="flex flex-col items-start gap-0.5 text-left">
                                    {item.business !== null ? (
                                        <span className="text-xs font-normal text-muted-foreground">
                                            {item.business}
                                        </span>
                                    ) : null}
                                    {item.question}
                                </span>
                            </AccordionTrigger>
                            <AccordionContent className="text-muted-foreground">
                                {item.answer}
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </Reveal>
        </Section>
    );
}
