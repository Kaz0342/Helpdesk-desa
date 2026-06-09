<?php
/**
 * HALAMAN: DETAIL PENGADUAN
 * Menampilkan detail lengkap laporan, foto bukti, dan Peta (OpenStreetMap)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pengaduan.php");
    exit;
}

$id = (int)$_GET['id'];
$db = getKoneksiDatabase();

$stmt = $db->prepare("SELECT * FROM pengaduan WHERE id = :id");
$stmt->execute([':id' => $id]);
$laporan = $stmt->fetch();

if (!$laporan) {
    die("Laporan tidak ditemukan.");
}

$currentPage = 'pengaduan';
$pageTitle   = 'Detail Laporan #' . $id;

require_once __DIR__ . '/components/header.php';

// Format Badge Status
function detailStatusBadge(string $status): string {
    return match ($status) {
        'Menunggu' => 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800',
        'Diproses' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
        'Selesai'  => 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
        'Ditolak'  => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
        default    => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
    };
}
?>

<!-- Include Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<div class="mb-6 flex justify-between items-center">
    <a href="pengaduan.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors">
        <i class="ph ph-arrow-left text-lg"></i>
        Kembali ke Daftar
    </a>
    <span class="px-3 py-1 text-sm font-bold border rounded-full <?= detailStatusBadge($laporan['status']) ?>">
        <?= htmlspecialchars($laporan['status']) ?>
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Informasi Utama & Foto -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 border-b border-gray-100 dark:border-gray-700 pb-4">
                <i class="ph ph-info text-blue-600 dark:text-blue-400 text-xl"></i>
                Informasi Pelapor
            </h3>
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Nomor WA</p>
                    <p class="text-lg font-semibold text-gray-800 dark:text-white">+<?= htmlspecialchars($laporan['nomor_wa']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Nama WhatsApp (Pushname)</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($laporan['nama'] ?? 'Tidak diketahui') ?></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Waktu Lapor</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= date('d F Y, H:i:s', strtotime($laporan['created_at'])) ?></p>
                </div>
                <div class="pt-2">
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Deskripsi Pengaduan</p>
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($laporan['deskripsi']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Foto Bukti -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 border-b border-gray-100 dark:border-gray-700 pb-4">
                <i class="ph ph-image text-blue-600 dark:text-blue-400 text-xl"></i>
                Foto Bukti
            </h3>
            <?php if (!empty($laporan['foto_path']) && $laporan['foto_path'] !== 'foto_dikirim_via_wa'): ?>
                <!-- Fonnte berbayar: URL Media tersedia -->
                <img src="<?= htmlspecialchars($laporan['foto_path']) ?>" alt="Foto Pengaduan" class="w-full h-auto rounded-xl border border-gray-200 dark:border-gray-600 object-cover max-h-96">
            <?php elseif ($laporan['foto_path'] === 'foto_dikirim_via_wa'): ?>
                <!-- Fonnte gratis: Foto dikirim tapi no URL -->
                <div class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 border-dashed text-center">
                    <i class="ph ph-image-square text-4xl text-gray-400 dark:text-gray-500 mb-2"></i>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Foto Dikirim</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Foto dikirim via WA tapi tidak ada URL (Fonnte Free Tier).</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Silakan cek riwayat chat WA Admin secara manual.</p>
                </div>
            <?php else: ?>
                <!-- Tidak ada foto -->
                <div class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 border-dashed text-center">
                    <i class="ph ph-image-broken text-4xl text-gray-400 dark:text-gray-500 mb-2"></i>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Tidak ada foto bukti</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pelapor memilih untuk melewati langkah ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Peta Lokasi -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm h-fit">
        <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 border-b border-gray-100 dark:border-gray-700 pb-4">
            <i class="ph ph-map-pin text-red-500 text-xl"></i>
            Lokasi Pengaduan (GPS)
        </h3>
        
        <?php if (!empty($laporan['lokasi_lat']) && !empty($laporan['lokasi_long'])): ?>
            <div class="mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Koordinat: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded"><?= htmlspecialchars($laporan['lokasi_lat']) ?>, <?= htmlspecialchars($laporan['lokasi_long']) ?></code></p>
            </div>
            <!-- Container Peta -->
            <div id="map" class="w-full h-96 rounded-xl border border-gray-200 dark:border-gray-600 z-0 relative"></div>
            
            <!-- Tombol Navigasi ke G-Maps -->
            <div class="mt-4">
                <a href="https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars($laporan['lokasi_lat']) ?>,<?= htmlspecialchars($laporan['lokasi_long']) ?>" target="_blank" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 font-bold text-sm rounded-xl transition-colors border border-blue-200 dark:border-blue-800">
                    <i class="ph ph-google-logo text-lg"></i>
                    Buka di Google Maps
                </a>
            </div>

            <!-- Include Leaflet JS -->
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const lat = <?= json_encode($laporan['lokasi_lat']) ?>;
                    const lng = <?= json_encode($laporan['lokasi_long']) ?>;
                    
                    // Inisialisasi Peta
                    const map = L.map('map').setView([lat, lng], 16);
                    
                    // Gunakan OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    }).addTo(map);
                    
                    // Tambahkan Pin/Marker
                    const marker = L.marker([lat, lng]).addTo(map);
                    marker.bindPopup("<b>Lokasi Laporan</b><br>Nomor WA: +<?= htmlspecialchars($laporan['nomor_wa']) ?>").openPopup();
                });
            </script>
            
        <?php else: ?>
            <div class="flex flex-col items-center justify-center p-12 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 border-dashed text-center h-96">
                <i class="ph ph-map-pin-line text-5xl text-gray-400 dark:text-gray-500 mb-3"></i>
                <p class="text-base font-bold text-gray-700 dark:text-gray-300">Lokasi Tidak Dibagikan</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-xs mx-auto">Pelapor memilih untuk melewati pengiriman titik lokasi (Shareloc) saat melapor.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/components/footer.php';
?>
