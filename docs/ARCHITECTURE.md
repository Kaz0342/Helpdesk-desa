# Arsitektur Sistem - Helpdesk Desa

Dokumen ini menjelaskan arsitektur teknis dan alur kerja sistem Helpdesk Pelayanan Publik Desa.

## Gambaran Umum

Sistem ini terdiri dari dua komponen utama:

1. **Chatbot WhatsApp** - Antarmuka pelaporan untuk warga desa melalui WhatsApp.
2. **Dashboard Admin** - Panel web untuk petugas desa mengelola dan memantau laporan.

Kedua komponen terhubung melalui database SQLite yang berfungsi sebagai satu-satunya sumber data (Single Source of Truth).

## Diagram Arsitektur

```
Warga Desa                  Fonnte API               Server Hosting
+-----------+              +-----------+              +-----------+
|           |  Pesan WA    |           |  HTTP POST   |           |
| WhatsApp  | -----------> |  Fonnte   | -----------> | webhook   |
|           |              |  Gateway  |              |   .php    |
|           | <----------- |           | <----------- |           |
|           |  Balasan WA  |           |  cURL POST   |           |
+-----------+              +-----------+              +-----+-----+
                                                            |
                                                            | PDO
                                                            v
Admin Desa                                            +-----------+
+-----------+              +-----------+              |           |
|           |  HTTP GET    |           |  PDO Query   |  SQLite   |
| Browser   | -----------> | Dashboard | -----------> | database  |
|           | <----------- |  (PHP)    | <----------- |  .sqlite  |
|           |  HTML/CSS/JS |           |  Result Set  |           |
+-----------+              +-----------+              +-----------+
```

## Alur Pelaporan (State Machine)

Chatbot menggunakan pola State Machine untuk mengelola alur percakapan multi-langkah. Setiap nomor WhatsApp memiliki satu sesi aktif yang melacak posisi pengguna dalam alur.

```
                   +---------------+
         Pesan     |               |  Ketik "1"
  +--------------> |  menu_utama   | ----------+
  |    pertama     |               |           |
  |                +---------------+           |
  |                                            v
  |                                  +-----------------+
  |                                  |                 |
  |                                  | lapor_deskripsi |
  |                                  |                 |
  |                                  +--------+--------+
  |                                           |
  |                              Deskripsi    | (min 10 karakter)
  |                                           v
  |                                  +-----------------+
  |                                  |                 |
  |                                  |   lapor_foto    |
  |                                  |                 |
  |                                  +--------+--------+
  |                                           |
  |                            Foto/LEWATI    |
  |                                           v
  |                                  +-----------------+
  |                                  |                 |
  |                                  |  lapor_lokasi   |
  |                                  |                 |
  |                                  +--------+--------+
  |                                           |
  |                      Shareloc/LEWATI      |
  |                                           v
  |                                  +-----------------+
  |                                  |                 |
  +----------------------------------+  Simpan ke DB   |
             Sesi dihapus            |                 |
                                     +-----------------+
```

Perintah `BATAL` dapat diketik kapan saja untuk menghapus sesi dan kembali ke awal.

## Struktur Database

### Tabel: sesi_chat
Menyimpan status percakapan aktif setiap pengguna WhatsApp.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| nomor_wa | TEXT (PK) | Nomor WhatsApp pengguna (format 628xxx) |
| state | TEXT | Posisi dalam alur chatbot |
| data_temp | TEXT (JSON) | Data sementara selama proses pelaporan |
| last_activity | DATETIME | Timestamp aktivitas terakhir |

### Tabel: pengaduan
Menyimpan data laporan yang telah selesai dikumpulkan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INTEGER (PK) | Auto-increment |
| nomor_wa | TEXT | Nomor WhatsApp pelapor |
| nama | TEXT | Nama WhatsApp pelapor (pushname) |
| deskripsi | TEXT | Isi lengkap pengaduan |
| foto_path | TEXT | URL foto atau penanda "foto_dikirim_via_wa" |
| lokasi_lat | REAL | Koordinat latitude lokasi kejadian |
| lokasi_long | REAL | Koordinat longitude lokasi kejadian |
| status | TEXT | Menunggu / Diproses / Selesai / Ditolak |
| created_at | DATETIME | Timestamp pembuatan laporan |

### Tabel: admin
Menyimpan kredensial admin dashboard.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INTEGER (PK) | Auto-increment |
| username | TEXT (UNIQUE) | Username untuk login |
| password_hash | TEXT | Hash bcrypt dari password |
| created_at | DATETIME | Timestamp pembuatan akun |

### Tabel: pengaturan
Menyimpan konfigurasi sistem dalam format key-value.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| kunci | TEXT (PK) | Nama konfigurasi (contoh: "auto_reply") |
| nilai | TEXT | Nilai konfigurasi (contoh: "1" atau "0") |

## Struktur Direktori

```
Helpdesk_desa/
|-- api/
|   +-- webhook.php            # Endpoint penerima pesan WhatsApp (Fonnte)
|-- config/
|   +-- database.php           # Koneksi PDO ke SQLite (Singleton)
|-- db/
|   |-- .htaccess              # Blokir akses langsung ke database
|   |-- database.sqlite        # File database (tidak di-commit ke Git)
|   |-- install_db.php         # Script instalasi tabel dan seeding
|   +-- migrations/            # Script migrasi database
|-- docs/
|   |-- PANDUAN_ADMIN.md       # Panduan operasional untuk admin desa
|   |-- PANDUAN_WARGA.md       # Panduan pelaporan untuk warga
|   |-- DEPLOYMENT.md          # Panduan deployment ke hosting
|   +-- ARCHITECTURE.md        # Dokumen arsitektur (file ini)
|-- public/
|   |-- api/                   # API internal dashboard
|   |-- components/            # Komponen PHP (header, footer)
|   |-- index.php              # Halaman dashboard utama
|   |-- login.php              # Halaman login admin
|   |-- logout.php             # Proses logout
|   |-- auth.php               # Middleware autentikasi
|   |-- pengaduan.php          # Halaman data pengaduan (tabel + filter)
|   |-- detail_pengaduan.php   # Halaman detail laporan + peta
|   |-- pengaturan.php         # Halaman pengaturan sistem
|   +-- export_csv.php         # Ekspor data ke CSV
|-- CHANGELOG.md               # Riwayat perubahan versi
|-- README.md                  # Dokumentasi utama project
+-- .gitignore                 # Daftar file yang diabaikan Git
```

## Keamanan

| Lapisan | Implementasi |
|---------|-------------|
| Webhook Authentication | Secret Token via query parameter (`?token=xxx`) |
| SQL Injection | PDO Prepared Statements di seluruh query |
| XSS | `htmlspecialchars()` pada seluruh output ke HTML |
| Password Storage | Hash bcrypt via `password_hash()` |
| Session Management | PHP native session dengan validasi |
| Input Validation | `filter_var()` untuk URL, `is_numeric()` dan range check untuk koordinat, minimal karakter untuk deskripsi |
| Database Protection | `.htaccess` memblokir akses HTTP langsung ke file `.sqlite` |

---
*Helpdesk Desa v1.0 - Dikembangkan oleh Benedict Saviola Pradana (2026)*
