# KasirKu - Sistem Informasi Toko Kasir

Aplikasi Point of Sale (POS) dan Sistem Informasi Toko berbasis web yang dibangun menggunakan **PHP Native 8.x** dan **MySQL/MariaDB** dengan **PDO (PHP Data Objects)** serta antarmuka modern menggunakan **Bootstrap 5**. Dirancang dengan arsitektur yang bersih, modular, aman, dan mudah dipahami oleh mahasiswa maupun pengembang pemula.

---

## 📌 Daftar Isi

1. [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
2. [Fitur Utama](#-fitur-utama)
3. [Hak Akses Pengguna (Role)](#-hak-akses-pengguna-role)
4. [Struktur Folder](#-struktur-folder)
5. [Skema Database](#-skema-database)
6. [Panduan Instalasi](#-panduan-instalasi)
   - [Instalasi di Laragon](#a-instalasi-di-laragon)
   - [Instalasi di XAMPP](#b-instalasi-di-xampp)
7. [Akun Pengguna Default](#-akun-pengguna-default)
8. [Panduan Penggunaan Alur Kasir](#-panduan-penggunaan-alur-kasir)
9. [Fitur Keamanan](#-fitur-keamanan)
10. [Troubleshooting & Solusi Error](#-troubleshooting--solusi-error)

---

## 💻 Teknologi yang Digunakan

- **Backend**: PHP Native 8.x (Tanpa framework seperti Laravel/CodeIgniter)
- **Database Engine**: MySQL / MariaDB (Driver PDO)
- **Frontend / UI**: HTML5, CSS3 kustom, JavaScript Vanilla
- **UI Framework**: Bootstrap 5.3 & Bootstrap Icons
- **Visualisasi Data**: Chart.js 4.4
- **Format Cetak**: CSS `@media print` untuk Printer Thermal 58mm/80mm & Kertas A4

---

## ✨ Fitur Utama

### 1. Modul Autentikasi & Keamanan
- Login multi-role (Admin dan Kasir) menggunakan prepared statement.
- Proteksi brute-force dan pencurian sesi dengan `session_regenerate_id()`.
- Enkripsi password menggunakan algoritma standar industri `password_hash()` (BCRYPT) dan `password_verify()`.
- Validasi CSRF Token di seluruh form transaksi dan manipulasi data.
- Proteksi status akun: pengguna yang dinonaktifkan otomatis ditolak masuk atau dikeluarkan dari sesi aktif.

### 2. Modul Dashboard Interaktif
- **Dashboard Admin**:
  - 5 Card Statistik: Pendapatan Hari Ini, Pendapatan Bulan Ini, Total Transaksi, Total Produk Aktif, dan Total Kategori.
  - Peringatan stok menipis (`stok <= stok_minimum`) lengkap dengan tombol pintas restock.
  - Tabel 5 transaksi kasir terbaru secara real-time.
- **Dashboard Kasir**:
  - Total transaksi dan total omset hari ini yang diproses khusus oleh kasir yang sedang bertugas.
  - Tombol cepat untuk langsung membuka terminal POS kasir.
  - Tabel transaksi terakhir milik kasir tersebut.

### 3. Master Data Produk & Kategori
- **Kategori**: CRUD lengkap dengan validasi integritas (kategori yang masih digunakan oleh produk tidak dapat dihapus).
- **Produk**:
  - Kode produk unik / barcode, nama produk, kategori, harga beli (modal), harga jual, stok fisik, batas stok minimum, satuan barang, dan foto.
  - Upload foto produk dengan validasi tipe MIME/ekstensi (`.jpg`, `.jpeg`, `.png`, `.webp`) dan batas ukuran file (maksimal 2 MB).
  - Filter pencarian ganda: teks pencarian realtime dan dropdown kategori.
  - Detail produk lengkap dengan kalkulasi otomatis margin keuntungan (Rupiah & Persentase).

### 4. Manajemen Stok (Kartu Mutasi Stok)
- Mendukung 3 jenis mutasi stok:
  1. **Stok Masuk**: Penerimaan barang / restock dari distributor.
  2. **Stok Keluar**: Barang rusak, retur ke supplier, kadaluarsa, atau hilang.
  3. **Penyesuaian (Stock Opname)**: Audit fisik langsung di rak/gudang toko.
- Otomatis mencatat mutasi saat produk baru didaftarkan dengan stok awal > 0.
- Proteksi stok negatif: sistem menolak pengurangan stok yang melebihi jumlah sisa fisik.
- Log riwayat mutasi stok lengkap dengan waktu, kuantitas, jejak `stok_sebelum` &rarr; `stok_sesudah`, alasan/keterangan, serta nama petugas.

### 5. Terminal Kasir POS (Point of Sale)
- Layout 2 kolom yang intuitif dan cepat digunakan:
  - **Kiri**: Katalog produk, kotak pencarian instan (kode/nama), dan filter tombol pill kategori.
  - **Kanan**: Keranjang belanja interaktif dengan tombol kuantitas (`+` / `-`), hapus item, dan kosongkan keranjang.
- Validasi batas stok saat memasukkan barang ke keranjang.
- Perhitungan instan: Subtotal, Potongan Diskon (Rp), dan Grand Total.
- Metode pembayaran: **Tunai**, **Transfer**, dan **QRIS**.
- Tombol nominal cepat: "Uang Pas", `+10rb`, `+20rb`, `+50rb`, `+100rb`.
- Kalkulasi otomatis nilai kembalian dengan validasi uang tidak boleh kurang dari total tagihan.
- Penomoran transaksi unik otomatis dengan format: `TRX-YYYYMMDD-0001`.
- **Database Transaction (PDO)**: Menjamin data transaksi, detail penjualan, pemotongan stok barang, dan pencatatan kartu mutasi stok tersimpan secara konsisten dan aman dari *race condition*.

### 6. Cetak Struk Pembayaran
- Struk otomatis muncul setelah transaksi berhasil disimpan.
- Mendukung printer thermal kasir ukuran 58mm atau 80mm dengan font monospace standar kasir.
- Kompatibel untuk dicetak pada kertas biasa (A4/Letter).

### 7. Riwayat Transaksi
- **Admin**: Dapat melihat seluruh transaksi penjualan dari semua kasir, filter tanggal, filter kasir, filter metode pembayaran, melihat detail barang per transaksi, dan mencetak ulang struk.
- **Kasir**: Hanya dapat melihat transaksi yang diproses oleh kasir tersebut demi privasi dan akuntabilitas sesi kerja.

### 8. Laporan Penjualan & Analisis Produk
- **Laporan Penjualan**:
  - Filter rentang tanggal fleksibel: Hari Ini, Kemarin, Minggu Ini, Bulan Ini, dan Rentang Kustom.
  - Metrik lengkap: Jumlah Transaksi, Total Unit Terjual, Penjualan Kotor, Total Diskon, Pendapatan Bersih (Omset), dan **Estimasi Keuntungan Kotor (Gross Profit)**.
  - Tampilan Siap Cetak (Print View) formal dengan kop toko dan kolom tanda tangan.
  - Fitur **Export Excel (.xls)** native tanpa memerlukan library Composer pihak ketiga.
- **Laporan Produk & Valuasi Stok**:
  - Valuasi total modal HPP aset stok toko dan potensi nilai jualnya.
  - Grafik tren omset penjualan 7 hari terakhir menggunakan **Chart.js**.
  - Daftar Top 5 Produk Terlaris (*Best Seller*).
  - Daftar 5 Produk Paling Sedikit Terjual (*Slow Moving*).

### 9. Manajemen Pengguna / Kasir
- Kelola akun kasir dan administrator baru.
- Fitur reset kata sandi pengguna oleh administrator.
- Sakelar aktivasi/nonaktifkan status akun secara instan.
- Proteksi integritas audit: akun yang sudah tercatat dalam riwayat transaksi atau stok tidak dapat dihapus, melainkan dinonaktifkan.

---

## 👥 Hak Akses Pengguna (Role)

| Menu / Fitur | Administrator | Kasir |
|---|:---:|:---:|
| Login / Logout | ✅ | ✅ |
| Dashboard Khusus | ✅ (Lengkap) | ✅ (Penjualan Pribadi) |
| Master Data Produk & Kategori | ✅ | ❌ |
| Kelola & Penyesuaian Stok | ✅ | ❌ |
| Kartu Riwayat Mutasi Stok | ✅ | ❌ |
| Terminal Kasir POS (Transaksi) | ✅ | ✅ |
| Cetak Struk Pembayaran | ✅ | ✅ |
| Riwayat Transaksi Pribadi | ✅ | ✅ |
| Riwayat Semua Transaksi Kasir | ✅ | ❌ |
| Laporan Penjualan & Export Excel | ✅ | ❌ |
| Analisis Produk & Valuasi Stok | ✅ | ❌ |
| Manajemen Akun Pengguna / Kasir | ✅ | ❌ |

---

## 📁 Struktur Folder

```text
kasirku/
├── config/
│   └── database.php                # Pengaturan PDO, BASE_URL dinamis, & Helper
├── auth/
│   ├── login.php                   # Form login & autentikasi pengguna
│   └── logout.php                  # Pembersihan sesi login
├── admin/
│   ├── index.php                   # Dashboard Administrator
│   ├── kategori/                   # CRUD Kategori Produk
│   │   ├── index.php
│   │   ├── tambah.php
│   │   ├── edit.php
│   │   └── hapus.php
│   ├── produk/                     # CRUD Produk & Upload Foto
│   │   ├── index.php
│   │   ├── tambah.php
│   │   ├── edit.php
│   │   ├── detail.php
│   │   └── hapus.php
│   ├── stok/                       # Kartu Stok & Penyesuaian Mutasi
│   │   ├── index.php
│   │   └── sesuaikan.php
│   ├── transaksi/                  # Monitoring Seluruh Transaksi Toko
│   │   ├── index.php
│   │   └── detail.php
│   ├── laporan/                    # Laporan Penjualan, Cetak, & Analisis
│   │   ├── index.php
│   │   ├── cetak.php
│   │   ├── export_excel.php
│   │   └── produk.php
│   └── user/                       # CRUD Pengguna & Reset Password
│       ├── index.php
│       ├── tambah.php
│       ├── edit.php
│       ├── reset_password.php
│       ├── toggle_status.php
│       └── hapus.php
├── kasir/
│   ├── index.php                   # Dashboard Kasir
│   ├── transaksi.php               # Terminal POS Kasir
│   ├── proses_transaksi.php        # Pemrosesan Transaksi & Auto-Potong Stok
│   ├── riwayat.php                 # Riwayat Transaksi Pribadi Kasir
│   └── struk.php                   # Format Struk Thermal & Kertas Biasa
├── includes/
│   ├── auth.php                    # Middleware Cek Login & Status Aktif
│   ├── admin_auth.php              # Middleware Pembatasan Akses Admin
│   ├── kasir_auth.php              # Middleware Akses Kasir/Admin
│   ├── header.php                  # Template Header HTML & CSS Bootstrap 5
│   ├── navbar.php                  # Template Navigasi Dinamis
│   └── footer.php                  # Template Footer & JS Bundle
├── assets/
│   └── css/
│       └── style.css               # Styling Tambahan & Media Print
├── uploads/
│   └── produk/                     # Folder Penyimpanan Gambar Produk
├── index.php                       # Pengarah Rute Awal
├── database.sql                    # Skema Database & Data Dummy Awal
└── README.md                       # Dokumentasi Proyek
```

---

## 🗄️ Skema Database

File `database.sql` mencakup seluruh struktur tabel berikut:

1. **`users`**: Menyimpan kredensial akun, role (`admin`, `kasir`), dan status (`aktif`, `nonaktif`).
2. **`kategori`**: Menyimpan klasifikasi kategori produk.
3. **`produk`**: Menyimpan data barang, kode unik, harga modal (beli), harga jual, stok fisik, batas minimum, dan foto.
4. **`stock_movements`**: Kartu audit mutasi stok barang (`masuk`, `keluar`, `penyesuaian`).
5. **`penjualans`**: Header transaksi penjualan (nomor transaksi, kasir, tanggal, subtotal, diskon, total, pembayaran, kembalian, metode pembayaran).
6. **`detail_penjualan`**: Rincian item barang yang dibeli per transaksi (produk, harga jual saat transaksi, kuantitas, subtotal).

---

## 🚀 Panduan Instalasi

### A. Instalasi di Laragon

1. Pastikan folder proyek berada di direktori web server Laragon:
   ```text
   C:\laragon\www\KASIR
   ```
2. Buka aplikasi **Laragon**, lalu klik tombol **Start All** (memulai Apache & MySQL).
3. Buka HeidiSQL (tombol **Database** pada Laragon) atau akses phpMyAdmin melalui browser di `http://localhost/phpmyadmin`.
4. Buat database baru dengan nama:
   ```sql
   CREATE DATABASE kasirku;
   ```
5. Pilih database `kasirku`, buka tab **Query** atau **Import**, lalu pilih file `database.sql` yang ada di dalam folder proyek.
6. Jalankan eksekusi impor data.
7. Buka browser dan akses alamat:
   ```text
   http://localhost/KASIR/
   ```

### B. Instalasi di XAMPP

1. Pindahkan folder proyek ke direktori `htdocs`:
   ```text
   C:\xampp\htdocs\KASIR
   ```
2. Buka **XAMPP Control Panel**, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. Buka browser dan kunjungi `http://localhost/phpmyadmin`.
4. Klik menu **New**, ketikkan nama database `kasirku`, lalu klik **Create**.
5. Pilih database `kasirku`, lalu klik tab **Import**.
6. Klik **Choose File** / **Pilih Berkas**, pilih file:
   ```text
   C:\xampp\htdocs\KASIR\database.sql
   ```
7. Klik tombol **Import** / **Go** di bagian bawah.
8. Buka browser dan kunjungi alamat:
   ```text
   http://localhost/KASIR/
   ```

---

## 🔑 Akun Pengguna Default

Database awal telah dilengkapi dengan 2 akun bawaan yang siap digunakan:

| Role | Username | Password | Keterangan |
|---|---|---|---|
| **Administrator** | `admin` | `admin123` | Akses penuh seluruh master data, stok, laporan, user, dan POS |
| **Kasir** | `kasir` | `kasir123` | Akses terminal kasir POS, riwayat transaksi, dan cetak struk |

> 🔒 *Seluruh kata sandi dienkripsi menggunakan fungsi bawaan PHP `password_hash()` dengan algoritma BCRYPT.*

---

## 🛒 Panduan Penggunaan Alur Kasir

1. **Login**: Masuk menggunakan akun kasir (`kasir` / `kasir123`).
2. **Membuka Kasir**: Klik tombol **Mulai Transaksi Baru (POS)**.
3. **Pencarian Barang**:
   - Ketikkan nama barang atau kode produk pada kolom pencarian realtime.
   - Atau klik salah satu tombol pill kategori (misal: "Sembako", "Minuman").
4. **Memilih Barang**: Klik kartu produk yang diinginkan &rarr; barang akan otomatis masuk ke keranjang belanja di sebelah kanan.
5. **Mengatur Jumlah**: Gunakan tombol `+` atau `-` untuk mengubah kuantitas beli.
6. **Input Diskon (Opsional)**: Masukkan nominal potongan pada kolom Diskon (Rp).
7. **Pilih Metode Pembayaran**: Pilih antara **Tunai**, **Transfer**, atau **QRIS**.
8. **Input Pembayaran**: Masukkan uang yang diterima dari pembeli atau klik tombol cepat seperti **Uang Pas** atau pecahan nominal.
9. **Simpan Transaksi**: Klik tombol hijau **BAYAR & CETAK STRUK**.
10. **Cetak Struk**: Halaman struk akan tampil otomatis dan siap dicetak menggunakan tombol **Cetak Struk**.

---

## 🛡️ Fitur Keamanan

1. **Prepared Statements**: Seluruh kueri database dieksekusi menggunakan PDO Prepared Statements dengan parameter binding (`:param`), menutup celah serangan **SQL Injection**.
2. **Password Hashing**: Tidak pernah menyimpan password dalam bentuk teks polos (*plain text*), melainkan menggunakan `password_hash(..., PASSWORD_DEFAULT)`.
3. **Proteksi Sesi & Fixation**: Menggunakan `session_regenerate_id(true)` saat login berhasil untuk mencegah serangan pembajakan sesi (*Session Fixation*).
4. **Role-Based Access Control (RBAC)**: Proteksi middleware berbasis role (`admin_auth.php` dan `kasir_auth.php`) mencegah kasir membuka URL modul administrasi.
5. **Cross-Site Request Forgery (CSRF)**: Token CSRF acak divalidasi pada setiap formulir POST.
6. **Pencegahan XSS**: Menggunakan helper `e()` yang membungkus `htmlspecialchars()` dengan opsi `ENT_QUOTES` untuk mengamankan output HTML.
7. **Validasi File Upload**: Memeriksa ekstensi gambar dan membatasi ukuran maksimal (2 MB) serta memberikan nama acak unik untuk mencegah eksekusi file berbahaya.
8. **Integritas Transaksi**: Menggunakan transaksi PDO (`beginTransaction`, `commit`, `rollBack`) dan baris kunci `SELECT ... FOR UPDATE` guna mencegah inkonsistensi data dan *race condition* stok barang.

---

## ❓ Troubleshooting & Solusi Error

| Gejala Error | Kemungkinan Penyebab | Langkah Solusi |
|---|---|---|
| **Koneksi Database Gagal! Unknown database 'kasirku'** | Database belum dibuat di MySQL/MariaDB | Buat database baru bernama `kasirku` melalui phpMyAdmin lalu impor file `database.sql`. |
| **SQLSTATE[HY000] [2002] Connection refused** | Layanan MySQL pada XAMPP/Laragon belum aktif | Buka Laragon atau XAMPP Control Panel, lalu klik **Start** pada layanan MySQL. |
| **Token keamanan tidak valid** | Sesi browser telah habis atau form terkirim ganda | Cukup lakukan *refresh* / muat ulang halaman web, lalu isi dan kirimkan kembali form. |
| **Format gambar tidak diizinkan / Gambar gagal diupload** | Ekstensi file bukan gambar standar atau ukuran > 2 MB | Gunakan gambar dengan format JPG, JPEG, PNG, atau WEBP di bawah 2 MB. Pastikan folder `uploads/produk/` memiliki izin tulis (*write permission*). |
| **Tampilan font icon / styling tidak muncul** | Tidak ada koneksi internet saat pertama kali memuat CDN | Hubungkan perangkat ke internet agar browser dapat mengunduh pustaka Bootstrap 5 dan Bootstrap Icons dari CDN. |

---

## 📄 Lisensi

Proyek ini dikembangkan untuk tujuan edukasi dan pembelajaran sistem informasi kasir berbasis web. Bebas digunakan, dipelajari, dan dimodifikasi untuk kebutuhan tugas kuliah, skripsi, maupun pengembangan usaha toko UMKM.
