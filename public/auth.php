<?php
/**
 * ============================================================
 * AUTH GUARD — Proteksi Halaman Dashboard
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Require file ini di SETIAP halaman yang butuh login.
 * File ini akan:
 * 1. Memulai session (jika belum dimulai)
 * 2. Mengecek apakah admin sudah login
 * 3. Redirect ke login.php jika belum terautentikasi
 *
 * Penggunaan:
 *   require_once __DIR__ . '/auth.php';
 *   // $adminUsername tersedia setelah baris ini
 * ============================================================
 */

// Mulai session hanya jika belum aktif (mencegah warning duplikat)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah admin sudah login melalui session
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Data admin yang tersedia untuk dipakai di seluruh halaman
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
