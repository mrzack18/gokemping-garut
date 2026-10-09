# ROADMAP — Redesign UI Halaman Public GoKemping

**Dasar dokumen:** [PRD.md](./PRD.md) · [ROADMAP.md](./ROADMAP.md)
**Tech Stack:** Laravel 13 · Inertia.js 3 · React 19 · TypeScript · Tailwind CSS v4 · shadcn/ui · Motion · Wayfinder · pnpm
**Status:** Implementasi utama selesai; pemeriksaan otomatis lulus. QA visual browser per breakpoint masih perlu dilakukan.
**Terakhir diperbarui:** 2026-10-06

Dokumen ini adalah panduan tunggal untuk tahap implementasi. Semua keputusan visual, file target, komponen baru, dan urutan pengerjaan sudah dikunci di sini supaya implementasi tidak perlu analisis ulang.

---

## 0. Ruang Lingkup

### 0.1 Area yang di-redesign (public)

| Route                                                  | Komponen                                                               | Keterangan                     |
| ------------------------------------------------------ | ---------------------------------------------------------------------- | ------------------------------ |
| `/`                                                    | `resources/js/pages/welcome.tsx` + `resources/js/components/landing/*` | Landing + section `#cek-tiket` |
| `/pilih-layanan`                                       | `resources/js/pages/services/index.tsx`                                | Perbandingan dua unit          |
| `/gokemping`, `/sewa-sepeda-garut`                     | `resources/js/pages/catalog/index.tsx`                                 | Katalog + filter + pagination  |
| `/gokemping/{product}`, `/sewa-sepeda-garut/{product}` | `resources/js/pages/catalog/show.tsx`                                  | Detail produk                  |
| `/{unit}/booking/{product}`                            | `resources/js/pages/booking/form.tsx`                                  | Langkah 1 — jadwal & jumlah    |
| `/{unit}/booking/biodata`                              | `resources/js/pages/booking/biodata.tsx`                               | Langkah 2 — biodata            |
| `/{unit}/booking/review`                               | `resources/js/pages/booking/review.tsx`                                | Langkah 3 — review & metode    |
| `/{unit}/booking/payment/{method}`                     | `resources/js/pages/booking/payment.tsx`                               | Langkah 4 — pembayaran & bukti |
| `/{unit}/booking/success`                              | `resources/js/pages/booking/success.tsx`                               | Tiket & WhatsApp               |
| Shell semua halaman di atas                            | `resources/js/layouts/public-layout.tsx`                               | Header + footer publik         |
| Komponen tiket                                         | `resources/js/components/ticket/*`                                     | QR, hasil tiket, scanner       |

### 0.2 Area yang TIDAK disentuh

- Seluruh `resources/js/pages/admin/**`, `resources/js/layouts/app/**`, `resources/js/components/admin/**`.
- `resources/js/components/ui/**` — **default class tidak diubah** (dipakai admin). Variasi visual dilakukan lewat `className` di titik pemakaian atau komponen publik baru.
- Business logic, controller, service, route, migration, dan alur booking. Pengecualian kecil yang diizinkan hanya pada `LandingController` (menambah data `photo` produk unggulan — lihat §6.2).
- Auth admin (`pages/auth/**`), settings, halaman laporan PDF (`resources/views/reports/*`).

---

## 1. Kondisi UI Public Saat Ini

### 1.1 Tema & token

- `resources/css/app.css` masih memakai tema default shadcn **monokrom**: `--primary: oklch(0.205 0 0)` (hitam), seluruh surface abu netral. Tidak ada warna brand sama sekali.
- Font hanya **Instrument Sans** (400/500/600), dikonfigurasi di `vite.config.ts` lewat `bunny('Instrument Sans')` dan dipakai via `@fonts` di `resources/views/app.blade.php`.
- `--radius: 0.625rem` seragam untuk semua elemen.
- Dark mode aktif mengikuti preferensi sistem (`HandleAppearance` + `initializeTheme()`), tetapi custom visual publik (mis. kotak amber) baru sebagian menanganinya.

### 1.2 Shell publik (`public-layout.tsx`)

- Header sticky tipis: logo hanya teks "GoKemping", nav anchor, satu tombol "Masuk Admin"/"Dashboard", Sheet mobile.
- Footer 3 kolom: deskripsi, daftar unit, alamat. Belum ada navigasi, kontak telp/email/WA, maupun ruang untuk identitas.
- Semua halaman publik kecuali `/pilih-layanan` sudah mengoper `anchorBase="/"`.

### 1.3 Landing (`welcome.tsx` + `components/landing/*`)

- Urutan: Hero → BannerCarousel → strip "Lihat semua layanan" → About → Services → FeaturedProducts → RentalSteps → TicketCheck → Advantages → FAQ → Contact.
- Hampir semua section memakai `Section` yang identik: `border-t`, eyebrow kecil, `h2` sama besar, grid kartu seragam → **tidak ada hierarki visual**, terasa datar dan berulang.
- Hero memakai latar `radial-gradient` hijau-hitam transparan (satu-satunya "warna") dan hanya teks.
- `FeaturedProducts` hanya menampilkan ikon `Boxes` berulang karena payload tidak menyertakan `photo`; kartunya tidak bisa diklik.
- Konten About/Contact dan Hero-highlights/Advantages saling mengulang.
- Animasi memakai tiga pola berbeda (Reveal, `motion.*` inline, `animate` saat mount); prop `delay` pada `Reveal` kemungkinan tidak berefek karena `transition` ada di dalam variant.
- Typo "pesenan" di `advantages.tsx` (baris ~42).

### 1.4 `/pilih-layanan` (`pages/services/index.tsx`)

- Sudah memprioritaskan data DB (`service_intro`, `service_highlights`, `rental_terms`, contoh produk), tetapi CTA katalog memakai `<a>` (full reload) dan **tidak mengoper `anchorBase="/"`** sehingga menu anchor header menuju section yang tidak ada di halaman ini.

### 1.5 Katalog & detail

- `catalog/index.tsx`: filter dibungkus satu `Card` besar dengan judul "Cari dan filter" + copy teknis ("Filter tersimpan di alamat halaman…"). Kartu produk memakai `Card` standar; tombol "Lihat Detail" outline kecil; status ketersediaan badge hitam/merah; info "Kode booking unit ini dimulai dengan …" tampil menonjol padahal jargon internal.
- `catalog/show.tsx`: galeri + tiga kartu informasi + CTA; tidak ada breadcrumb; tidak ada aksi sticky di mobile; spesifikasi memakai tabel `grid-cols-3` sederhana.

### 1.6 Alur booking (5 halaman)

- Tidak ada **step indicator** di seluruh alur.
- Pola layout sama (max-w-6xl, grid 3 kolom, sidebar sticky `lg:top-24`) tetapi banyak detail yang tidak konsisten: `space-y-4` vs `space-y-6`, durasi animasi 0.45/0.5 detik, error via `InputError` vs `<p>` manual, success page `max-w-3xl` terpisah.
- `form.tsx`: bukan elemen `<form>`, input quantity memakai `<input>` mentah, dan **server error dari `usePage().props.errors` tidak pernah dibersihkan** sehingga tombol "Lanjut" bisa tersangkut nonaktif sampai reload.
- `biodata.tsx`: status lookup lama berada jauh dari field pemicu dan tidak `aria-live`.
- `review.tsx`: RadioGroup metode pembayaran tidak dibungkus `fieldset`/`legend`; helper text kontradiktif dengan perilaku disabled.
- `payment.tsx`: state upload vs file lokal sulit dibedakan; error delete proof tidak pernah tampil.
- `success.tsx`: tidak ada tombol salin kode booking; status hanya teks; format rupiah berbeda (`Rp {total_label}` vs `formatRupiah`).
- Mikro-teks usang menyebut "dibangun pada ROADMAP 3.11 / 3.12" padahal fitur sudah ada, di `biodata.tsx` dan `payment.tsx`.

### 1.7 Tiket

- `TicketResult` memakai `Card` + `Table` admin-style dan mengimpor `components/admin/booking-status-badge` (kebocoran layer, biarkan dulu demi scope).
- `TicketScanner` belum punya reticle, status loading, atau tombol coba lagi.
- Form `#cek-tiket` hanya kode booking + WhatsApp, satu kartu di tengah halaman.

### 1.8 Brand asset

- `public/favicon.svg` dan `public/apple-touch-icon.png` masih **aset default Laravel** (logo merah `#FF2D20`). Tidak ada logo mark GoKemping.

---

## 2. Masalah Utama (Prioritas Perbaikan)

1. **Tidak ada identitas brand.** Warna, logo, dan tipografi serba default; tidak terasa seperti produk GoKemping.
2. **Hierarki visual datar.** Semua section identik; tidak ada variasi tone, layout, atau imagery; mata tidak punya titik fokus.
3. **Fotografi produk tidak dimanfaatkan.** Katalog punya foto asli, tapi landing menampilkan ikon placeholder berulang.
4. **Duplikasi konten & komponen.** About vs Contact, Hero vs Advantages, kartu buatan vs `Card`, `<a>` vs Inertia `Link`, tiga pola animasi.
5. **Alur booking terasa administratif.** Tanpa stepper, tanpa aksi mobile yang mudah dijangkau, error tidak konsisten, dan ada bug error tersangkut di `form.tsx`.
6. **Inkonsistensi kecil yang menumpuk.** Lebar container success, format rupiah, status tanpa badge, copy teknis/jargon ("prefix kode booking", "filter tersimpan di alamat halaman").
7. **Aksesibilitas.** Tidak ada `aria-live` untuk status async (ketersediaan, lookup), `aria-describedby` tidak dipasang, radiogroup tanpa label grup.
8. **Navigasi rusak di `/pilih-layanan`** karena `anchorBase` tidak dikirim.
9. **Aset & danger statement usang/**typo mengikis kesan profesional.

---

## 3. Arah Desain

### 3.1 Konsep: "Basecamp"

GoKemping adalah penyewaan alat camping **dan** sepeda di Garut (dataran tinggi, gunung, udara terbuka). Karakternya: **outdoor, rapi, tegas, dapat dipercaya**. Desain baru diarahkan ke nuansa **basecamp/perlengkapan outdoor yang tertata** — seperti etalase toko outdoor profesional yang bersih, bukan template SaaS.

Kata kunci visual:

- **Pine & sand** — hijau pinus sebagai warna brand, netral pasir/batu sebagai kanvas.
- **Editorial** — tipografi besar dan tegas, whitespace luas, teks informatif jelas.
- **Fungsional** — harga, status, dan CTA selalu terbaca dalam satu pandangan.
- **Fotografi dulu** — foto produk/banner jadi fokus utama; dekorasi diminimalkan.
- **Garis tipis** — border crisp, radius kecil, shadow hampir tidak ada.

### 3.2 Prinsip

1. **Satu aksen brand, dipakai hemat.** Hijau pinus untuk aksi & identitas; aksen "ember" (jingga hangat) hanya untuk penanda kecil yang perlu perhatian (mis. badge promo/highlight), bukan untuk seluruh tombol.
2. **Hindari template AI:** tanpa glassmorphism, tanpa gradient besar, tanpa blob/inner-glow, tanpa kartu serba `rounded-2xl` dengan shadow lembut. Radius maksimal `rounded-xl`, kebanyakan `rounded-lg`/`rounded-md`.
3. **Variasi tone section, bukan variasi hiasan.** Hierarki dibangun dari pergantian latar (putih → pasir → hijau gelap) dan ukuran tipografi, bukan dari ornamen.
4. **Motif topografi** sebagai satu-satunya elemen dekoratif, opacity rendah, hanya di panel hijau gelap (hero band, footer, band cek tiket).
5. **Konsisten lintas halaman:** container, header halaman, tombol, badge, jarak, dan sudut sama di semua halaman publik.
6. **Jangan korban-kan fungsi.** Semua interaksi (availability, upload, WhatsApp, QR, lookup) tetap utuh; redesign hanya mengubah presentasi.

### 3.3 Referensi Karakter Visual (rujukan rasa, bukan jiplakan)

- **REI / Decathlon** — katalog outdoor informatif: harga jelas, status stok jelas, foto besar.
- **Patagonia** — foto alam dan tipografi editorial, section bernapas, tone latar bergantian.
- **AllTrails / Komoot** — informasi fungsional padat (peta, kondisi, langkah) dengan ikon garis tegas.
- **Alur checkout Airbnb/Klook** — ringkasan sticky, langkah jelas, tombol aksi mobile mudah dijangkau.
- **WhatsApp-first commerce Indonesia** — kepercayaan lewat transparansi harga, kontak, dan langkah manual yang jelas.

### 3.4 Yang dipertahankan dari branding lama

- Nama **GoKemping** dan sub-label "Sewa camping & sepeda Garut".
- Dua unit usaha sebagai dua jalur utama (GoKemping & Sewa Sepeda Garut) — struktur informasi PRD tidak berubah.
- Struktur section landing dan anchor `#tentang`, `#layanan`, `#produk`, `#cara-sewa`, `#cek-tiket`, `#keunggulan`, `#faq`, `#kontak` (dipakai tautan dari halaman sukses).

---

## 4. Design Tokens

Semua token ditulis di `resources/css/app.css`. Nilai di bawah adalah acuan; boleh disetel halus saat implementasi selama **kontras AA tetap terpenuhi**.

### 4.1 Palet warna (OKLCH)

Brand — Pine:

| Token        | Nilai usulan            | Pemakaian               |
| ------------ | ----------------------- | ----------------------- |
| `--pine-950` | `oklch(0.19 0.035 155)` | Band gelap, footer      |
| `--pine-900` | `oklch(0.25 0.045 155)` | Panel hero, hover band  |
| `--pine-800` | `oklch(0.32 0.06 155)`  | Primary hover           |
| `--pine-700` | `oklch(0.40 0.075 155)` | **--primary**           |
| `--pine-600` | `oklch(0.47 0.09 155)`  | Link/aksen teks         |
| `--pine-100` | `oklch(0.93 0.03 155)`  | Latar ikon/badge lembut |
| `--pine-50`  | `oklch(0.97 0.015 155)` | Sorotan section         |

Netral — Sand/Stone:

| Token                | Nilai usulan            | Pemakaian                    |
| -------------------- | ----------------------- | ---------------------------- |
| `--background`       | `oklch(0.995 0.002 95)` | Latar halaman (putih hangat) |
| `--card`             | `oklch(1 0 0)`          | Kartu                        |
| `--sand-50`          | `oklch(0.98 0.008 90)`  | Tone section "sand"          |
| `--sand-100`         | `oklch(0.96 0.012 90)`  | Muted surface                |
| `--border`           | `oklch(0.90 0.008 120)` | Garis                        |
| `--foreground`       | `oklch(0.24 0.015 160)` | Teks utama (hitam kehijauan) |
| `--muted-foreground` | `oklch(0.50 0.012 150)` | Teks sekunder                |

Aksen & status:

| Token              | Nilai usulan           | Pemakaian                  |
| ------------------ | ---------------------- | -------------------------- |
| `--ember-500`      | `oklch(0.72 0.15 60)`  | Aksen kecil, highlight     |
| `--ember-600`      | `oklch(0.63 0.14 55)`  | Teks aksen di latar terang |
| `--success`        | `oklch(0.52 0.12 152)` | Status tersedia/lunas      |
| `--destructive`    | `oklch(0.55 0.20 27)`  | Habis/batal/ditolak        |
| `--warning-bg`     | `oklch(0.97 0.04 85)`  | Kotak peringatan           |
| `--warning-border` | `oklch(0.86 0.09 80)`  | Border peringatan          |
| `--warning-text`   | `oklch(0.42 0.09 70)`  | Teks peringatan            |

Dark mode (nilai kanvas hijau gelap):

| Token                  | Nilai usulan                                      |
| ---------------------- | ------------------------------------------------- |
| `--background`         | `oklch(0.16 0.012 160)`                           |
| `--card`               | `oklch(0.20 0.014 160)`                           |
| `--foreground`         | `oklch(0.95 0.01 150)`                            |
| `--muted-foreground`   | `oklch(0.72 0.012 150)`                           |
| `--border`             | `oklch(0.30 0.015 155)`                           |
| `--primary`            | `oklch(0.72 0.11 155)` (primary-foreground gelap) |
| `--primary-foreground` | `oklch(0.18 0.02 160)`                            |

> Catatan implementasi: token semantic brand di-scope pada `.public-theme`; `PublicLayout` juga mengaktifkan class tersebut di `<body>` selama halaman publik tampil agar portal Radix (Sheet/Dialog/Select) memakai token yang sama. Nilai default `:root`/`.dark`, sidebar, dan chart admin dipertahankan sehingga halaman admin tidak ikut berubah.

Chart token (`--chart-*`) dibiarkan mendekati nilai sekarang atau disesuaikan seperlunya; jangan sampai grafik admin kehilangan kontras.

### 4.2 Tipografi

| Peran           | Font                              | Ukuran/kelas                                                                      | Catatan                           |
| --------------- | --------------------------------- | --------------------------------------------------------------------------------- | --------------------------------- |
| Display/H1 hero | **Bricolage Grotesque** (600/700) | `text-4xl sm:text-5xl lg:text-6xl tracking-[-0.03em] leading-[1.05] text-balance` | Karakter tegas, sedikit editorial |
| H2 section      | Bricolage Grotesque 600           | `text-2xl sm:text-3xl tracking-tight`                                             |                                   |
| H3 kartu        | Instrument Sans 600               | `text-base`/`text-lg`                                                             |                                   |
| Body            | Instrument Sans 400               | `text-base leading-relaxed text-pretty`                                           |                                   |
| Small/meta      | Instrument Sans 400/500           | `text-sm`/`text-xs`                                                               |                                   |
| Angka           | Instrument Sans                   | `tabular-nums`                                                                    | Harga, durasi, tanggal            |

Implementasi font:

- `vite.config.ts` → tambah `bunny('Bricolage Grotesque', { weights: [600, 700] })` di array `fonts`. Jika family tidak tersedia di Bunny, fallback ke `Sora` atau `Plus Jakarta Sans` lalu catat di PR.
- `app.css` → `--font-sans: 'Instrument Sans', ...` (tetap) dan tambah `--font-display: 'Bricolage Grotesque', var(--font-sans)`; daftarkan `--font-display` di blok `@theme`.
- Heading memakai `font-display`; body tetap `font-sans`. Jangan pakai font display untuk teks panjang.
- Naikkan sedikit kontras bobot: heading 600 (bukan 700 kecuali display hero).

### 4.3 Radius, border, shadow

| Elemen         | Nilai           | Tailwind                      |
| -------------- | --------------- | ----------------------------- |
| `--radius`     | `0.5rem` (8px)  | `rounded-lg`                  |
| Kartu & panel  | 8px             | `rounded-lg`                  |
| Gambar/foto    | 6px             | `rounded-md`                  |
| Tombol/input   | 6px             | `rounded-md`                  |
| Badge status   | pill fungsional | `rounded-full` (hanya status) |
| Badge kategori | 4px             | `rounded-sm`                  |

- Shadow: `shadow-xs` hanya untuk tombol & popover; kartu `shadow-none` + `border`.
- Kartu interaktif (produk/unit): hover `border-foreground/20` + `shadow-sm` + `-translate-y-0.5`; transisi 150–200ms.
- Tidak ada ring dekoratif. `focus-visible:ring-[3px] ring-primary/35` konsisten.

### 4.4 Spacing & layout

- Container: `mx-auto w-full max-w-6xl px-4 sm:px-6` (semua halaman publik, termasuk success).
- Ritme section: `py-14 sm:py-20` (default), hero `py-16 sm:py-24`.
- Jarak antar elemen: `space-y-6` untuk blok konten, `gap-4` grid rapat, `gap-6`/`gap-8` grid besar.
- Padding kartu: `p-5` (kartu rapat) / `p-6` (kartu utama).
- Header halaman: `pb-8` lalu konten `mt-8`; hindari dua `border-b` berurutan.

### 4.5 Ikon

- Tetap `lucide-react`. Ukuran: 16 (`size-4`) inline, 20 (`size-5`) fitur, maksimum 28.
- Warna ikon mengikuti konteks (`text-primary`, `text-muted-foreground`, atau status).
- Hindari pola "icon tile" `bg-primary/10` berulang di semua kartu; batasi ke 2–3 tempat strategis (mis. kartu unit di `/pilih-layanan`).
- Ikon dekoratif diberi `aria-hidden`.

### 4.6 Visual treatment khusus

- **Topografi:** komponen SVG `components/brand/topography-pattern.tsx`, garis tipis `currentColor` opacity `0.07–0.10`, dipakai di panel pine-950 (hero band, footer, band cek tiket).
- **Foto produk:** rasio `aspect-4/3` (kartu katalog, kartu unggulan), `aspect-square` (thumbnail booking), `object-cover`; selalu dengan `alt`, `loading="lazy"` (kecuali hero), dan `width`/`height` bila memungkinkan untuk mencegah CLS.
- **Overlay foto:** gradient hitam tipis hanya untuk legibilitas teks di atas banner (`from-black/70 via-black/20 to-transparent`).
- **Badge status:** komponen `StatusPill` dengan dot + label; "Tersedia" hijau, "Tidak tersedia/Habis" merah, status booking mengikuti palet badge admin (publik aman, tanpa NIK).
- **Nomor langkah:** angka besar outline lebih tegas daripada lingkaran `bg-primary/10`.

---

## 5. Rencana Per Halaman

### 5.1 `PublicLayout` (header & footer)

**Header**

- Kiri: logo mark baru (SVG) + wordmark "GoKemping" + tagline kecil "Sewa camping & sepeda Garut" (disembunyikan < `sm`).
- Tengah (desktop `lg`): nav anchor `Tentang, Layanan, Produk, Cara Sewa, Cek Tiket, FAQ, Kontak` dengan underline indicator saat hover/focus.
- Kanan: tombol outline "Cek Tiket" (anchor `#cek-tiket`) + tombol primary "Mulai Sewa" → `/pilih-layanan` saat belum login; saat login tetap tombol "Dashboard".
- "Masuk Admin" dipindah sebagai link kecil di footer (lebih profesional; tetap tersedia).
- Mobile: Sheet berisi logo, nav besar, dua CTA full-width, plus tautan unit langsung.
- Tetap: prop `businesses`, `anchorBase`, state `auth.user`, target anchor (`#...`), `sticky top-0 z-40 border-b bg-background/90 backdrop-blur`.

**Footer**

- Tone gelap (`bg-pine-950 text-pine-50`) + pattern topografi tipis.
- 4 kolom: (1) brand + deskripsi + jam layanan bila ada; (2) Layanan: dua unit + link katalog; (3) Navigasi: anchor landing + Cek Tiket + Pilih Layanan; (4) Kontak: WA, telepon, email, alamat tiap unit (dari `businesses`).
- Bar bawah: copyright, "Pembayaran manual dikonfirmasi admin", link "Masuk Admin".
- Tetap: seluruh data dari `businesses`, tidak ada hardcode nomor.

### 5.2 Landing (`welcome.tsx` + komponen)

Urutan section dipertahankan. Perubahan per komponen:

**`hero.tsx`**

- Ganti gradient radial dengan komposisi split `lg:grid-cols-12`: kiri `lg:col-span-7` teks (eyebrow, H1, subcopy, dua CTA unit + "Cara sewa", trust row 3 poin dengan ikon garis), kanan `lg:col-span-5` panel foto: gambar banner pertama (bila ada) atau kolase 2 foto produk unggulan; fallback: panel `bg-pine-900` + pattern topografi + ikon outline.
- Tambah baris statistik ringkas statis (mis. "2 unit usaha · Tanpa akun · Konfirmasi WhatsApp") — jangan mengarang angka yang tidak ada datanya.
- CTA unit tetap `serviceUrl(business)`; jaga aksesibilitas kontras teks.

**`banner-carousel.tsx`**

- Perlebar menjadi band full container dengan `aspect-[16/7]` desktop dan `aspect-[4/3]` mobile; dots indicator + panah; label unit kecil di atas judul.
- Autoplay opsional dimatikan demi performa; jika diaktifkan, jeda ≥ 6 detik dan berhenti saat hover/fokus.
- Tetap: Embla, loop jika >1, branching link internal (Inertia `Link`)/eksternal, render `null` bila kosong.

**Strip "Lihat semua layanan"** (di `welcome.tsx`)

- Ubah menjadi band tipis `bg-sand-50` dengan link teks + panah, bukan tombol outline penuh lebar.

**`about.tsx`**

- Layout editorial `lg:grid-cols-[1.1fr_1fr]`: kiri narasi + bullet kepercayaan (tanpa akun, data per unit terpisah, konfirmasi admin); kanan dua kartu unit ringkas (nama, deskripsi singkat, alamat, tombol WA).
- Hapus kotak dashed; WhatsApp link tetap.

**`services.tsx`**

- Dua kartu unit besar (bukan kartu kecil): ikon Tent/Bike, nama unit, `service_intro` DB (fallback copy), highlight check-list, contoh kategori, CTA primary "Lihat Peralatan"/"Lihat Sepeda" via **Inertia `Link`**.
- "Bandingkan layanan" cukup sekali di bawah grid, bukan diulang di tiap kartu.
- Tetap: `serviceUrl`, data `businesses`.

**`featured-products.tsx`**

- Pakai `photo` dari payload (lihat §6.2), fallback placeholder ikon yang lebih rapi.
- Kartu bisa diklik penuh ke halaman detail produk (sesuai unit), dengan `focus-visible` ring; harga tegas `text-lg font-semibold`; badge kategori kecil; status stok sebagai `StatusPill` atau teks `Sisa N`.
- CTA "Lihat semua produk" per unit di bawah grid.
- Tetap: animasi reveal, empty state.

**`rental-steps.tsx`**

- Ganti 4 kartu seragam menjadi deretan langkah bernomor dengan garis penghubung horizontal (desktop) / vertikal (mobile); angka outline besar + judul + deskripsi singkat.
- Sinkronkan urutan dengan alur nyata: pilih barang → atur jadwal → isi data & bayar → konfirmasi WhatsApp.
- Tetap: `<ol>` semantik, prop `businesses` (dipakai untuk catatan admin akhir).

**`ticket-check.tsx`**

- Jadikan band kontras: panel `bg-pine-950 text-pine-50` + pattern, form putih di dalamnya (2 kolom), tombol submit primary besar, tombol `TicketScanner` di samping, hasil `TicketResult` tampil sebagai kartu receipt di bawah.
- Tambah helper statis "Kode booking diawali `GK-` atau `SSG-`" dan `autoFocus` pada input kode (opsional, hormati UX mobile).
- Tetap (kritis): `Form {...tickets.lookup.form()}` dengan `preserveScroll/preserveState`, error mapping, `handleScan` → validasi same-origin → `router.get`, `resetUrl` kembali ke `#cek-tiket`, data tanpa NIK/alamat.

**`advantages.tsx`**

- Layout border-only (tanpa `Card`, tanpa shadow): grid 2/3 kolom dengan pemisah garis tipis, ikon garis `text-primary`, judul, deskripsi.
- Hilangkan konten yang menduplikasi hero; pertahankan 6 poin, perbaiki typo "pesenan".

**`faq.tsx`**

- Accordion tetap; tambah label unit kecil, padding lebih lega, dan heading section yang lebih tegas. Pertahankan fallback 6 FAQ dan jalur data DB.

**`contact.tsx`**

- Fokus kontak: kartu per unit dengan blok kontak (alamat, telp, email, WA) + tombol WA primary; peta `iframe` di dalam bingkai `rounded-md border` dengan heading kecil.
- Tambah `loading="lazy"` placeholder `bg-muted` di sekitar iframe.

**`section.tsx` & `reveal.tsx`**

- `section.tsx`: tambah prop `tone?: 'default' | 'sand' | 'dark' | 'bare'`; `border-t` hanya untuk `tone="default"` dan hilangkan border ganda setelah hero; heading memakai `font-display`; untuk `dark` pakai teks terang.
- `reveal.tsx`: perbaiki agar `delay` benar-benar bekerja (pindahkan `transition` ke `visible` dengan merge `delay`, atau pakai prop `transition`), hormati `prefers-reduced-motion` dengan fallback tanpa animasi, hapus `cn()` berargumen tunggal.

### 5.3 `/pilih-layanan` (`services/index.tsx`)

- Page header konsisten: eyebrow "Pilih layanan", H1, deskripsi.
- Kartu unit besar dua kolom dengan ikon unit, `service_intro` DB-first, highlight, contoh barang sebagai badge, `rental_terms` di blok pasir, CTA full-width via Inertia `Link` ke katalog.
- **Perbaikan bug:** oper `anchorBase="/"` ke `PublicLayout`.
- Tambah baris kecil "Butuh bantuan memilih? Chat admin" dengan link WA unit.
- Tetap: `previewProducts`, `booking_code_prefix` (tampilkan sebagai catatan kecil), fallback copy.

### 5.4 Katalog (`catalog/index.tsx`)

- Header halaman: judul "Katalog {unit}", deskripsi, breadcrumb kecil "Pilih layanan / Katalog".
- **Toolbar filter baru:** baris pertama search besar (ikon di kiri) + tombol "Filter" (mobile membuka panel collapsible; desktop menampilkan kategori, urutan, harga min/maks dalam satu baris). Copy teknis dihapus, ganti microcopy singkat "Filter mengikuti URL sehingga bisa dibagikan".
- Info hasil: "Menampilkan X–Y dari N produk" di kiri; prefix kode booking dipindah ke tooltip/teks kecil di bawah atau ke halaman tiket.
- **Kartu produk baru** (`ProductCard`): foto `aspect-4/3`, category chip kecil, judul 2 baris, harga `text-lg` dengan unit, `StatusPill` stok, deskripsi `line-clamp-2`, seluruh kartu dapat diklik ke detail + satu tombol "Lihat detail" (atau seluruh kartu link dengan affordance jelas).
- Empty state: ikon + pesan + tombol reset.
- Pagination: tombol prev/next dengan chevron + nomor halaman, `aria-current="page"`.
- Tetap (kritis): state `draft`, `apply`/`buildQuery`, `router.get`, `preserveScroll`, `pending`, batas harga, `visiblePages`, foto fallback.

### 5.5 Detail produk (`catalog/show.tsx`)

- Breadcrumb "Pilih layanan / Katalog Unit / Nama produk".
- Grid `lg:grid-cols-[1.05fr_1fr]`: kiri galeri (carousel tetap; tambah strip thumbnail bila >1 atau indikator titik), kanan panel info: badge kategori + status, H1, harga besar, sisa stok, CTA "Sewa Sekarang" (`size="lg"`, full-width di mobile), catatan singkat alur booking.
- Deskripsi/Spesifikasi/Ketentuan: tiga blok dengan heading kecil dan garis pemisah, tanpa tiga `Card` bershadow; spesifikasi jadi daftar `dl` yang rapi (2 kolom di layar lebar).
- **Mobile sticky bar:** harga + tombol "Sewa Sekarang" sticky di bawah (`lg:hidden`), tetap menghormati status stok.
- Tetap: kondisi `images` (0/1/banyak), carousel + tombol navigasi keyboard, tombol disabled saat stok habis, `bookingUrl`.

### 5.6 Alur booking (5 halaman)

**Komponen bersama baru (dipakai semua halaman booking):**

- `components/booking/booking-shell.tsx` — kerangka: `PublicLayout`, page header dengan tombol kembali, grid `lg:grid-cols-3`, slot konten + slot ringkasan, dan `BookingMobileBar` di mobile.
- `components/booking/booking-steps.tsx` — indikator 4 langkah: `Jadwal → Biodata → Review → Pembayaran`; langkah aktif ditandai; halaman sukses menampilkan keempat langkah selesai.
- `components/booking/booking-summary.tsx` — kartu ringkasan sticky `lg:top-24` dengan baris `dl`, separator, total, dan slot aksi; varian ini menggantikan tiga implementasi ringkasan yang sekarang berbeda.
- `components/booking/booking-mobile-bar.tsx` — bar sticky bawah (mobile) berisi total + CTA utama; muncul saat CTA utama tidak terlihat (boleh sederhana: selalu tampil di < `lg` kecuali di halaman sukses).
- `components/booking/form-field.tsx` (opsional) — label + deskripsi + `InputError` + `aria-describedby` konsisten.

**`form.tsx` (Jadwal & jumlah)**

- Bungkus konten dalam `<form onSubmit>`; tombol submit `type="submit"`.
- Tanggal: pertahankan `input type="date"` (sederhana & native) dengan `min`, atau ganti ke pola popover kalender hanya jika tidak menambah dependensi baru; jangan ubah kontrak data.
- Quantity stepper: pakai `Input` yang sama dengan halaman lain (lebar tetap, `text-center tabular-nums`), tombol ± dengan `aria-label`.
- Status ketersediaan: tambah `aria-live="polite"`, ikon status, dan teks yang lebih jelas; pertahankan logika clamp quantity.
- **Perbaikan bug:** error dari `usePage().props.errors` harus bisa dibersihkan saat nilai berubah (simpan salinan lokal + reset saat `startDate/endDate/quantity` berubah, atau pindah sepenuhnya ke validasi klien + `useForm`). Jangan biarkan tombol "Lanjut" tersangkut nonaktif.
- Ringkasan estimasi + formula tetap; CTA "Lanjut" di sidebar dan di `BookingMobileBar`.
- Tetap: fetch availability dengan AbortController, parameter `start_date/end_date`, endpoint per unit, `router.post` draft dengan `onStart/onFinish`, redirect ke biodata.

**`biodata.tsx`**

- Pakai `BookingShell` + `BookingSteps`; kartu form dengan grid 2 kolom tetap.
- Pindahkan indikator lookup tepat di bawah field WhatsApp/NIK, beri `aria-live="polite"`; pesan "data lama ditemukan" tetap.
- Hapus catatan usang ROADMAP; ganti dengan catatan berguna ("Data dipakai admin untuk verifikasi penyewa").
- Tetap: `useForm`, lookup on blur dengan AbortController, merge data, guard slug, `renter_count` hanya unit sepeda, submit ke `biodata.store`.

**`review.tsx`**

- Pakai `BookingShell`; dua kartu (Detail Booking, Data Penyewa) dengan pola `dl` seragam; tombol "Ubah" konsisten (satu label).
- RadioGroup dibungkus `<fieldset>` + `<legend>` "Metode pembayaran"; item disabled diberi alasan jelas.
- Hapus helper kontradiktif; sesuaikan urutan (metode dulu, lalu centang konfirmasi) atau ubah logika disabled agar cocok dengan teks.
- Total + CTA di summary/mobile bar; `paymentForm.errors.method` pakai `InputError`.
- Tetap: `paymentForm.post`, `chooseMethod`, `canPay`, `readyMethods`, redirect server-side saat stok berubah.

**`payment.tsx`**

- Kartu metode dengan header jelas (QRIS / Transfer / Cash), blok instruksi rapi, tombol salin dengan state "Tersalin" yang kembali setelah ~2 detik.
- Area upload: dropzone-style (border dashed, ikon, teks format & ukuran) yang memicu input file; preview dengan nama file + status "Sudah diunggah"/"Belum diunggah" yang kontras; tombol Hapus & Ganti.
- Tampilkan error `removeForm`; perbaiki ternary label tombol.
- Hapus catatan usang ROADMAP 3.12.
- Tetap: validasi 5 MB klien, `proofForm.post`, `removeForm.delete`, `bookingForm.post` (`store`), revoke object URL, `canConfirm`, peringatan ketersediaan amber (restyle ke token warning), QRIS image & alt.

**`success.tsx`**

- Ubah jadi **halaman receipt** (`max-w-3xl` dipertahankan): ikon centang + "Booking tersimpan", kartu kode booking besar + tombol salin (dengan feedback), ringkasan `dl`, `StatusPill` untuk status pembayaran & booking, total besar.
- CTA utama WhatsApp full-width (tetap `target="_blank" rel="noopener noreferrer"`), fallback `Alert` bila nomor tidak tersedia; tombol sekunder "Cek Status Tiket" ke `#cek-tiket`; kartu QR + tombol unduh; accordion pesan WhatsApp; dua catatan info.
- Format uang konsisten `formatRupiah`; tombol kembali juga di atas.
- Tetap: `receipt` dari session, `ticket_url`, `whatsapp.message`, `TicketQr`, `Accordion`.

### 5.7 Tiket (publik)

- `ticket-check.tsx`: lihat §5.2.
- `ticket-result.tsx`: restyle ke gaya receipt (header kode booking mono + status pill, daftar item tanpa `Table` admin — gunakan baris `flex justify-between` rapi, total ditebalkan); tetap memakai `BookingStatusBadge`/`PaymentStatusBadge` agar mapping status tidak terduplikasi.
- `ticket-scanner.tsx`: tambah reticle (frame sudut) di atas video, spinner saat inisialisasi, pesan error + tombol "Coba lagi" yang me-restart kamera, teks izin kamera lebih jelas. Pertahankan lifecycle kamera (start saat dialog terbuka, stop saat decode/tutup).
- `ticket-qr.tsx`: pertahankan latar putih; rapikan bingkai, tambah `aria-live` untuk status gagal, dan empty state tombol unduh.

---

## 6. Rencana Komponen

### 6.1 Komponen publik baru

| Komponen              | File                                        | Fungsi                                                                          |
| --------------------- | ------------------------------------------- | ------------------------------------------------------------------------------- |
| `Logo`                | `components/brand/logo.tsx`                 | Mark SVG GoKemping + wordmark; varian `default`/`inverse`                       |
| `TopographyPattern`   | `components/brand/topography-pattern.tsx`   | SVG garis kontur dekoratif, `currentColor`, `aria-hidden`                       |
| `PageHeader`          | `components/public/page-header.tsx`         | Eyebrow + H1 + deskripsi + tombol kembali/breadcrumb + slot aksi                |
| `ProductCard`         | `components/public/product-card.tsx`        | Kartu produk katalog & unggulan (foto, harga, status, link)                     |
| `UnitCard`            | `components/public/unit-card.tsx`           | Kartu unit untuk landing & `/pilih-layanan`                                     |
| `StatusPill`          | `components/public/status-pill.tsx`         | Dot + label status (tersedia/habis/status booking)                              |
| `EmptyState`          | `components/public/empty-state.tsx`         | Ikon + pesan + aksi (katalog kosong, dll.)                                      |
| `InfoNote`            | `components/public/info-note.tsx`           | Kotak info/peringatan dengan token warning (menggantikan kelas amber manual)    |
| `BookingShell`        | `components/booking/booking-shell.tsx`      | Kerangka halaman booking                                                        |
| `BookingSteps`        | `components/booking/booking-steps.tsx`      | Indikator 4 langkah                                                             |
| `BookingSummary`      | `components/booking/booking-summary.tsx`    | Kartu ringkasan sticky                                                          |
| `BookingMobileBar`    | `components/booking/booking-mobile-bar.tsx` | CTA sticky bawah di mobile                                                      |
| `ServiceRoute` helper | `lib/public-routes.ts`                      | Konsolidasi `routesFor`, `catalogUrlBySlug`, `productUrlBuilders`, `serviceUrl` |

`lib/public-routes.ts` menggantikan duplikasi resolver yang sekarang ada di 5 file booking + katalog + detail. Perilaku fallback unknown slug dipertahankan persis.

### 6.2 Perubahan backend minimal (hanya presentasi data)

**`app/Http/Controllers/LandingController.php`**

- Tambahkan foto utama produk unggulan ke payload: eager load `images` (mis. `with(['images:id,product_id,path,is_primary,sort_order'])`), lalu map produk ke array yang menyertakan `photo` dengan logika yang sama seperti `CatalogController::photo()` (`is_primary` dulu, jika tidak ada ambil gambar pertama).
- Tambah `LandingProduct.photo: string | null` di `resources/js/types/landing.ts` dan pakai di `featured-products.tsx`.
- Opsional: lakukan hal yang sama untuk `ServiceSelectionController` (contoh barang) bila ingin badge berfoto.
- Jalankan `composer run test` setelah perubahan; test landing yang memeriksa struktur props harus tetap hijau (sesuaikan test hanya jika memang kontrak baru).

### 6.3 Komponen lama yang diubah

| File                                                                            | Perubahan                                                            |
| ------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| `resources/css/app.css`                                                         | Token palet, radius, font display, token status/warning (light+dark) |
| `vite.config.ts`                                                                | Tambah family font display di array `fonts`                          |
| `resources/js/layouts/public-layout.tsx`                                        | Header + footer baru, logo, CTA, link admin di footer                |
| `resources/js/components/landing/section.tsx`                                   | Prop `tone`, tipografi display, aturan border                        |
| `resources/js/components/landing/reveal.tsx`                                    | Delay berfungsi, reduced-motion, plus prop `transition`              |
| Seluruh `components/landing/*.tsx`                                              | Restyle sesuai §5.2                                                  |
| `resources/js/components/ticket/*`                                              | Restyle sesuai §5.7                                                  |
| `pages/services/index.tsx`, `pages/catalog/index.tsx`, `pages/catalog/show.tsx` | Restyle sesuai §5.3–5.5                                              |
| `pages/booking/*.tsx`                                                           | Restyle + komponen bersama sesuai §5.6                               |
| `public/favicon.svg`, `public/apple-touch-icon.png`                             | Ganti dengan mark GoKemping (favicon versi sederhana dari logo mark) |

**Tidak diubah:** semua `components/ui/*` (default class), `components/admin/*`, `pages/admin/*`, `layouts/app/*`, `components/ticket` bagian logika QR/scanner, serta seluruh controller/service kecuali §6.2.

---

## 7. Motion & Aksesibilitas

### 7.1 Motion

- Gunakan satu pola: `Reveal` (whileInView, `once: true`, `amount: 0.2`) dengan durasi 0.4–0.5s dan easing `[0.22, 1, 0.36, 1]`.
- Delay hanya untuk stagger, maksimum 3 item × 0.08s; jangan menunda konten utama.
- Hover hanya transformasi kecil (`translate-y-0.5`) dan perubahan border/shadow; tanpa animasi berulang.
- Hormati `prefers-reduced-motion: reduce` di `Reveal` dan komponen motion lain (langsung tampil tanpa transisi).
- Animasi mount halaman (`motion.div` initial/animate) boleh dipertahankan tetapi diseragamkan durasinya.

### 7.2 Aksesibilitas

- Semua status async (ketersediaan, lookup penyewa, pencarian tiket, upload) diberi `aria-live="polite"`.
- Error field memakai `InputError` + `aria-describedby` + `aria-invalid`.
- RadioGroup metode pembayaran dibungkus `fieldset`/`legend`.
- Kontras minimum AA: teks di `pine-950` memakai `pine-50`; teks tombol primary putih di `pine-700`.
- Fokus terlihat (`focus-visible:ring-[3px] ring-primary/35`) di semua elemen interaktif termasuk kartu yang dapat diklik.
- Target sentuh minimal 40×40px untuk tombol icon; stepper quantity diperbesar di mobile.
- `alt` deskriptif untuk foto produk; SVG dekoratif `aria-hidden`.
- Tambah skip-link "Lewati ke konten" di `PublicLayout` (opsional, low effort).

---

## 8. Responsive Strategy

Breakpoint Tailwind: `sm 640` · `md 768` · `lg 1024` · `xl 1280`. Desain mobile-first karena mayoritas penyewa memakai ponsel.

| Area                      | Mobile (<640)                                                                                            | Tablet (640–1023)              | Desktop (≥1024)                                  |
| ------------------------- | -------------------------------------------------------------------------------------------------------- | ------------------------------ | ------------------------------------------------ |
| Header                    | Logo mark + tombol menu Sheet; CTA utama di dalam Sheet                                                  | Sama; nav mulai muncul di `lg` | Nav lengkap + 2 CTA                              |
| Hero                      | 1 kolom: teks → CTA full-width → panel foto/pattern                                                      | 1 kolom, panel lebih pendek    | Split 7/5                                        |
| Banner                    | `aspect-[4/3]`, teks overlay lebih kecil                                                                 | `aspect-[16/9]`                | `aspect-[16/7]`, panah di sisi                   |
| Section landing           | `py-14`, heading `text-2xl`                                                                              | `py-16`                        | `py-20`, heading `text-3xl`                      |
| Kartu produk              | 1 kolom, foto penuh                                                                                      | 2 kolom                        | 3 kolom (katalog), 4 kolom (unggulan)            |
| Katalog filter            | Search + tombol "Filter" membuka panel (Collapsible)                                                     | Search + panel 2 kolom         | Satu baris: search + kategori + urutan + harga   |
| Detail produk             | Galeri → info → CTA; sticky bar bawah                                                                    | Sama                           | 2 kolom; panel info sticky `lg:sticky lg:top-24` |
| Alur booking              | 1 kolom; summary pindah ke atas konten atau diwakili MobileBar + ringkasan collapsible; CTA sticky bawah | 1 kolom atau 2 kolom ringan    | 2/3 konten + 1/3 summary sticky                  |
| Table spesifikasi/riwayat | Stack label/nilai vertikal                                                                               | 2 kolom                        | Grid tetap                                       |
| Footer                    | 1–2 kolom, accordion tidak perlu                                                                         | 2 kolom                        | 4 kolom + bar bawah                              |

Aturan tambahan:

- Jangan menaruh CTA utama hanya di akhir halaman pada mobile; sediakan `BookingMobileBar` / sticky CTA di katalog detail dan alur booking.
- Foto selalu `w-full h-auto object-cover` agar tidak overflow.
- Uji 320px (Android kecil), 390px, 768px, 1024px, 1440px.
- Pastikan QRIS 280×280 tetap muat di 320px (skala dengan `max-w-full`).

---

## 9. Prioritas Pengerjaan

### P0 — Fondasi (wajib selesai lebih dulu)

1. `app.css`: palet pine/sand/ember, token status, radius 0.5rem, font display (light + dark).
2. `vite.config.ts`: font Bricolage Grotesque.
3. Perbaikan cepat: typo "pesenan", catatan usang ROADMAP 3.11/3.12, `anchorBase` di `/pilih-layanan`, perbaikan `Reveal` delay + reduced motion.
4. `Logo` + aset favicon baru; `TopographyPattern`.
5. Redesign `PublicLayout` (header + footer).
6. `Section` dengan `tone`; `PageHeader`; konsolidasi route helper `lib/public-routes.ts`.
7. Verifikasi: build + lint + typecheck + cek admin tidak rusak layout.

### P1 — Landing

Hero, banner, about, services, featured products, rental steps, ticket band, advantages, FAQ, contact, strip layanan.

### P2 — Katalog & detail

`ProductCard`, `StatusPill`, `EmptyState`, toolbar filter, pagination, detail produk + sticky bar.

### P3 — Alur booking

`BookingShell`, `BookingSteps`, `BookingSummary`, `BookingMobileBar`; refactor 5 halaman; perbaikan bug error `form.tsx`.

### P4 — `/pilih-layanan`, tiket, dan polish

`UnitCard`, restyle services page, `TicketResult`/`TicketScanner`/`TicketQr`, dark mode pass, QA responsif, QA kontras, verifikasi admin.

---

## 10. Checklist Implementasi

### 10.1 Fondasi

- [x] Token palet & status ditulis di `resources/css/app.css` (light + dark)
- [x] `--radius` 0.5rem; radius kartu/gambar/tombol konsisten
- [x] `--font-display` terdaftar di `@theme`; font ditambahkan di `vite.config.ts`
- [x] `Logo` + `TopographyPattern` dibuat
- [x] `public/favicon.svg` dan `apple-touch-icon.svg` diganti mark GoKemping
- [x] `PublicLayout` header/footer baru; nav + CTA + link admin di footer
- [x] `Section` mendukung `tone`; border antar section ditata ulang
- [x] `Reveal` delay berfungsi; reduced-motion dihormati
- [ ] `lib/public-routes.ts` dibuat dan dipakai di katalog, detail, dan 5 halaman booking
- [x] `anchorBase="/"` di `/pilih-layanan`
- [x] Typo dan copy usang dibersihkan
- [x] Token default dan layout admin tetap terpisah dari tema public

### 10.2 Landing & katalog

- [x] Hero baru (split + foto/pattern + trust row)
- [x] Banner dengan rasio & dots; perilaku loop/perbandingan link tetap
- [x] About, Services, FeaturedProducts, RentalSteps, Advantages, FAQ, Contact direstyle
- [x] Foto produk unggulan tersedia (§6.2) dan kartu menaut ke detail
- [x] Ticket band baru; `#cek-tiket` tetap berfungsi
- [x] Toolbar filter katalog ditata ulang dan responsif
- [x] `ProductCard` + `StatusPill` + `EmptyState` dipakai di katalog & unggulan
- [x] Pagination mempertahankan `aria-current`
- [x] Detail produk: breadcrumb, layout 2 kolom, sticky bar mobile
- [x] Spesifikasi/deskripsi/ketentuan rapi tanpa tiga kartu bershadow

### 10.3 Alur booking

- [x] `BookingSteps` dan `BookingMobileBar` dipakai pada alur booking; ringkasan sticky ditata konsisten
- [ ] Lebar container & animasi seragam di semua halaman booking
- [x] `form.tsx` memakai `<form>`, error server bisa dibersihkan, tidak ada tombol tersangkut
- [x] `biodata.tsx`: indikator lookup dekat field, `aria-live`, catatan usang dihapus
- [x] `review.tsx`: `fieldset`/`legend`, helper text konsisten, `InputError`
- [x] `payment.tsx`: area bukti lebih jelas, error remove tampil, copy state reset, catatan usang dihapus
- [x] `success.tsx`: kartu kode booking + salin, status pill, format Rupiah konsisten, CTA WhatsApp & QR tetap
- [ ] Semua efek samping (AbortController, createObjectURL/revoke, router.post, redirect) diverifikasi ulang manual

### 10.4 Kualitas

- [ ] Dark mode dicek di landing, katalog, booking, tiket, dan halaman admin
- [ ] Responsif 320/390/768/1024/1440
- [ ] Kontras AA; fokus terlihat; target sentuh 40px
- [ ] `aria-live` pada semua status async
- [x] `pnpm run check`, `pnpm run types:check`, `pnpm run build` hijau
- [x] `composer run ci:check` hijau (Pint, PHPStan, PHPUnit)
- [ ] Alur manual: pilih layanan → katalog → detail → booking → biodata → review → pembayaran → upload bukti → konfirmasi → success → WhatsApp → cek tiket → scan QR
- [ ] Uji kegagalan: stok habis, tanggal invalid, lookup penyewa gagal, upload >5 MB, hapus bukti, nomor WA admin kosong, akses slug unit tidak dikenal

---

## 11. Risiko & Guardrail

### 11.1 Fungsi yang wajib dipertahankan (tidak boleh berubah)

1. **Routing & Wayfinder.** Semua URL dibangun dari `@/routes/*`; jangan menulis path literal baru. Fallback unknown slug tetap ada.
2. **Availability checking** (`form.tsx`): fetch dengan `AbortController`, query `start_date`/`end_date`, clamp quantity dari respons, pesan error bila gagal.
3. **Draft booking** `router.post(draft.store)`, redirect ke biodata.
4. **Lookup penyewa** (`biodata.tsx`): blur WhatsApp/NIK, abort request lama, merge data tanpa menimpa field yang sudah diisi secara keliru, NIK tidak dikembalikan server untuk lookup via WhatsApp.
5. **Upload bukti** (`payment.tsx`): validasi 5 MB klien + server, `accept` jpg/jpeg/png/webp, replace & delete, revoke object URL.
6. **WhatsApp**: URL dari server (`whatsapp.url`), pesan dari `whatsapp.message`, `target="_blank" rel="noopener noreferrer"`, fallback Alert.
7. **Cek tiket**: POST `/cek-tiket` dengan `preserveScroll/preserveState`, error dari server, tidak pernah menampilkan NIK/alamat, scan token HMAC via `router.get`.
8. **QR tiket**: generate di klien (`qrcode.toDataURL`), tombol unduh, spinner/gagal.
9. **Scanner**: start/stop kamera hanya saat dialog terbuka, `facingMode: environment`.
10. **Filter katalog**: query string, sort, bounds, pagination `preserveScroll`.
11. **Anchor `#cek-tiket`, `#tentang`, dll.** dipakai dari halaman sukses dan navigasi; jangan diganti id-nya.
12. **Struktur props Inertia & tipe TypeScript**; jangan ubah nama field tanpa memperbarui controller + test.

### 11.2 Risiko teknis

| Risiko                                                  | Mitigasi                                                                                       |
| ------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| Tema public terbawa ke portal Radix                     | Aktifkan class `public-theme` pada `<body>` selama layout public terpasang                     |
| `components/ui/*` juga dipakai admin                    | Jangan ubah default class; variasi lewat `className`/komponen publik                           |
| Font baru gagal di Bunny                                | Fallback `Sora`/`Plus Jakarta Sans`; verifikasi `pnpm run build` dan tampilan `@fonts`         |
| Token scoped tidak memengaruhi elemen admin             | Token semantic hanya aktif di `.public-theme`; `:root`/`.dark` tetap memakai nilai starter kit |
| Refactor `lib/public-routes.ts` menyentuh 7+ file       | Lakukan di P0 sebelum redesign halaman; verifikasi tiap route manual + `types:check`           |
| Komponen booking besar berisiko regresi                 | Refactor per halaman, commit terpisah, uji manual tiap langkah setelah tiap perubahan          |
| Menambah `photo` mengubah kontrak test landing          | Cek `tests/Feature` terkait landing; sesuaikan test bila memang kontrak berubah                |
| Motion berlebihan                                       | Batasi animasi ke satu pola; hormati reduced-motion                                            |
| Dark mode terlewat                                      | Checklist QA dark mode per halaman; status/warning memakai token, bukan kelas warna manual     |
| CTA ganda membingungkan (mis. dua "Bandingkan layanan") | Satu CTA utama per kartu; link sekunder sekali per section                                     |

### 11.3 Di luar scope

- Redesign admin/dashboard, halaman auth, settings.
- Perubahan alur bisnis, status booking, perhitungan harga, atau aturan ketersediaan.
- Payment gateway, autentikasi baru, atau fitur baru.
- SEO lanjutan (meta description dinamis, structured data) — boleh menyusul di PR terpisah.
- Internasionalisasi/format locale selain Indonesia.

---

## 12. Definisi Selesai (Definition of Done)

1. Seluruh halaman publik memakai bahasa visual yang sama: palet pine/sand, tipografi display, container dan spacing konsisten, tanpa gradient/glassmorphism berlebihan.
2. Tidak ada halaman publik monokrom default yang tersisa; logo mark dan favicon GoKemping terpasang.
3. Landing punya hierarki visual jelas (minimal 3 tone section berbeda) dan menampilkan foto produk/banner asli.
4. Alur booking punya stepper, ringkasan konsisten, dan CTA mobile yang mudah dijangkau; bug error `form.tsx` hilang.
5. Semua fungsi di §11.1 terverifikasi manual tetap berjalan.
6. `pnpm run check`, `pnpm run types:check`, `pnpm run build`, dan `composer run ci:check` hijau.
7. Dark mode dan responsif (320–1440px) lolos checklist QA.
8. Admin hanya terpengaruh warna aksen token; tidak ada perubahan layout/komponen admin.
