# Changelog - Helpdesk Desa

Semua perubahan penting pada project ini didokumentasikan dalam file ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/).

## [1.0.0] - 2026-06-09

### Ditambahkan
- Chatbot WhatsApp dengan alur State Machine (Menu -> Deskripsi -> Foto -> Lokasi).
- Integrasi Fonnte API untuk menerima dan mengirim pesan WhatsApp.
- Dukungan penerimaan foto bukti dari warga (URL media untuk Fonnte Premium, penanda untuk Free Tier).
- Dukungan penerimaan koordinat GPS dari fitur Share Location WhatsApp.
- Validasi URL media menggunakan `filter_var(FILTER_VALIDATE_URL)`.
- Validasi rentang koordinat bumi (Latitude: -90 s/d 90, Longitude: -180 s/d 180).
- Autentikasi webhook menggunakan Secret Token via query parameter.
- Perintah global chatbot: BATAL (membatalkan proses), HKI (informasi hak cipta).
- Dashboard admin dengan statistik laporan (Total, Menunggu, Diproses, Selesai).
- Halaman data pengaduan dengan fitur pencarian, filter status, filter tanggal, dan pagination.
- Update status pengaduan via Fetch API (AJAX) tanpa reload halaman.
- Lightbox modal untuk melihat foto bukti pengaduan.
- Halaman detail pengaduan dengan peta interaktif Leaflet.js (OpenStreetMap).
- Tombol navigasi ke Google Maps dari halaman detail.
- Halaman pengaturan dengan informasi sistem dan toggle auto-reply.
- Sistem autentikasi admin (login/logout) dengan password hash bcrypt.
- Ekspor data pengaduan ke format CSV.
- Database SQLite3 dengan konfigurasi WAL, foreign keys, dan busy timeout.
- Script instalasi database otomatis (`db/install_db.php`) dengan seeding data contoh.
- Proteksi folder database menggunakan `.htaccess`.
- Dukungan Dark Mode pada seluruh halaman dashboard.
- Desain responsif menggunakan Tailwind CSS.

---
*Helpdesk Desa - Dikembangkan oleh Benedict Saviola Pradana (2026)*
