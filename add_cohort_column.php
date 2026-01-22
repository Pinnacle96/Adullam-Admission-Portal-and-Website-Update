<?php
require 'dashboard/db.php';

try {
    // 1. Add cohort column
    $pdo->exec("ALTER TABLE applications ADD COLUMN cohort VARCHAR(50) DEFAULT NULL");
    echo "Added 'cohort' column to applications table.<br>";

    // 2. Set default cohort for existing records (e.g., based on year)
    // For simplicity, let's assume everything before now is '2024/2025'
    $pdo->exec("UPDATE applications SET cohort = '2024/2025' WHERE cohort IS NULL");
    echo "Updated existing records to cohort '2024/2025'.<br>";

    // 3. Add current_cohort setting if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = 'current_cohort'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO settings (`key`, `value`) VALUES ('current_cohort', '2024/2025')");
        echo "Inserted default 'current_cohort' setting.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>