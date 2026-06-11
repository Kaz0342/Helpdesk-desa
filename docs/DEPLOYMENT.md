# Panduan Deployment - Helpdesk Desa

Dokumen ini menjelaskan langkah-langkah untuk men-deploy sistem Helpdesk Desa ke server produksi (Shared Hosting).

## Prasyarat

- **Shared Hosting** dengan dukungan PHP 8.0 atau lebih baru dan ekstensi SQLite3/PDO.
- **Domain** yang sudah terhubung ke hosting (contoh: `helpdesk.desa-contoh.id`).
- **SSL/HTTPS** aktif (gunakan Let's Encrypt yang disediakan gratis oleh hosting).
- **Akun Fonnte** dengan token API yang valid.

## Langkah Deployment

### 1. Upload File ke Hosting

Upload seluruh isi folder project ke hosting melalui **File Manager** (cPanel) atau **FTP**:

```
/home/username/public_html/
    api/
        webhook.php
    config/
        database.php
    db/
        .htaccess
        install_db.php
        migrations/
    public/
        index.php
        login.php
        pengaduan.php
        pengaturan.php
        detail_pengaduan.php
        export_csv.php
        auth.php
        logout.php
        api/
        components/
    cek_db.php
    init_db.php
```

### 2. Konfigurasi Document Root

Jika hosting mendukung pengaturan Document Root, arahkan ke folder `public/` agar file di luar folder tersebut tidak dapat diakses langsung dari browser.

Jika tidak mendukung, pastikan file `.htaccess` di folder `db/` sudah aktif untuk memblokir akses langsung ke database.

### 3. Inisialisasi Database

Akses terminal hosting (SSH) atau gunakan fitur **Terminal** di cPanel, lalu jalankan:

```bash
cd /home/username/public_html
php db/install_db.php
```

Perintah ini akan membuat file `db/database.sqlite` beserta seluruh tabel dan akun admin default.

### 4. Konfigurasi Webhook Fonnte

1. Login ke [Fonnte Dashboard](https://md.fonnte.com).
2. Pada menu **Device**, klik device WhatsApp Anda.
3. Pada bagian **Webhook URL**, masukkan:
   ```
   https://domain-anda.com/api/webhook.php?token=DESA_HELPDESK_2026
   ```
4. Ganti `DESA_HELPDESK_2026` dengan secret token yang Anda tentukan di file `api/webhook.php` (variabel `$webhook_secret`).

### 5. Konfigurasi Token Fonnte

Buka file `api/webhook.php`, cari baris berikut dan ganti dengan token dari dashboard Fonnte:

```php
$fonnte_token = "TOKEN_FONNTE_ANDA";
```

### 6. Ubah Secret Webhook

Masih di file `api/webhook.php`, ganti secret token default menjadi nilai yang unik dan rahasia:

```php
$webhook_secret = "GANTI_DENGAN_TOKEN_UNIK_ANDA";
```

### 7. Konfigurasi SSL (Produksi)

Untuk server produksi, aktifkan verifikasi SSL di fungsi `kirimBalasanWA()` pada file `api/webhook.php`:

```php
// Ganti dari:
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_SSL_VERIFYHOST => false,

// Menjadi:
CURLOPT_SSL_VERIFYPEER => true,
CURLOPT_SSL_VERIFYHOST => 2,
```

### 8. Hapus File Instalasi

Setelah database berhasil diinisialisasi, hapus atau pindahkan file-file berikut agar tidak dapat diakses dari web:

- `db/install_db.php`
- `init_db.php`
- `cek_db.php`

## Verifikasi Deployment

1. **Dashboard:** Buka `https://domain-anda.com/public/login.php` dan login dengan akun admin.
2. **Webhook:** Kirim pesan `MENU` ke nomor WhatsApp yang terhubung dengan Fonnte. Bot harus membalas dengan menu utama.
3. **Pelaporan:** Lakukan satu siklus pelaporan lengkap (LAPOR -> Deskripsi -> Foto -> Lokasi) dan pastikan data muncul di dashboard.

## Keamanan Produksi

| Item | Status |
|------|--------|
| Password admin default sudah diganti | Wajib |
| Secret token webhook sudah diganti | Wajib |
| SSL/HTTPS aktif | Wajib |
| Verifikasi SSL pada cURL diaktifkan | Wajib |
| File instalasi dihapus dari server | Wajib |
| Folder `db/` dilindungi `.htaccess` | Sudah ada |

---
*Helpdesk Desa v1.0 - Dikembangkan oleh Benedict Saviola Pradana (2026)*
