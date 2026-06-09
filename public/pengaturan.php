<?php
/**
 * ============================================================
 * HALAMAN: PENGATURAN SISTEM
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Halaman ini menampilkan informasi sistem dan konfigurasi:
 * - Status koneksi database
 * - Status WhatsApp Gateway (bot)
 * - Informasi sistem dan HKI
 *
 * CATATAN: Fitur Akun Admin dihilangkan untuk MVP ini
 * karena belum ada sistem autentikasi.
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

// Variabel layout — digunakan oleh komponen header.php
$currentPage = 'pengaturan';
$pageTitle   = 'Pengaturan';

// Dapatkan koneksi database menggunakan fungsi baru
$db = getKoneksiDatabase();

// Hitung total record di database untuk ditampilkan di kartu info
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM pengaduan");
$stmtTotal->execute();
$totalPengaduan = $stmtTotal->fetchColumn();

// Hitung ukuran file database (dalam kilobyte)
$pathDatabase = __DIR__ . '/../db/database.sqlite';
$ukuranDB = file_exists($pathDatabase)
    ? number_format(filesize($pathDatabase) / 1024, 1)
    : '0';

// Ambil status auto_reply
$stmtAutoReply = $db->prepare("SELECT nilai FROM pengaturan WHERE kunci = 'auto_reply'");
$stmtAutoReply->execute();
$autoReplyStatus = $stmtAutoReply->fetchColumn() ?: '0';

// Include komponen header (sidebar, topbar, layout awal)
require_once __DIR__ . '/components/header.php';
?>

<!-- ============================================ -->
<!-- GRID KARTU PENGATURAN                        -->
<!-- ============================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ========================================== -->
    <!-- KARTU 1: STATUS DATABASE                   -->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph ph-database text-blue-600 dark:text-blue-400 text-xl"></i>
                Status Database
            </h3>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Tipe Database</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white">SQLite 3 (PDO)</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Total Record</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= number_format($totalPengaduan) ?> pengaduan</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Ukuran File</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= $ukuranDB ?> KB</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Status Koneksi</p>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                    Terhubung
                </span>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- KARTU 2: STATUS WHATSAPP BOT               -->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph ph-whatsapp-logo text-green-500 text-xl"></i>
                Gateway Bot WA
                <!-- Badge Status MVP -->
                <span class="inline-flex items-center gap-1.5 ml-auto px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                    MVP
                </span>
            </h3>
        </div>
        <div class="p-6 space-y-6">
            <!-- Status Gateway -->
            <div>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Status</p>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 italic">Mode Dummy (error_log)</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Pesan balasan dicatat ke log server, belum terkirim ke WhatsApp.</p>
            </div>

            <!-- Webhook Endpoint -->
            <div>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Webhook URL</p>
                <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-lg block break-all">
                    /api/webhook.php
                </code>
            </div>

            <!-- Toggle Auto-Reply -->
            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">Auto-Reply</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Chatbot otomatis</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer" title="Aktifkan/Matikan Chatbot">
                    <input type="checkbox" id="toggleAutoReply" class="sr-only peer" <?= $autoReplyStatus == '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-300 dark:bg-gray-600 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- KARTU 3: INFORMASI SISTEM                  -->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph ph-info text-blue-600 dark:text-blue-400 text-xl"></i>
                Informasi Sistem
            </h3>
        </div>
        <div class="p-6 flex-1 flex flex-col justify-between">
            <div class="space-y-5">
                <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Nama Sistem</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">WhatsApp Helpdesk Desa</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Versi</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-white">1.0 MVP</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Pengembang</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-white">Benedict Saviola P.</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Teknologi</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">PHP 8.x · SQLite 3 · Tailwind CSS</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Institusi</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">Universitas Atma Jaya Yogyakarta</p>
                </div>
            </div>

            <!-- HKI Footer -->
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 text-center">
                <p class="text-[11px] text-gray-400 dark:text-gray-600 font-medium">
                    © 2026 Benedict Saviola Pradana. Dilindungi oleh HKI.
                </p>
            </div>
        </div>
    </div>

</div>

<?php
// ============================================================
// IDENTIFIKASI HAK KEKAYAAN INTELEKTUAL (HKI)
// ============================================================
// Kode Sertifikasi  : HKI-EC65-2026-BSP
// Pengembang        : Benedict Saviola Pradana
// Institusi         : Universitas Atma Jaya Yogyakarta — Sistem Informasi
// Tahun Pembuatan   : 2026
// Hak Cipta         : Dilindungi UU No. 28 Tahun 2014
// ============================================================

require_once __DIR__ . '/components/footer.php';
?>
