# Helpdesk Desa - Sistem Pengaduan Publik Berbasis WhatsApp

Sistem **Helpdesk Pelayanan Publik Desa** adalah aplikasi web ringan berbasis **PHP Native** dan **SQLite**, terintegrasi langsung dengan Chatbot WhatsApp (via Fonnte API). Dirancang khusus agar warga desa dapat melapor masalah infrastruktur, lingkungan, atau layanan desa hanya dengan mengirim chat WhatsApp.

## Fitur Utama
*   **Chatbot WhatsApp (State Machine):** Alur pelaporan terstruktur dari Menu -> Deskripsi -> Foto Bukti -> Share Location.
*   **Integrasi Shareloc:** Menangkap koordinat GPS dari fitur *Share Location* WhatsApp secara presisi.
*   **Dukungan Foto Bukti:** Menerima lampiran foto dari warga sebagai bukti otentik pengaduan.
*   **Dashboard Admin:** Panel admin modern dengan statistik real-time (Total Laporan, Menunggu, Diproses, Selesai).
*   **Interactive Maps:** Menampilkan titik lokasi kejadian menggunakan Leaflet.js (OpenStreetMap) di halaman detail pengaduan.
*   **Keamanan Ekstra:** Webhook dilengkapi dengan autentikasi *Secret Token*, filter URL media, dan batas validasi koordinat bumi.

## Tech Stack
*   **Backend:** PHP 8+ (Native / Vanilla)
*   **Database:** SQLite3 (Portable, Zero-config)
*   **Frontend:** HTML5, Tailwind CSS (via CDN untuk portabilitas), Vanilla JavaScript
*   **Maps:** Leaflet.js
*   **WhatsApp Gateway:** Fonnte API

## Cara Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/Kaz0342/Helpdesk-desa.git
   cd Helpdesk-desa
   ```

2. **Setup Database**
   Jalankan script instalasi melalui terminal untuk membuat file `database.sqlite` dan men-generate tabel beserta akun admin default:
   ```bash
   php db/install_db.php
   ```

3. **Jalankan Server Lokal**
   Gunakan built-in web server PHP:
   ```bash
   php -S localhost:8080
   ```
   Akses dashboard melalui browser: `http://localhost:8080/public/login.php`

4. **Konfigurasi Webhook Fonnte**
   *   Daftarkan akun di [Fonnte](https://md.fonnte.com).
   *   Dapatkan **Token API Fonnte** dan ubah nilai variabel `$fonnte_token` di dalam file `api/webhook.php`.
   *   Sambungkan URL webhook publik Anda (misal menggunakan hosting atau Ngrok) di menu Fonnte Device. Tambahkan secret key di URL, contoh:
       `https://domain-desa.com/api/webhook.php?token=DESA_HELPDESK_2026`

## Akun Default Admin
*   **Username:** `admin`
*   **Password:** `admin123`
*(Sangat disarankan untuk segera mengubah password setelah login pertama kali di menu Pengaturan)*

---
*Dikembangkan oleh **Benedict Saviola Pradana** © 2026. Hak Cipta Dilindungi.*
