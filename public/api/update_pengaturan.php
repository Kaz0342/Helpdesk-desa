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

if (empty($kunci)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Kunci pengaturan tidak boleh kosong.']);
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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'pesan' => 'Database error: ' . $e->getMessage()]);
}
