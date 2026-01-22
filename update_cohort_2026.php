<?php
require 'db.php';
try {
    $pdo->exec("UPDATE settings SET value = 'January 2026' WHERE `key` = 'current_cohort'");
    $pdo->exec("UPDATE applications SET cohort = 'January 2026' WHERE cohort = 'January 2025'");
    echo "Cohort updated to January 2026 successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
