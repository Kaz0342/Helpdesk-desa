<?php
/**
 * ============================================================
 * EXPORT CSV — Unduh Data Pengaduan dalam Format CSV
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * File ini mengekspor seluruh data pengaduan dari database
 * ke format CSV yang bisa dibuka di Microsoft Excel.
 *
 * Fitur:
 * - BOM UTF-8 agar karakter Indonesia tampil benar di Excel
 * - Header kolom deskriptif
 * - Urutan data dari terbaru ke terlama
 * - Prepared Statement untuk keamanan query
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

// Dapatkan koneksi database menggunakan fungsi baru
$db = getKoneksiDatabase();

// Tentukan nama file berdasarkan tanggal hari ini
$namaFile = 'pengaduan_desa_' . date('Y-m-d') . '.csv';

// Tetapkan header HTTP agar browser mengunduh sebagai file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $namaFile . '"');

// Buka output stream sebagai file handle
$output = fopen('php://output', 'w');

// Tulis BOM (Byte Order Mark) UTF-8 agar Excel bisa membaca
// karakter khusus Bahasa Indonesia dengan benar
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Tulis baris header kolom
fputcsv($output, [
    'No',
    'Waktu Lapor',
    'Nomor WA',
    'Deskripsi Keluhan',
    'Foto Bukti',
    'Latitude',
    'Longitude',
    'Status',
]);

// Ambil semua data pengaduan dari database (Prepared Statement)
$stmt = $db->prepare("
    SELECT id, created_at, nomor_wa, deskripsi,
           foto_path, lokasi_lat, lokasi_long, status
    FROM pengaduan
    ORDER BY created_at DESC
");
$stmt->execute();
$data = $stmt->fetchAll();

// Tulis setiap baris data ke output CSV
foreach ($data as $i => $baris) {
    fputcsv($output, [
        $i + 1,
        $baris['created_at'],
        $baris['nomor_wa'],
        $baris['deskripsi'],
        $baris['foto_path'] ?? '-',
        $baris['lokasi_lat'] ?? '-',
        $baris['lokasi_long'] ?? '-',
        $baris['status'],
    ]);
}

fclose($output);

// ============================================================
// IDENTIFIKASI HAK KEKAYAAN INTELEKTUAL (HKI)
// ============================================================
// Pengembang : Benedict Saviola Pradana
// Institusi  : Universitas Atma Jaya Yogyakarta
// Tahun      : 2026
// ============================================================
