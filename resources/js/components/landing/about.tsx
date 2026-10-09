import { ArrowRight } from 'lucide-react';
import TopographyPattern from '@/components/brand/topography-pattern';
import Reveal from '@/components/landing/reveal';
import Section from '@/components/landing/section';

export default function About() {
    return (
        <Section
            id="tentang"
            eyebrow="Tentang GoKemping"
            title="Lebih banyak menikmati perjalanan."
            description="Siapkan pengalaman outdoor di Garut dengan perlengkapan yang bisa disewa sesuai kebutuhan."
        >
            <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-stretch">
                <Reveal className="relative isolate flex min-h-56 items-end overflow-hidden rounded-lg bg-pine-950 p-6 text-pine-50 sm:min-h-64 sm:p-8">
                    <TopographyPattern className="pointer-events-none absolute inset-0 -z-10 size-full text-pine-50 opacity-10" />
                    <div>
                        <p className="text-xs font-semibold tracking-[0.16em] text-ember-500 uppercase">
                            Outdoor · Garut
                        </p>
                        <p className="mt-3 max-w-lg font-display text-2xl leading-tight font-semibold tracking-tight text-balance sm:text-3xl">
                            Tidak harus memiliki semua perlengkapan untuk mulai
                            menjelajah.
                        </p>
                    </div>
                </Reveal>

                <Reveal className="flex flex-col justify-center border-y border-border py-6 lg:px-6">
                    <p className="text-lg leading-relaxed text-foreground">
                        Pilih alat camping untuk bermalam di alam atau sepeda
                        untuk berkeliling. Kamu bisa melihat katalog dan
                        menyiapkan jadwal sewa sebelum menghubungi admin.
                    </p>
                    <a
                        href="#layanan"
                        className="mt-5 inline-flex w-fit items-center gap-2 rounded-sm text-sm font-semibold text-pine-700 underline decoration-pine-700/30 underline-offset-4 transition-colors hover:decoration-pine-700 focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none dark:text-pine-600"
                    >
                        Temukan layanan yang cocok
                        <ArrowRight aria-hidden="true" className="size-4" />
                    </a>
                </Reveal>
            </div>
        </Section>
    );
}
