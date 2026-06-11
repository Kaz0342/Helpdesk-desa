# Panduan Admin - Dashboard Helpdesk Desa

Dokumen ini ditujukan untuk **Petugas/Admin Desa** yang bertugas mengelola laporan pengaduan warga melalui Dashboard Helpdesk Desa.

## Login ke Dashboard

1. Buka browser (Chrome / Firefox / Edge), ketik alamat website yang diberikan oleh pengembang.
2. Masukkan **Username** dan **Password** yang telah diberikan.
3. Klik tombol **Masuk**.

**Catatan:** Jika ini pertama kali login, gunakan akun default berikut:
- Username: `admin`
- Password: `admin123`

Segera ubah password default melalui menu **Pengaturan** setelah berhasil login.

## Halaman Dashboard (Beranda)

Setelah login, Anda akan melihat halaman utama yang menampilkan:

- **Kartu Statistik:** Menampilkan jumlah total laporan, laporan berstatus Menunggu, Diproses, dan Selesai.
- **Tabel 5 Laporan Terbaru:** Ringkasan laporan terbaru yang masuk dari warga. Klik ikon mata untuk melihat detail.

## Mengelola Data Pengaduan

Klik menu **Pengaduan** di sidebar untuk membuka halaman pengelolaan data lengkap.

### Mencari Laporan

Gunakan kolom pencarian di bagian atas tabel untuk mencari berdasarkan:
- Nomor WhatsApp pelapor
- Isi deskripsi pengaduan

### Memfilter Laporan

- **Filter Status:** Klik tombol Semua, Menunggu, Diproses, atau Selesai.
- **Filter Tanggal:** Gunakan kolom tanggal (Dari - Sampai) untuk memfilter berdasarkan rentang waktu.

### Mengubah Status Laporan

1. Pada baris laporan yang ingin diubah, klik ikon titik tiga di kolom **Aksi**.
2. Pilih status baru dari menu dropdown yang muncul:
   - **Menunggu** - Laporan baru masuk, belum ditindaklanjuti.
   - **Diproses** - Laporan sedang dalam penanganan petugas.
   - **Selesai** - Masalah sudah ditangani dan terselesaikan.
   - **Ditolak** - Laporan tidak valid atau tidak dapat ditindaklanjuti.
3. Konfirmasi perubahan status pada dialog yang muncul.

### Melihat Detail Laporan

Klik ikon mata atau pilih **Lihat Detail** dari menu aksi. Halaman detail menampilkan:
- Informasi pelapor (Nomor WA, Nama WhatsApp).
- Deskripsi lengkap pengaduan.
- Foto bukti (jika dilampirkan oleh pelapor).
- Peta lokasi kejadian (jika pelapor mengirimkan titik lokasi). Klik **Buka di Google Maps** untuk navigasi ke lokasi.

### Ekspor Data ke CSV

Klik tombol **Export CSV** untuk mengunduh seluruh data pengaduan dalam format CSV. File ini dapat dibuka di Microsoft Excel atau Google Sheets untuk keperluan pelaporan.

## Halaman Pengaturan

Menu **Pengaturan** di sidebar menampilkan:
- **Status Database:** Informasi koneksi database, jumlah record, dan ukuran file.
- **Gateway Bot WA:** Status koneksi chatbot WhatsApp.
- **Toggle Auto-Reply:** Aktifkan atau matikan chatbot otomatis. Jika dimatikan, pesan dari warga tetap masuk ke WhatsApp desa tetapi bot tidak akan membalas secara otomatis.

## Alur Kerja yang Disarankan (SOP)

1. Periksa dashboard setiap pagi untuk melihat laporan baru berstatus **Menunggu**.
2. Buka detail laporan, periksa deskripsi, foto, dan lokasi.
3. Ubah status menjadi **Diproses** setelah laporan diterima oleh petugas lapangan.
4. Setelah masalah terselesaikan, ubah status menjadi **Selesai**.
5. Lakukan ekspor CSV secara berkala (mingguan/bulanan) untuk arsip dan pelaporan ke Kepala Desa.

---
*Helpdesk Desa v1.0 - Dikembangkan oleh Benedict Saviola Pradana (2026)*
