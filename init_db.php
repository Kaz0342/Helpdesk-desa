<?php
/**
 * ============================================================
 * INISIALISASI DATABASE — Pembuatan Tabel SQLite
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Script ini dijalankan SATU KALI untuk membuat struktur tabel
 * yang dibutuhkan oleh dashboard admin helpdesk.
 *
 * Cara Penggunaan:
 *   Jalankan via Command Line: php init_db.php
 *   Setelah berhasil, AMANKAN file ini dari akses publik.
 * ============================================================
 */

// Muat konfigurasi koneksi database
require_once __DIR__ . '/config/database.php';

echo "============================================\n";
echo " HELPDESK DESA — Inisialisasi Database\n";
echo " Oleh: Benedict Saviola Pradana (2026)\n";
echo "============================================\n\n";

try {
    // Dapatkan koneksi database
    $pdo = getKoneksiDatabase();
    echo "[✓] Koneksi database berhasil.\n\n";

    // ========================================================
    // TABEL: pengaduan
    // ========================================================
    // Tabel utama untuk menyimpan data pengaduan/laporan warga.
    //
    // Kolom:
    // - id          : Primary key auto-increment
    // - nomor_wa    : Nomor WhatsApp pelapor (format: 628xxx)
    // - nama        : Nama pelapor (opsional, bisa diisi dari chatbot)
    // - deskripsi   : Isi lengkap pengaduan warga
    // - foto_path   : Path relatif ke file foto lampiran (nullable)
    // - lokasi_lat  : Koordinat latitude lokasi kejadian (REAL)
    // - lokasi_long : Koordinat longitude lokasi kejadian (REAL)
    // - status      : Status pengaduan, dibatasi oleh CHECK constraint
    //                 Nilai valid: 'Menunggu', 'Diproses', 'Selesai', 'Ditolak'
    // - created_at  : Timestamp otomatis saat laporan dibuat
    // ========================================================
    $pdo->exec("
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
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[✓] Tabel 'pengaduan' berhasil dibuat.\n";

    // Index: nomor_wa — mempercepat pencarian pengaduan per warga
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_pengaduan_nomor_wa 
        ON pengaduan(nomor_wa)
    ");
    echo "    ├─ Index 'idx_pengaduan_nomor_wa' dibuat.\n";

    // Index: created_at — mempercepat sorting berdasarkan tanggal
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_pengaduan_created_at 
        ON pengaduan(created_at)
    ");
    echo "    ├─ Index 'idx_pengaduan_created_at' dibuat.\n";

    // Index: status — mempercepat filter berdasarkan status
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_pengaduan_status 
        ON pengaduan(status)
    ");
    echo "    └─ Index 'idx_pengaduan_status' dibuat.\n\n";

    // ========================================================
    // DATA CONTOH (SEED) — Untuk Keperluan Demo & Pengujian
    // ========================================================
    // Masukkan data dummy jika tabel masih kosong.
    // Data ini membantu memverifikasi tampilan dashboard.
    // ========================================================
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pengaduan");
    $count = $stmt->fetch()['total'];

    if ($count === 0) {
        $seedData = [
            [
                'nomor_wa'   => '6281234567890',
                'nama'       => 'Budi Santoso',
                'deskripsi'  => 'Jalan di RT 03/RW 02 rusak parah dan berlubang besar, sudah beberapa kali menyebabkan kecelakaan motor terutama saat malam hari karena tidak ada penerangan.',
                'status'     => 'Menunggu',
            ],
            [
                'nomor_wa'   => '6289876543210',
                'nama'       => 'Siti Aminah',
                'deskripsi'  => 'Lampu penerangan jalan di Gang Mawar RT 05 sudah mati selama 2 minggu. Warga merasa tidak aman karena kondisi gelap total setelah maghrib.',
                'status'     => 'Diproses',
            ],
            [
                'nomor_wa'   => '6281122334455',
                'nama'       => 'Ahmad Hidayat',
                'deskripsi'  => 'Saluran air/selokan di depan rumah nomor 17 tersumbat sampah dan menyebabkan genangan air saat hujan. Sudah dilaporkan ke RT tapi belum ada tindakan.',
                'status'     => 'Selesai',
            ],
            [
                'nomor_wa'   => '6287788990011',
                'nama'       => 'Dewi Lestari',
                'deskripsi'  => 'Pohon besar di pinggir jalan utama desa condong ke jalan dan rawan tumbang saat angin kencang. Mohon segera ditangani sebelum musim hujan.',
                'status'     => 'Menunggu',
            ],
            [
                'nomor_wa'   => '6285566778899',
                'nama'       => 'Rina Wulandari',
                'deskripsi'  => 'Tempat pembuangan sampah liar di belakang balai desa sudah menumpuk dan menimbulkan bau tidak sedap. Warga sekitar mengeluhkan dampak kesehatan.',
                'status'     => 'Menunggu',
            ],
        ];

        $insert = $pdo->prepare("
            INSERT INTO pengaduan (nomor_wa, nama, deskripsi, status)
            VALUES (:nomor_wa, :nama, :deskripsi, :status)
        ");

        foreach ($seedData as $data) {
            $insert->execute($data);
        }

        echo "[✓] Data contoh berhasil dimasukkan (" . count($seedData) . " laporan).\n\n";
    } else {
        echo "[i] Tabel sudah berisi data, lewati proses seeding.\n\n";
    }

    // ========================================================
    // RINGKASAN
    // ========================================================
    echo "============================================\n";
    echo " ✅ Inisialisasi database SELESAI!\n";
    echo " 📁 Lokasi DB: db/database.sqlite\n";
    echo "============================================\n";
    echo "\n⚠  PERINGATAN KEAMANAN:\n";
    echo "   1. HAPUS atau AMANKAN file init_db.php ini\n";
    echo "      setelah inisialisasi selesai.\n";
    echo "   2. Pastikan db/.htaccess aktif untuk\n";
    echo "      memblokir akses langsung ke database.\n\n";

} catch (RuntimeException $e) {
    echo "[✗] GAGAL: " . $e->getMessage() . "\n";
    exit(1);
} catch (PDOException $e) {
    echo "[✗] SQL ERROR: " . $e->getMessage() . "\n";
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
