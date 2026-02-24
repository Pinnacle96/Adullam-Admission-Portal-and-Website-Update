<?php
require_once 'db.php';

try {
    // 1. Create onboarding_queue table
    $pdo->exec("CREATE TABLE IF NOT EXISTS onboarding_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        last_error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE(user_id)
    )");

    // 2. Add onboarded column to tuition_payment
    // Check if column exists first to avoid error
    $check = $pdo->query("SHOW COLUMNS FROM tuition_payment LIKE 'onboarded'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE tuition_payment ADD COLUMN onboarded INT DEFAULT 0");
    }

    echo "Database updated successfully.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
