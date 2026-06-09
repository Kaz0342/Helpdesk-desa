<?php
require_once __DIR__ . '/config/database.php';
$db = getKoneksiDatabase();

echo "=== SESI CHAT (Aktif) ===\n";
$sesi = $db->query('SELECT * FROM sesi_chat ORDER BY last_activity DESC')->fetchAll();
if (empty($sesi)) {
    echo "(kosong)\n";
} else {
    foreach ($sesi as $s) {
        echo "  WA: {$s['nomor_wa']} | State: {$s['state']} | Data: {$s['data_temp']} | Last: {$s['last_activity']}\n";
    }
}

echo "\n=== 10 PENGADUAN TERBARU ===\n";
$data = $db->query('SELECT id, nomor_wa, status, created_at, SUBSTR(deskripsi, 1, 50) as desk FROM pengaduan ORDER BY created_at DESC LIMIT 10')->fetchAll();
if (empty($data)) {
    echo "(kosong)\n";
} else {
    foreach ($data as $d) {
        echo "  #{$d['id']} | WA: {$d['nomor_wa']} | Status: {$d['status']} | {$d['created_at']} | {$d['desk']}...\n";
    }
}

echo "\n=== CEK ERROR LOG TERAKHIR (HELPDESK-BOT) ===\n";
// Cek apakah ada log terbaru dari webhook
$logFile = ini_get('error_log');
echo "Log file: " . ($logFile ?: '(default PHP log)') . "\n";
