<?php
/**
 * Adullam Broadcast Email Cron Script
 * Run this script via cron every 2-5 minutes
 * Command: /usr/bin/php /home/u499616432/domains/adullam.ng/public_html/dashboard/broadcast_cron.php
 */

// Define project root for CLI environment
define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/dashboard/db.php';
require_once PROJECT_ROOT . '/dashboard/mailer.php';

// Logging Function
function logBroadcast($message) {
    $logDir = PROJECT_ROOT . '/dashboard/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/broadcast_cron.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo $message . "\n";
}

// Ensure the table structure is ready
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        to_email VARCHAR(255) NOT NULL,
        to_name VARCHAR(255),
        subject VARCHAR(255),
        body TEXT,
        from_email VARCHAR(255),
        from_name VARCHAR(255),
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        last_error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Check and add columns if they were missing from an existing table
    $pdo->exec("ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS status ENUM('pending', 'sent', 'failed') DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS attempts INT DEFAULT 0");
    $pdo->exec("ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS last_error TEXT");
    $pdo->exec("ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch (Exception $e) {
    // Log if something went wrong but continue
}

// Fetch up to 30 pending emails to process per run
$stmt = $pdo->prepare("SELECT * FROM email_queue WHERE (status = 'pending' OR status IS NULL) AND attempts < 3 LIMIT 30");
$stmt->execute();
$queue = $stmt->fetchAll();

if (empty($queue)) {
    logBroadcast("No pending broadcast emails found.");
    exit;
}

logBroadcast("Processing " . count($queue) . " pending broadcast emails...");

foreach ($queue as $item) {
    $queueId = $item['id'];
    $toEmail = $item['to_email'];
    $toName = $item['to_name'];
    $subject = $item['subject'];
    $body = $item['body'];
    $fromEmail = $item['from_email'];
    $fromName = $item['from_name'];

    // Send the email
    $success = sendMail($toEmail, $toName, $subject, $body, null, null, $fromEmail, $fromName);

    if ($success) {
        $pdo->prepare("UPDATE email_queue SET status = 'sent', updated_at = NOW() WHERE id = ?")->execute([$queueId]);
        logBroadcast("✅ Sent to {$toEmail}");
    } else {
        $pdo->prepare("UPDATE email_queue SET status = 'failed', attempts = attempts + 1, updated_at = NOW() WHERE id = ?")->execute([$queueId]);
        logBroadcast("❌ Failed for {$toEmail}");
    }
}
logBroadcast("Batch broadcast completed.");
