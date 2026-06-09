<?php
/**
 * ============================================================
 * KONFIGURASI DATABASE — Koneksi PDO ke SQLite
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * File ini menyediakan fungsi tunggal untuk mendapatkan
 * koneksi PDO ke database SQLite. Menggunakan pola Singleton
 * agar koneksi hanya dibuat satu kali per siklus request.
 *
 * Penggunaan:
 *   require_once __DIR__ . '/../config/database.php';
 *   $db = getKoneksiDatabase();
 * ============================================================
 */

/**
 * Mendapatkan instance koneksi PDO ke database SQLite.
 *
 * Fungsi ini mengimplementasikan pola Singleton melalui
 * variabel statis. Koneksi pertama akan disimpan dan
 * digunakan kembali pada panggilan berikutnya.
 *
 * Konfigurasi yang diterapkan:
 * - ATTR_ERRMODE       → ERRMODE_EXCEPTION (error langsung melempar exception)
 * - ATTR_DEFAULT_FETCH  → FETCH_ASSOC (hasil query berupa array asosiatif)
 * - EMULATE_PREPARES   → false (gunakan prepared statement native)
 * - PRAGMA journal_mode → WAL (mendukung pembacaan dan penulisan bersamaan)
 * - PRAGMA foreign_keys → ON (menjaga integritas referensi antar tabel)
 * - PRAGMA busy_timeout → 5000ms (toleransi jika database sedang terkunci)
 *
 * @return PDO Instance koneksi database yang telah dikonfigurasi
 * @throws RuntimeException Jika koneksi ke database gagal
 */
function getKoneksiDatabase(): PDO
{
    // Simpan instance koneksi di variabel statis (Singleton)
    static $pdo = null;

    // Jika koneksi sudah pernah dibuat, langsung kembalikan
    if ($pdo !== null) {
        return $pdo;
    }

    // Tentukan path absolut ke file database SQLite
    $pathDatabase = __DIR__ . '/../db/database.sqlite';

    // Pastikan direktori /db/ sudah ada sebelum PDO mencoba membuat file
    $direktoriDatabase = dirname($pathDatabase);
    if (!is_dir($direktoriDatabase)) {
        mkdir($direktoriDatabase, 0755, true);
    }

    try {
        // Buat koneksi PDO baru ke file SQLite
        $pdo = new PDO('sqlite:' . $pathDatabase);

        // --- Konfigurasi Atribut PDO ---

        // Mode Error: Lempar exception saat terjadi kesalahan query
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Mode Fetch Default: Array asosiatif (kunci = nama kolom)
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Nonaktifkan emulasi prepared statement — gunakan yang native
        // untuk keamanan maksimal terhadap SQL Injection
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // --- Konfigurasi PRAGMA SQLite ---

        // WAL (Write-Ahead Logging): mendukung operasi baca dan tulis bersamaan
        $pdo->exec('PRAGMA journal_mode = WAL');

        // Foreign Keys: menjaga integritas data antar tabel yang berelasi
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Busy Timeout: tunggu 5 detik jika database sedang dikunci proses lain
        $pdo->exec('PRAGMA busy_timeout = 5000');

    } catch (PDOException $e) {
        // Catat detail error ke log server untuk keperluan debugging
        error_log('[HELPDESK-DESA] Koneksi database gagal: ' . $e->getMessage());

        // Lempar exception generik — jangan tampilkan detail teknis ke pengguna
        throw new RuntimeException(
            'Koneksi ke database gagal. Silakan hubungi administrator sistem.',
            500,
            $e
        );
    }

    return $pdo;
}

// ============================================================
// IDENTIFIKASI HAK KEKAYAAN INTELEKTUAL (HKI)
// ============================================================
// Kode Sertifikasi  : HKI-EC65-2026-BSP
// Pengembang        : Benedict Saviola Pradana
// Institusi         : Universitas Atma Jaya Yogyakarta — Program Studi Sistem Informasi
// Tahun Pembuatan   : 2026
// Hak Cipta         : Dilindungi Undang-Undang Republik Indonesia
//                     No. 28 Tahun 2014 tentang Hak Cipta
// Deskripsi Sistem  : Sistem Helpdesk Pelayanan Publik Desa dengan
//                     Chatbot WhatsApp dan Dashboard Monitoring.
// ============================================================
