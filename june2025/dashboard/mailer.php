<?php
// mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// mailer.php — Safe autoloader logic for all environments

$autoloadPath = '/home/adullamn/public_html/vendor/autoload.php'; // Absolute path (for cron/outside dashboard)
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php'; // Relative path (for dashboard and web scripts)
}

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    error_log("❌ autoload.php not found. Make sure Composer dependencies are installed.");
    exit("Mailer error: Composer autoloader not found.");
}

function logMailerEvent($message, $type = "INFO")
{
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
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adullamadmissions@gmail.com';
        $mail->Password   = 'lbwo hnjp ylnj hruh'; // ⚠️  Consider storing this in a secure config/env file
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        // Server settings
        // $mail->isSMTP();
        // $mail->Host       = 'mail.adullam.ng';
        // $mail->SMTPAuth   = true;
        // $mail->Username   = 'info@adullam.ng';
        // $mail->Password   = 'adullam@##!!$&';
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        // $mail->Port       = 465;

        // // Recipients
        $mail->setFrom('rcntsonline@gmail.com', 'RCNTS ADULLAM');

        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // For email clients that don't support HTML

        $mail->send();
        logMailerEvent("Mail sent successfully to {$toEmail}", "SUCCESS");
        return true;
    } catch (Exception $e) {
        logMailerEvent("Mailer Error to {$toEmail}: " . $mail->ErrorInfo, "ERROR");
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}





// <?php
// // mailer.php — Universal mailer with fallback-safe Composer autoloading

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// // 🔁 Safe autoloader path detection
// $autoloadPath = '/home/adullamn/public_html/vendor/autoload.php'; // Absolute path (for cron)
// if (!file_exists($autoloadPath)) {
//     $autoloadPath = __DIR__ . '/../vendor/autoload.php'; // Relative path (for web routes)
// }

// if (file_exists($autoloadPath)) {
//     require_once $autoloadPath;
// } else {
//     error_log("❌ autoload.php not found. Make sure Composer dependencies are installed.");
//     exit("Mailer error: Composer autoloader not found.");
// }

// // 📧 Global mail function
// function sendMail($toEmail, $toName, $subject, $body, $fromEmail = 'rcntsonline@gmail.com', $fromName = 'RCNTS ADULLAM')
// {
//     $mail = new PHPMailer(true);

//     try {
//         // 🔐 SMTP settings
//         $mail->isSMTP();
//         $mail->Host       = 'smtp.gmail.com';
//         $mail->SMTPAuth   = true;
//         $mail->Username   = 'rcntsonline@gmail.com';
//         $mail->Password   = 'xdez xgzg mpyr ssyk'; // ⚠️ Consider storing this in a secure config/env file
//         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
//         $mail->Port       = 465;

//         // 📨 Recipients
//         $mail->setFrom($fromEmail, $fromName);
//         $mail->addAddress($toEmail, $toName);

//         // ✍️ Email Content
//         $mail->isHTML(true);
//         $mail->Subject = $subject;
//         $mail->Body    = $body;
//         $mail->AltBody = strip_tags($body); // Fallback plain text

//         $mail->send();
//         return true;
//     } catch (Exception $e) {
//         error_log("❌ PHPMailer Error: " . $mail->ErrorInfo);
//         return false;
//     }
// }

