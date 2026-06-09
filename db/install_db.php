<?php
/**
 * ============================================================
 * INSTALASI DATABASE — Pembuatan Tabel SQLite
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Script ini dijalankan SATU KALI melalui Command Line (CLI)
 * untuk membuat seluruh tabel yang dibutuhkan oleh sistem.
 *
 * Cara Penggunaan:
 *   cd ke root project, lalu jalankan:
 *   php db/install_db.php
 *
 * PERINGATAN KEAMANAN:
 *   Setelah inisialisasi berhasil, HAPUS atau AMANKAN file ini
 *   dari akses publik. Jangan biarkan file ini dapat diakses
 *   melalui browser.
 * ============================================================
 */

// Muat fungsi koneksi database
require_once __DIR__ . '/../config/database.php';

echo "============================================\n";
echo " HELPDESK DESA — Instalasi Database\n";
echo " Oleh: Benedict Saviola Pradana (2026)\n";
echo "============================================\n\n";

try {
    // Dapatkan instance koneksi PDO
    $db = getKoneksiDatabase();
    echo "[✓] Koneksi ke database berhasil.\n\n";

    // ========================================================
    // TABEL 1: sesi_chat
    // ========================================================
    // Menyimpan status percakapan (state) setiap pengguna
    // WhatsApp. Tabel ini adalah inti dari State Machine
    // chatbot — setiap nomor WA memiliki satu baris yang
    // melacak posisi mereka dalam alur percakapan.
    //
    // Kolom:
    // - nomor_wa      : PRIMARY KEY, nomor WhatsApp pengguna
    //                   dalam format 628xxx (tanpa tanda +)
    // - state         : Status/posisi pengguna dalam alur
    //                   chatbot (contoh: 'menu_utama',
    //                   'lapor_deskripsi', 'lapor_foto')
    // - data_temp     : String JSON yang menyimpan jawaban
    //                   sementara pengguna selama proses
    //                   pelaporan multi-langkah. Direset
    //                   setelah data berhasil disimpan.
    // - last_activity : Timestamp aktivitas terakhir,
    //                   digunakan untuk membersihkan sesi
    //                   yang sudah kedaluwarsa (expired).
    // ========================================================
    $db->exec("
        CREATE TABLE IF NOT EXISTS sesi_chat (
            nomor_wa      TEXT PRIMARY KEY NOT NULL,
            state         TEXT NOT NULL DEFAULT 'menu_utama',
            data_temp     TEXT NOT NULL DEFAULT '{}',
            last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[✓] Tabel 'sesi_chat' berhasil dibuat.\n";

    // Indeks untuk pembersihan sesi berdasarkan waktu kedaluwarsa
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_sesi_last_activity
        ON sesi_chat(last_activity)
    ");
    echo "    └─ Indeks 'idx_sesi_last_activity' dibuat.\n\n";

    // ========================================================
    // TABEL 2: pengaduan
    // ========================================================
    // Menyimpan data laporan/pengaduan yang telah selesai
    // dikumpulkan melalui chatbot WhatsApp. Data masuk ke
    // tabel ini hanya setelah pengguna menyelesaikan seluruh
    // tahapan pelaporan (deskripsi → foto → lokasi).
    //
    // Kolom:
    // - id          : Primary key auto-increment
    // - nomor_wa    : Nomor WhatsApp pelapor (tidak unik —
    //                 satu nomor bisa membuat banyak laporan)
    // - deskripsi   : Isi lengkap pengaduan/keluhan warga
    // - foto_path   : Path relatif ke file foto bukti yang
    //                 diunggah (nullable jika tidak ada foto)
    // - lokasi_lat  : Koordinat latitude lokasi kejadian
    //                 (tipe REAL untuk presisi desimal)
    // - lokasi_long : Koordinat longitude lokasi kejadian
    // - status      : Status penanganan pengaduan, dibatasi
    //                 oleh CHECK constraint. Nilai yang
    //                 diperbolehkan: 'Menunggu', 'Diproses',
    //                 'Selesai', 'Ditolak'
    // - created_at  : Timestamp otomatis saat laporan dibuat
    // ========================================================
    $db->exec("
        CREATE TABLE IF NOT EXISTS pengaduan (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            nomor_wa    TEXT NOT NULL,
            nama        TEXT DEFAULT NULL,
            deskripsi   TEXT NOT NULL,
            foto_path   TEXT DEFAULT NULL,
            lokasi_lat  REAL DEFAULT NULL,
            lokasi_long REAL DEFAULT NULL,
            status      TEXT NOT NULL DEFAULT 'Menunggu'
                        CHECK(status IN ('Menunggu', 'Diproses', 'Selesai', 'Ditolak')),
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[✓] Tabel 'pengaduan' berhasil dibuat.\n";

    // Indeks: mempercepat pencarian laporan berdasarkan nomor WA
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_pengaduan_nomor_wa
        ON pengaduan(nomor_wa)
    ");
    echo "    ├─ Indeks 'idx_pengaduan_nomor_wa' dibuat.\n";

    // Indeks: mempercepat pengurutan berdasarkan tanggal pembuatan
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_pengaduan_created_at
        ON pengaduan(created_at)
    ");
    echo "    ├─ Indeks 'idx_pengaduan_created_at' dibuat.\n";

    // Indeks: mempercepat filter berdasarkan status di dashboard
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_pengaduan_status
        ON pengaduan(status)
    ");
    echo "    └─ Indeks 'idx_pengaduan_status' dibuat.\n\n";

    // ========================================================
    // MIGRASI: Tambah kolom 'nama' jika database sudah ada
    // ========================================================
    // Untuk database yang sudah dibuat sebelumnya tanpa kolom
    // 'nama', script ini akan menambahkannya secara otomatis.
    // ========================================================
    $kolom = $db->query("PRAGMA table_info(pengaduan)")->fetchAll();
    $adaKolomNama = false;
    foreach ($kolom as $k) {
        if ($k['name'] === 'nama') { $adaKolomNama = true; break; }
    }
    if (!$adaKolomNama) {
        $db->exec("ALTER TABLE pengaduan ADD COLUMN nama TEXT DEFAULT NULL");
        echo "[✓] Migrasi: Kolom 'nama' ditambahkan ke tabel 'pengaduan'.\n\n";
    }

    // ========================================================
    // TABEL 3: admin
    // ========================================================
    // Menyimpan kredensial admin yang bisa login ke dashboard.
    // Password disimpan sebagai hash bcrypt (password_hash).
    //
    // Kolom:
    // - id            : Primary key auto-increment
    // - username      : Username unik untuk login
    // - password_hash : Hash bcrypt dari password
    // - created_at    : Timestamp pembuatan akun
    // ========================================================
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[✓] Tabel 'admin' berhasil dibuat.\n\n";

    // ========================================================
    // SEED AKUN ADMIN DEFAULT
    // ========================================================
    // Membuat akun admin default jika belum ada.
    // Username : admin
    // Password : admin123
    //
    // ⚠ PERINGATAN: Segera ubah password default setelah
    //   instalasi melalui menu Pengaturan di dashboard!
    // ========================================================
    $cekAdmin = $db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
    if ($cekAdmin == 0) {
        $hashPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmtAdmin = $db->prepare("
            INSERT INTO admin (username, password_hash)
            VALUES (:username, :password_hash)
        ");
        $stmtAdmin->execute([
            ':username'      => 'admin',
            ':password_hash' => $hashPassword,
        ]);
        echo "[✓] Akun admin default dibuat (username: admin, password: admin123).\n";
        echo "    ⚠  SEGERA UBAH PASSWORD DEFAULT!\n\n";
    } else {
        echo "[i] Akun admin sudah ada, seeding dilewati.\n\n";
    }

    // ========================================================
    // DATA CONTOH (SEED) — Untuk Keperluan Demo & Pengujian
    // ========================================================
    // Memasukkan beberapa data dummy ke tabel pengaduan jika
    // tabel masih kosong. Data ini membantu memverifikasi
    // tampilan dashboard saat pertama kali dijalankan.
    // ========================================================
    $cekJumlah = $db->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn();

    if ($cekJumlah == 0) {
        $dataContoh = [
            [
                'nomor_wa'  => '6281234567890',
                'deskripsi' => 'Jalan di RT 03/RW 02 rusak parah dan berlubang besar, sudah beberapa kali menyebabkan kecelakaan motor terutama saat malam hari karena tidak ada penerangan.',
                'status'    => 'Menunggu',
            ],
            [
                'nomor_wa'  => '6289876543210',
                'deskripsi' => 'Lampu penerangan jalan di Gang Mawar RT 05 sudah mati selama 2 minggu. Warga merasa tidak aman karena kondisi gelap total setelah maghrib.',
                'status'    => 'Diproses',
            ],
            [
                'nomor_wa'  => '6281122334455',
                'deskripsi' => 'Saluran air di depan rumah nomor 17 tersumbat sampah dan menyebabkan genangan air saat hujan. Sudah dilaporkan ke RT tapi belum ada tindakan.',
                'status'    => 'Selesai',
            ],
            [
                'nomor_wa'  => '6287788990011',
                'deskripsi' => 'Pohon besar di pinggir jalan utama desa condong ke jalan dan rawan tumbang saat angin kencang. Mohon segera ditangani sebelum musim hujan.',
                'status'    => 'Menunggu',
            ],
            [
                'nomor_wa'  => '6285566778899',
                'deskripsi' => 'Tempat pembuangan sampah liar di belakang balai desa sudah menumpuk dan menimbulkan bau tidak sedap. Warga sekitar mengeluhkan dampak kesehatan.',
                'status'    => 'Menunggu',
            ],
        ];

        // Gunakan Prepared Statement untuk keamanan
        $stmt = $db->prepare("
            INSERT INTO pengaduan (nomor_wa, deskripsi, status)
            VALUES (:nomor_wa, :deskripsi, :status)
        ");

        foreach ($dataContoh as $data) {
            $stmt->execute($data);
        }

        echo "[✓] Data contoh berhasil dimasukkan (" . count($dataContoh) . " laporan).\n\n";
    } else {
        echo "[i] Tabel sudah berisi data, proses seeding dilewati.\n\n";
    }

    // ========================================================
    // RINGKASAN AKHIR
    // ========================================================
    echo "============================================\n";
    echo " ✅ Instalasi database SELESAI!\n";
    echo " 📁 Lokasi file: db/database.sqlite\n";
    echo "============================================\n";
    echo "\n⚠  PERINGATAN KEAMANAN:\n";
    echo "   1. HAPUS atau AMANKAN file install_db.php\n";
    echo "      setelah instalasi selesai.\n";
    echo "   2. Pastikan db/.htaccess aktif untuk\n";
    echo "      memblokir akses langsung ke database.\n\n";

} catch (RuntimeException $e) {
    echo "[✗] GAGAL: " . $e->getMessage() . "\n";
    exit(1);
} catch (PDOException $e) {
    echo "[✗] KESALAHAN SQL: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================
// IDENTIFIKASI HAK KEKAYAAN INTELEKTUAL (HKI)
// ============================================================
// Kode Sertifikasi  : HKI-EC65-2026-BSP
// Pengembang        : Benedict Saviola Pradana
// Tahun Pembuatan   : 2026
// Hak Cipta         : Dilindungi UU No. 28 Tahun 2014
// ============================================================
