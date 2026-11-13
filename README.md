# 🎯 ASFOUR 2025 — Aspiration of Four MMXXV

> **Sistem Website Aspirasi & Survei Berbasis Web**  
> Platform pengumpulan aspirasi dari sivitas sekolah, dilengkapi panel admin untuk manajemen data.

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Struktur Proyek](#-struktur-proyek)
- [Skema Database](#-skema-database)
- [Alur Aplikasi](#-alur-aplikasi)
- [Instalasi & Konfigurasi](#️-instalasi--konfigurasi)
- [Panduan Pengguna](#-panduan-pengguna)
- [Panduan Admin](#-panduan-admin)
- [Referensi API & Endpoint](#-referensi-api--endpoint)
- [Keamanan](#-keamanan)
- [Troubleshooting](#-troubleshooting)
- [Catatan Teknis](#-catatan-teknis)
- [Informasi Versi](#️-informasi-versi)
- [Changelog](#-changelog)

---

## 📖 Tentang Proyek

**ASFOUR 2025** (*Aspiration of Four MMXXV*) adalah website aspirasi berbasis web yang dirancang untuk menampung dan mengelola aspirasi dari seluruh elemen sekolah — mulai dari siswa, guru, hingga warga sekolah umum.

Aplikasi ini dibangun menggunakan PHP Native + MySQL (MariaDB) dan dijalankan di atas server XAMPP (lokal) maupun hosting berbasis cPanel. Tampilan publik menggunakan HTML/CSS murni dengan animasi modern, sedangkan panel admin menggunakan PHP yang terproteksi oleh sesi (session).

### 🎯 Tujuan
- Menyediakan wadah aspirasi digital yang mudah diakses oleh semua pihak
- Memudahkan panitia/admin dalam mengelola, memantau, dan mengekspor data aspirasi
- Memberikan pengalaman pengisian yang menyenangkan dengan UI animatif dan musik latar

---

## ✨ Fitur Utama

### Sisi Publik (User)
| Fitur | Keterangan |
|-------|-----------|
| 🎬 Landing Page Animatif | Halaman awal dengan logo dan tombol masuk |
| 🎵 Musik Latar Otomatis | Musik diputar saat pengguna menekan tombol masuk |
| ⏳ Loading Screen | Progress bar animasi sebelum form muncul |
| 📝 Form Aspirasi | Formulir pengisian aspirasi dengan validasi |
| 🏷️ Kategori Pengisi | Guru / Siswa / Warga Sekolah / Umum |
| 🎓 Angkatan Dinamis | Muncul otomatis saat kategori "Siswa" dipilih (A'23, A'24, A'25) |
| 🖼️ Upload Gambar | Lampiran file pendukung (opsional, format JPG/JPEG/PNG/GIF) |
| ✅ Halaman Terima Kasih | Konfirmasi setelah aspirasi berhasil dikirim |
| 🏠 Tombol Home | Kembali ke landing page kapan saja |

### Sisi Admin
| Fitur | Keterangan |
|-------|-----------|
| 🔐 Login Aman | Autentikasi berbasis sesi PHP + password hashing bcrypt |
| 📊 Dashboard Data | Tabel seluruh data aspirasi yang masuk |
| 👁️ Lihat Detail | Dropdown per baris: gambar, waktu, kategori, angkatan |
| 🗑️ Hapus Data | Hapus entri aspirasi dengan konfirmasi |
| 📤 Export Excel | Unduh seluruh data dalam format `.xls` |
| 🚪 Logout Aman | Menghapus sesi secara bersih |

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Backend | PHP | 8.2.12 |
| Database | MariaDB (via MySQL) | 10.4.32 |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) | — |
| Font | Google Fonts (Poppins, Roboto) | — |
| Server Lokal | XAMPP | — |
| Server Produksi | Hosting cPanel / Apache | — |
| DB Manager | phpMyAdmin | 5.2.1 |

---

## 🗂️ Struktur Proyek

```
ASFOUR-2025/
│
├── 📄 index.html               # Halaman utama publik (landing + form aspirasi)
├── 📄 terimakasih.html         # Halaman konfirmasi setelah submit aspirasi
│
├── 🔧 proses.php               # Backend: validasi, upload foto, simpan ke DB
├── 🔧 futaridakeno.php         # Halaman login admin + autentikasi
├── 🔧 dorimutaimu.php          # Dashboard admin: tampil & kelola data
├── 🔧 mirakuru.php             # Logout admin (destroy session)
├── 🔧 hapus_survei.php         # ⚠️ Ada di filesystem, namun TIDAK digunakan; hapus dilakukan via dorimutaimu.php?delete={id}
├── 🔧 export_excel.php         # Generate & download file Excel (.xls)
├── 🔧 aksi_pilih.php           # ⚠️ Ada di filesystem, namun TIDAK digunakan aktif; file cadangan/sisa pengembangan
│
├── 🎨 style.css                # Stylesheet admin dashboard
├── ⚙️ .htaccess                # Konfigurasi Apache (opsional)
├── ⚙️ php.ini                  # Override konfigurasi PHP
├── 🗄️ asfourmy_asfour.sql      # Dump database (struktur + data awal)
│
├── 📁 images/
│   ├── asfour.png              # Background utama halaman publik
│   ├── BANNER ASFOUR.png       # Banner di dalam form aspirasi
│   ├── Desain tanpa judul.png  # Logo di landing page
│   ├── icon asfour.png         # Favicon tab browser
│   ├── t.webp                  # Ikon tombol Home
│   ├── Tsumugi.jpeg            # Favicon tab browser (halaman admin)
│   └── WhatsApp Image ...jpg   # Aset gambar pendukung
│
├── 📁 music/
│   ├── backsound.mp3           # Musik latar cadangan
│   ├── all might.mp3           # Musik latar cadangan
│   ├── fft.MP3                 # Musik latar cadangan
│   ├── [Ochame Kinou] ...mp3   # Musik latar cadangan
│   ├── サカナクション 怪獣instrumental.mp3  # Musik latar cadangan
│   └── 【FULL】オーケストラ編曲 ...mp3     # ✅ Musik latar AKTIF (diputar saat masuk)
│
└── 📁 uploads/
    └── here is where to upload files  # Folder penyimpanan foto lampiran
```

---

## 🗄️ Skema Database

**Nama Database:** `asfourmy_asfour`

### Tabel `admin`
Menyimpan kredensial akun admin.

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | INT(11) AUTO_INCREMENT | Primary Key |
| `username` | VARCHAR(255) UNIQUE | Username login admin |
| `password` | VARCHAR(255) | Password ter-hash dengan bcrypt (`password_hash`) |

**Data default:**
```
username : admin
password : (lihat instruksi "Mengubah Password Admin" — wajib diganti segera setelah instalasi)
```

> [!CAUTION]
> Password admin awal disimpan dalam SQL dump sebagai **bcrypt hash**. Jangan pernah mengungkapkan nilai plaintext-nya di dokumentasi publik. Segera ganti password setelah import database selesai.

---

### Tabel `hasil_survei`
Menyimpan seluruh data aspirasi yang masuk dari publik.

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | INT(11) AUTO_INCREMENT | Primary Key |
| `makanan` | VARCHAR(255) | Kategori pengisi: Guru / Siswa / Warga Sekolah / Umum |
| `makan_perhari` | VARCHAR(255) | Angkatan (A'23, A'24, A'25) — opsional, hanya untuk Siswa |
| `pengalaman` | TEXT | Isi teks aspirasi |
| `foto` | VARCHAR(255) | Nama file gambar yang diupload (disimpan di `uploads/`) |
| `tanggal` | TIMESTAMP | Waktu submit (auto, timezone server) |
| `waktu` | DATETIME | Waktu submit (auto, timezone server) |
| `ip_address` | VARCHAR(45) | IP address pengisi |

> [!NOTE]
> Kolom `makanan` dan `makan_perhari` menggunakan nama kolom bawaan dari template database lama. Secara fungsional, `makanan` = **kategori pengisi** dan `makan_perhari` = **angkatan siswa**.

---

### Tabel `log_aktivitas`
Tabel log untuk mencatat aktivitas admin (tersedia di DB, belum diimplementasikan di kode).

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | INT(11) AUTO_INCREMENT | Primary Key |
| `username` | VARCHAR(100) | Username admin yang beraktivitas |
| `aktivitas` | TEXT | Deskripsi aktivitas |
| `waktu` | DATETIME | Waktu aktivitas |
| `waktu_login` | DATETIME | Waktu login sesi |

---

## 🔄 Alur Aplikasi

```
┌─────────────────────────────────────────────────────────────────┐
│                        ALUR PENGGUNA (PUBLIK)                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  index.html                                                      │
│  ┌──────────────┐    klik "Masuk"    ┌──────────────────────┐  │
│  │ Landing Page │ ──────────────────► │   Loading Screen     │  │
│  │  (logo +     │   + musik mulai    │   (progress bar)     │  │
│  │  tombol)     │                    └──────────┬───────────┘  │
│  └──────────────┘                               │ 2.1 detik    │
│                                                 ▼              │
│                                     ┌──────────────────────┐  │
│                                     │   Form Aspirasi      │  │
│                                     │  - Kategori (radio)  │  │
│                                     │  - Angkatan (cond.)  │  │
│                                     │  - Teks aspirasi     │  │
│                                     │  - Upload foto (opt) │  │
│                                     └──────────┬───────────┘  │
│                                                │ submit POST   │
│                                                ▼              │
│                                     ┌──────────────────────┐  │
│                                     │     proses.php       │  │
│                                     │  - Validasi file     │  │
│                                     │  - Upload ke uploads/│  │
│                                     │  - Simpan ke DB      │  │
│                                     │  - Catat IP address  │  │
│                                     └──────────┬───────────┘  │
│                                                │ redirect      │
│                                                ▼              │
│                                     ┌──────────────────────┐  │
│                                     │  terimakasih.html    │  │
│                                     │  (konfirmasi sukses) │  │
│                                     └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                        ALUR ADMIN                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  futaridakeno.php          dorimutaimu.php                      │
│  ┌──────────────┐  login   ┌──────────────────────────────┐    │
│  │  Form Login  │ ────────► │       Dashboard Admin        │    │
│  │  (user+pass) │          │                              │    │
│  └──────────────┘          │  ┌──────────────────────┐   │    │
│                            │  │  Tabel hasil_survei  │   │    │
│                            │  │  - ID | Aspirasi     │   │    │
│                            │  │  - Opsi | Hapus      │   │    │
│                            │  └──────────┬───────────┘   │    │
│                            │             │               │    │
│                            │   ┌─────────┴──────────┐   │    │
│                            │   │  Dropdown "Opsi":  │   │    │
│                            │   │  • Lihat Gambar    │   │    │
│                            │   │  • Lihat Waktu     │   │    │
│                            │   │  • Lihat Yang Isi  │   │    │
│                            │   │  • Lihat Angkatan  │   │    │
│                            │   └────────────────────┘   │    │
│                            │                            │    │
│                            │  [Export ke Excel] ──► export_excel.php │
│                            │  [Logout] ──────────► mirakuru.php      │
│                            └──────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ Instalasi & Konfigurasi

### Prasyarat
- **XAMPP** (PHP 8.x + Apache + MariaDB) atau hosting PHP
- **phpMyAdmin** untuk import database
- Browser modern (Chrome, Firefox, Edge)

---

### 🖥️ Instalasi Lokal (XAMPP)

**1. Letakkan file proyek**
```
Salin folder ASFOUR-2025 ke:
C:\xampp\htdocs\ASFOUR\ASFOUR-2025\
```

**2. Jalankan XAMPP**
- Aktifkan modul **Apache** dan **MySQL** dari XAMPP Control Panel.

**3. Import database**
- Buka browser → akses `http://localhost/phpmyadmin`
- Klik **"New"** di sidebar kiri → buat database baru dengan nama `asfourmy_asfour`
- Pilih database tersebut → klik tab **"Import"**
- Pilih file `asfourmy_asfour.sql` → klik **"Go"**

**4. Konfigurasi koneksi database**

Koneksi database dikonfigurasi langsung di masing-masing file PHP. Pastikan parameter berikut sesuai dengan environment lokal Anda:

```php
// Lokasi: proses.php, futaridakeno.php, dorimutaimu.php, export_excel.php
$conn = new mysqli("localhost", "root", "", "asfourmy_asfour");
//                   ↑ host      ↑ user  ↑ pass  ↑ nama database
```

> [!IMPORTANT]
> Di XAMPP default, username adalah `root` dan password **kosong**. Jika Anda menggunakan MySQL mandiri atau hosting, sesuaikan `user` dan `password`-nya.

**5. Akses aplikasi**
```
Halaman publik : http://localhost/ASFOUR/ASFOUR-2025/index.html
Panel admin    : http://localhost/ASFOUR/ASFOUR-2025/futaridakeno.php
```

---

### 🌐 Instalasi di Hosting (cPanel)

**1. Upload file**
- Buka **cPanel → File Manager**
- Navigasi ke `public_html/`
- Upload seluruh isi folder `ASFOUR-2025/` ke dalam `public_html/` (atau subdirektori pilihan Anda)

**2. Import database**
- Buka **cPanel → phpMyAdmin**
- Buat database baru melalui **cPanel → MySQL Databases**
- Buat user database dan berikan akses penuh ke database tersebut
- Di phpMyAdmin, pilih database → **Import** → pilih `asfourmy_asfour.sql` → **Go**

**3. Update konfigurasi koneksi**

Edit keempat file PHP berikut dan sesuaikan parameter koneksi:

| File | Baris yang diedit |
|------|-------------------|
| `proses.php` | Baris 3 |
| `futaridakeno.php` | Baris 3 |
| `dorimutaimu.php` | Baris 8 |
| `export_excel.php` | Baris 2 |

```php
// Contoh untuk hosting (ganti dengan data dari cPanel Anda):
$conn = new mysqli("localhost", "cpanelusername_dbuser", "password_db", "cpanelusername_asfour");
```

**4. Set permission folder uploads**
```bash
chmod 0777 uploads/
```
Atau melalui cPanel File Manager → klik kanan folder `uploads/` → **Change Permissions** → centang semua kolom **Write**.

---

## 👥 Panduan Pengguna

### Langkah Pengisian Aspirasi

```
1. Buka URL situs di browser
2. Di halaman landing, klik tombol [𝗠𝗮𝘀𝘂𝗸]
   → Musik latar mulai diputar
   → Progress bar loading muncul (~2 detik)
3. Formulir aspirasi tampil:

   a. Pilih kategori Anda:
      ○ Guru
      ○ Warga Sekolah
      ○ Siswa  ← jika dipilih, muncul pilihan Angkatan
      ○ Umum

   b. (Khusus Siswa) Pilih angkatan:
      ○ A'23  ○ A'24  ○ A'25

   c. Tulis aspirasi Anda di kolom teks (wajib diisi)

   d. Lampirkan gambar pendukung (opsional)
      Format yang diterima: JPG, JPEG, PNG, GIF

4. Klik tombol [𝗞𝗶𝗿𝗶𝗺]
5. Anda akan diarahkan ke halaman konfirmasi
```

> [!TIP]
> Gunakan tombol 🏠 di pojok kanan atas untuk kembali ke halaman utama kapan saja tanpa kehilangan data sesi.

---

## 🔐 Panduan Admin

### Login Admin

Akses panel admin melalui:
```
http://[domain-anda]/futaridakeno.php
```

> [!CAUTION]
> **Ganti password admin segera setelah instalasi!** Password default tersimpan di SQL dump. Lihat bagian [Mengubah Password Admin](#mengubah-password-admin) untuk langkah-langkahnya.

---

### Dashboard Admin (`dorimutaimu.php`)

Setelah login berhasil, Anda akan diarahkan ke dashboard yang menampilkan:

| Kolom | Keterangan |
|-------|-----------|
| **ID** | Nomor urut unik setiap aspirasi |
| **Aspirasi** | Isi teks aspirasi yang dikirim |
| **Opsi** | Dropdown untuk melihat detail tambahan |
| **Hapus** | Tombol hapus entri (dengan konfirmasi) |

**Dropdown "Opsi" per baris:**
- **Lihat Gambar** → Menampilkan thumbnail foto lampiran (jika ada)
- **Lihat Waktu** → Menampilkan waktu pengiriman aspirasi
- **Lihat Yang Isi** → Menampilkan kategori pengisi (Guru/Siswa/dll)
- **Lihat Angkatan** → Menampilkan angkatan (khusus Siswa)

---

### Export Data ke Excel

Klik tombol **"Export ke Excel"** di dashboard → browser akan mengunduh file `rekap_asfour.xls`.

Format kolom Excel yang dihasilkan:

| No | Yang Ngisi | Angkatan | Aspirasi | Link Gambar | Waktu |
|----|-----------|----------|---------|------------|-------|
| 1  | Siswa      | A'25     | ...     | http://... | 2025-06-25 |

---

### Mengubah Password Admin

Karena password disimpan dalam format **bcrypt hash**, Anda tidak bisa langsung mengubahnya di phpMyAdmin dengan teks biasa. Gunakan salah satu cara berikut:

**Cara 1: Melalui skrip PHP sementara**

Buat file baru `ganti_pass.php` di folder proyek:
```php
<?php
$password_baru = "password_rahasia_anda";
echo password_hash($password_baru, PASSWORD_DEFAULT);
?>
```
Akses file tersebut di browser → salin hash yang dihasilkan → tempel ke kolom `password` di tabel `admin` via phpMyAdmin → **hapus file tersebut segera**.

**Cara 2: Langsung via phpMyAdmin dengan fungsi SQL**
```sql
UPDATE admin 
SET password = '$2y$10$HASH_BCRYPT_BARU_DI_SINI' 
WHERE username = 'admin';
```

> [!WARNING]
> Jangan pernah menyimpan password dalam format teks biasa di database. Selalu gunakan `password_hash()`.

---

### Logout Admin

Klik tombol **"Logout"** di pojok kanan atas dashboard, atau akses langsung:
```
http://[domain-anda]/mirakuru.php
```
Sesi admin akan dihapus dan Anda diarahkan kembali ke halaman login.

---

## 🔗 Referensi API & Endpoint

| Endpoint | Method | Deskripsi | Akses |
|----------|--------|-----------|-------|
| `index.html` | GET | Halaman publik (landing + form aspirasi) | Publik |
| `proses.php` | POST | Memproses & menyimpan data aspirasi | Publik |
| `terimakasih.html` | GET | Halaman konfirmasi setelah submit | Publik |
| `futaridakeno.php` | GET/POST | Halaman & proses login admin | Admin |
| `dorimutaimu.php` | GET | Dashboard admin (lihat data, hapus) | Admin (session) |
| `dorimutaimu.php?delete={id}` | GET | Hapus entri aspirasi berdasarkan ID | Admin (session) |
| `export_excel.php` | GET | Generate & download file Excel | Admin (session)* |
| `mirakuru.php` | GET | Logout admin | Admin |

> [!NOTE]
> `export_excel.php` saat ini tidak memiliki pengecekan sesi — siapapun yang mengetahui URL-nya dapat mengunduh data. Disarankan untuk menambahkan pengecekan sesi.

---

### Parameter `proses.php` (POST)

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|-----------|
| `makanan` | string (radio) | ✅ Ya | Kategori pengisi: `Guru`, `Siswa`, `Warga Sekolah`, `Umum` |
| `makan_perhari` | string (radio) | Jika Siswa | Angkatan: `A'23`, `A'24`, `A'25` |
| `pengalaman` | string (textarea) | ✅ Ya | Isi teks aspirasi |
| `foto` | file (image) | ❌ Tidak | File gambar (JPG/JPEG/PNG/GIF) |

---

## 🔒 Keamanan

### Yang Sudah Diterapkan ✅
- **bcrypt password hashing** — Password admin tidak disimpan plaintext.
- **Prepared Statements Menyeluruh** — `proses.php`, `futaridakeno.php`, `hapus_survei.php`, dan `dorimutaimu.php` (proses hapus) menggunakan `mysqli::prepare()` untuk mencegah SQL Injection.
- **Session-based Auth & Proteksi Sesi Lengkap** — Halaman admin (`dorimutaimu.php`, `hapus_survei.php`, dan `export_excel.php`) dilindungi pengecekan `$_SESSION['admin']`.
- **Session Fixation Prevention** — Pemanggilan `session_regenerate_id(true)` saat login sukses di `futaridakeno.php`.
- **Anti-Brute Force (Rate Limiting)** — Login admin dibatasi maksimal 5 kali gagal per IP/sesi; jika terlampaui, akses terkunci selama 10 menit.
- **Pesan Kesalahan Generik** — Form login tidak membocorkan apakah username atau password yang salah guna mencegah user enumeration.
- **Proteksi CSRF (Cross-Site Request Forgery)** — Token kriptografis acak (`random_bytes(32)`) diterapkan pada pengiriman aspirasi publik (`proses.php`) dan tombol hapus data admin (`dorimutaimu.php` & `hapus_survei.php`).
- **Aksi Hapus via POST** — Mengubah aksi hapus data dari link GET menjadi form POST terproteksi CSRF untuk mencegah penghapusan data yang tidak disengaja/eksternal.
- **Validasi File Tingkat Lanjut (finfo MIME + Ekstensi + Ukuran)**:
  - Whitelist ekstensi ketat: `jpg`, `jpeg`, `png`, `gif`.
  - Deteksi MIME type riil berbasis konten menggunakan `finfo` (`image/jpeg`, `image/png`, `image/gif`).
  - Verifikasi struktur gambar menggunakan `getimagesize()`.
  - Pembatasan ukuran berkas di sisi server (maksimum 5 MB).
  - Nama berkas acak via `uniqid("img_", true)` untuk mencegah path traversal dan penimpaan file.
- **Anti-Eksekusi Skrip di `uploads/`** — Aturan Apache pada `.htaccess` root memblokir akses dan eksekusi seluruh skrip (`.php`, `.phtml`, `.phar`, `.cgi`, dll.) di dalam folder `uploads/`.
- **Header Keamanan HTTP** — Menambahkan header `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, dan `Referrer-Policy`.
- **Nonaktifkan Directory Listing** — `Options -Indexes` mencegah pengunjung melihat daftar berkas direktori.
- **Perlindungan File Sensitif** — `.htaccess` memblokir akses langsung ke file database `.sql`, `.htaccess`, dan `php.ini`.
- **Output Encoding** — `htmlspecialchars()` pada tampilan data untuk mencegah XSS.

### Rekomendasi Tambahan ⚠️
- [ ] Pasang sertifikat SSL/HTTPS aktif pada domain/hosting produksi.
- [ ] Gunakan Web Application Firewall (WAF) seperti Cloudflare jika diakses publik luas.

---

## 🧪 Troubleshooting

### ❌ Tidak bisa konek ke database
```
Koneksi gagal: Access denied for user 'root'@'localhost'
```
**Solusi:** Periksa username/password di file PHP. Di XAMPP default, password root adalah kosong `""`.

---

### ❌ Error 500 Internal Server Error
**Kemungkinan penyebab & solusi:**
1. **`.htaccess` bermasalah** → Kosongkan atau hapus isi `.htaccess`
2. **`php.ini` tidak kompatibel** → Hapus atau sesuaikan `php.ini` lokal
3. **Versi PHP tidak kompatibel** → Pastikan PHP ≥ 7.4

---

### ❌ Foto tidak bisa diupload
```
Gagal mengupload file.
```
**Solusi:**
1. Pastikan folder `uploads/` ada dan dapat ditulis:
   ```bash
   chmod 0777 uploads/
   ```
2. Periksa `upload_max_filesize` dan `post_max_size` di `php.ini`
3. Pastikan ekstensi file adalah JPG, JPEG, PNG, atau GIF

---

### ❌ Musik tidak diputar
**Solusi:**
- Browser modern memblokir autoplay audio. Musik baru dimulai **setelah pengguna mengklik tombol Masuk** — ini sudah sesuai dengan kebijakan autoplay browser.
- Pastikan file MP3 yang aktif ada di folder `music/`:
  ```
  music/【FULL】オーケストラ編曲 アニメ【チ。...mp3
  ```

---

### ❌ Login admin gagal
```
❌ Username tidak ditemukan!
❌ Password salah!
```
**Solusi:**
1. Pastikan tabel `admin` sudah ada (import SQL berhasil)
2. Gunakan username `admin` dengan password yang tercatat saat instalasi (lihat SQL dump atau hubungi administrator sistem)
3. Jika lupa password, gunakan cara reset di bagian [Mengubah Password Admin](#mengubah-password-admin)

---

### ❌ Halaman admin bisa diakses tanpa login
Pastikan kode berikut ada di awal `dorimutaimu.php`:
```php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: futaridakeno.php");
    exit;
}
```

---

## 📝 Catatan Teknis

### Konfigurasi PHP (`php.ini` lokal)
File `php.ini` di root proyek dapat digunakan untuk meng-override pengaturan PHP default, misalnya:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
```

### Font yang Digunakan
- **Poppins** (Google Fonts) — Digunakan di halaman publik (`index.html`)
- **Roboto** (Google Fonts) — Digunakan di halaman admin (`futaridakeno.php`, `dorimutaimu.php`)

### Animasi CSS (index.html)
| Animasi | Elemen | Durasi |
|---------|--------|--------|
| `fadeIn` | Landing page, thanks page | 1s / 0.8s |
| `scaleUp` | Form survei | 0.6s |
| `fillProgress` | Progress bar loading | 2s |
| `pulse` | Tombol home | 1.5s |

### Responsivitas
- Breakpoint **600px** — Form publik menyesuaikan padding dan ukuran font
- Breakpoint **768px** — Header admin berubah ke layout vertikal
- Breakpoint **480px** — Tabel dan tombol admin mengecil

### Timezone
Aplikasi menggunakan timezone **Asia/Jakarta (WIB, UTC+7)**. Diset di `dorimutaimu.php`:
```php
date_default_timezone_set('Asia/Jakarta');
```

---

## 🧪 Tabel Uji Coba Sistem

| # | Aksi | File/Endpoint | Input | Output yang Diharapkan |
|---|------|---------------|-------|----------------------|
| 1 | Buka halaman publik | `index.html` | — | Landing page dengan logo & tombol |
| 2 | Klik tombol Masuk | `index.html` | — | Musik diputar, loading 2 detik, form muncul |
| 3 | Pilih "Siswa" | `index.html` | Radio: Siswa | Dropdown angkatan muncul dengan animasi |
| 4 | Submit form aspirasi | `proses.php` | POST data valid | Redirect ke `terimakasih.html` |
| 5 | Submit tanpa aspirasi | `proses.php` | POST kosong | Form validation — tidak terkirim |
| 6 | Upload file non-gambar | `proses.php` | POST + file .pdf | "Format file tidak didukung." |
| 7 | Login admin benar | `futaridakeno.php` | user: admin, pass: (sesuai instalasi) | Redirect ke `dorimutaimu.php` |
| 8 | Login admin salah | `futaridakeno.php` | user: salah | "❌ Username tidak ditemukan!" |
| 9 | Lihat data dashboard | `dorimutaimu.php` | — | Tabel data semua aspirasi |
| 10 | Gunakan dropdown Opsi | `dorimutaimu.php` | Pilih "Lihat Gambar" | Thumbnail foto tampil |
| 11 | Hapus data | `dorimutaimu.php?delete=1` | Konfirmasi OK | Data terhapus, reload dashboard |
| 12 | Export Excel | `export_excel.php` | — | File `rekap_asfour.xls` terunduh |
| 13 | Logout admin | `mirakuru.php` | — | Sesi dihapus, redirect ke login |
| 14 | Akses dashboard tanpa login | `dorimutaimu.php` | — | Redirect ke `futaridakeno.php` |

---

## 📁 Deskripsi Lengkap Setiap File

### `index.html`
Halaman utama publik. Mengandung empat "state" dalam satu file:
1. **Landing Page** — Logo + tombol masuk
2. **Loading Screen** — Progress bar animasi 2 detik
3. **Form Aspirasi** — Form lengkap dengan validasi HTML5
4. **Halaman Terima Kasih** — Pesan konfirmasi (alternatif dari `terimakasih.html`)

Dikontrol sepenuhnya oleh JavaScript vanilla tanpa library tambahan.

---

### `proses.php`
Backend handler form aspirasi. Alur kerja:
```
1. Terima POST data
2. Validasi dan upload foto (jika ada)
   - Cek ekstensi: hanya jpg/jpeg/png/gif
   - Generate nama unik dengan uniqid()
   - Simpan ke uploads/
3. Simpan ke tabel hasil_survei via prepared statement
4. Catat IP address pengisi
5. Redirect ke terimakasih.html
```

---

### `futaridakeno.php`
Halaman login admin. Alur:
```
1. Tampilkan form login
2. Jika POST:
   - Query user dari tabel admin (prepared statement)
   - Verifikasi password dengan password_verify()
   - Jika valid: set $_SESSION['admin'] → redirect dashboard
   - Jika tidak: tampilkan pesan error
```

---

### `dorimutaimu.php`
Dashboard admin utama. Fitur:
```
1. Cek sesi admin (redirect ke login jika tidak ada)
2. Tampilkan semua data hasil_survei dalam tabel
3. Dropdown per baris untuk melihat detail (JS: toggleContent)
4. Link hapus data via GET parameter ?delete={id}
5. Link Export ke Excel
6. Link Logout
```

---

### `hapus_survei.php`
> [!NOTE]
> File ini **ada di filesystem** tetapi **tidak digunakan** dalam alur aktif. Fungsi hapus data dilakukan langsung di `dorimutaimu.php` melalui query `DELETE` yang dipanggil via GET parameter `?delete={id}`. File ini adalah sisa pengembangan dan saat ini tidak dipanggil oleh file manapun.

---

### `aksi_pilih.php`
> [!NOTE]
> File ini **ada di filesystem** tetapi **tidak digunakan** dalam alur aktif. Merupakan file cadangan dari sesi pengembangan sebelumnya. Tidak dipanggil oleh file manapun di versi ini.

---

### `mirakuru.php`
Logout handler. Fungsi:
```php
session_start();
session_destroy();
header("Location: futaridakeno.php");
```

---

### `export_excel.php`
Export data ke format Excel. Menggunakan pendekatan "fake XLS" (tabel HTML dengan header MIME `application/vnd.ms-excel`). Kolom: No, Yang Ngisi, Angkatan, Aspirasi, Link Gambar, Waktu.

---

### `style.css`
Stylesheet untuk panel admin (`dorimutaimu.php`). Berisi styling untuk: header, tabel data, tombol logout, tombol export, tombol hapus, dropdown content, dan responsive design (768px & 480px).

---

## 🗓️ Informasi Versi

| Item | Detail |
|------|--------|
| Nama Proyek | ASFOUR 2025 — Aspiration of Four MMXXV |
| **Versi** | **v1.0.1** |
| Tanggal Rilis | 14 November 2026 |
| Tanggal DB Dibuat | 25 Juni 2025 |
| PHP Minimum | 7.4 (direkomendasikan 8.x) |
| MariaDB | 10.4+ |
| Lisensi | Privat — © 2025 ASFOUR. All Rights Reserved. |

---

## 📜 Changelog

Format mengacu pada [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Proyek ini menggunakan [Semantic Versioning](https://semver.org/lang/id/).

---

### [v1.0.1] — 2026-11-14 *(Security Hardening Release)*

#### Security
- **Perlindungan SQL Injection**: Mengubah proses hapus data di `dorimutaimu.php` dan `hapus_survei.php` menggunakan Prepared Statements dengan validasi integer ketat (`filter_var`).
- **Pencegahan CSRF**: Mengubah aksi hapus data dari tautan GET menjadi form POST terproteksi token CSRF kriptografis; menyisipkan token CSRF pada form pengisian aspirasi di `index.html` dan memvalidasinya di `proses.php`.
- **Proteksi Akses Ekspor**: Menambahkan verifikasi sesi admin pada `export_excel.php` (mencegah unduhan data tanpa otentikasi).
- **Anti-Brute Force Login**: Membatasi kesalahan login di `futaridakeno.php` maksimal 5 kali dengan penguncian akun selama 10 menit.
- **Pencegahan User Enumeration**: Menyeragamkan pesan error login di `futaridakeno.php` agar tidak membedakan keberadaan username.
- **Pencegahan Session Fixation**: Menambahkan `session_regenerate_id(true)` sesaat setelah autentikasi admin berhasil.
- **Validasi Berkas Diperketat**: Memeriksa MIME type asli berbasis konten menggunakan PHP `finfo` (`FILEINFO_MIME_TYPE`), membatasi ukuran berkas maksimal 5 MB, dan memeriksa integritas gambar via `getimagesize()`.
- **Whitelist Input Form**: Membatasi nilai kategori pengisi dan angkatan hanya pada pilihan yang sah.
- **Pengerasan Server Apache (`.htaccess`)**:
  - Mematikan directory indexing (`Options -Indexes`).
  - Memblokir akses dan eksekusi skrip (`.php`, `.phtml`, `.phar`, `.cgi`, dll.) di direktori `uploads/`.
  - Melindungi berkas sensitif (`.sql`, `.htaccess`, `php.ini`, log).
  - Menambahkan security headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, dan `Referrer-Policy`.

---

### [v1.0.0] — 2026-11-14 *(Initial Release)*

#### Added
- **Landing Page** animatif dengan logo, tombol masuk, dan musik latar otomatis
- **Loading Screen** progress bar 2 detik sebelum form tampil
- **Form Aspirasi** publik dengan kategori (Guru, Siswa, Warga Sekolah, Umum) dan tombol kirim
- **Field Angkatan** (A'23, A'24, A'25) yang muncul secara dinamis saat kategori "Siswa" dipilih
- **Upload gambar lampiran** opsional (JPG/JPEG/PNG/GIF) dengan nama file unik via `uniqid()`
- **Halaman Terima Kasih** (`terimakasih.html`) sebagai konfirmasi submit berhasil
- **Tombol Home** di pojok kanan atas untuk kembali ke landing page
- **Backend `proses.php`**: validasi ekstensi file, upload ke `uploads/`, simpan ke DB via prepared statement, catat IP address
- **Login Admin** (`futaridakeno.php`) dengan autentikasi bcrypt (`password_verify`)
- **Dashboard Admin** (`dorimutaimu.php`) — tabel data aspirasi dengan dropdown opsi per baris (gambar, waktu, kategori, angkatan) dan proteksi sesi
- **Hapus Data** via GET parameter `?delete={id}` langsung dari dashboard
- **Export ke Excel** (`export_excel.php`) — download file `rekap_asfour.xls` berformat tabel HTML
- **Logout Admin** (`mirakuru.php`) — `session_destroy()` + redirect ke login
- **Stylesheet Admin** (`style.css`) — header, tabel, tombol, dropdown, responsive 768px & 480px
- **Database** `asfourmy_asfour` dengan 3 tabel: `admin`, `hasil_survei`, `log_aktivitas`
- **Musik latar** dari folder `music/` (file aktif: orkestra Sakanaction "Kaibutsu")

#### Notes
- `hapus_survei.php` dan `aksi_pilih.php` ada di filesystem namun tidak digunakan dalam alur aktif v1.0.0
- `export_excel.php` belum memiliki pengecekan sesi (terdaftar sebagai item keamanan yang perlu ditangani)
- Tabel `log_aktivitas` sudah ada di skema DB namun belum diimplementasikan di kode PHP

---

*README ini dibuat secara menyeluruh berdasarkan analisis seluruh kode sumber proyek ASFOUR 2025 — v1.0.1.*
