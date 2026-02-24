<?php
// mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Safe autoloader logic for all environments
$autoloadPath = '/home/adullamn/public_html/vendor/autoload.php'; // Absolute path (for cron/outside dashboard)
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php'; // Relative path (for dashboard and web scripts)
}

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    logMailerEvent("❌ autoload.php not found. Make sure Composer dependencies are installed.", "ERROR");
    exit("Mailer error: Composer autoloader not found.");
}

/**
 * Logs mailer events (success or error) into logs/mailer.log
 */
function logMailerEvent($message, $type = "INFO") {
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/mailer.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $entry = "[" . date("Y-m-d H:i:s") . "] [$type] " . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

function sendMail($toEmail, $toName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // 🔐 SMTP settings
        // $mail->isSMTP();
        // $mail->Host       = 'smtp.gmail.com';
        // $mail->SMTPAuth   = true;
        // $mail->Username   = 'rcntsonline@gmail.com';
        // $mail->Password   = 'cciy mzlr ehtl kjiw'; // ⚠️ Move this to a secure config/env file
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        // $mail->Port       = 465;

        // // Recipients
        // $mail->setFrom('rcntsonline@gmail.com', 'RCNTS ADULLAM');
        // $mail->addAddress($toEmail, $toName);


        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adullamadmissions@gmail.com';
        $mail->Password   = 'lbwo hnjp ylnj hruh'; // ⚠️ Move this to a secure config/env file
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('adullamadmissions@gmail.com', 'RCNTS ADULLAM');
        $mail->addAddress($toEmail, $toName);
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
        logMailerEvent("Mailer Error to {$toEmail}: " . $mail->ErrorInfo, "ERROR");
        return false;
    }
}
