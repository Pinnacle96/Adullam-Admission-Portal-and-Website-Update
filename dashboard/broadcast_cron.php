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
    // Add 'attempts' if missing
    $check = $pdo->query("SHOW COLUMNS FROM email_queue LIKE 'attempts'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE email_queue ADD COLUMN attempts INT DEFAULT 0");
    }
    
    // Add 'last_error' if missing (it has error_message, let's keep it consistent)
    $check = $pdo->query("SHOW COLUMNS FROM email_queue LIKE 'last_error'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE email_queue ADD COLUMN last_error TEXT");
    }

    // Ensure status column exists and has correct enum
    $check = $pdo->query("SHOW COLUMNS FROM email_queue LIKE 'status'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE email_queue ADD COLUMN status ENUM('pending', 'sent', 'failed') DEFAULT 'pending'");
    }

} catch (Exception $e) {
    logBroadcast("Schema Update Error: " . $e->getMessage());
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
        // Use 'sent_at' if it exists
        $pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$queueId]);
        logBroadcast("✅ Sent to {$toEmail}");
    } else {
        // Use 'error_message' if it exists, otherwise last_error
        $pdo->prepare("UPDATE email_queue SET status = 'failed', attempts = attempts + 1, error_message = 'Failed to send' WHERE id = ?")->execute([$queueId]);
        logBroadcast("❌ Failed for {$toEmail}");
    }
}
logBroadcast("Batch broadcast completed.");
