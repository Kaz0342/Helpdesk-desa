<?php
/**
 * ============================================================
 * WEBHOOK API — Endpoint Penerima Pesan WhatsApp (Fonnte)
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * File ini menerima payload dari Fonnte WhatsApp Gateway
 * setiap kali ada pesan masuk. Alur percakapan dikendalikan
 * oleh State Machine yang menyimpan posisi pengguna di tabel
 * sesi_chat.
 *
 * Alur State Machine:
 * menu_utama -> lapor_deskripsi -> lapor_foto -> lapor_lokasi
 *
 * Format Payload Masuk (dari Fonnte):
 * POST fields:
 *   sender  = "6281234567890"
 *   message = "Teks pesan dari pengguna"
 *   url     = "https://..." (opsional, URL media jika user kirim gambar)
 *
 * Perintah Global (berlaku di semua state):
 * - "BATAL" : Membatalkan proses, menghapus sesi percakapan
 * - "HKI"   : Menampilkan informasi Hak Kekayaan Intelektual
 * ============================================================
 */

// ============================================================
// KONFIGURASI FONNTE API
// ============================================================
// Ganti value di bawah ini dengan token dari dashboard Fonnte.
// Dapatkan token di: https://md.fonnte.com/
// ============================================================
$fonnte_token = "Crxr2Uy5NfFYX1TtKDsy";

// ============================================================
// TOKEN AUTENTIKASI WEBHOOK
// ============================================================
// Secret key untuk memastikan hanya Fonnte yang bisa nge-POST
// ke endpoint ini. Daftarkan URL webhook di Fonnte dengan
// parameter ?token=<nilai di bawah>.
//
// Contoh URL di Fonnte:
//   https://domain-lo.com/api/webhook.php?token=DESA_HELPDESK_2026
//
// Tanpa token ini, siapapun bisa spam database lo pake Postman.
// ============================================================
$webhook_secret = "DESA_HELPDESK_2026";

if (!isset($_GET['token']) || $_GET['token'] !== $webhook_secret) {
    http_response_code(403);
    error_log("[HELPDESK-BOT] AKSES DITOLAK — Token webhook tidak valid atau tidak ada.");
    echo json_encode(['status' => 'error', 'pesan' => 'Akses ditolak. Token tidak valid.']);
    exit;
}

// Muat koneksi database
require_once __DIR__ . '/../config/database.php';

// ============================================================
// FUNGSI UTILITAS
// ============================================================

/**
 * Mengirim balasan ke pengguna WhatsApp via Fonnte API.
 *
 * Endpoint : https://api.fonnte.com/send
 * Method   : POST
 * Header   : Authorization: <token>
 * Body     : target=<nomor_wa>&message=<pesan>
 *
 * @param string $nomor_wa Nomor WhatsApp tujuan (format 628xxx)
 * @param string $pesan    Isi pesan balasan yang akan dikirim
 * @return void
 */
function kirimBalasanWA(string $nomor_wa, string $pesan): void
{
    // Akses token Fonnte dari variabel global
    global $fonnte_token;

    // Inisialisasi cURL
    $ch = curl_init();

    // Konfigurasi cURL untuk Fonnte API
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . $fonnte_token,
        ],
        CURLOPT_POSTFIELDS     => [
            'target'  => $nomor_wa,
            'message' => $pesan,
        ],
        // Timeout 30 detik agar tidak menggantung selamanya
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        // Bypass verifikasi SSL — fix untuk Laragon Windows
        // yang path cacert.pem-nya sering tidak ditemukan.
        // CATATAN: Di server produksi, ganti ini dengan path
        //          ke file cacert.pem yang valid.
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    // Eksekusi request
    $response = curl_exec($ch);

    // Tangkap error cURL jika ada
    if (curl_errno($ch)) {
        error_log('[HELPDESK-BOT] cURL Error: ' . curl_error($ch));
    } else {
        // Log respons Fonnte untuk debugging
        error_log("[HELPDESK-BOT] Kepada: {$nomor_wa} | Respons Fonnte: {$response}");
    }

    curl_close($ch);
}

/**
 * Mengambil atau membuat sesi percakapan untuk nomor WA tertentu.
 *
 * Jika nomor WA belum memiliki sesi, akan dibuatkan sesi baru
 * dengan state 'menu_utama' dan data_temp kosong (JSON '{}').
 * Jika sudah ada, waktu aktivitas terakhir akan diperbarui.
 *
 * @param PDO    $db       Instance koneksi database
 * @param string $nomor_wa Nomor WhatsApp pengguna
 * @return array Data sesi: ['nomor_wa', 'state', 'data_temp', 'last_activity']
 */
function ambilAtauBuatSesi(PDO $db, string $nomor_wa): array
{
    // Cari sesi yang sudah ada berdasarkan nomor WA
    $stmt = $db->prepare("SELECT * FROM sesi_chat WHERE nomor_wa = :nomor_wa");
    $stmt->execute([':nomor_wa' => $nomor_wa]);
    $sesi = $stmt->fetch();

    if ($sesi) {
        // Sesi ditemukan — perbarui waktu aktivitas terakhir
        $stmtUpdate = $db->prepare("
            UPDATE sesi_chat SET last_activity = CURRENT_TIMESTAMP
            WHERE nomor_wa = :nomor_wa
        ");
        $stmtUpdate->execute([':nomor_wa' => $nomor_wa]);
        return $sesi;
    }

    // Sesi belum ada — buat sesi baru dengan state awal
    $stmtInsert = $db->prepare("
        INSERT INTO sesi_chat (nomor_wa, state, data_temp)
        VALUES (:nomor_wa, 'menu_utama', '{}')
    ");
    $stmtInsert->execute([':nomor_wa' => $nomor_wa]);

    return [
        'nomor_wa'      => $nomor_wa,
        'state'         => 'menu_utama',
        'data_temp'     => '{}',
        'last_activity' => date('Y-m-d H:i:s'),
    ];
}

/**
 * Memperbarui state dan data_temp sesi percakapan pengguna.
 *
 * @param PDO    $db        Instance koneksi database
 * @param string $nomor_wa  Nomor WhatsApp pengguna
 * @param string $stateBaru State baru yang akan disimpan
 * @param array  $dataTemp  Array asosiatif data sementara (akan di-encode ke JSON)
 * @return void
 */
function perbaruiSesi(PDO $db, string $nomor_wa, string $stateBaru, array $dataTemp): void
{
    $stmt = $db->prepare("
        UPDATE sesi_chat
        SET state = :state, data_temp = :data_temp, last_activity = CURRENT_TIMESTAMP
        WHERE nomor_wa = :nomor_wa
    ");
    $stmt->execute([
        ':state'     => $stateBaru,
        ':data_temp' => json_encode($dataTemp, JSON_UNESCAPED_UNICODE),
        ':nomor_wa'  => $nomor_wa,
    ]);
}

/**
 * Menghapus sesi percakapan pengguna (reset ke awal).
 *
 * Dipanggil ketika pengguna mengetik "BATAL" atau ketika
 * proses pelaporan telah selesai (data sudah disimpan).
 *
 * @param PDO    $db       Instance koneksi database
 * @param string $nomor_wa Nomor WhatsApp pengguna
 * @return void
 */
function hapusSesi(PDO $db, string $nomor_wa): void
{
    $stmt = $db->prepare("DELETE FROM sesi_chat WHERE nomor_wa = :nomor_wa");
    $stmt->execute([':nomor_wa' => $nomor_wa]);
}

/**
 * Menyimpan data pengaduan yang sudah lengkap ke tabel pengaduan.
 *
 * Fungsi ini dipanggil setelah pengguna menyelesaikan seluruh
 * tahapan pelaporan (deskripsi, foto, lokasi).
 *
 * @param PDO    $db       Instance koneksi database
 * @param string $nomor_wa Nomor WhatsApp pelapor
 * @param array  $data     Data lengkap pengaduan dari data_temp
 * @return int             ID pengaduan yang baru disimpan
 */
function simpanPengaduan(PDO $db, string $nomor_wa, array $data): int
{
    $stmt = $db->prepare("
        INSERT INTO pengaduan (nomor_wa, nama, deskripsi, foto_path, lokasi_lat, lokasi_long)
        VALUES (:nomor_wa, :nama, :deskripsi, :foto_path, :lokasi_lat, :lokasi_long)
    ");
    $stmt->execute([
        ':nomor_wa'    => $nomor_wa,
        ':nama'        => $data['nama'] ?? null,
        ':deskripsi'   => $data['deskripsi'] ?? '',
        ':foto_path'   => $data['foto_path'] ?? null,
        ':lokasi_lat'  => $data['lokasi_lat'] ?? null,
        ':lokasi_long' => $data['lokasi_long'] ?? null,
    ]);

    return (int) $db->lastInsertId();
}

// ============================================================
// PROSES UTAMA — Penerimaan dan Pemrosesan Pesan
// ============================================================

// Hanya terima request dengan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'pesan' => 'Metode tidak diizinkan. Gunakan POST.']);
    exit;
}

// ============================================================
// DEBUG LOGGING — Catat SEMUA data yang masuk ke webhook
// ============================================================
// Log ini membantu memverifikasi apakah Fonnte benar-benar
// mengirim data, dan dalam format apa data tersebut dikirim.
// Hapus blok ini setelah bot berjalan stabil di produksi.
// ============================================================
$rawBody = file_get_contents('php://input');
error_log("[HELPDESK-BOT] === WEBHOOK REQUEST MASUK ===");
error_log("[HELPDESK-BOT] Method: " . $_SERVER['REQUEST_METHOD']);
error_log("[HELPDESK-BOT] Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'TIDAK ADA'));
error_log("[HELPDESK-BOT] POST data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
error_log("[HELPDESK-BOT] Raw body: " . $rawBody);
error_log("[HELPDESK-BOT] ===========================");

// ============================================================
// PARSING PAYLOAD FONNTE
// ============================================================
// Fonnte mengirim data sebagai JSON body (application/json).
// Parameter utama yang dikirim:
//   - sender   : Nomor WA pengirim (format 628xxx)
//   - message  : Isi pesan teks ("non-text message" jika media)
//   - url      : URL media jika paket berbayar (kosong di free)
//   - location : Koordinat "lat,long" jika user share location
//   - name     : Nama kontak pengirim (pushname)
// ============================================================
$payload = json_decode($rawBody, true);

// Fallback: kalau JSON gagal di-parse, coba baca dari $_POST
if (!is_array($payload) || empty($payload)) {
    $payload = $_POST;
}

$nomorWA      = trim($payload['sender'] ?? '');
$pesan        = trim($payload['message'] ?? '');
$media_url    = trim($payload['url'] ?? '');
$lokasiShare  = trim($payload['location'] ?? '');
$namaPelapor  = trim($payload['pushname'] ?? $payload['name'] ?? '');

// Deteksi apakah user mengirim media (gambar/video/dokumen)
// Fonnte free tier: url kosong, tapi message = "non-text message"
$adalahMedia = (!empty($media_url) || $pesan === 'non-text message');

// Validasi: sender wajib ada, message boleh kosong jika media
if (empty($nomorWA)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Parameter "sender" wajib diisi.']);
    exit;
}

// Kalau bukan media dan pesan kosong, tolak
if (!$adalahMedia && empty($pesan)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Parameter "message" wajib diisi.']);
    exit;
}

// Dapatkan koneksi database
$db = getKoneksiDatabase();

// ============================================================
// CEK STATUS AUTO-REPLY
// ============================================================
$stmtConfig = $db->prepare("SELECT nilai FROM pengaturan WHERE kunci = 'auto_reply'");
$stmtConfig->execute();
$autoReplyEnabled = $stmtConfig->fetchColumn();

// Jika auto-reply dimatikan (0), hentikan eksekusi script tanpa membalas
if ($autoReplyEnabled === '0') {
    error_log("[HELPDESK-BOT] Pesan dari {$nomorWA} diabaikan (Auto-Reply OFF).");
    echo json_encode(['status' => 'ok', 'pesan' => 'Auto-reply sedang nonaktif.']);
    exit;
}

// ============================================================
// PERINTAH GLOBAL — Berlaku di semua state
// ============================================================
// Dicek sebelum masuk State Machine. strtoupper() agar tidak
// case-sensitive (user bisa ketik "batal", "Batal", "BATAL").
// ============================================================
$pesanUpper = strtoupper($pesan);

// Perintah "BATAL" — Membatalkan proses dan menghapus sesi
if ($pesanUpper === 'BATAL') {
    hapusSesi($db, $nomorWA);
    kirimBalasanWA($nomorWA,
        "Proses dibatalkan.\n\nKetik MENU kapan saja untuk memulai kembali."
    );
    echo json_encode(['status' => 'ok', 'aksi' => 'sesi_dibatalkan']);
    exit;
}

// Perintah "HKI" — Menampilkan informasi Hak Kekayaan Intelektual
if ($pesanUpper === 'HKI') {
    kirimBalasanWA($nomorWA,
        "Sistem Helpdesk Desa v1.0. Hak Cipta Benedict Saviola Pradana."
    );
    echo json_encode(['status' => 'ok', 'aksi' => 'info_hki']);
    exit;
}

// ============================================================
// STATE MACHINE — Alur Percakapan Chatbot
// ============================================================
// Ambil atau buat sesi percakapan untuk nomor WA ini.
// Kemudian proses pesan berdasarkan state saat ini.
// ============================================================
$sesi     = ambilAtauBuatSesi($db, $nomorWA);
$stateNow = $sesi['state'];
$dataTemp = json_decode($sesi['data_temp'], true) ?: [];

switch ($stateNow) {

    // ========================================================
    // STATE: menu_utama
    // ========================================================
    // State awal. Pengguna ditunjukkan menu utama dan diminta
    // memilih layanan yang tersedia.
    //
    // Input yang diterima:
    // - "1" atau "LAPOR" -> Masuk ke alur pelaporan pengaduan
    // - "MENU"           -> Tampilkan ulang menu utama
    // - Lainnya          -> Tampilkan menu utama
    // ========================================================
    case 'menu_utama':
        if ($pesanUpper === '1' || $pesanUpper === 'LAPOR') {
            // Pindah ke state berikutnya: input deskripsi
            // Simpan nama pelapor dari WhatsApp pushname
            perbaruiSesi($db, $nomorWA, 'lapor_deskripsi', ['nama' => $namaPelapor]);
            kirimBalasanWA($nomorWA,
                "LAPOR PENGADUAN\n\n" .
                "Langkah 1 dari 3:\n" .
                "Silakan tuliskan deskripsi pengaduan Anda secara lengkap.\n\n" .
                "Contoh: Jalan di RT 03 rusak parah dan berlubang besar.\n\n" .
                "Ketik BATAL untuk membatalkan."
            );
        } else {
            // Pesan tidak dikenali atau MENU — tampilkan menu utama
            kirimBalasanWA($nomorWA,
                "HELPDESK DESA\n\n" .
                "Selamat datang di layanan pengaduan desa.\n" .
                "Silakan pilih menu di bawah ini:\n\n" .
                "1. LAPOR - Buat laporan pengaduan baru\n\n" .
                "Ketik angka atau kata kunci untuk memilih."
            );
        }
        break;

    // ========================================================
    // STATE: lapor_deskripsi
    // ========================================================
    // Pengguna diminta menuliskan deskripsi pengaduan mereka.
    // Teks yang dikirim akan disimpan ke data_temp sebagai
    // nilai 'deskripsi', kemudian lanjut ke state lapor_foto.
    //
    // Validasi: deskripsi minimal 10 karakter agar cukup
    // informatif untuk ditindaklanjuti oleh petugas.
    // ========================================================
    case 'lapor_deskripsi':
        // Validasi panjang minimal deskripsi
        if (mb_strlen($pesan) < 10) {
            kirimBalasanWA($nomorWA,
                "Deskripsi terlalu singkat.\n\n" .
                "Mohon tuliskan pengaduan Anda secara lengkap (minimal 10 karakter).\n\n" .
                "Ketik BATAL untuk membatalkan."
            );
            break;
        }

        // Simpan deskripsi ke data_temp dan pindah ke state berikutnya
        $dataTemp['deskripsi'] = $pesan;
        perbaruiSesi($db, $nomorWA, 'lapor_foto', $dataTemp);

        kirimBalasanWA($nomorWA,
            "Deskripsi diterima.\n\n" .
            "Langkah 2 dari 3: Foto Bukti\n\n" .
            "Silakan kirimkan foto bukti pengaduan Anda.\n\n" .
            "Jika tidak ada foto, ketik LEWATI untuk melewati langkah ini.\n\n" .
            "Ketik BATAL untuk membatalkan."
        );
        break;

    // ========================================================
    // STATE: lapor_foto
    // ========================================================
    // Pengguna diminta mengirimkan foto bukti pengaduan.
    //
    // Deteksi gambar:
    // - Fonnte BERBAYAR: field 'url' berisi link media
    // - Fonnte GRATIS : field 'url' kosong, tapi 'message'
    //   bernilai "non-text message" → kita simpan catatan
    //   bahwa foto sudah dikirim (tanpa URL langsung).
    //
    // Input yang diterima:
    // - Media terdeteksi ($adalahMedia) -> Catat foto
    // - "LEWATI" -> Melewati foto, lanjut ke lokasi
    // - Teks lainnya -> Validasi error, minta ulang
    // ========================================================
    case 'lapor_foto':
        $pesanLokasi = "Langkah 3 dari 3: Lokasi Kejadian\n\n" .
            "Silakan *bagikan lokasi* (Share Location) dari WhatsApp Anda.\n\n" .
            "Caranya:\n" .
            "📎 Klik ikon lampiran (+) → Lokasi → Kirim Lokasi Anda Saat Ini\n\n" .
            "Atau ketik LEWATI jika tidak tahu lokasinya.\n\n" .
            "Ketik BATAL untuk membatalkan.";

        if ($adalahMedia) {
            // Pengguna mengirim gambar
            // Validasi URL media: pastikan format URL valid (anti-injection)
            // Jika URL tersedia DAN valid (paket berbayar), simpan URL
            // Jika URL ada tapi format ngaco, tetap catat sebagai "dikirim"
            // Jika tidak ada URL sama sekali (free tier), simpan marker
            if (!empty($media_url) && filter_var($media_url, FILTER_VALIDATE_URL)) {
                $dataTemp['foto_path'] = $media_url;
            } else {
                $dataTemp['foto_path'] = 'foto_dikirim_via_wa';
            }
            perbaruiSesi($db, $nomorWA, 'lapor_lokasi', $dataTemp);

            kirimBalasanWA($nomorWA,
                "✅ Foto diterima.\n\n" . $pesanLokasi
            );
        } elseif ($pesanUpper === 'LEWATI') {
            // Pengguna melewati foto — set null
            $dataTemp['foto_path'] = null;
            perbaruiSesi($db, $nomorWA, 'lapor_lokasi', $dataTemp);

            kirimBalasanWA($nomorWA,
                "Foto dilewati.\n\n" . $pesanLokasi
            );
        } else {
            // Teks biasa tanpa media — minta ulang
            kirimBalasanWA($nomorWA,
                "Mohon kirimkan *foto bukti* pengaduan, atau ketik LEWATI jika tidak ada."
            );
        }
        break;

    // ========================================================
    // STATE: lapor_lokasi
    // ========================================================
    // State terakhir dalam alur pelaporan.
    //
    // Menerima lokasi dari 3 sumber:
    // 1. Share Location WA → Fonnte kirim di field 'location'
    //    Format: "lat,long" (contoh: "-6.200000,106.816666")
    // 2. Ketik manual       → "lat,long" di field 'message'
    // 3. "LEWATI"           → Simpan tanpa lokasi
    //
    // Setelah lokasi diterima (atau dilewati), semua data dari
    // data_temp akan di-INSERT ke tabel pengaduan, dan sesi
    // percakapan akan dihapus (kembali ke awal).
    // ========================================================
    case 'lapor_lokasi':
        if ($pesanUpper === 'LEWATI') {
            // Pengguna melewati lokasi — set null
            $dataTemp['lokasi_lat']  = null;
            $dataTemp['lokasi_long'] = null;
        } else {
            // Prioritas 1: Cek field 'location' dari Share Location WA
            // Prioritas 2: Cek field 'message' untuk input manual
            $sumberLokasi = !empty($lokasiShare) ? $lokasiShare : $pesan;

            // Coba parse format "latitude,longitude"
            $koordinat = explode(',', $sumberLokasi);

            if (count($koordinat) === 2) {
                $lat  = trim($koordinat[0]);
                $long = trim($koordinat[1]);

                // Validasi bahwa kedua nilai adalah angka yang valid
                if (is_numeric($lat) && is_numeric($long)) {
                    $latFloat  = (float) $lat;
                    $longFloat = (float) $long;

                    // Validasi rentang koordinat Planet Bumi:
                    // Latitude  : -90  s/d  90  (Kutub Selatan ke Kutub Utara)
                    // Longitude : -180 s/d  180 (Barat ke Timur)
                    // Kalo di luar range ini, berarti koordinatnya di luar angkasa.
                    if ($latFloat >= -90 && $latFloat <= 90 && $longFloat >= -180 && $longFloat <= 180) {
                        $dataTemp['lokasi_lat']  = $latFloat;
                        $dataTemp['lokasi_long'] = $longFloat;
                    } else {
                        // Koordinat di luar jangkauan Bumi — minta ulang
                        kirimBalasanWA($nomorWA,
                            "Koordinat tidak valid (di luar jangkauan peta).\n\n" .
                            "Silakan *bagikan lokasi* (Share Location) dari WhatsApp.\n\n" .
                            "📎 Klik ikon lampiran (+) → Lokasi → Kirim Lokasi Anda Saat Ini\n\n" .
                            "Atau ketik LEWATI untuk melewati."
                        );
                        break;
                    }
                } else {
                    // Format salah (bukan angka) — minta ulang
                    kirimBalasanWA($nomorWA,
                        "Lokasi tidak valid.\n\n" .
                        "Silakan *bagikan lokasi* (Share Location) dari WhatsApp.\n\n" .
                        "📎 Klik ikon lampiran (+) → Lokasi → Kirim Lokasi Anda Saat Ini\n\n" .
                        "Atau ketik LEWATI untuk melewati."
                    );
                    break;
                }
            } else {
                // Bukan format koordinat — minta ulang
                kirimBalasanWA($nomorWA,
                    "Mohon *bagikan lokasi* (Share Location) dari WhatsApp.\n\n" .
                    "📎 Klik ikon lampiran (+) → Lokasi → Kirim Lokasi Anda Saat Ini\n\n" .
                    "Atau ketik LEWATI untuk melewati."
                );
                break;
            }
        }

        // ====================================================
        // FINALISASI: Simpan pengaduan ke database
        // ====================================================
        // Semua data sudah terkumpul di $dataTemp.
        // INSERT ke tabel pengaduan, lalu hapus sesi.
        // foto_path bisa berisi URL dari Fonnte atau null.
        // ====================================================
        $idPengaduan = simpanPengaduan($db, $nomorWA, $dataTemp);

        // Hapus sesi percakapan (kembali ke menu_utama saat pesan berikutnya)
        hapusSesi($db, $nomorWA);

        // Siapkan teks foto dan lokasi untuk ringkasan
        $infoFoto   = !empty($dataTemp['foto_path']) ? 'Terlampir' : 'Tidak ada';
        $infoLokasi = !empty($dataTemp['lokasi_lat'])
            ? $dataTemp['lokasi_lat'] . ',' . $dataTemp['lokasi_long']
            : 'Tidak tersedia';

        // Potong deskripsi jika terlalu panjang untuk ringkasan
        $deskripsiSingkat = mb_strimwidth($dataTemp['deskripsi'], 0, 80, '...');

        // Kirim konfirmasi ke pengguna
        kirimBalasanWA($nomorWA,
            "PENGADUAN BERHASIL DIKIRIM!\n\n" .
            "No. Laporan: #{$idPengaduan}\n" .
            "Deskripsi: {$deskripsiSingkat}\n" .
            "Foto: {$infoFoto}\n" .
            "Lokasi: {$infoLokasi}\n" .
            "Status: Menunggu\n\n" .
            "Terima kasih telah melapor. Petugas akan menindaklanjuti pengaduan Anda.\n\n" .
            "Ketik MENU untuk kembali ke menu utama."
        );
        break;

    // ========================================================
    // DEFAULT — State tidak dikenali
    // ========================================================
    // Sebagai fallback, hapus sesi yang rusak dan kembalikan
    // pengguna ke menu utama.
    // ========================================================
    default:
        hapusSesi($db, $nomorWA);
        kirimBalasanWA($nomorWA,
            "Terjadi kesalahan pada sesi Anda.\n\n" .
            "Sesi telah direset. Ketik MENU untuk memulai kembali."
        );
        break;
}

// Kirim respons sukses ke Fonnte Gateway
http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'state'  => $stateNow,
    'pesan'  => 'Pesan berhasil diproses.',
]);

// ============================================================
// IDENTIFIKASI HAK KEKAYAAN INTELEKTUAL (HKI)
// ============================================================
// Kode Sertifikasi  : HKI-EC65-2026-BSP
// Pengembang        : Benedict Saviola Pradana
// Institusi         : Universitas Atma Jaya Yogyakarta — Program Studi Sistem Informasi
// Tahun Pembuatan   : 2026
// Hak Cipta         : Dilindungi Undang-Undang Republik Indonesia
//                     No. 28 Tahun 2014 tentang Hak Cipta
// Deskripsi         : Modul Webhook Chatbot WhatsApp untuk Sistem
//                     Helpdesk Pelayanan Publik Pemerintahan Desa.
// ============================================================
