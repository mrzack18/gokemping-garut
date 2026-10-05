# ROADMAP — GoKemping Rental & Booking System

**Dasar dokumen:** [PRD.md](./PRD.md)
**Tech Stack:** Laravel 13 · Inertia.js 3 · React 19 · TypeScript · Tailwind CSS v4 · shadcn/ui · Motion · MySQL 8 · pnpm
**Status:** Fase 2 berjalan — 4.1 s/d 4.4 selesai
**Terakhir diperbarui:** 2026-10-05

---

## 1. Ringkasan Fase

| Fase | Nama              | Fokus                                          | Output Utama                                | Estimasi   |
| ---- | ----------------- | ---------------------------------------------- | ------------------------------------------- | ---------- |
| 0    | Foundation        | Bootstrap, auth admin, isolasi `business_id`   | Admin bisa login & melihat dashboard kosong | 3–4 hari   |
| 1    | Core Public       | Landing sampai kirim WhatsApp                  | Alur booking end-to-end berfungsi           | 12–15 hari |
| 2    | Admin Back-office | CRUD produk, booking, pembayaran, penyewa      | Admin bisa operasional penuh                | 12–15 hari |
| 3    | Management        | Laporan, export, statistik, content management | Laporan & konten bisa dikelola admin        | 7–10 hari  |

Total estimasi: **34–44 hari kerja** untuk 1 developer.

---

## 2. Fase 0 — Foundation

### 2.1 Prasyarat Lingkungan

- [x] Install Composer 2.x
- [x] Install pnpm (`corepack enable pnpm` atau `npm i -g pnpm`) — terpasang 12.8.1
- [x] Install Laravel CLI (`composer global require laravel/installer`)
- [x] Pastikan service MySQL 8 berjalan
- [x] Buat database `gokemping` + user khusus — database `gokemping` dan
      `gokemping_test` dibuat, user `gokemping` dengan hak
      SELECT/INSERT/UPDATE/DELETE/CREATE/DROP/ALTER/INDEX/REFERENCES pada kedua
      database tersebut (tanpa akses `root`).

### 2.2 Inisialisasi Proyek

- [x] `laravel new gokemping --react`
- [x] `pnpm install`
- [x] `pnpm add motion`
- [x] `pnpm dlx shadcn@latest add button card input label textarea dialog sheet`
- [x] `pnpm dlx shadcn@latest add select table badge tabs dropdown-menu`
- [x] `pnpm dlx shadcn@latest add calendar popover checkbox radio-group`
- [x] `pnpm dlx shadcn@latest add sonner skeleton separator avatar`
- [x] Siapkan `.env` (DB, `APP_NAME`, timezone `Asia/Jakarta`)
- [x] Setup disk `public` untuk storage produk & bukti bayar

### 2.3 Package Backend

- [x] `laravel/fortify` — autentikasi admin
- [x] `intervention/image` — kompresi & resize gambar produk
- [x] `openspout/openspout` — export Excel (Fase 3)
- [x] `barryvdh/laravel-dompdf` — export PDF (Fase 3)
- [x] `laravel/pint` + `larastan/larastan` (PHPStan level 7) - code quality

### 2.4 Database — Migration & Seeder

- [x] Migration `businesses`
- [x] Migration `users` + kolom `business_id` + `role`
- [x] Migration `categories`
- [x] Migration `products`
- [x] Migration `product_images`
- [x] Migration `customers`
- [x] Migration `bookings`
- [x] Migration `booking_items`
- [x] Migration `payments`
- [x] Migration `payment_methods`
- [x] Enum classes: `BookingStatus`, `PaymentStatus`, `PaymentMethodType`
- [x] Seeder 2 business (GoKemping, Sewa Sepeda Garut)
- [x] Seeder 2 user admin (satu per business)
- [x] Seeder kategori contoh + produk contoh
- [x] Factory untuk seluruh model (dipakai di test)

### 2.5 Isolasi Data (BR-05)

- [x] `EnsureBusinessAccess` middleware
- [x] Scope global `business_id` pada query
- [x] Policy per model: `ProductPolicy`, `CategoryPolicy`, `BookingPolicy`, `PaymentPolicy`
- [x] Halaman login admin
- [x] Layout dashboard (sidebar) terisolasi dari halaman publik
- [x] Redirect otomatis admin ke dashboard sesuai `business_id`

**Deliverable Fase 0:** Admin dapat login dan hanya melihat data unit bisnisnya.

**Status: selesai.** Verifikasi: `composer run ci:check` (Pint, PHPStan level 7, 31 test)
dan `pnpm run check` + `pnpm run types:check` + `pnpm run build` hijau.
Seluruh item pada 2.1–2.5 sudah tercentang.

Catatan environment lokal yang berbeda dari asumsi awal:

- pnpm yang terpasang adalah versi 12.8.1, bukan 10.x.
- Analisa statis memakai `larastan/larastan` dengan `phpstan analyse --memory-limit=1G`
  karena limit bawaan 128M tidak cukup untuk mem-boot Laravel.
- Aplikasi memakai user MySQL khusus `gokemping` (bukan `root`). Password ada di `.env`
  lokal dan tidak disimpan di repository.
- Akun dev: `admin@gokemping.test` dan `admin@sewasepedagarut.test`, password `password`
  (wajib diganti sebelum production).

---

## 3. Fase 1 — Core Public

### 3.1 Landing Page

- [x] Hero section + CTA (Sewa Alat Camping / Sewa Sepeda)
- [x] Section tentang GoKemping
- [x] Section layanan
- [x] Produk unggulan
- [x] Cara penyewaan (stepper)
- [x] Section keunggulan
- [x] FAQ
- [x] Kontak & lokasi
- [x] Animasi Motion: hero, scroll reveal, hover card

Implementasi:

- `LandingController` mengirim unit bisnis aktif dan produk unggulan, maksimal 4
  produk per unit supaya kedua unit sama-sama terlihat.
- `BusinessScope` dinonaktifkan secara eksplisit pada query produk karena
  halaman ini lintas tenant, termasuk ketika admin yang sedang login membuka
  landing page.
- Tiap section dipisah sebagai komponen di `resources/js/components/landing/`
  dengan `PublicLayout` untuk header, navigasi anchor, dan footer.
- `app.tsx` memetakan halaman `welcome` ke layout `null` karena layout publik
  dirender dari dalam halaman.
- Konten statis (tentang, layanan, keunggulan, FAQ, cara penyewaan) berada di
  komponen. Konten dinamis (nama unit, alamat, WhatsApp, produk, harga, stok)
  dibaca dari database.
- 8 feature test menutup akses tamu, filter unit dan produk nonaktif, batas
  jumlah produk per unit, urutan terbaru, serta kasus admin yang login.

### 3.2 Halaman Pilih Layanan

- [x] Kartu GoKemping → `/gokemping`
- [x] Kartu Sewa Sepeda Garut → `/sewa-sepeda-garut`

Implementasi:

- Route `GET /pilih-layanan` bernama `services.index`, ditangani
  `ServiceSelectionController`.
- Halaman memakai `PublicLayout` yang sama dengan landing page, jadi
  `app.tsx` memetakan prefix `services/` ke layout `null`.
- Kartu unit diambil dari database (nama, alamat, deskripsi, prefix kode
  booking) sehingga unit baru otomatis muncul tanpa ubah kode.
- Label tombol mengikuti PRD section 8: `Lihat Peralatan` dan `Lihat Sepeda`,
  disimpan di `service-copy.ts` bersama URL katalog masing-masing.
- Tiap kartu menampilkan maksimal 3 contoh barang beserta harga dari unit
  tersebut sebagai gambaran isi katalog sebelum pengunjung memilih.
- Copy per unit dipusatkan di `service-copy.ts` supaya landing page dan
  halaman pilih layanan tidak menduplikasi teks.
- 7 feature test menutup akses tamu, filter unit nonaktif, kebocoran produk
  antar unit, produk nonaktif dan stok nol, batas jumlah contoh, prefix kode
  booking, serta kasus admin yang login.

### 3.3 Katalog Produk

- [x] Halaman katalog per unit bisnis
- [x] Card produk: foto, nama, kategori, harga, satuan, status, deskripsi singkat
- [x] Search (nama produk)
- [x] Filter kategori
- [x] Filter harga (min/max)
- [x] Sorting (harga, nama, terbaru)
- [x] Pagination
- [x] State URL (`Inertia` + query string) agar filter bisa di-share

Implementasi:

- Path katalog mengikuti PRD section 34: `/gokemping` dan
  `/sewa-sepeda-garut`. Kedua path memakai `CatalogController` yang sama,
  slug unit dikirim sebagai route default sehingga controller tidak perlu
  membaca path URL secara manual.
- `BusinessScope` dinonaktifkan secara eksplisit karena katalog adalah halaman
  publik lintas tenant. Unit nonaktif dan slug yang tidak terdaftar
  menghasilkan 404, bukan katalog kosong.
- Card mengikuti PRD section 9: foto (gambar `is_primary`, atau gambar
  pertama menurut `sort_order`), nama, kategori, harga, satuan, status
  ketersediaan, dan deskripsi yang dipotong 160 karakter. Produk tanpa foto
  menampilkan placeholder, bukan gambar rusak.
- Semua state filter dibaca dari query string (`q`, `category`, `min_price`,
  `max_price`, `sort`, `page`) sehingga URL bisa di-share dan filter bertahan
  saat halaman di-refresh. Filter default tidak ditulis ke URL agar tautan
  tetap pendek.
- Parameter yang tidak valid tidak menghasilkan 422. `min_price` lebih besar
  dari `max_price` diperbaiki ke batas yang sama supaya daftar tidak kosong
  tanpa alasan, dan `sort` di luar daftar yang dikenal kembali ke `terbaru`.
- Karakter wildcard LIKE pada pencarian di-escape, sehingga `q=%` tidak
  berubah menjadi pencarian semua produk.
- Filter kategori hanya menerima slug kategori milik unit yang sedang dibuka.
  Slug kategori milik unit lain diabaikan, bukan dipakai menarik produk
  lintas tenant.
- Pagination 12 produk per halaman dengan `withQueryString()`, dan nomor
  halaman dirender ringkas dengan elipsis supaya URL panjang tidak/link
  quebrado.
- `PublicLayout` mendapat props opsional `anchorBase` supaya menu navigasi
  tetap mengarah ke section landing page saat dibuka dari halaman katalog.
- Tombol "Lihat Detail" pada PRD section 9 baru ditambahkan saat ROADMAP 3.4
  selesai, karena halaman detail produk baru ada pada langkah tersebut.
- 27 feature test menutup akses tamu, isolasi antar unit, unit nonaktif, slug
  tak dikenal, pelolosan wildcard, tiap filter, sorting, pagination, state URL,
  foto utama, dan status ketersediaan.

### 3.4 Detail Produk

- [x] Foto utama + gallery (shadcn/ui carousel)
- [x] Nama, kategori, harga, satuan
- [x] Deskripsi
- [x] Spesifikasi
- [x] Ketentuan penyewaan
- [x] Status ketersediaan
- [x] CTA "Sewa Sekarang"

Implementasi:

- Path detail mengikuti PRD section 34: `/gokemping/{product}` dan
  `/sewa-sepeda-garut/{product}`. Kedua path memakai
  `ProductDetailController` yang sama dengan slug unit sebagai route default.
- Produk dicari per pasangan unit bisnis dan slug, bukan lewat route model
  binding global. `products` hanya punya unique key per unit
  (`business_id`, `slug`), jadi slug yang sama bisa ada di dua unit dan
  binding global akan membuka produk dari unit yang salah.
- `BusinessScope` dinonaktifkan karena halaman ini publik lintas tenant.
  Produk nonaktif, produk terhapus, produk milik unit lain, dan unit nonaktif
  semuanya menghasilkan 404.
- Gallery memakai shadcn/ui carousel (dependensi baru
  `embla-carousel-react`). Card memakai carousel, satu foto memakai gambar
  tunggal, dan nol foto memakai placeholder, sehingga tombol navigasi tidak
  pernah muncul tanpa gunanya.
- Foto utama (`is_primary`) selalu berada di posisi pertama, sisanya mengikuti
  `sort_order`. Pengurutan dilakukan dua tahap dengan urutan yang benar:
  `Collection::sortBy()` stabil, jadi sortir `sort_order` dulu baru menaruh
  foto utama ke depan. Urutan terbalik akan mengembalikan foto utama ke tempat
  semula.
- Spesifikasi dirender sebagai daftar label/value. Nilainya diperlakukan
  sebagai `unknown` di TypeScript karena kolomnya JSON bebas, lalu dirender
  lewat `String(value)`.
- CTA "Sewa Sekarang" dirender sesuai PRD section 10 dalam keadaan nonaktif,
  dengan keterangan bahwa form booking dibangun pada ROADMAP 3.5. Tombol ini
  sengaja tidak ditautkan supaya tidak ada 404 baru di halaman yang baru saja
  dirapikan.
- Card katalog (ROADMAP 3.3) sekarang menampilkan tombol "Lihat Detail" yang
  menuju halaman ini, memakai route Wayfinder sehingga path yang ditulis
  selalu path yang terdaftar.
- 15 feature test menutup akses tamu, seluruh field PRD section 10, produk
  tanpa kategori/spesifikasi/ketentuan/foto, status ketersediaan, urutan
  gallery, slug yang sama di dua unit, isolasi antar unit, produk nonaktif,
  produk terhapus, dan admin yang tetap bisa membuka detail unit lain.

Catatan dependency:

- `pnpm dlx shadcn add carousel` juga menulis ulang
  `resources/js/components/ui/button.tsx` ke versi registry terbaru, yang
  mengimpor `cn` dari paket `"cn"` dan `Slot` dari paket `"radix-ui"`. Kedua
  paket itu tidak ada di proyek ini, jadi `button.tsx` dikembalikan seperti
  semula. Hanya import `cn` di `carousel.tsx` yang diarahkan ke
  `@/lib/utils` agar sesuai konvensi repo.

### 3.5 Form Booking

- [x] Pilih tanggal mulai
- [x] Pilih tanggal selesai
- [x] Hitung durasi otomatis
- [x] Kalkulator harga real-time (harga × jumlah × durasi)
- [x] Stepper jumlah barang (`[-] n [+]`)
- [x] Validasi tanggal selesai ≥ tanggal mulai
- [x] Validasi tanggal tidak di masa lalu

Implementasi:

- Path form mengikuti PRD section 34: `/gokemping/booking/{product}` dan
  `/sewa-sepeda-garut/booking/{product}`. Kedua path memakai
  `BookingFormController` yang sama dengan slug unit sebagai route default.
- Halaman ini murni memilih jadwal, jumlah, dan menghitung perkiraan harga.
  Halaman tidak menyimpan apa pun ke database: penyimpanan booking ada di
  ROADMAP 3.11, pengecekan ketersediaan real-time ada di ROADMAP 3.6, dan
  biodata penyewa ada di ROADMAP 3.7. Ada feature test yang mengunci batas ini
  dengan memastikan tabel `bookings` tetap kosong setelah halaman dibuka.
- Upper bound stepper memakai `products.stock`, bukan stok tersisa. Pengurangan
  stok oleh booking lain adalah tanggung jawab ROADMAP 3.6, jadi stepper sengaja
  tidak ikut menghitung booking aktif.
- Durasi memakai selisih `tanggal selesai - tanggal mulai`, mengikuti contoh di
  PRD section 11: 10 Oktober sampai 12 Oktober = 2 hari. Tanggal selesai
  diperlakukan sebagai batas pengembalian dan tidak ikut dihitung sebagai hari
  sewa.
- ROADMAP menyebut validasi `tanggal selesai ≥ tanggal mulai`, sedangkan
  contoh PRD menghasilkan durasi 2 hari untuk rentang 2 hari. Dua aturan itu
  disatukan dengan membolehkan tanggal yang sama dan memakai durasi minimum 1
  hari, sehingga tidak ada booking bernilai nol rupiah. Aturan ini perlu
  dikonfirmasi ke pemilik produk.
- Validasi tanggal tidak di masa lalu memakai `minDate` yang dikirim backend
  sebagai `today()` pada timezone aplikasi, sehingga frontend tidak menghitung
  ulang zona waktu. Field tanggal juga memakai `<input type="date">` native,
  jadi batas `min` ditegakkan browser tanpa library kalender tambahan.
- Harga, satuan, stok, dan tanggal minimum dikirim backend. Kalkulator berjalan
  real-time di frontend tanpa request tambahan.
- Produk stok 0 tetap dirender dengan pemberitahuan stok kosong, bukan 404,
  karena produknya masih ada dan halaman ini yang menjelaskan kondisinya.
  Tombol "Lanjut" sengaja nonaktif karena langkah berikutnya (3.6 dan 3.7)
  belum dibangun, supaya tidak ada tujuan tautan yang belum ada.
- CTA "Sewa Sekarang" di detail produk (ROADMAP 3.4) sekarang tautannya aktif
  menuju form ini, memakai route Wayfinder.
- 15 feature test menutup akses tamu kedua unit, kelengkapan data kalkulator,
  batas tanggal minimum, produk tanpa kategori/foto, foto utama, stok 0, slug
  yang sama di dua unit, isolasi antar unit, produk nonaktif, produk terhapus,
  unit nonaktif, admin yang tetap bisa membuka form unit lain, dan jaminan tidak
  ada booking yang tersimpan.

### 3.6 Availability Checking (BR-04)

- [x] Endpoint cek ketersediaan (JSON, dipanggil saat tanggal berubah)
- [x] Hitung stok terpakai dari booking aktif pada periode tersebut
- [x] Tampilkan "Tersedia X unit"
- [x] Blockir lanjut jika stok tidak mencukupi
- [x] Booking aktif yang dihitung: `dikonfirmasi` + `sedang_disewa`

Implementasi:

- `app/Services/AvailabilityService.php` dibuat sesuai PRD section 39.3. Method
  `usedUnits()` menjumlahkan `booking_items.quantity` untuk booking yang
  beririsan periode dan menahan stok, `availableUnits()` mengurangi dari
  `products.stock`, dan `summarise()` menyusun payload untuk endpoint.
- Endpoint JSON ada di dua path, mengikuti pola path form booking:
  `/gokemping/booking/{product}/availability` dan
  `/sewa-sepeda-garut/booking/{product}/availability`. Form booking memanggilnya
  setiap kali tanggal berubah, memakai `AbortController` supaya request yang
  sudah basi dibatalkan dan tidak menimpa hasil yang lebih baru.
- Jumlah barang sengaja tidak ikut memicu request. ROADMAP menyebut endpoint
  dipanggil saat tanggal berubah, jadi jumlah hanya dibandingkan dengan angka
  `available` yang sudah diterima di klien. Dengan begitu satu periode
  menghasilkan satu request.
- **Status yang menahan stok.** ROADMAP menyebut `dikonfirmasi` dan
  `sedang_disewa`, tapi enum `BookingStatus::holdsStock()` juga memasukkan
  `menunggu_konfirmasi`. Dipakai versi enum, bukan tiga status, karena
  `menunggu_konfirmasi` juga sudah menahan barang dan mengabaikannya membuka
  celah overbooking: dua pembeli bisa sama-sama mendapat unit terakhir.
  Endpoint juga mengirim daftar status tersebut di `holding_statuses` supaya
  angka ketersediaan bisa ditelusuri.
- **Batas periode eksklusif.** `Booking::scopeOverlappingPeriod()` diubah dari
  `<=` dan `>=` menjadi `<` dan `>`. Alasannya PRD section 11 menghitung sewa
  10 Oktober sampai 12 Oktober sebagai 2 hari, jadi tanggal selesai adalah batas
  pengembalian dan barang sudah bisa disewa lagi pada tanggal tersebut. Booking
  10 sampai 12 masih dianggap beririsan dengan permintaan mulai 12, tapi tidak dengan
  permintaan mulai 13. Scope ini belum dipakai kode lain, jadi perubahan tidak
  merusak apa pun.
- Isolasi unit dijaga dengan memfilter `bookings.business_id` ke unit produk.
  Booking unit lain pada produk yang sama tidak pernah ikut dihitung, dan admin
  yang login ke unit lain tetap melihat angka ketersediaan yang benar untuk unit
  yang dia buka.
- Hasil yang bisa negatif dicegah `max(0, ...)`, jadi booking yang telanjur
  melebihi stok tidak membuat angka tersedia negatif.
- **`lockForUpdate` belum dipakai di endpoint.** PRD section 39.3 memetakan BR-04
  ke `AvailabilityService` yang jalan di dalam `DB::transaction` +
  `lockForUpdate`. Endpoint ini hanya membaca, dipanggil setiap kali tanggal
  berubah, jadi mengunci baris akan menambah kontensi tanpa mencegah apa pun.
  Pengaman race yang sesungguhnya ada di penyimpanan booking ROADMAP 3.11, tepat
  sebelum stok dikurangi. Perlu dikonfirmasi ke pemilik produk.
- Periode satu hari (`start_date` sama dengan `end_date`) dulu tidak pernah
  beririsan dengan apa pun karena rentang eksklusifnya kosong. Diperbaiki di
  ROADMAP 3.11, dan test di sini ikut memakai perhitungan yang sudah diperbaiki
  karena endpoint, review, dan penyimpanan booking memakai scope yang sama.
- Validasi endpoint menolak tanggal kurang, format bukan `Y-m-d`, tanggal selesai
  sebelum tanggal mulai, tanggal mulai di masa lalu, dan jumlah di bawah 1.
  Tanggal wajib yang tidak lolos validasi dijawab 422 dengan pesan yang sama
  dengan pesan di form.
- Batas atas stepper jumlah barang sekarang mengikuti `available`, bukan
  `products.stock`. Jumlah yang melebihi ketersediaan ditolak di ringkasan biaya
  dengan pesan "Stok tidak mencukupi pada periode tersebut." sesuai PRD
  section 12. Tombol "Lanjut" tetap nonaktif karena langkah berikutnya adalah
  biodata penyewa (ROADMAP 3.7).
- 15 test service dan 23 test endpoint menutup perhitungan stok terpakai, periode
  tidak beririsan, batas eksklusif, tiga status yang menahan stok, status yang
  sudah lepas, isolasi antar unit, admin yang login, seluruh aturan validasi,
  respons 404, dan jaminan endpoint tidak menulis booking baru.

### 3.7 Biodata Penyewa

- [x] Nama lengkap *
- [x] Nomor WhatsApp *
- [x] Email (opsional)
- [x] NIK *
- [x] Alamat *
- [x] Kota/Kabupaten
- [x] Catatan
- [x] Field tambahan penyewa sepeda: jumlah penyewa
- [x] Deteksi pelanggan lama berdasarkan WhatsApp/NIK → prefill otomatis (PRD §25)

Catatan implementasi 3.7:

- Biodata dibuat sebagai halaman terpisah di
  `/gokemping/booking/biodata` dan `/sewa-sepeda-garut/booking/biodata`, bukan
  sebagai bagian dari form jadwal. Literal `booking/biodata` didaftarkan
  sebelum `/booking/{product}` supaya tidak tertangkap sebagai slug produk.
- Data yang sudah diisi pada langkah sebelumnya disimpan sebagai draft di
  session lewat `App\Support\BookingDraft` (`booking.draft`). Session hanya
  menyimpan identitas dan input pengguna: slug unit, `product_id`, tanggal
  mulai, tanggal selesai, jumlah, dan data penyewa. Tidak ada tulisan ke
  `bookings` maupun `customers` sampai ROADMAP 3.11.
- Draft ditulis dengan `merge()`, bukan `write()`, sehingga penyewa yang
  mengganti jadwal di form dan menekan "Lanjut" lagi tidak kehilangan data
  biodata yang sudah diisi. Tombol "Kembali ke form booking" juga mengembalikan
  nilai draft lewat prop `initial` dari `BookingFormController`.
- Halaman biodata menampilkan ringkasan periode, durasi, dan subtotal yang
  dihitung ulang dari `products` terbaru, jadi harga yang tampil tidak pernah
  basi. Ringkasan lengkap booking tetap milik ROADMAP 3.8.
- Nomor WhatsApp dinormalkan ke format `62...` oleh
  `App\Support\WhatsappNumber` sebelum divalidasi dan disimpan, karena
  `customers.whatsapp` punya unique constraint sehingga format berbeda akan
  memecah satu orang menjadi beberapa baris.
- `renter_count` hanya dirender, divalidasi, dan disimpan untuk unit
  `sewa-sepeda-garut`. Untuk unit camping nilainya dipaksa `null` supaya tidak
  ikut ke draft dan tidak menjadi data penyewa yang tidak dipakai.
- Deteksi pelanggan lama memakai endpoint JSON publik
  `GET /booking/customer-lookup` (PRD §25) yang dipanggil saat field WhatsApp
  atau NIK kehilangan fokus. Endpoint tidak memerlukan login karena penyewa
  tidak punya akun, jadi dia sengaja dibatasi `throttle:30,1`, hanya menerima
  `whatsapp` dan `nik`, mengembalikan paling banyak satu pelanggan yang cocok,
  dan tidak pernah mengembalikan daftar pelanggan. Urutan lookup mengikuti
  kekuatan kunci: `whatsapp` lebih dulu, `nik` hanya dipakai kalau nomor tidak
  diberikan. Nomor atau NIK yang tidak valid dan tidak ditemukan menghasilkan
  `found: false`, bukan pesan error, supaya form tetap bisa diisi manual.
- Penyimpanan draft availability dicek ulang di server oleh
  `StoreBookingDraftRequest` walaupun frontend sudah memanggil endpoint
  pengecekan 3.6. Pemeriksaan ini berada di dalam `withValidator()` supaya
  kegagalan menghentikan request dengan 422 sebelum draft ditulis, bukan
  sekadar menambah pesan pada validator yang sudah selesai berjalan.
- Penyimpanan ke `customers` dan `bookings` tetap menunggu ROADMAP 3.11. Ada
  feature test yang mengunci batas ini dengan memastikan kedua tabel tetap
  kosong setelah alur biodata selesai.

### 3.8 Review Booking

- [x] Ringkasan produk, jumlah, periode, durasi, harga, subtotal
- [x] Ringkasan data penyewa
- [x] Total
- [x] Tombol "Lanjut Pembayaran"
- [x] Edit data (kembali ke form sebelumnya)

Catatan implementasi 3.8:

- Halaman review berada di `/gokemping/booking/review` dan
  `/sewa-sepeda-garut/booking/review`. Literal `booking/review` didaftarkan
  sebelum `/booking/{product}` supaya tidak tertangkap sebagai slug produk,
  pola yang sama dengan `booking/biodata`.
- Setelah biodata tersimpan, `BookingBiodataController::store` sekarang
  mengarahkan pengguna ke halaman review, sesuai urutan alur PRD section 36.
  Banner konfirmasi berbasis flash yang sebelumnya dipakai di halaman biodata
  dihapus karena sudah tidak diperlukan setelah halaman review ada.
- Durasi, subtotal, dan total dihitung server lewat `App\Support\BookingPeriod`
  dan dikirim sebagai angka siap tampil bersama label tanggal berbahasa
  Indonesia (`10 Oktober 2026`). Angka yang dibaca penyewa sekarang sama
  dengan angka yang akan dipakai `booking_items` di ROADMAP 3.11, bukan hasil
  hitungan ulang di browser.
- Helper tanggal dan durasi yang sebelumnya terduplikasi di `form.tsx` dan
  `biodata.tsx` dipindahkan ke `resources/js/lib/booking.ts` supaya form
  jadwal, biodata, dan review memakai satu implementasi yang sama.
- Ketersediaan dicek ulang oleh `BookingReviewController` memakai
  `AvailabilityService` (BR-04). Draft sudah divalidasi sejak ROADMAP 3.5,
  tetapi stok bisa terpakai booking lain di antara langkah tersebut. Kalau unit
  yang tersisa lebih sedikit dari yang diminta, halaman menampilkan
  peringatan, mematikan checkbox konfirmasi, dan menawarkan tautan untuk
  mengubah jadwal. Booking berstatus batal dan yang sudah lewat tidak ikut
  mengurangi ketersediaan, mengikuti `BookingStatus::holdsStock()`.
- PRD section 15 mengharuskan konfirmasi pengguna sebelum lanjut, jadi halaman
  memakai checkbox "Saya pastikan detail booking dan data penyewa di atas sudah
  benar" sebagai syarat. Kolom yang tidak wajib diisi seperti email, kota, dan
  catatan hanya ditampilkan kalau penyewa mengisinya.
- Tombol "Lanjut Pembayaran" sudah ada sesuai checklist, tetapi sengaja
  dinonaktifkan dengan keterangan bahwa halaman pembayaran dibangun pada
  ROADMAP 3.9. Tidak ada tautan ke halaman yang belum ada. Begitu 3.9 siap,
  tombol ini cukup diarahkan ke route pembayaran.
- Halaman review mengarahkan pengguna ke form biodata bila data penyewa wajib
  (nama, WhatsApp, NIK, alamat) belum lengkap, dan mengembalikan 404 bila
  draft tidak ada, milik unit lain, atau produknya sudah tidak aktif.
- Review tetap bersifat baca saja. Ada feature test yang mengunci batas ini
  dengan memastikan `bookings`, `booking_items`, dan `customers` tetap kosong
  setelah halaman dibuka.

### 3.9 Metode Pembayaran & Halaman Pembayaran

- [x] Pilihan metode: Cash / QRIS / Transfer Bank
- [x] Halaman Cash: total + keterangan + tombol lanjut
- [x] Halaman QRIS: QRIS toko + total + upload bukti
- [x] Halaman Transfer: rekening + tombol salin + upload bukti
- [x] Ambil data rekening/QRIS dari `payment_methods` sesuai `business_id`
- [x] Salin nomor rekening ke clipboard

Catatan 3.9:

- Metode dipilih lewat radio group di halaman review, lalu disimpan ke draft
  sebagai `payment_method`. Halaman pembayaran dibaca dari `/booking/payment/{method}`
  untuk kedua unit bisnis.
- Konfigurasi pembayaran selalu dibaca dari `payment_methods` milik unit bisnis
  yang sedang diakses, jadi tidak ada nomor rekening atau QRIS yang ditulis
  langsung di frontend. Metode milik unit lain tidak bisa dipilih walau nilai
  method-nya valid.
- Metode yang aktif tetapi datanya belum lengkap (QRIS tanpa gambar, transfer
  tanpa nomor rekening) tetap ditampilkan dengan penanda belum bisa dipilih,
  bukan disembunyikan. Konfirmasi-konfirmasi ini yang membuat penyewa tidak
  terjebak di halaman pembayaran yang tidak bisa diselesaikan.
- Upload bukti(**"wajib"** untuk QRIS/Transfer) sengaja ditunda ke ROADMAP 3.10.
  Ketiga halaman pembayaran sudah menampilkan bagian bukti pembayaran beserta
  keterangan bahwa form unggahnya ada di 3.10, tetapi belum punya input upload.
- Tombol "Lanjut Pesan via WhatsApp" sudah tampil di halaman pembayaran sesuai
  PRD section 17, masih nonaktif karena pembuatannya ada di ROADMAP 3.12.
- Stok dan kelengkapan biodata dicek ulang di halaman pembayaran, jadi halaman
  ini tidak pernah menampilkan pembayaran untuk draft yang sudah tidak layak.
- `bookings`, `booking_items`, dan `customers` tetap kosong sampai ROADMAP 3.11.

### 3.10 Upload Bukti Pembayaran (BR-08)

- [x] Validasi `mimes:jpg,jpeg,png,webp`
- [x] Validasi `max:5120` (5 MB)
- [x] Preview gambar sebelum submit
- [x] Hapus & ganti bukti
- [x] Bukti **wajib** untuk QRIS/Transfer, **opsional** untuk Cash

Catatan 3.10:

- Aturan wajib/tidaknya bukti bergantung pada metode yang tercatat di draft,
  bukan pada isi request, jadi pemeriksaan itu dilakukan di `withValidator()`.
  Cash tidak menolak unggahan, hanya tidak mewajibkannya.
- Bukti disimpan ke disk `public` pada folder `payments/`. Disk default
  aplikasi adalah `local` yang private, jadi disk selalu disebutkan eksplisit.
- Nama berkas di disk memakai UUID, bukan nama asli dari perangkat penyewa.
  Nama asli hanya disimpan di draft untuk ditampilkan, jadi path yang tersimpan
  tidak pernah bergantung pada input pengguna dan tidak bisa keluar dari folder
  bukti.
- Pratinjau memakai `URL.createObjectURL` yang dibersihkan saat berkas diganti.
  Ukuran 5 MB juga dicek di browser supaya berkas besar tidak perlu sampai ke
  server dulu untuk ditolak; server tetap memvalidasinya ulang.
- Mengganti bukti menghapus berkas lama dari disk, dan membatalkan bukti
  menghapus berkasnya. Kalau tidak begitu, tiap unggahan yang dibatalkan akan
  meninggalkan berkas yatim di disk.
- Path bukti disimpan di draft, bukan di tabel `payments`, karena record
  pembayarannya baru dibuat di ROADMAP 3.11. Pemindahan path ke kolom
  `payments.proof` dilakukan di 3.11. Sampai saat itu `payments`, `bookings`,
  `booking_items`, dan `customers` tetap kosong.

### 3.11 Penyimpanan Booking

- [x] Simpan booking + `booking_items` dalam satu transaksi DB
- [x] Salin harga produk ke `booking_items` (BR-09)
- [x] Generate kode booking `GK-YYYYMMDD-NNN` / `SSG-YYYYMMDD-NNN` (BR-10)
- [x] Simpan record `payments`
- [x] Set status awal: booking `menunggu_konfirmasi`, payment `belum_dibayar` / `menunggu_verifikasi`

Catatan 3.11:

- Penyimpanan dimulai dari `BookingStoreController` lewat `BookingService`.
  Ketersediaan dihitung ulang di dalam `DB::transaction` tepat sebelum stok
  dipakai, karena nilai yang tampil di halaman review dan pembayaran hanya
  informatif dan bisa jadi basi di antara dipilihnya metode dan tombol
  konfirmasi ditekan.
- Urutan lock di dalam transaksi dijaga tetap: baris `business` dulu, lalu baris
  `products`. Baris `business` adalah mutex pembuatan kode booking. `lockForUpdate`
  di `bookings` tidak bisa jadi mutex karena pada kode pertama untuk tanggal
  tersebut belum ada baris sama sekali, jadi dua request bersamaan bisa
  sama-sama membaca urutan terakhir yang sama. Indeks unik `bookings.booking_code`
  tetap menjadi penjaga terakhir kalau kunci baris tersebut tetap terlewat.
- Stok habis saat submit tidak menghapus draft. Penyewa dikembalikan ke review
  supaya cukup mengganti periode tanpa mengulang biodata, dan bukti pembayaran
  yang sudah diunggah tetap utuh. Exception-nya `InsufficientStockException`,
  dipisah dari `RuntimeException` supaya tidak jadi halaman 500.
- Harga, nama produk, dan satuan harga disalin dari tabel `products` saat
  penyimpanan. Test mengirim `total` dan `price` palsu di body request untuk
  memastikan nilai klien tidak pernah dipakai.
- Kode booking memakai tanggal booking dibuat, bukan tanggal mulai sewa, dan
  nomornya dihitung ulang dari kode yang sudah tersimpan pada tanggal itu,
  bukan dari counter terpisah. Pembacaan kode terakhir secara leksikal hanya
  benar selama nomor urut muat tiga digit, jadi sisipan `exists()` yang melompati
  slot yang sudah terpakai, termasuk saat sudah lewat 999 kode per hari.
- Path bukti dari draft dipindahkan ke `payments.proof`. Berkasnya sendiri
  **tidak** dihapus dari disk, karena sekarang path itu dimiliki record
  pembayaran. Sebaliknya draft **dihapus** setelah booking tersimpan, jadi
  submit ulang berakhir dengan 404 dan tidak bisa membuat booking ganda.
- Halaman konfirmasi membaca ringkasan dari session (`BookingReceipt`), bukan
  dari URL, supaya kode booking tidak bisa dibaca orang lain dengan menebak
  alamat halaman. Halaman juga tidak pernah membaca booking berdasarkan
  request, jadi tidak ada jalur pembocoran data di luar session tersebut.
- Penyewa dicari ulang berdasarkan nomor WhatsApp yang sudah dinormalkan,
  konsisten dengan endpoint deteksi pelanggan lama (ROADMAP 3.7), sehingga
  orang yang menyewa dua kali tidak terpecah jadi dua baris.
- `PaymentStatus::initialFor()` diubah jadi metode statis karena status awal
  ditentukan oleh metode pembayaran, bukan oleh instance status yang dibaca.
  Cash mulai dari `belum_dibayar`, bukan `lunas`, supaya admin tetap yang
  menandai pembayaran diterima.

Perbaikan ketersediaan di 3.11:

- Periode satu hari (`start_date` sama dengan `end_date`) sebelumnya tidak
  pernah beririsan dengan apa pun, karena rentang eksklusifnya kosong. Satu
  barang bisa terjual berkali-kali untuk tanggal yang sama.
- `Booking::scopeOverlappingPeriod()` sekarang menormalkan batas atas periode
  yang diminta menjadi `start + 1` hari, dan booking satu hari ikut dihitung
  lewat syarat kedua yang membandingkan `end_date` dengan `start_date`.
- Dipakai bersama oleh endpoint ketersediaan (ROADMAP 3.6), halaman review
  (3.8), dan penyimpanan booking, jadi ketiganya tidak bisa berbeda pendapat.

### 3.12 WhatsApp Booking (BR-06)

- [x] Template pesan otomatis sesuai format PRD §19
- [x] Nomor tujuan mengikuti `business.whatsapp`
- [x] Tombol "Lanjut Pesan via WhatsApp" → `wa.me/<number>?text=<encoded>`
- [x] Halaman `/booking/success` berisi kode booking + tombol WhatsApp

Catatan implementasi 3.12:

- Pesan disusun di server oleh `App\Support\BookingWhatsappMessage`, bukan di
  browser. Isinya berasal dari database: nama produk, periode, total, metode,
  dan data penyewa. Kalau dirakit di frontend, nilainya bisa berbeda dari yang
  benar-benar disimpan di ROADMAP 3.11.
- Pesan dirakit sekali, saat booking disimpan, lalu ikut di dalam receipt
  session. Halaman sukses tidak pernah membaca ulang booking, jadi menekan
  tombol berulang kali tidak mengubah isi pesan dan tidak menambah query.
- Nomor tujuan selalu `business.whatsapp` unit yang dipakai penyewa, sudah
  dinormalkan ke `62...` oleh `WhatsappNumber`, jadi penyewa Sewa Sepeda Garut
  tidak bisa mengirim detail booking-nya ke admin GoKemping karena salah tombol.
- Kalau nomor unit tidak valid, `url` dikosongkan dan tombol WhatsApp tidak
  dirender. Isi pesannya tetap dikirim supaya penyewa bisa menyalin dan
  mengirim manual, dan halaman menjelaskan kenapa tombolnya tidak ada.
  Membuka WhatsApp ke nomor yang salah lebih merugikan daripada tidak membuka
  apa pun.
- `rawurlencode` dipakai untuk isi pesan, jadi baris baru dan spasi ter-encode
  dengan benar dan WhatsApp tidak memotong pesan di tengah.
- NIK tidak ikut terkirim lengkap, hanya 4 digit depan dan 4 digit belakang.
  Pesan WhatsApp bisa diteruskan dan di-screenshot, sedangkan admin tetap bisa
  membuka NIK lengkap dari booking berdasarkan kode booking. Ini lebih aman dari
  contoh di PRD section 19 yang menulis NIK penuh.
- `Jumlah Penyewa` hanya ikut untuk unit sewa sepeda, mengikuti `renter_count`
  yang memang cuma diisi di unit itu. `Catatan Penyewa` hanya ikut kalau isinya
  bukan string kosong.
- Garis pemisah bagian dibuat satu panjang seragam. PRD section 19 memakai
  panjang yang berbeda-beda (`15`, `12`, dan `16` karakter) yang tidak
  membawa informasi apa pun.
- `/booking/success` didaftarkan sebelum `/booking/{product}`, sama seperti
  `biodata`, `review`, dan `payment`. Konsekuensinya slug produk `success` tidak
  bisa dipakai. Belum ada daftar slug terlarang di aplikasi; harus dibuat saat
  manajemen produk dibangun di ROADMAP 4.3.
- Tidak ada field baru. Halaman sukses hanya menampilkan ringkasan yang sudah
  tersimpan di `bookings`, `booking_items`, dan `payments`.

**Deliverable Fase 1:** Pelanggan dapat menyelesaikan booking penuh dan mengirim detail ke WhatsApp admin tanpa login.

---

## 4. Fase 2 — Admin Back-office

### 4.1 Dashboard

- [x] Total produk
- [x] Booking hari ini
- [x] Sedang disewa
- [x] Menunggu konfirmasi
- [x] Pendapatan (periode berjalan)
- [x] Chart booking 7 hari terakhir
- [x] Widget booking terbaru
- [x] Semua query ter-scope `business_id`

Catatan implementasi 4.1:

- Angka dashboard dipindah dari controller ke `App\Services\DashboardService`,
  supaya definisi setiap angka bisa diuji tanpa harus merender halaman.
  Controller cuma meneruskan unit bisnis admin yang login.
- Service menerima `business_id` secara eksplisit dan tidak mengandalkan admin
  yang sedang login. `BusinessScope` tetap menyaring query sebagai lapisan kedua
  (BR-05), jadi pemanggil yang salah parameter akan mendapat angka nol, bukan
  angka unit lain.
- **Pendapatan hanya menghitung pembayaran `lunas` yang `verified_at`-nya di
  bulan berjalan.** Booking yang belum dibayar tidak dihitung supaya angka
  dashboard tidak lebih besar dari uang yang benar-benar masuk. Jumlah yang belum
  diverifikasi ditampilkan terpisah sebagai `revenue.pending`, dihitung dari
  payment record bulan berjalan, karena admin tetap perlu tahu ada uang yang
  menunggu verifikasi.
- Test menutup dua hal yang paling mudah tertukar: pembayaran `lunas` tanpa
  `verified_at` tidak dihitung, dan pembayaran `ditolak` tidak masuk daftar
  yang menunggu.
- "Booking hari ini" memakai tanggal booking **dibuat**, mengikuti timezone
  aplikasi, bukan tanggal mulai sewa. Booking yang dibuat hari ini untuk sewa
  bulan depan tidak boleh muncul sebagai booking hari ini. Test khusus ini ada
  karena kedua tanggal itu paling mudah tertukar.
- Grafik 7 hari selalu mengirim tujuh titik, termasuk hari tanpa booking. Kalau
  hanya mengirim hari yang ada booking-nya, grafik akan terlihat melompati
  tanggal dan admin salah baca.
- Grafik dibuat dengan elemen biasa, bukan library chart, karena kebutuhannya
  hanya tujuh batang. Menambah dependensi chart untuk ini tidak sebanding
  dengan bobotnya. Kalau laporan Fase 3 butuh grafik yang lebih rumit,
  `resources/js/components/admin/booking-chart.tsx` bisa diganti library chart
  tanpa menyentuh service-nya, karena frontend hanya menerima angka.
- Widget booking terbaru menampilkan nama produk dari `booking_items`, bukan
  dari `products`. Kalau produknya diubah atau produknya tidak lagi terhubung
  ke unit ini, booking lama tetap terbaca apa adanya (BR-09).
- Card "Fase 0 selesai" yang sebelumnya mengisi halaman dashboard dihapus,
  karena sekarang halaman ini sudah berisi data operasional.

### 4.2 Manajemen Kategori

- [x] Tabel kategori + jumlah produk
- [x] Tambah / edit / ubah status
- [x] Hapus kategori (dengan konfirmasi)
- [x] Guard: kategori dengan produk tidak bisa dihapus

Catatan implementasi 4.2:

- Route memakai `Route::resource` tanpa `create`, `edit`, dan `show`. Form tambah
  dan edit berupa dialog di halaman daftar, jadi tidak ada halaman form terpisah
  yang harus dijaga. `Route::resource` juga Implicit Model Binding lewat
  `slug`, jadi `BusinessScope` tetap menyaring kategori milik unit lain: request
  dari unit lain berakhir 404, bukan 403.
- **Slug kategori dibuat dari nama, tetapi tidak bisa diubah lewat form edit.**
  Slug dipakai sebagai filter kategori di URL katalog publik (`?category=...`),
  sehingga membuatnya ulang saat nama diganti akan memutus tautan yang sudah
  dibagikan pengunjung. Kategori lama mempertahankan slug-nya.
- Tabrakan nama di dalam satu unit bisnis tidak ditolak ke admin, diberi akhiran
  angka (`sepeda`, `sepeda-2`). Dua unit bisnis boleh punya kategori dengan nama
  dan slug yang sama, jadi keunikan dihitung per `business_id`, bukan global.
  Ini yang membuat `unique(['business_id', 'slug'])` di migration tetap punya
  arti sebagai pengaman.
- `business_id` tidak pernah diambil dari request. Atribut kategori disusun di
  `StoreCategoryRequest` dan `Category::create()` dipanggil dengan
  `business_id` milik admin yang login, jadi tidak ada field yang bisa diisi
  untuk memindahkan kategori ke unit lain.
- **Jumlah produk dihitung dengan satu agregasi (`withCount`) dan menghitung
  seluruh produk, termasuk yang nonaktif.** Kategori dengan produk nonaktif pun
  tetap tidak boleh dihapus, jadi admin perlu melihat jumlahnya. Kalau yang
  dihitung hanya produk aktif, kategori yang terlihat kosong padahal tidak bisa
  dihapus akan mengeliruikan.
- **Kategori yang punya produk tidak dihapus, dan produknya tidak ikut berubah.**
  Server menolak dengan pesan yang menyebutkan jumlah produk yang menghalangi,
  dan dialog hapus menampilkan alasan yang sama sebelum tombol ditekan. Cara lain
  — mengosongkan `category_id` atau ikut menghapus produk — lebih cepat tapi
  merusak data tanpa disadari admin.
- Ubah status punya endpoint sendiri (`PATCH admin/categories/{slug}/status`).
  Aksi di tabel cuma mengubah satu nilai boolean, jadi tidak perlu mengirim ulang
  nama dan deskripsi yang tidak sedang diedit. Menonaktifkan kategori **tidak**
  menonaktifkan produk di dalamnya: status produk punya arti sendiri di katalog.
- Notifikasi memakai `Inertia::flash('toast', ...)`, pola yang sama dengan
  pengaturan profil, supaya pesan sukses dan penolakan hapus sampai ke frontend
  lewat `useFlashToast`.
- Dialog tambah/edit di-render ulang dengan `key` per kategori (`category.id`, atau
  `new` untuk tambah) supaya isian form lama tidak pernah terbawa ke kategori
  berikutnya yang dibuka.
- Menu "Kategori" ditambahkan ke sidebar admin. `app-header.tsx` (layout header)
  ikut diisi walau layout itu belum dipakai halaman mana pun, supaya nav tidak
  berbeda kalau layout-nya nanti diganti.

### 4.3 Manajemen Produk

- [x] Tabel produk: foto, nama, kategori, harga, stok, status
- [x] Search + filter kategori + filter status
- [x] Form tambah produk
- [x] Form edit produk
- [x] Upload multiple foto + gallery
- [x] Kompresi gambar (Intervention)
- [x] Hapus / nonaktifkan produk (soft delete)
- [x] Atur harga, stok, deskripsi, spesifikasi, ketentuan sewa
- [x] Produk nonaktif tidak muncul di katalog publik

Catatan implementasi 4.3:

- **Slug produk dibuat dari nama, tetapi tidak bisa diubah lewat form edit.**
  `Product::generateSlug()` membuat slug unik di dalam satu unit bisnis, dan
  `UpdateProductRequest` mempertahankan slug lama. Slug ada di URL katalog dan
  halaman booking yang sudah dibagikan ke pelanggan, jadi nama boleh berubah
  tanpa memutus tautan.
- **Slug terlarang untuk path publik dapat akhiran angka, bukan nama produknya
  ditolak.** `booking`, `biodata`, `review`, `payment`, dan `success` sudah
  dipakai route halaman booking (lihat catatan ROADMAP 3.3), jadi nama produk
  "Success" tetap boleh dipakai hanya URL-nya menjadi `success-2`. Menolak nama
  produk karena nama route akan terasa seperti aplikasi yang melarang istilah yang
  sedang dipakai pelanggan. Slug yang tidak menghasilkan huruf latin memakai
  `produk` sebagai dasarnya, dan penomoran ikut memakai basis itu, jadi URL seperti
  `-2` tidak pernah muncul.
- **Kategori wajib saat menambah, boleh dikosongkan saat edit.** Katalog
  mengelompokkan dan memfilter produk berdasarkan kategori, jadi produk tanpa
  kategori tidak muncul di filter mana pun. `products.category_id` tetap
  nullable supaya data lama tidak ikut rusak dan produk yang sudah tervalidasi
  bisa dikembalikan ke kondisi tanpa kategori. Validasi memakai `exists` dengan
  batas `business_id`, bukan `exists` polos: `products.category_id` hanya punya
  batasan FK di database, jadi tanpa batas eksplisit kategori milik unit lain
  bisa lolos.
- **Spesifikasi dikirim sebagai baris label-isi, disimpan sebagai objek JSON.**
  Baris yang kosong tidak ikut disimpan, baris setengah terisi ditolak, dan label
  ganda ditolak karena dua baris dengan label sama akan saling menimpa di kolom
  JSON dan satu baris hilang tanpa ada yang menyadarinya. Urutan baris mengikuti
  urutan key di JSON: MySQL menormalkan urutan itu, jadi urutannya bisa berbeda
  dari urutan saat admin mengetik, tapi urutan yang dikirim balik selalu sama
  dengan yang dimuat sehingga baris tidak saling bertukar tempat setiap kali
  produk disimpan ulang.
- **Foto produk dikompresi ulang, berkas asli tidak disimpan.** Semua foto ditulis
  sebagai WebP dengan sisi terpanjang 1200 piksel dan kualitas 80 lewat
  Intervention Image. Alasannya dua: foto dari kamera HP bisa 5 MB sedangkan kartu
  katalog memuat belasan foto sekaligus, dan foto HP membawa EXIF berisi lokasi
  pengambilan yang ikut hilang saat gambar di-encode ulang. Nama berkas memakai
  UUID seperti bukti pembayaran, jadi nama asli dari perangkat admin tidak pernah
  menyentuh disk.
- **Batas foto: 8 per produk, 5 MB per berkas, hanya JPG/PNG/WebP.** Batas mime
  dan ukuran mengikuti BR-08 supaya aturannya sama dengan bukti pembayaran. Sisa
  kapasitas dihitung server pada `StoreProductImagesRequest`, jadi admin yang
  galerinya penuh mendapat error validasi sebelum berkas diproses, bukan
  kegagalan di tengah unggahan. Sisa kapasitas juga dikirim ke halaman edit
  supaya tombol unggah langsung dimatikan.
- **Galeri punya form sendiri, terpisah dari form produk.** Unggah foto lewat
  form produk akan mengirim ulang seluruh isian yang belum disimpan. Foto boleh
  ikut dipilih di form tambah, dan diproses setelah produknya tersimpan, karena
  nama folder fotonya butuh `products/{id}/{uuid}.webp`.
- **Foto utama selalu ada selama produk punya foto.** Foto pertama otomatis jadi
  foto utama, menghapus foto utama langsung mengangkat foto berikutnya, dan
  menukar foto utama lain memakai satu transaksi supaya tidak pernah terlihat
  dua foto utama sekaligus.
- **Baris foto dan berkasnya dibatalkan bersama kalau unggahan gagal di tengah.**
  Kalau barisnya dibiarkan tapi berkasnya dihapus, katalog akan menampilkan foto
  yang isinya 404 tanpa ada baris yang bisa dicari untuk membersihkannya.
- **`product_images` tidak punya `business_id`, jadi foto hanya dijangkau lewat
  produknya.** Setiap aksi foto memuat `$product->images()` yang sudah ter-scope
  `BusinessScope`, lalu memeriksa keanggotaannya. Foto milik produk lain pada URL
  yang benar berakhir 404, termasuk saat produknya milik unit yang sama.
- **Hapus produk memakai soft delete, dan produknya ikut dinonaktifkan.**
  `booking_items` menunjuk produk, dan nama serta harga produk sudah disalin ke
  detail booking saat transaksi dibuat (BR-09), jadi produk yang dihapus tidak
  merusak riwayat yang sudah lewat. Toast menyebut jumlah booking yang memakai
  produk itu. Foto di disk juga tidak dihapus supaya produknya masih bisa
  dipulihkan tanpa mengunggah ulang; `products.deleted_at` sudah menyediakan
  kolomnya, dan pemulihan belum ada di halaman admin.
- **Produk nonaktif ikut tampil di daftar admin.** Katalog publik menyembunyikannya
  lewat `scopeActive()`, jadi hide dari daftar akan membuat produk yang sedang
  tidak disewakan jadi mustahil dinyalakan lagi tanpa mencari lewat halaman lain.
  Sebaliknya produk yang sudah di-soft-delete tidak ikut tampil, karena memang
  sudah dihapus dari tempat kerja admin.
- **Checkbox "tampilkan di katalog" mengirim hidden `is_active=0`.** Checkbox HTML
  yang tidak dicentang tidak mengirim apa pun, jadi backend akan membaca statusnya
  tidak berubah dan admin tidak akan bisa menonaktifkan produk dari form edit.
  Hidden di depan checkbox membuat `is_active` selalu terkirim, dan nilai terakhir
  yang dibaca PHP tetap nilai checkbox kalau dicentang. Backend juga tidak
  mengarang nilai: request yang memang tidak mengirim `is_active` mempertahankan
  status yang sudah ada.
- **Filter membaca query string, dan nilai yang tidak valid diabaikan.** Daftar
  adalah halaman kerja, bukan form: admin tidak boleh terkunci karena URL yang
  tersalin tidak lengkap.
- Menu "Produk" ditambahkan ke sidebar admin, dan `resources/js/routes/admin/products`
  beserta `images` di-generate ulang oleh Wayfinder.

### 4.4 Manajemen Booking

- [x] Tabel: kode, penyewa, produk, periode, total, pembayaran, status
- [x] Search + filter status + filter rentang tanggal
- [x] Halaman detail booking
- [x] Ubah status: menunggu → dikonfirmasi → sedang disewa → selesai
- [x] Batalkan booking
- [x] Riwayat status booking
- [x] Konfirmasi pembatalan otomatis pada `payments`

Catatan implementasi 4.4:

- **URL admin memakai `booking_code`, bukan id numerik.** `Booking` sekarang
  memakai `getRouteKeyName()` yang mengembalikan `booking_code`, jadi admin dan
  penyewa membicarakan hal yang sama dan kode booking yang sudah dikirim lewat
  WhatsApp bisa langsung dibuka. `BusinessScope` tetap menyaring barisnya, jadi
  kode booking milik unit lain berakhir sebagai 404, bukan 403.
- **Status hanya boleh maju satu tahap.** `BookingStatus::canTransitionTo()`
  menjadi satu-satunya sumber aturan dan dipakai form request, service, dan
  test. Melompati tahap, mundur, dan mengubah booking yang sudah `selesai`
  ditolak dengan pesan yang menyebut status sekarang dan status tujuan, bukan
  diam-diam diabaikan. Aturan yang sama tidak ditulis ulang di frontend:
  halaman detail hanya menampilkan satu tombol, yaitu tahap berikutnya.
- **Semua perubahan status lewat `BookingStatusService` dalam satu transaksi
  dengan `lockForUpdate`.** Satu perubahan status selalu punya tiga efek:
  `bookings.booking_status`, satu timestamp tahapan, dan satu baris riwayat.
  Status dibaca ulang dari database di dalam transaksi, bukan dari instance yang
  dioper controller, karena instance itu sudah dimuat sebelum transaksi dimulai
  dan bisa saja basi. Tanpa lock itu, dua admin yang menekan tombol sama hampir
  bersamaan akan sama-sama menjalankan tahap yang sama dan menulis dua baris
  riwayat untuk perubahan yang sama.
- **Baris riwayat baru menyimpan urutan, alasan, dan pelaku saja.** Kapan sebuah
  tahap terjadi dibaca dari timestamp yang sudah ada di `bookings`
  (`confirmed_at` sampai `cancelled_at`), jadi tidak ada waktu yang dicatat dua
  kali. Migration baru mengisi satu baris riwayat untuk booking yang sudah ada,
  supaya halaman detail booking lama tidak kosong total.
- **Baris pertama riwayat dibuat tanpa pelaku.** Booking yang disewa penyewa
  selalu dimulai dari `menunggu_konfirmasi`, jadi `changed_by` boleh null dan
  riwayat menuliskannya sebagai "Penyewa (booking masuk)". Null dipakai juga
  untuk perubahan status di luar halaman admin, mis. saat perbaikan data.
- **Pembatalan tidak menghapus apa pun.** Booking tetap ada, barang yang
  sebelumnya menahan stok otomatis terbaca tersedia lagi karena
  `Booking::scopeHoldingStock()` tidak menghitung status `dibatalkan`, dan
  history-nya tetap utuh. Alasan pembatalan wajib diisi (maksimal 200 karakter)
  karena penyewa menerimanya lewat pesan pembatalan dan riwayat tanpa alasan
  tidak bisa ditindaklanjuti.
- **Pembatalan hanya mungkin sebelum selesai.** Status `selesai` dan `dibatalkan`
  tidak bisa dibatalkan lagi: membatalkan dua kali akan menulis baris riwayat
  kedua untuk perubahan yang sama. Booking `sedang_disewa` masih bisa dibatalkan
  karena barang bisa sudah dibawa atau tidak.
- **Pembatalan menutup pembayaran yang masih terbuka, dan tidak menyentuh yang
  sudah final.** `belum_dibayar` dan `menunggu_verifikasi` menjadi `ditolak`
  dengan alasan yang menyebut pembatalan, lalu `bookings.payment_status`
  disinkronkan supaya angka ringkasan tidak berbeda dari pembayarannya. Pembayaran
  `lunas` sengaja tidak diubah: uangnya sudah masuk, jadi pengembalian uang adalah
  keputusan manual admin, bukan efek samping menekan tombol pembatalan. Membalik
  `lunas` jadi `ditolak` juga akan ikut mengubah angka pendapatan dashboard.
  Berkas bukti pembayaran tidak dihapus, jadi bukti yang sudah ditolak masih bisa
  dibaca kalau dibutuhkan.
- **Pencarian mencakup kode booking, nama dan WhatsApp penyewa, serta nama
  produk di `booking_items`.** Nama produk dicari dari `booking_items`, bukan dari
  `products`, supaya booking lama tetap bisa dicari dengan nama yang tersimpan di
  booking itu walaupun produknya sudah diubah atau dihapus (BR-09). Karakter
  `%`, `_`, dan `\` diescaped lebih dulu supaya pencarian tidak berubah jadi
  wildcard diam-diam.
- **Filter tanggal memakai tanggal mulai sewa, bukan tanggal booking dibuat.**
  Admin yang bekerja dengan jadwal bertanya "barang ini terpakai tanggal berapa",
  dan booking untuk bulan depan memang sering dibuat minggu ini. Tanggal
  diverifikasi dengan `Carbon::createFromFormat()` supaya nilai seperti
  `2026-02-31` ditolak di awal dan tidak diam-diam tidak cocok dengan apa pun.
- **Nilai filter yang tidak valid dibuang, bukan membalas 422.** Daftar booking
  adalah halaman kerja, bukan form: admin tidak boleh terkunci karena tautan yang
  disalin tidak lengkap. Filter default juga tidak ikut ditulis ke URL supaya
  alamat halaman tetap pendek.
- **Warna badge status booking dan pembayaran dipusatkan di
  `booking-status-badge.tsx`.** Dashboard, daftar, dan detail memakai komponen
  yang sama supaya admin tidak membaca warna yang berbeda untuk status yang sama.
  Booking `dibatalkan` memakai warna `destructive`, bukan `outline` seperti
  `selesai`: yang selesai adalah akhir yang wajar, yang dibatalkan adalah masalah.
- Kode booking di dashboard kini tertaut ke detail booking, dan menu "Booking"
  ditambahkan ke sidebar admin. `resources/js/routes/admin/bookings` di-generate
  ulang oleh Wayfinder.

### 4.5 Manajemen Penyewa

- [ ] Tabel: nama, WhatsApp, NIK (masked), alamat, jumlah booking, total transaksi
- [ ] Search by nama / WhatsApp / NIK
- [ ] Halaman detail penyewa + riwayat booking
- [ ] NIK ditampilkan tersamar (masking) di seluruh halaman

### 4.6 Manajemen Pembayaran

- [ ] Tabel: metode, nominal, bukti, status
- [ ] Filter status & metode
- [ ] Lihat bukti pembayaran (modal preview)
- [ ] Verifikasi pembayaran → Lunas
- [ ] Tolak pembayaran (dengan alasan)
- [ ] Catat `verified_at` dan `verified_by`
- [ ] Cash: ubah langsung menjadi Lunas

### 4.7 Pengaturan Pembayaran

- [ ] Upload QRIS merchant
- [ ] Nama merchant
- [ ] Data bank: nama bank, nomor rekening, pemilik
- [ ] Keterangan pembayaran cash
- [ ] Aktif/nonaktifkan tiap metode
- [ ] Simpan per `business_id`

### 4.8 Pengaturan Akun Admin

- [ ] Ubah profil
- [ ] Ubah password
- [ ] Toggle light/dark mode

**Deliverable Fase 2:** Admin dapat menjalankan seluruh operasional secara mandiri.

---

## 5. Fase 3 — Management

### 5.1 Laporan

- [ ] Filter tanggal mulai & tanggal akhir
- [ ] Jumlah booking
- [ ] Booking selesai
- [ ] Booking dibatalkan
- [ ] Total pendapatan
- [ ] Produk paling banyak disewa
- [ ] Jumlah penyewa
- [ ] Rekap metode pembayaran
- [ ] Grafik pendapatan (harian/bulanan)

### 5.2 Export

- [ ] Export Excel — data booking
- [ ] Export Excel — rekap laporan
- [ ] Export PDF — laporan periode + kop surat

### 5.3 Statistik

- [ ] Grafik booking per bulan
- [ ] Grafik pendapatan per bulan
- [ ] Produk terlaris
- [ ] Metode pembayaran paling banyak dipakai

### 5.4 Content Management

- [ ] Banner / hero carousel
- [ ] Informasi layanan
- [ ] FAQ (CRUD)
- [ ] Ketentuan sewa
- [ ] Kontak (WhatsApp, telepon, alamat)
- [ ] Informasi lokasi (embed maps)
- [ ] Semua konten ter-scope `business_id`

**Deliverable Fase 3:** Admin dapat menganalisis bisnis dan mengelola konten tanpa sentuh kode.

---

## 6. Definition of Done

Sebuah task dianggap selesai bila:

- [ ] Fitur berfungsi sesuai PRD
- [ ] Validasi input tersedia di server (`FormRequest`), bukan hanya di frontend
- [ ] Query ter-scope `business_id` untuk seluruh data admin
- [ ] Ter-cover automated test (Pest/PHPUnit) untuk logic kritis: availability, booking code, booking total, scoping
- [ ] Lolos `pint` dan `phpstan`
- [ ] Lolos ESLint + TypeScript check (`pnpm run lint`, `pnpm tsc --noEmit`)
- [ ] Loading & empty state ditangani
- [ ] Responsive di mobile, tablet, desktop
- [ ] Nominal Rupiah ter-format konsisten
- [ ] NIK tidak pernah ditampilkan penuh di halaman publik

---

## 7. Prioritas Anti-Overbooking (BR-04)

Paling berisiko salah adalah pemesanan melebihi stok. Urutan implementasi wajib:

1. Query stok terpakai dengan filter overlap tanggal
2. Bungkus pembuatan booking dalam `DB::transaction`
3. `lockForUpdate()` pada row produk & booking aktif terkait
4. Hitung ulang ketersediaan **di dalam** transaksi
5. Tolak dengan 422 jika tidak cukup
6. Otomatis lepas stok reserved pada status `dibatalkan`

---

## 8. Risiko & Mitigasi

| Risiko                               | Dampak | Mitigasi                                                |
| ------------------------------------ | ------ | ------------------------------------------------------- |
| Overbooking saat booking bersamaan   | Tinggi | DB transaction + row locking (Bagian 7)                 |
| Harga produk berubah setelah booking | Sedang | Salin harga ke `booking_items` (BR-09)                  |
| Data tercampur antar unit bisnis     | Tinggi | Global scope + policy + middleware (BR-05)              |
| Bukti bayar tidak terbaca            | Sedang | Validasi mime/size + preview + kompresi                 |
| NIK terekspos                        | Tinggi | Masking di semua halaman + policy akses                 |
| Kode booking bentrok                 | Sedang | Unique index di database, bukan hanya validasi app      |
| Halaman katalog lambat               | Sedang | Index `business_id`/`status`, pagination, eager loading |
| Duplikasi kode produk yang mirip     | Rendah | Unique index `business_id` + `slug`                     |

---

## 9. Checklist Pelepasan (Release)

- [ ] Seluruh Phase 1–3 selesai
- [ ] Data seed demo dibersihkan sebelum production
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `php artisan config:cache` + `route:cache` + `view:cache`
- [ ] `pnpm build`
- [ ] Password admin default diganti
- [ ] Backup MySQL terjadwal
- [ ] HTTPS aktif
- [ ] Uji seluruh alur booking pada 2 unit bisnis
- [ ] Uji upload bukti bayar pada mobile
