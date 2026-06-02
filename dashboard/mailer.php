<?php
// mailer.php

// Safe autoloader logic for all environments
$autoloadPath = __DIR__ . '/../vendor/autoload.php'; // Start with relative path (for dashboard and web scripts)

if (!file_exists($autoloadPath)) {
    $autoloadPath = '/home/adullamn/public_html/vendor/autoload.php'; // Absolute path (for cron/outside dashboard on Linux server)
}

$phpMailerAvailable = false;
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    // Check if PHPMailer classes exist
    if (class_exists('PHPMailer\PHPMailer\PHPMailer') && class_exists('PHPMailer\PHPMailer\Exception')) {
        $phpMailerAvailable = true;
    }
}

/**
 * Logs mailer events (success or error) into logs/mailer.log
 */
function logMailerEvent($message, $type = "INFO") {
    // Log to both root logs and dashboard logs
    $logDirs = [
        __DIR__ . '/../logs',
        __DIR__ . '/logs'
    ];
    
    foreach ($logDirs as $logDir) {
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/mailer.log';
        $entry = "[" . date("Y-m-d H:i:s") . "] [{$type}] " . $message . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
    
    // Also log to PHP error log
    error_log("[MAILER] [{$type}] {$message}");
}

function sendMail($toEmail, $toName, $subject, $body, $attachmentPath = null, $attachmentName = null, $fromEmail = 'adullamadmissions@gmail.com', $fromName = 'RCNTS ADULLAM') {
    global $phpMailerAvailable;
    
    if (!$phpMailerAvailable) {
        logMailerEvent("PHPMailer not available - skipping email to {$toEmail}", "WARNING");
        return ['success' => false, 'error' => 'PHPMailer not available'];
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // 🔐 SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adullamadmissions@gmail.com';
        $mail->Password   = 'lbwo hnjp ylnj hruh'; // ⚠️ Move this to a secure config/env file
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        // Attachments
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath, $attachmentName);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();

        // ✅ Log success
        logMailerEvent("Mail sent successfully to {$toEmail}", "SUCCESS");
        return true;

    } catch (Exception $e) {
        // ❌ Log error
        logMailerEvent("Mailer Error to {$toEmail}: " . $e->getMessage(), "ERROR");
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
