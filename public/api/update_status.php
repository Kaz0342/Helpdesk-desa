<?php
/**
 * API: Update Status Pengaduan (AJAX Endpoint)
 * ==============================================
 * Menerima POST request JSON:
 *   { "id": 1, "status": "DIPROSES" }
 * 
 * Mengembalikan JSON response.
 */
session_start();
header('Content-Type: application/json');

// Auth check: hanya admin yang boleh akses
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
$id = intval($input['id'] ?? 0);
$newStatus = $input['status'] ?? '';
$allowedStatuses = ['MENUNGGU', 'DIPROSES', 'SELESAI', 'DITOLAK'];

if ($id <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID atau status tidak valid.']);
    exit;
}

try {
    $db = getKoneksiDatabase();
    $stmt = $db->prepare("UPDATE pengaduan SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Pengaduan tidak ditemukan.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => "Status berhasil diubah ke {$newStatus}."]);
} catch (Exception $e) {
    error_log('[HELPDESK-DESA] Update status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status.']);
}
