<?php
/**
 * ============================================================
 * HALAMAN: DATA PENGADUAN — Tabel Lengkap + Filter + Modal
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Halaman ini menampilkan seluruh data pengaduan warga dalam
 * format tabel lengkap dengan fitur:
 * - Filter berdasarkan status (Menunggu, Diproses, Selesai, Ditolak)
 * - Pencarian berdasarkan nomor WA dan deskripsi
 * - Filter berdasarkan rentang tanggal
 * - Pagination server-side
 * - Update status via dropdown aksi (Fetch API)
 * - Lightbox modal untuk melihat foto bukti
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

// Variabel layout — digunakan oleh komponen header.php
$currentPage = 'pengaduan';
$pageTitle   = 'Data Pengaduan';

// Dapatkan koneksi database menggunakan fungsi baru
$db = getKoneksiDatabase();

// ============================================================
// HANDLER POST: Update Status Pengaduan (via Fetch API)
// ============================================================
// Menerima request AJAX dari dropdown aksi di tabel.
// Format body JSON: { "id": 1, "status": "Diproses" }
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $statusBaru = $input['status'] ?? '';

    // Whitelist status yang diizinkan (Title Case sesuai database)
    $statusDiizinkan = ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'];

    if (!$id || !in_array($statusBaru, $statusDiizinkan, true)) {
        http_response_code(400);
        echo json_encode(['sukses' => false, 'pesan' => 'Data tidak valid.']);
        exit;
    }

    try {
        // Prepared Statement untuk mencegah SQL Injection
        $stmt = $db->prepare("UPDATE pengaduan SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $statusBaru, ':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['sukses' => true, 'pesan' => "Status laporan #{$id} diubah menjadi \"{$statusBaru}\"."]);
        } else {
            http_response_code(404);
            echo json_encode(['sukses' => false, 'pesan' => "Laporan #{$id} tidak ditemukan."]);
        }
    } catch (PDOException $e) {
        error_log('[HELPDESK-DESA] Gagal update status: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['sukses' => false, 'pesan' => 'Terjadi kesalahan server.']);
    }
    exit;
}

// ============================================================
// PARAMETER FILTER & PENCARIAN
// ============================================================
$cari       = trim($_GET['search'] ?? '');
$tglDari    = $_GET['date_from'] ?? '';
$tglSampai  = $_GET['date_to'] ?? '';
$status     = $_GET['status'] ?? 'Semua';
$halaman    = max(1, intval($_GET['page'] ?? 1));
$perHalaman = 10;
$offset     = ($halaman - 1) * $perHalaman;

// ============================================================
// BANGUN QUERY DINAMIS (Prepared Statements)
// ============================================================
$kondisi = [];
$params  = [];

// Filter pencarian teks
if (!empty($cari)) {
    $kondisi[] = "(nomor_wa LIKE :cari OR deskripsi LIKE :cari)";
    $params[':cari'] = '%' . $cari . '%';
}

// Filter rentang tanggal
if (!empty($tglDari)) {
    $kondisi[] = "DATE(created_at) >= :tgl_dari";
    $params[':tgl_dari'] = $tglDari;
}
if (!empty($tglSampai)) {
    $kondisi[] = "DATE(created_at) <= :tgl_sampai";
    $params[':tgl_sampai'] = $tglSampai;
}

// Filter status (Title Case: Menunggu, Diproses, Selesai, Ditolak)
$daftarStatus = ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'];
if ($status !== 'Semua' && in_array($status, $daftarStatus, true)) {
    $kondisi[] = "status = :status";
    $params[':status'] = $status;
}

$klausaWhere = !empty($kondisi) ? 'WHERE ' . implode(' AND ', $kondisi) : '';

// Hitung total baris untuk pagination
$stmtJumlah = $db->prepare("SELECT COUNT(*) FROM pengaduan {$klausaWhere}");
$stmtJumlah->execute($params);
$totalBaris = $stmtJumlah->fetchColumn();
$totalHalaman = max(1, ceil($totalBaris / $perHalaman));

// Query data dengan pagination
$stmtData = $db->prepare("
    SELECT * FROM pengaduan {$klausaWhere}
    ORDER BY created_at DESC
    LIMIT {$perHalaman} OFFSET {$offset}
");
$stmtData->execute($params);
$barisData = $stmtData->fetchAll();

// ============================================================
// FUNGSI HELPER
// ============================================================

/**
 * Menghasilkan badge HTML untuk status pengaduan.
 * Menggunakan warna Title Case sesuai database baru.
 *
 * @param string $status Status pengaduan
 * @return string HTML badge dengan class Tailwind
 */
function badgeStatus(string $status): string
{
    $konfigurasi = [
        'Menunggu' => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'text' => 'text-amber-700 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-800'],
        'Diproses' => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'text' => 'text-blue-700 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-800'],
        'Selesai'  => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'text' => 'text-green-700 dark:text-green-400', 'border' => 'border-green-200 dark:border-green-800'],
        'Ditolak'  => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'text' => 'text-red-700 dark:text-red-400', 'border' => 'border-red-200 dark:border-red-800'],
    ];
    $c = $konfigurasi[$status] ?? $konfigurasi['Menunggu'];
    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ' . $c['bg'] . ' ' . $c['text'] . ' ' . $c['border'] . '">' . htmlspecialchars($status) . '</span>';
}

/**
 * Membangun query string URL untuk link pagination.
 *
 * @param array $override Parameter yang ingin di-override
 * @return string Query string yang sudah di-encode
 */
function bangunQueryString(array $override = []): string
{
    $params = array_merge($_GET, $override);
    return http_build_query($params);
}

// Include komponen header (sidebar, topbar, layout awal)
require_once __DIR__ . '/components/header.php';
?>

<!-- ============================================ -->
<!-- FILTER BAR — Pencarian & Filter Status       -->
<!-- ============================================ -->
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 mb-6">
    <form method="GET" action="" class="flex flex-wrap items-end gap-4">
        <!-- Kolom Pencarian -->
        <div class="flex-1 min-w-[200px]">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="ph ph-magnifying-glass text-gray-400 text-lg"></i>
                </div>
                <input type="text" name="search" value="<?= htmlspecialchars($cari) ?>"
                       placeholder="Cari nomor WA/deskripsi..."
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
            </div>
        </div>

        <!-- Filter Tanggal: Dari -->
        <div>
            <input type="date" name="date_from" value="<?= htmlspecialchars($tglDari) ?>"
                   class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all">
        </div>
        <span class="text-gray-400 dark:text-gray-500 text-sm pb-2.5">—</span>
        <!-- Filter Tanggal: Sampai -->
        <div>
            <input type="date" name="date_to" value="<?= htmlspecialchars($tglSampai) ?>"
                   class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all">
        </div>

        <!-- Tombol Filter Status -->
        <div class="flex gap-1.5">
            <?php
            // Daftar filter status yang tersedia (Title Case sesuai database)
            $filterTampil = ['Semua', 'Menunggu', 'Diproses', 'Selesai'];
            foreach ($filterTampil as $fs):
                $aktif = ($status === $fs);
            ?>
            <button type="submit" name="status" value="<?= $fs ?>"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200
                    <?= $aktif
                        ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/20'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' ?>">
                <?= $fs ?>
            </button>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- TABEL DATA PENGADUAN                         -->
<!-- ============================================ -->
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                    <th class="px-5 py-4">No</th>
                    <th class="px-5 py-4">Waktu Lapor</th>
                    <th class="px-5 py-4">Pelapor</th>
                    <th class="px-5 py-4">Deskripsi Keluhan</th>
                    <th class="px-5 py-4 text-center">Bukti</th>
                    <th class="px-5 py-4 text-center">Lokasi</th>
                    <th class="px-5 py-4 text-center">Status</th>
                    <th class="px-5 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                <?php if (empty($barisData)): ?>
                <!-- Tampilan kosong -->
                <tr>
                    <td colspan="8" class="px-5 py-16 text-center text-gray-400 dark:text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="ph ph-magnifying-glass text-5xl"></i>
                            <p class="font-medium">Tidak ada data ditemukan.</p>
                            <p class="text-xs">Coba ubah filter pencarian Anda.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($barisData as $i => $baris): ?>
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors duration-150" id="baris-<?= $baris['id'] ?>">
                    <!-- Nomor Urut -->
                    <td class="px-5 py-4 text-gray-500 dark:text-gray-400 font-medium">
                        <?= $offset + $i + 1 ?>
                    </td>
                    <!-- Waktu Lapor -->
                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        <?= date('d M Y, H:i', strtotime($baris['created_at'])) ?>
                    </td>
                    <!-- Pelapor (Nama + Nomor WA) -->
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-white text-sm"><?= htmlspecialchars($baris['nama'] ?? 'Warga') ?></p>
                            <p class="text-xs text-blue-500 dark:text-blue-400 mt-0.5">+<?= htmlspecialchars($baris['nomor_wa']) ?></p>
                        </div>
                    </td>
                    <!-- Deskripsi -->
                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300 max-w-[280px] truncate" title="<?= htmlspecialchars($baris['deskripsi']) ?>">
                        <?= htmlspecialchars($baris['deskripsi']) ?>
                    </td>
                    <!-- Bukti Foto -->
                    <td class="px-5 py-4 text-center">
                        <?php if ($baris['foto_path'] && $baris['foto_path'] !== 'foto_dikirim_via_wa'): ?>
                        <button onclick="bukaModalFoto('<?= htmlspecialchars($baris['foto_path'], ENT_QUOTES) ?>')"
                                class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-700 text-xs font-semibold transition-colors">
                            <i class="ph ph-image text-base"></i> Lihat
                        </button>
                        <?php elseif ($baris['foto_path'] === 'foto_dikirim_via_wa'): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800">
                            <i class="ph ph-check text-xs"></i> Di WA
                        </span>
                        <?php else: ?>
                        <span class="text-gray-300 dark:text-gray-600 text-xs">— tidak ada —</span>
                        <?php endif; ?>
                    </td>
                    <!-- Lokasi -->
                    <td class="px-5 py-4 text-center">
                        <?php if ($baris['lokasi_lat'] && $baris['lokasi_long']): ?>
                        <a href="https://www.google.com/maps?q=<?= $baris['lokasi_lat'] ?>,<?= $baris['lokasi_long'] ?>" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-700 text-xs font-semibold transition-colors">
                            <i class="ph ph-map-pin text-base"></i> Maps
                        </a>
                        <?php else: ?>
                        <span class="text-gray-300 dark:text-gray-600 text-xs">— tidak ada —</span>
                        <?php endif; ?>
                    </td>
                    <!-- Status Badge -->
                    <td class="px-5 py-4 text-center" id="status-<?= $baris['id'] ?>">
                        <?= badgeStatus($baris['status']) ?>
                    </td>
                    <!-- Aksi: Dropdown Update Status -->
                    <td class="px-5 py-4 text-center">
                        <div class="relative inline-block" id="dropdown-<?= $baris['id'] ?>">
                            <button onclick="toggleDropdown(<?= $baris['id'] ?>)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 transition-all">
                                <i class="ph ph-dots-three-outline-vertical text-lg"></i>
                            </button>
                            <div id="menu-<?= $baris['id'] ?>" class="hidden absolute right-0 top-full mt-1 w-44 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-xl z-50 py-1.5 transform opacity-0 scale-95 transition-all duration-150">
                                <a href="detail_pengaduan.php?id=<?= $baris['id'] ?>" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors flex items-center gap-2">
                                    <i class="ph ph-eye text-blue-500"></i> Lihat Detail
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>
                                <p class="px-3 py-1.5 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Ubah Status</p>
                                <?php
                                // Daftar status (Title Case sesuai database)
                                $semuaStatus = ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'];
                                $warnaStatus = ['Menunggu' => 'bg-amber-400', 'Diproses' => 'bg-blue-400', 'Selesai' => 'bg-green-400', 'Ditolak' => 'bg-red-400'];
                                foreach ($semuaStatus as $s):
                                    if ($s === $baris['status']) continue;
                                ?>
                                <button onclick="updateStatus(<?= $baris['id'] ?>, '<?= $s ?>')"
                                        class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full <?= $warnaStatus[$s] ?>"></span>
                                    <?= $s ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-200"><?= $offset + 1 ?>-<?= min($offset + $perHalaman, $totalBaris) ?></span> dari
            <span class="font-semibold text-gray-700 dark:text-gray-200"><?= number_format($totalBaris) ?></span> entri
        </p>
        <div class="flex items-center gap-1.5">
            <?php if ($halaman > 1): ?>
            <a href="?<?= bangunQueryString(['page' => $halaman - 1]) ?>"
               class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="ph ph-caret-left text-lg"></i>
            </a>
            <?php endif; ?>
            <?php if ($halaman < $totalHalaman): ?>
            <a href="?<?= bangunQueryString(['page' => $halaman + 1]) ?>"
               class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="ph ph-caret-right text-lg"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL LIGHTBOX FOTO                          -->
<!-- ============================================ -->
<div id="modalFoto" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph ph-image text-blue-600"></i> Bukti Foto Pengaduan
            </h3>
            <button onclick="tutupModalFoto()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 transition-all">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <div class="p-5 flex items-center justify-center min-h-[300px]">
            <img id="gambarModalFoto" src="" alt="Bukti Foto" class="max-w-full max-h-[60vh] rounded-lg object-contain">
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT HALAMAN PENGADUAN                 -->
<!-- ============================================ -->
<script>
/**
 * Dropdown Aksi — Toggle tampil/sembunyi menu dropdown.
 */
let dropdownAktif = null;

function toggleDropdown(id) {
    const menu = document.getElementById('menu-' + id);
    // Tutup dropdown lain yang sedang terbuka
    if (dropdownAktif && dropdownAktif !== menu) {
        dropdownAktif.classList.add('hidden', 'opacity-0', 'scale-95');
    }
    menu.classList.toggle('hidden');
    setTimeout(() => {
        menu.classList.toggle('opacity-0');
        menu.classList.toggle('scale-95');
    }, 10);
    dropdownAktif = menu.classList.contains('hidden') ? null : menu;
}

// Tutup dropdown saat klik di luar area dropdown
document.addEventListener('click', function(e) {
    if (dropdownAktif && !e.target.closest('[id^="dropdown-"]')) {
        dropdownAktif.classList.add('hidden', 'opacity-0', 'scale-95');
        dropdownAktif = null;
    }
});

/**
 * Update Status — Kirim perubahan status via Fetch API.
 * Mengirim POST request ke halaman ini sendiri (pengaduan.php).
 */
function updateStatus(id, statusBaru) {
    // Tutup dropdown terlebih dahulu
    if (dropdownAktif) {
        dropdownAktif.classList.add('hidden', 'opacity-0', 'scale-95');
        dropdownAktif = null;
    }

    tampilkanKonfirmasi(`Ubah status pengaduan #${id} menjadi "${statusBaru}"?`, () => {
        fetch('pengaduan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: statusBaru })
        })
        .then(res => res.json())
        .then(data => {
            if (data.sukses) {
                tampilkanToast(data.pesan, 'sukses');
                setTimeout(() => location.reload(), 800);
            } else {
                tampilkanToast(data.pesan || 'Gagal mengubah status.', 'error');
            }
        })
        .catch(() => {
            tampilkanToast('Terjadi kesalahan jaringan.', 'error');
        });
    });
}

/**
 * Modal Foto — Lightbox untuk melihat foto bukti pengaduan.
 */
function bukaModalFoto(pathFoto) {
    const modal = document.getElementById('modalFoto');
    const gambar = document.getElementById('gambarModalFoto');
    gambar.src = pathFoto;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function tutupModalFoto() {
    const modal = document.getElementById('modalFoto');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('gambarModalFoto').src = '';
}

// Tutup modal foto saat klik area gelap (backdrop)
document.getElementById('modalFoto').addEventListener('click', function(e) {
    if (e.target === this) tutupModalFoto();
});

// Tutup modal foto dengan tombol Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupModalFoto();
});
</script>

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
