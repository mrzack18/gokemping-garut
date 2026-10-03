# PRD — Sistem Sewa & Booking GoKemping

**Nama Produk:** GoKemping Rental & Booking System
**Perusahaan:** GoKemping
**Anak Perusahaan:** Sewa Sepeda Garut
**Jenis Sistem:** Web-based Rental & Booking Management System
**Target:** Penyewa dan admin operasional
**Tech Stack:** Laravel 13 · Inertia.js 3 · React 19 · TypeScript · Tailwind CSS v4 · shadcn/ui · Motion (Framer Motion) · MySQL 8 · pnpm

---

## 1. Gambaran Umum

**GoKemping** merupakan perusahaan yang bergerak di bidang penyewaan perlengkapan camping terlengkap di Garut. Selain layanan penyewaan alat camping, GoKemping memiliki anak perusahaan bernama **Sewa Sepeda Garut** yang menyediakan layanan penyewaan sepeda.

Sistem ini dibuat sebagai platform terpusat untuk mengelola proses **sewa dan booking** dari kedua layanan tersebut.

Sistem memungkinkan pelanggan melakukan pemesanan tanpa harus membuat akun. Pelanggan cukup memilih produk atau layanan yang ingin disewa, menentukan jadwal sewa, mengisi biodata, memilih metode pembayaran manual, mengunggah bukti pembayaran jika diperlukan, kemudian mengirim detail pesanan kepada admin melalui WhatsApp.

Sistem memiliki dua admin dengan akses yang dipisahkan berdasarkan unit bisnis:

- **Admin GoKemping**
- **Admin Sewa Sepeda Garut**

Kedua admin memiliki fungsi pengelolaan yang serupa, tetapi hanya dapat mengelola data dan pesanan milik unit bisnis masing-masing.

---

# 2. Tujuan Sistem

### Tujuan Utama

Membangun sistem booking yang mempermudah pelanggan melakukan penyewaan alat camping maupun sepeda secara online tanpa proses registrasi akun.

### Tujuan Khusus

1. Memudahkan pelanggan melihat katalog barang/sepeda yang tersedia.
2. Memudahkan pelanggan memilih tanggal dan periode penyewaan.
3. Mencegah terjadinya booking pada barang yang sedang disewa.
4. Memudahkan pelanggan melakukan booking tanpa login/register.
5. Menyediakan pembayaran manual melalui:
    - Cash
    - QRIS toko
    - Transfer bank
6. Menyediakan halaman pembayaran dan upload bukti pembayaran.
7. Mengirim detail booking ke WhatsApp admin.
8. Memisahkan pengelolaan GoKemping dan Sewa Sepeda Garut.
9. Membantu admin mengelola produk, booking, pembayaran, dan pelanggan.

---

# 3. Aktor Sistem

| Aktor                       | Deskripsi                                             |
| --------------------------- | ----------------------------------------------------- |
| **Penyewa/User**            | Pelanggan yang ingin menyewa alat camping atau sepeda |
| **Admin GoKemping**         | Mengelola seluruh operasional penyewaan GoKemping     |
| **Admin Sewa Sepeda Garut** | Mengelola seluruh operasional penyewaan sepeda        |

Tidak terdapat role customer/member.

**User tidak perlu membuat akun.**

---

# 4. Konsep Role Admin

### Admin GoKemping

Hanya dapat mengakses:

- Dashboard GoKemping
- Produk alat camping
- Kategori alat camping
- Booking GoKemping
- Data penyewa GoKemping
- Pembayaran GoKemping
- Pengaturan pembayaran GoKemping
- Laporan GoKemping

### Admin Sewa Sepeda Garut

Hanya dapat mengakses:

- Dashboard Sewa Sepeda Garut
- Produk sepeda
- Kategori sepeda
- Booking sepeda
- Data penyewa sepeda
- Pembayaran sepeda
- Pengaturan pembayaran Sewa Sepeda Garut
- Laporan Sewa Sepeda Garut

### Catatan

Admin **tidak dapat melihat atau mengubah data unit bisnis lain**.

---

# 5. Struktur Produk

Sistem memiliki dua unit bisnis.

```
GoKemping
│
├── Alat Camping
│   ├── Tenda
│   ├── Sleeping Bag
│   ├── Matras
│   ├── Kompor Camping
│   ├── Carrier
│   ├── Kursi Camping
│   └── Peralatan lainnya
│
└── Produk lainnya


Sewa Sepeda Garut
│
├── Sepeda MTB
├── Sepeda City Bike
├── Sepeda Anak
├── Sepeda lainnya
└── Produk lainnya
```

Kategori tersebut **dinamis** sehingga admin dapat menambah, mengubah, atau menghapus kategori.

---

# 6. Customer Flow

Flow utama sistem:

```
Landing Page
      ↓
Pilih Layanan
      ↓
GoKemping / Sewa Sepeda Garut
      ↓
Katalog Produk
      ↓
Pilih Produk
      ↓
Pilih Tanggal & Durasi Sewa
      ↓
Cek Ketersediaan
      ↓
Isi Biodata Penyewa
      ↓
Review Booking
      ↓
Pilih Metode Pembayaran
      ↓
Halaman Pembayaran
      ↓
Upload Bukti Pembayaran
      ↓
Lanjut Pesan via WhatsApp
      ↓
WhatsApp Admin
      ↓
Admin Memproses Booking
```

---

# 7. Landing Page

Landing page menjadi halaman awal sistem.

### Konten

**Hero Section**

Contoh:

> **Mau Camping atau Gowes?**
> Sewa perlengkapan camping dan sepeda dengan mudah di Garut.

CTA:

- **Sewa Alat Camping**
- **Sewa Sepeda**

### Section lainnya

- Tentang GoKemping
- Layanan
- Produk unggulan
- Cara penyewaan
- Keunggulan
- FAQ
- Kontak
- Lokasi

---

# 8. Pilih Layanan

User memilih jenis penyewaan.

### Pilihan

**GoKemping**

> Sewa perlengkapan camping lengkap untuk kebutuhan camping dan outdoor.

Button:

`Lihat Peralatan`

---

**Sewa Sepeda Garut**

> Sewa sepeda untuk gowes, rekreasi, maupun aktivitas outdoor di Garut.

Button:

`Lihat Sepeda`

Setelah memilih, user diarahkan ke katalog masing-masing.

---

# 9. Katalog Produk

Produk ditampilkan dalam bentuk card.

### Informasi produk

- Foto
- Nama produk
- Kategori
- Harga sewa
- Satuan harga
- Status ketersediaan
- Deskripsi singkat

Contoh:

```
┌────────────────────────────┐
│        FOTO PRODUK         │
├────────────────────────────┤
│ Tenda Dome 4 Person        │
│ Tenda                      │
│ Rp75.000 / hari            │
│                            │
│ ✓ Tersedia                 │
│                            │
│ [ Lihat Detail ]           │
└────────────────────────────┘
```

### Fitur katalog

- Search
- Filter kategori
- Filter harga
- Sorting
- Pagination

---

# 10. Detail Produk

User dapat melihat informasi lengkap.

### Data

- Foto utama
- Gallery foto
- Nama
- Kategori
- Harga
- Deskripsi
- Spesifikasi
- Ketentuan penyewaan
- Ketersediaan

CTA:

**Sewa Sekarang**

---

# 11. Booking

Setelah memilih produk, user menentukan jadwal.

### Input

**Tanggal Mulai**

`[ 10 Oktober 2026 ]`

**Tanggal Selesai**

`[ 12 Oktober 2026 ]`

Sistem otomatis menghitung durasi.

Contoh:

```
Tanggal mulai : 10 Oktober
Tanggal selesai: 12 Oktober

Durasi: 2 hari
Harga : Rp75.000/hari

Total:
Rp75.000 × 2
= Rp150.000
```

---

# 12. Availability Checking

Sistem harus melakukan pengecekan ketersediaan sebelum booking dapat dilanjutkan.

Contoh:

Produk:

> Tenda Dome 4 Person

Jumlah tersedia:

> 5 unit

Booking aktif:

> 3 unit

Maka:

> Tersedia 2 unit

Jika user meminta 3 unit:

> **Stok tidak mencukupi pada periode tersebut.**

Sistem harus memperhitungkan tanggal booking yang sedang aktif.

---

# 13. Jumlah Barang

Untuk produk yang memiliki stok lebih dari satu, user dapat menentukan jumlah.

Contoh:

```
Tenda Dome 4 Person

Jumlah:
[-] 2 [+]
```

Perhitungan:

```
Harga       : Rp75.000
Jumlah      : 2
Durasi      : 3 hari

Rp75.000 × 2 × 3
= Rp450.000
```

---

# 14. Biodata Penyewa

Setelah produk dan tanggal dipilih, user mengisi data.

### Form

**Data Penyewa**

- Nama lengkap \*
- Nomor WhatsApp \*
- Email
- NIK \*
- Alamat \*
- Kota/Kabupaten
- Catatan

### Untuk penyewaan sepeda

Dapat ditambahkan:

- Jumlah penyewa
- Catatan tambahan

Email dapat dibuat **opsional**, sedangkan:

> Nama + WhatsApp + NIK + alamat

menjadi data utama penyewa.

---

# 15. Review Booking

Sebelum pembayaran, user mendapatkan halaman review.

```
DETAIL BOOKING

Produk
Tenda Dome 4 Person

Jumlah
2 unit

Tanggal
10 Oktober 2026
s/d
12 Oktober 2026

Durasi
2 hari

Harga
Rp75.000 / hari

Subtotal
Rp300.000

────────────────

DATA PENYEWA

Nama
Zaki

WhatsApp
08xxxxxxxxxx

NIK
xxxxxxxxxxxx

Alamat
Garut

────────────────

TOTAL
Rp300.000

[ Lanjut Pembayaran ]
```

User harus melakukan konfirmasi sebelum lanjut.

---

# 16. Metode Pembayaran

Sistem **tidak menggunakan payment gateway**.

User dapat memilih:

### 1. Cash

```
○ Cash
```

Keterangan:

> Pembayaran dilakukan secara langsung sesuai ketentuan GoKemping/Sewa Sepeda Garut.

### 2. QRIS Toko

```
○ QRIS
```

Setelah dipilih, sistem menampilkan QRIS milik toko.

```
Scan QRIS

[ QR CODE ]

Total Pembayaran
Rp300.000
```

### 3. Transfer Bank

```
○ Transfer Bank
```

Menampilkan rekening toko.

Contoh:

```
Bank BCA

GoKemping

1234567890

[ Salin Nomor Rekening ]
```

Nomor rekening dan QRIS harus dapat diatur admin.

---

# 17. Halaman Pembayaran

Setelah memilih metode pembayaran, user masuk ke halaman pembayaran.

### Jika Cash

```
PEMBAYARAN CASH

Total:
Rp300.000

Pembayaran dilakukan langsung kepada admin.

[ Lanjut Pesan via WhatsApp ]
```

### Jika QRIS

```
PEMBAYARAN QRIS

Total:
Rp300.000

[ QRIS ]

Setelah melakukan pembayaran,
upload bukti pembayaran.

[ Upload Bukti ]

✓ bukti-transfer.jpg

[ Lanjut Pesan via WhatsApp ]
```

### Jika Bank

```
PEMBAYARAN TRANSFER

BCA
GoKemping
1234567890

Total:
Rp300.000

[ Upload Bukti Pembayaran ]

✓ bukti-transfer.jpg

[ Lanjut Pesan via WhatsApp ]
```

---

# 18. Upload Bukti Pembayaran

Untuk pembayaran:

- QRIS
- Transfer Bank

User dapat mengunggah bukti pembayaran.

Format:

- JPG
- JPEG
- PNG
- WebP

Maksimal ukuran misalnya:

**5 MB**

Bukti pembayaran disimpan pada data booking dan dapat dilihat admin.

Untuk **Cash**, bukti pembayaran tidak wajib.

---

# 19. WhatsApp Booking

Setelah proses pembayaran selesai, user menekan:

> **Lanjut Pesan via WhatsApp**

Sistem membuat pesan WhatsApp otomatis.

Contoh:

```
Halo Admin GoKemping,

Saya ingin melakukan booking sewa.

DETAIL BOOKING
━━━━━━━━━━━━━━━━

Kode Booking: GK-20261003-001

Produk:
Tenda Dome 4 Person

Jumlah:
2 Unit

Tanggal Sewa:
10 Oktober 2026 - 12 Oktober 2026

Durasi:
2 Hari

Total:
Rp300.000

DATA PENYEWA
━━━━━━━━━━━━━━━━

Nama:
Zaki Muhammad

No. WhatsApp:
08xxxxxxxxxx

NIK:
xxxxxxxxxxxx

Alamat:
Garut

METODE PEMBAYARAN
━━━━━━━━━━━━━━━━

QRIS

Bukti pembayaran:
Sudah diupload

Mohon konfirmasi booking saya.

Terima kasih.
```

Nomor WhatsApp tujuan otomatis mengikuti unit bisnis.

```
GoKemping
      ↓
WhatsApp Admin GoKemping

Sewa Sepeda Garut
      ↓
WhatsApp Admin Sewa Sepeda Garut
```

---

# 20. Kode Booking

Setiap booking mendapatkan kode unik.

Format:

```
GK-20261003-001
```

Untuk Sewa Sepeda:

```
SSG-20261003-001
```

Contoh:

```
GK = GoKemping
SSG = Sewa Sepeda Garut
```

Kode booking digunakan untuk identifikasi transaksi oleh admin.

---

# 21. Status Booking

Booking memiliki status:

```
Menunggu Konfirmasi
        ↓
Dikonfirmasi
        ↓
Sedang Disewa
        ↓
Selesai
```

Status tambahan:

```
Dibatalkan
```

### Detail

**Menunggu Konfirmasi**

Booking sudah dibuat tetapi belum dikonfirmasi admin.

**Dikonfirmasi**

Admin menyetujui booking.

**Sedang Disewa**

Barang/sepeda sedang berada pada penyewa.

**Selesai**

Barang sudah dikembalikan dan transaksi selesai.

**Dibatalkan**

Booking dibatalkan oleh admin sesuai kondisi transaksi.

---

# 22. Dashboard Admin

Dashboard berbeda berdasarkan unit bisnis.

### Dashboard GoKemping

Menampilkan:

```
Total Produk
24

Booking Hari Ini
8

Sedang Disewa
13

Menunggu Konfirmasi
4

Pendapatan
Rp12.500.000
```

### Dashboard Sewa Sepeda Garut

Data yang sama tetapi hanya untuk unit sepeda.

---

# 23. Manajemen Produk

Admin dapat:

- Melihat produk
- Menambah produk
- Edit produk
- Hapus/nonaktifkan produk
- Upload foto
- Mengatur harga
- Mengatur stok
- Mengatur kategori
- Mengatur deskripsi
- Mengatur status produk

### Status produk

```
Aktif
Nonaktif
```

Produk nonaktif tidak muncul pada katalog user.

---

# 24. Manajemen Booking

Admin dapat melihat seluruh booking milik unitnya.

### Table

| Kode   | Penyewa | Produk  | Periode   | Total  | Pembayaran | Status       |
| ------ | ------- | ------- | --------- | ------ | ---------- | ------------ |
| GK-001 | Zaki    | Tenda   | 10–12 Okt | Rp300k | QRIS       | Menunggu     |
| GK-002 | Andi    | Carrier | 11–13 Okt | Rp150k | Cash       | Dikonfirmasi |

Admin dapat:

- Melihat detail
- Melihat biodata
- Melihat produk
- Melihat bukti pembayaran
- Mengubah status
- Membatalkan booking
- Melihat riwayat

---

# 25. Manajemen Penyewa

Data penyewa tidak membutuhkan akun.

Data dibuat berdasarkan booking.

Admin dapat melihat:

- Nama
- WhatsApp
- NIK
- Alamat
- Riwayat booking
- Total transaksi

Jika pelanggan yang sama melakukan booking lagi, sistem dapat menyimpan data pelanggan berdasarkan nomor WhatsApp/NIK tanpa mengharuskan login.

---

# 26. Manajemen Pembayaran

Admin dapat melihat:

- Metode pembayaran
- Nominal
- Bukti pembayaran
- Status pembayaran

### Status pembayaran

```
Belum Dibayar
Menunggu Verifikasi
Lunas
Ditolak
```

Untuk cash:

```
Belum Dibayar
↓
Lunas
```

Untuk QRIS/Bank:

```
Belum Dibayar
↓
Menunggu Verifikasi
↓
Lunas
```

atau:

```
Menunggu Verifikasi
↓
Ditolak
```

---

# 27. Pengaturan Pembayaran

Setiap admin dapat mengatur metode pembayaran unit bisnisnya.

### QRIS

- Upload QRIS
- Nama merchant

### Bank

- Nama bank
- Nomor rekening
- Nama pemilik rekening

### Cash

- Keterangan pembayaran

Contoh:

```
Pengaturan Pembayaran

QRIS
[ upload QRIS ]

Bank
BCA
1234567890
GoKemping

Cash
Pembayaran langsung di lokasi.

[ Simpan ]
```

---

# 28. Manajemen Konten

Admin dapat mengelola konten yang berkaitan dengan unitnya.

Contohnya:

- Banner
- Informasi layanan
- FAQ
- Ketentuan sewa
- Kontak
- Informasi lokasi

---

# 29. Laporan

Admin dapat melihat laporan berdasarkan periode.

Filter:

```
Tanggal Mulai
Tanggal Akhir
```

Data:

- Jumlah booking
- Booking selesai
- Booking dibatalkan
- Total pendapatan
- Produk paling banyak disewa
- Jumlah penyewa
- Metode pembayaran

Export:

- Excel
- PDF

---

# 30. Data Model Utama

Struktur database yang disarankan:

```
users
├── id
├── name
├── email
├── password
├── role
└── business_id

businesses
├── id
├── name
├── slug
├── whatsapp
├── description
└── status

categories
├── id
├── business_id
├── name
└── status

products
├── id
├── business_id
├── category_id
├── name
├── slug
├── description
├── price
├── stock
├── status
└── ...

product_images
├── id
├── product_id
└── image

customers
├── id
├── name
├── whatsapp
├── email
├── nik
└── address

bookings
├── id
├── booking_code
├── business_id
├── customer_id
├── start_date
├── end_date
├── total_days
├── subtotal
├── total
├── payment_method
├── payment_status
├── booking_status
└── notes

booking_items
├── id
├── booking_id
├── product_id
├── quantity
├── price
└── subtotal

payments
├── id
├── booking_id
├── method
├── amount
├── proof
├── status
└── verified_at

payment_methods
├── id
├── business_id
├── type
├── bank_name
├── account_number
├── account_name
├── qris_image
└── status
```

---

# 31. Relasi Data

```
Business
   │
   ├──────── Categories
   │             │
   │             └── Products
   │
   ├──────── Admin
   │
   ├──────── Payment Methods
   │
   └──────── Bookings
                    │
                    ├── Customer
                    │
                    ├── Booking Items
                    │        │
                    │        └── Products
                    │
                    └── Payment
```

---

# 32. Business Rules

### BR-01 — Tanpa Registrasi

User tidak wajib memiliki akun untuk melakukan booking.

### BR-02 — Booking Harus Memiliki Produk

Booking tidak dapat dibuat tanpa produk.

### BR-03 — Booking Harus Memiliki Periode

Tanggal mulai dan tanggal selesai wajib diisi.

### BR-04 — Tidak Boleh Overbooking

Sistem harus memeriksa booking aktif pada periode yang sama sebelum menyatakan produk tersedia.

### BR-05 — Admin Terisolasi

Admin GoKemping hanya dapat mengakses data GoKemping.

Admin Sewa Sepeda Garut hanya dapat mengakses data Sewa Sepeda Garut.

### BR-06 — WhatsApp

Nomor WhatsApp tujuan ditentukan berdasarkan `business_id`.

### BR-07 — Pembayaran Manual

Sistem tidak melakukan proses pembayaran secara otomatis.

Sistem hanya menyediakan informasi pembayaran dan penyimpanan bukti pembayaran.

### BR-08 — Bukti Pembayaran

Bukti pembayaran wajib untuk QRIS dan transfer, sedangkan cash tidak membutuhkan upload bukti.

### BR-09 — Harga

Harga yang digunakan dalam booking harus disimpan pada `booking_items`, sehingga perubahan harga produk di kemudian hari tidak mengubah transaksi lama.

### BR-10 — Booking Code

Setiap booking harus memiliki kode unik.

---

# 33. Non-Functional Requirements

### Performance

- Halaman katalog harus cepat dimuat.
- Gambar produk dikompresi.
- Pagination digunakan untuk katalog dan dashboard.
- Query database dioptimalkan.

### Security

- Password admin disimpan menggunakan hashing.
- Admin menggunakan autentikasi.
- Authorization berdasarkan `business_id`.
- Upload bukti pembayaran divalidasi.
- NIK tidak ditampilkan secara terbuka.
- Endpoint admin harus terlindungi authorization.
- User tidak dapat mengakses dashboard admin.

### Responsive

Sistem harus mendukung:

- Desktop
- Tablet
- Mobile

Karena sebagian besar user kemungkinan melakukan booking melalui smartphone.

---

# 34. Struktur Halaman User

```
/
├── Home
│
├── /gokemping
│   ├── Katalog
│   ├── Detail Produk
│   ├── Booking
│   ├── Biodata
│   ├── Review
│   └── Pembayaran
│
├── /sewa-sepeda-garut
│   ├── Katalog
│   ├── Detail Sepeda
│   ├── Booking
│   ├── Biodata
│   ├── Review
│   └── Pembayaran
│
└── /booking
    └── Success
```

---

# 35. Struktur Dashboard

```
/admin

├── Login
│
├── GoKemping
│   ├── Dashboard
│   ├── Produk
│   ├── Kategori
│   ├── Booking
│   ├── Penyewa
│   ├── Pembayaran
│   ├── Laporan
│   └── Pengaturan
│
└── Sewa Sepeda Garut
    ├── Dashboard
    ├── Produk
    ├── Kategori
    ├── Booking
    ├── Penyewa
    ├── Pembayaran
    ├── Laporan
    └── Pengaturan
```

---

# 36. User Journey Final

Flow yang paling penting dari sisi pelanggan:

```
                     LANDING PAGE
                          │
             ┌────────────┴────────────┐
             ↓                         ↓
        GO KEMPING             SEWA SEPEDA GARUT
             │                         │
             └────────────┬────────────┘
                          ↓
                       KATALOG
                          ↓
                    DETAIL PRODUK
                          ↓
                 PILIH TANGGAL SEWA
                          ↓
                 PILIH JUMLAH PRODUK
                          ↓
                   CEK KETERSEDIAAN
                          ↓
                   ISI BIODATA
                          ↓
                  REVIEW BOOKING
                          ↓
                PILIH PEMBAYARAN
                          │
             ┌────────────┼────────────┐
             ↓            ↓            ↓
           CASH          QRIS         BANK
             │            │            │
             │       Upload Bukti     │
             │            │       Upload Bukti
             └────────────┼────────────┘
                          ↓
                  KONFIRMASI BOOKING
                          ↓
              GENERATE PESAN WHATSAPP
                          ↓
             ┌────────────┴────────────┐
             ↓                         ↓
       WA ADMIN GOKEMPING       WA ADMIN SEPEDA
```

---

# 37. MVP / Prioritas Pengembangan

### Phase 1 — Core

- Landing page
- Pilihan GoKemping / Sewa Sepeda
- Katalog
- Detail produk
- Booking
- Availability checking
- Biodata
- Perhitungan harga
- Metode pembayaran
- Upload bukti
- WhatsApp booking

### Phase 2 — Admin

- Login admin
- Dashboard
- CRUD produk
- CRUD kategori
- Booking management
- Customer management
- Payment verification
- Status booking
- Payment settings

### Phase 3 — Management

- Laporan
- Export Excel/PDF
- Statistik
- Riwayat transaksi
- Content management

---

## 38. Prinsip UX Utama

Sistem ini **bukan marketplace dan bukan sistem membership**.

Prinsip utamanya:

> **Pilih → Tentukan Jadwal → Isi Data → Bayar Manual → Kirim WhatsApp**

Jadi user **tidak perlu**:

- Register
- Login
- Mengingat password
- Menunggu email
- Menggunakan payment gateway

Sedangkan admin mendapatkan sistem back-office untuk memastikan **produk, stok, jadwal sewa, pembayaran, dan booking** tetap terkontrol.

**Arsitektur bisnis yang paling pas:** satu aplikasi dengan satu database, tetapi setiap data memiliki `business_id` yang membedakan **GoKemping** dan **Sewa Sepeda Garut**. Dengan begitu dua admin bisa menggunakan sistem yang sama tanpa data kedua bisnis tercampur.

---

## 39. Tech Stack

### 39.1 Komponen Teknologi

| Layer              | Teknologi                   | Versi / Catatan                      |
| ------------------ | --------------------------- | ------------------------------------ |
| Backend            | Laravel Framework           | `^13.0`                              |
| Bahasa Backend     | PHP                         | `^8.3` (min), recommended `8.4`      |
| Dependency Manager | Composer                    | 2.x                                  |
| Bridge SPA         | Inertia.js                  | `^3.0` (`inertiajs/inertia-laravel`) |
| Frontend           | React                       | `^19.2`                              |
| Bahasa Frontend    | TypeScript                  | `^5.x`                               |
| Styling            | Tailwind CSS                | `^4.1` (plugin `@tailwindcss/vite`)  |
| Component Library  | shadcn/ui                   | shadcn/ui + Radix UI + lucide-react  |
| Animation          | Motion (dulu Framer Motion) | `^12.x`, import dari `motion/react`  |
| Bundler            | Vite                        | `laravel-vite-plugin: ^3.0`          |
| Package Manager    | **pnpm**                    | 10.x                                 |
| Database           | MySQL                       | 8.x (lokal: 8.4)                     |
| Authenticated Area | Laravel Fortify + session   | `^1.37`                              |
| Type-safe Routing  | Laravel Wayfinder           | `^0.1`                               |
| Testing Backend    | PHPUnit                     | `^12.0`                              |
| Static Analysis    | Laravel Pint + PHPStan      | —                                    |
| Linting Frontend   | ESLint + Prettier           | —                                    |

### 39.2 Alasan Pemilihan

1. **Laravel + Inertia (bukan API terpisah)**
   Seluruh backend dan frontend berada dalam satu project yang sama. Sesuai §38, sistem ini adalah satu aplikasi dengan satu database — bukan microservice. Inertia memungkinkan controller Laravel mengirim data langsung ke komponen React tanpa menulis endpoint REST terpisah.

2. **Inertia.js 3 + React 19 + Tailwind 4 + shadcn/ui**
   Kombinasi ini persis sama dengan React Starter Kit resmi Laravel 13, sehingga tidak perlu konfigurasi manual yang rawan error. shadcn/ui menyimpan komponen di dalam repository (`resources/js/components/ui/`) sehingga mudah dikustomisasi sesuai identitas GoKemping tanpa ketergantungan package tertutup.

3. **Motion (Framer Motion)**
   Dipakai untuk animasi transisi halaman Inertia, animasi section landing page, feedback interaksi tombol, dan animasi stepper pada alur booking. Library ini gratis (MIT) tanpa biaya API.

4. **MySQL 8**
   Digunakan karena availability checking (BR-04) memerlukan transaksi database dengan row locking. MySQL + InnoDB mendukung `SELECT ... FOR UPDATE` untuk mencegah dua booking mengambil unit stok yang sama secara bersamaan.

5. **pnpm**
   Overlay filesystem membuat `node_modules` jauh lebih ringan dan install berulang kali sangat cepat. Starter Kit Laravel sudah menyediakan `pnpm-workspace.yaml`, sehingga pnpm menjadi pilihan yang paling sesuai.

### 39.3 Struktur Direktori

```bash
app/
├── Enums/                        # BookingStatus, PaymentStatus, PaymentMethodType
├── Models/
│   ├── Business.php
│   ├── Category.php
│   ├── Product.php
│   ├── ProductImage.php
│   ├── Customer.php
│   ├── Booking.php
│   ├── BookingItem.php
│   ├── Payment.php
│   └── PaymentMethod.php
├── Http/
│   ├── Controllers/
│   │   ├── Public/               # katalog, detail produk, booking, pembayaran
│   │   └── Admin/                # seluruh modul back-office
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php
│   │   └── EnsureBusinessAccess.php   # implementasi BR-05
│   ├── Requests/
│   └── Resources/
├── Policies/                     # authorization berbasis business_id
├── Services/
│   ├── AvailabilityService.php   # BR-04
│   ├── BookingService.php
│   ├── PaymentService.php
│   └── WhatsappService.php       # BR-06
└── Support/
    └── BookingCodeGenerator.php  # BR-10

database/
├── migrations/
├── seeders/                      # seeding 2 business + 2 admin
└── factories/

resources/js/
├── pages/
│   ├── public/                   # katalog, detail, booking, review, pembayaran
│   └── admin/                    # dashboard, produk, kategori, booking, ...
├── components/
│   ├── ui/                       # hasil shadcn/ui (di-commit ke repo)
│   ├── landing/
│   ├── catalog/
│   ├── booking/
│   └── admin/
├── hooks/
├── lib/
│   ├── utils.ts                  # cn() helper
│   ├── format.ts                 # format Rupiah, tanggal Indonesia
│   └── motion.ts                 # konfigurasi variants Motion
└── types/

routes/
├── web.php                       # route publik
├── admin.php                     # route dashboard (middleware auth + business)
└── auth.php
```

### 39.4 Implementasi Aturan Bisnis di Stack Ini

| BR    | Implementasi                                                                                    |
| ----- | ----------------------------------------------------------------------------------------------- |
| BR-01 | Tidak ada registrasi publik. Route admin memakai middleware `auth`.                             |
| BR-03 | Validasi `date_after_or_equal` + kalender (date picker) dari shadcn/ui.                         |
| BR-04 | `AvailabilityService` dijalankan di dalam `DB::transaction` + `lockForUpdate`.                  |
| BR-05 | Scope global `business_id` pada query + `Policy` per model + `EnsureBusinessAccess` middleware. |
| BR-06 | `WhatsappService` membaca `business.whatsapp` berdasarkan `business_id`.                        |
| BR-08 | Validasi upload: `mimes:jpg,jpeg,png,webp`, `max:5120` (5 MB).                                  |
| BR-09 | `booking_items.price` di-copy dari `products.price` saat transaksi disimpan.                    |
| BR-10 | `BookingCodeGenerator` membuat kode `GK-YYYYMMDD-NNN` / `SSG-YYYYMMDD-NNN`.                     |

### 39.5 Prasyarat Lingkungan

| Kebutuhan    | Versi  | Status               |
| ------------ | ------ | -------------------- |
| PHP          | 8.3+   | ✅ 8.4.26 terpasang  |
| Node.js      | 20+    | ✅ 24.21.0 terpasang |
| MySQL Server | 8.x    | ✅ 8.4.11 terpasang  |
| Composer     | 2.x    | ❌ belum terpasang   |
| pnpm         | 10.x   | ❌ belum terpasang   |
| Laravel CLI  | latest | ❌ belum terpasang   |

### 39.6 Perintah Setup

```bash
# 1. Prasyarat yang belum terpasang
composer global require laravel/installer
npm install -g pnpm

# 2. Buat proyek (React Starter Kit = Inertia 3 + React 19 + Tailwind 4 + shadcn/ui)
laravel new gokemping --react

# 3. Install dependency frontend dengan pnpm
pnpm install

# 4. Tambah Motion
pnpm add motion

# 5. Tambah komponen shadcn/ui sesuai kebutuhan
pnpm dlx shadcn@latest add button card input dialog calendar
pnpm dlx shadcn@latest add select table badge tabs dropdown-menu

# 6. Konfigurasi database + migrate + seed
php artisan migrate --seed

# 7. Jalankan
composer run dev
```

### 39.7 Catatan Teknis

1. **Motion vs Framer Motion** — paket `framer-motion` sudah deprecated. Gunakan paket `motion` dengan import `import { motion } from "motion/react"`.

2. **shadcn/ui di Laravel** — komponen di-install ke dalam repo pada `resources/js/components/ui/`, bukan di `node_modules`. Artinya komponen tetap bisa diedit langsung.

3. **Dua admin dalam satu repo** — karena branch kode untuk user dan admin sama, seluruh halaman admin berada di `resources/js/pages/admin/` dengan layout terpisah dari halaman publik agar user tidak dapat melihat halaman dashboard admin.

4. **Format** — seluruh nominal Rupiah ditampilkan dengan helper `format.ts` agar konsisten, sedangkan nilai di database disimpan sebagai `integer` (rupiah penuh, tanpa titik).

5. **Naming** — migration, class, dan file mengikuti konvensi Laravel (snake_case untuk tabel, StudlyCase untuk class). Folder halaman mengikuti `resources/js/pages/<area>/<Feature>/<Page>.tsx`.
