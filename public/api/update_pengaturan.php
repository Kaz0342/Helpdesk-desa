<?php
/**
 * API: Update Pengaturan
 * Endpoint untuk memperbarui konfigurasi sistem (seperti auto_reply)
 */
require_once __DIR__ . '/../auth.php'; // Hanya admin yang boleh akses
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'pesan' => 'Metode tidak diizinkan.']);
    exit;
}

$kunci = $_POST['kunci'] ?? '';
$nilai = $_POST['nilai'] ?? '';

// Whitelist kunci pengaturan yang diizinkan
// Mencegah penyerang menyuntikkan konfigurasi sembarang
$kunciDiizinkan = ['auto_reply'];

if (empty($kunci) || !in_array($kunci, $kunciDiizinkan, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Kunci pengaturan tidak valid.']);
    exit;
}

try {
    $db = getKoneksiDatabase();
    
    // Gunakan UPSERT (INSERT ON CONFLICT UPDATE) khusus untuk SQLite
    $stmt = $db->prepare("
        INSERT INTO pengaturan (kunci, nilai)
        VALUES (:kunci, :nilai)
        ON CONFLICT(kunci) DO UPDATE SET nilai = excluded.nilai
    ");
    
    $stmt->execute([
        ':kunci' => $kunci,
        ':nilai' => $nilai
    ]);
    
    echo json_encode([
        'status' => 'success',
        'pesan'  => 'Pengaturan berhasil diperbarui.'
    ]);
    
} catch (PDOException $e) {
    // Log detail error ke server, tapi JANGAN kirim ke client
    error_log('[HELPDESK-DESA] Update pengaturan error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'pesan' => 'Terjadi kesalahan server.']);
}
