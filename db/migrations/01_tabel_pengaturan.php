<?php
/**
 * Migrasi Database: Tambah Tabel Pengaturan
 * Script ini digunakan untuk menambahkan tabel 'pengaturan' secara aman
 * jika belum ada.
 */
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getKoneksiDatabase();
    
    // Buat tabel pengaturan
    $queryCreateTable = "
        CREATE TABLE IF NOT EXISTS pengaturan (
            kunci VARCHAR(50) PRIMARY KEY,
            nilai TEXT NOT NULL
        )
    ";
    $db->exec($queryCreateTable);
    echo "Tabel 'pengaturan' berhasil dibuat atau sudah ada.\n";
    
    // Seed data default untuk auto_reply
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pengaturan WHERE kunci = 'auto_reply'");
    $stmtCheck->execute();
    $exists = $stmtCheck->fetchColumn();
    
    if ($exists == 0) {
        $stmtInsert = $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('auto_reply', '0')");
        $stmtInsert->execute();
        echo "Data default 'auto_reply' = '0' berhasil disisipkan.\n";
    } else {
        echo "Data 'auto_reply' sudah ada di database.\n";
    }
    
} catch (PDOException $e) {
    die("Error Migrasi: " . $e->getMessage() . "\n");
}
