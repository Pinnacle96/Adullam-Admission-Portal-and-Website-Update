<?php
$host = 'localhost';
$db   = 'u499616432_adullamn_cams'; // Assuming this is the local DB name too, based on the file.
// If local wamp, often the DB name is simpler, but let's try.
// If it fails, I'll list databases.

$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // If successful, run updates
    $pdo->exec("UPDATE settings SET value = 'January 2026' WHERE `key` = 'current_cohort'");
    // Check if table exists before update
    $pdo->exec("UPDATE applications SET cohort = 'January 2026' WHERE cohort = 'January 2025'");
    
    echo "Cohort updated to January 2026 successfully (Local Root).\n";
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // If DB name is wrong, we might need to find it.
}
