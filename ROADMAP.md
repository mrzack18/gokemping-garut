# ROADMAP — GoKemping Rental & Booking System

**Dasar dokumen:** [PRD.md](./PRD.md)
**Tech Stack:** Laravel 13 · Inertia.js 3 · React 19 · TypeScript · Tailwind CSS v4 · shadcn/ui · Motion · MySQL 8 · pnpm
**Status:** Fase 0 selesai
**Terakhir diperbarui:** 2026-10-03

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
  `menunggu_konfirmasi`. Dipakai versi enum, 즉 tiga status, karena
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
- **`lockForUpdate` belum dipakai.** PRD section 39.3 memetakan BR-04 ke
  `AvailabilityService` yang jalan di dalam `DB::transaction` + `lockForUpdate`.
  Endpoint ini hanya membaca, dipanggil setiap kali tanggal berubah, jadi
  mengunci baris akan menambah kontensi tanpa mencegah apa pun. Pengaman race
  yang sesungguhnya adalah saat penyimpanan booking di ROADMAP 3.11, tepat
  sebelum stok dikurangi. Perlu dikonfirmasi ke pemilik produk.
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

- [ ] Ringkasan produk, jumlah, periode, durasi, harga, subtotal
- [ ] Ringkasan data penyewa
- [ ] Total
- [ ] Tombol "Lanjut Pembayaran"
- [ ] Edit data (kembali ke form sebelumnya)

### 3.9 Metode Pembayaran & Halaman Pembayaran

- [ ] Pilihan metode: Cash / QRIS / Transfer Bank
- [ ] Halaman Cash: total + keterangan + tombol lanjut
- [ ] Halaman QRIS: QRIS toko + total + upload bukti
- [ ] Halaman Transfer: rekening + tombol salin + upload bukti
- [ ] Ambil data rekening/QRIS dari `payment_methods` sesuai `business_id`
- [ ] Salin nomor rekening ke clipboard

### 3.10 Upload Bukti Pembayaran (BR-08)

- [ ] Validasi `mimes:jpg,jpeg,png,webp`
- [ ] Validasi `max:5120` (5 MB)
- [ ] Preview gambar sebelum submit
- [ ] Hapus & ganti bukti
- [ ] Bukti **wajib** untuk QRIS/Transfer, **opsional** untuk Cash

### 3.11 Penyimpanan Booking

- [ ] Simpan booking + `booking_items` dalam satu transaksi DB
- [ ] Salin harga produk ke `booking_items` (BR-09)
- [ ] Generate kode booking `GK-YYYYMMDD-NNN` / `SSG-YYYYMMDD-NNN` (BR-10)
- [ ] Simpan record `payments`
- [ ] Set status awal: booking `menunggu_konfirmasi`, payment `belum_dibayar` / `menunggu_verifikasi`

### 3.12 WhatsApp Booking (BR-06)

- [ ] Template pesan otomatis sesuai format PRD §19
- [ ] Nomor tujuan mengikuti `business.whatsapp`
- [ ] Tombol "Lanjut Pesan via WhatsApp" → `wa.me/<number>?text=<encoded>`
- [ ] Halaman `/booking/success` berisi kode booking + tombol WhatsApp

**Deliverable Fase 1:** Pelanggan dapat menyelesaikan booking penuh dan mengirim detail ke WhatsApp admin tanpa login.

---

## 4. Fase 2 — Admin Back-office

### 4.1 Dashboard

- [ ] Total produk
- [ ] Booking hari ini
- [ ] Sedang disewa
- [ ] Menunggu konfirmasi
- [ ] Pendapatan (periode berjalan)
- [ ] Chart booking 7 hari terakhir
- [ ] Widget booking terbaru
- [ ] Semua query ter-scope `business_id`

### 4.2 Manajemen Kategori

- [ ] Tabel kategori + jumlah produk
- [ ] Tambah / edit / ubah status
- [ ] Hapus kategori (dengan konfirmasi)
- [ ] Guard: kategori dengan produk tidak bisa dihapus

### 4.3 Manajemen Produk

- [ ] Tabel produk: foto, nama, kategori, harga, stok, status
- [ ] Search + filter kategori + filter status
- [ ] Form tambah produk
- [ ] Form edit produk
- [ ] Upload multiple foto + gallery
- [ ] Kompresi gambar (Intervention)
- [ ] Hapus / nonaktifkan produk (soft delete)
- [ ] Atur harga, stok, deskripsi, spesifikasi, ketentuan sewa
- [ ] Produk nonaktif tidak muncul di katalog publik

### 4.4 Manajemen Booking

- [ ] Tabel: kode, penyewa, produk, periode, total, pembayaran, status
- [ ] Search + filter status + filter rentang tanggal
- [ ] Halaman detail booking
- [ ] Ubah status: menunggu → dikonfirmasi → sedang disewa → selesai
- [ ] Batalkan booking
- [ ] Riwayat status booking
- [ ] Konfirmasi pembatalan otomatis pada `payments`

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
