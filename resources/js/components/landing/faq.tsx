import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';

const faqs = [
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

export default function Faq() {
    return (
        <Section
            id="faq"
            eyebrow="FAQ"
            title="Pertanyaan yang sering diajukan"
            description="Kalau jawabannya belum ada di sini, langsung tanya admin lewat WhatsApp."
        >
            <Reveal className="mx-auto max-w-3xl">
                <Accordion type="single" collapsible className="w-full">
                    {faqs.map((faq, index) => (
                        <AccordionItem
                            key={faq.question}
                            value={`faq-${index}`}
                        >
                            <AccordionTrigger>{faq.question}</AccordionTrigger>
                            <AccordionContent className="text-muted-foreground">
                                {faq.answer}
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </Reveal>
        </Section>
    );
}
