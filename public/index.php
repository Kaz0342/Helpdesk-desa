<?php
/**
 * ============================================================
 * HALAMAN: DASHBOARD — Overview Helpdesk Desa
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Halaman utama dashboard yang menampilkan ringkasan data:
 * - Kartu Statistik  (Total, Menunggu, Diproses, Selesai)
 * - Tabel 5 Laporan Terbaru (quick overview)
 *
 * Navigasi ke halaman lain:
 * - pengaduan.php   → Tabel lengkap data pengaduan
 * - pengaturan.php  → Informasi dan konfigurasi sistem
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

// Variabel layout — digunakan oleh komponen header.php
$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';

// Dapatkan koneksi database
$db = getKoneksiDatabase();

// ============================================================
// QUERY STATISTIK — Kartu Ringkasan
// ============================================================

// Total seluruh laporan
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM pengaduan");
$stmtTotal->execute();
$totalLaporan = $stmtTotal->fetchColumn();

// Laporan berstatus "Menunggu"
$stmtMenunggu = $db->prepare("SELECT COUNT(*) FROM pengaduan WHERE status = :status");
$stmtMenunggu->execute([':status' => 'Menunggu']);
$totalMenunggu = $stmtMenunggu->fetchColumn();

// Laporan berstatus "Diproses"
$stmtDiproses = $db->prepare("SELECT COUNT(*) FROM pengaduan WHERE status = :status");
$stmtDiproses->execute([':status' => 'Diproses']);
$totalDiproses = $stmtDiproses->fetchColumn();

// Laporan berstatus "Selesai"
$stmtSelesai = $db->prepare("SELECT COUNT(*) FROM pengaduan WHERE status = :status");
$stmtSelesai->execute([':status' => 'Selesai']);
$totalSelesai = $stmtSelesai->fetchColumn();

// ============================================================
// QUERY 5 LAPORAN TERBARU — Quick Overview
// ============================================================
$stmtTerbaru = $db->prepare("
    SELECT id, nomor_wa, deskripsi, status, created_at
    FROM pengaduan
    ORDER BY created_at DESC
    LIMIT 5
");
$stmtTerbaru->execute();
$laporanTerbaru = $stmtTerbaru->fetchAll();

/**
 * Menghasilkan class CSS Tailwind untuk badge status.
 * Mendukung dark mode.
 *
 * @param string $status Status pengaduan (Title Case)
 * @return string Class CSS Tailwind
 */
function kelasStatusBadge(string $status): string
{
    return match ($status) {
        'Menunggu' => 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800',
        'Diproses' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
        'Selesai'  => 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
        'Ditolak'  => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
        default    => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
    };
}

// Include komponen header (sidebar, topbar, layout awal)
require_once __DIR__ . '/components/header.php';
?>

<!-- ============================================ -->
<!-- KARTU STATISTIK                              -->
<!-- ============================================ -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Kartu: Total Laporan -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden">
        <div class="flex justify-between items-start">
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Laporan</span>
            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center">
                <i class="ph ph-files text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
        </div>
        <span class="text-4xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($totalLaporan) ?></span>
    </div>

    <!-- Kartu: Menunggu -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden">
        <div class="flex justify-between items-start">
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Menunggu</span>
            <div class="w-10 h-10 bg-amber-50 dark:bg-amber-900/20 rounded-xl flex items-center justify-center">
                <i class="ph ph-clock text-amber-500 text-xl"></i>
            </div>
        </div>
        <span class="text-4xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($totalMenunggu) ?></span>
    </div>

    <!-- Kartu: Diproses -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden">
        <div class="flex justify-between items-start">
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Diproses</span>
            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center">
                <i class="ph ph-spinner text-blue-500 text-xl"></i>
            </div>
        </div>
        <span class="text-4xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($totalDiproses) ?></span>
    </div>

    <!-- Kartu: Selesai -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden">
        <div class="flex justify-between items-start">
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Selesai</span>
            <div class="w-10 h-10 bg-green-50 dark:bg-green-900/20 rounded-xl flex items-center justify-center">
                <i class="ph ph-check-circle text-green-500 text-xl"></i>
            </div>
        </div>
        <span class="text-4xl font-bold text-green-600 dark:text-green-400"><?= number_format($totalSelesai) ?></span>
    </div>
</div>

<!-- ============================================ -->
<!-- TABEL: 5 LAPORAN TERBARU                     -->
<!-- ============================================ -->
<div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30 flex justify-between items-center">
        <h4 class="font-semibold text-gray-800 dark:text-white">Laporan Terbaru</h4>
        <a href="pengaduan.php" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium no-underline">
            Lihat Semua →
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold border-b border-gray-100 dark:border-gray-700">
                    <th class="px-6 py-4">No</th>
                    <th class="px-6 py-4">Waktu Lapor</th>
                    <th class="px-6 py-4">Nomor WA</th>
                    <th class="px-6 py-4">Deskripsi</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                <?php if (empty($laporanTerbaru)): ?>
                <!-- Tampilan kosong -->
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="ph ph-inbox text-4xl"></i>
                            <p>Belum ada laporan pengaduan masuk.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($laporanTerbaru as $i => $laporan): ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?= $i + 1 ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        <?= date('d M Y, H:i', strtotime($laporan['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                        +<?= htmlspecialchars($laporan['nomor_wa']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">
                        <?= htmlspecialchars(mb_strimwidth($laporan['deskripsi'], 0, 60, '...')) ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border <?= kelasStatusBadge($laporan['status']) ?>">
                            <?= htmlspecialchars($laporan['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="detail_pengaduan.php?id=<?= $laporan['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Lihat Detail">
                            <i class="ph ph-eye text-lg"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
