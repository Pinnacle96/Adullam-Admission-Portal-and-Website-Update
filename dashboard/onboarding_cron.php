<?php
/**
 * Adullam Onboarding Cron Script
 * Run this script via cron every 5-10 minutes
 * Command: php /path/to/dashboard/onboarding_cron.php
 */

// Define project root for CLI environment
define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/dashboard/db.php';
require_once PROJECT_ROOT . '/dashboard/mailer.php';

// Logging Function
function logOnboarding($message) {
    $logDir = PROJECT_ROOT . '/dashboard/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/onboarding_cron.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo $message . "\n";
}

// Fetch up to 20 pending emails to process per run to avoid timeout
$stmt = $pdo->prepare("SELECT oq.*, u.first_name, u.last_name, u.email, d.program 
                       FROM onboarding_queue oq
                       JOIN users u ON oq.user_id = u.id
                       JOIN application_details d ON oq.user_id = d.user_id
                       WHERE oq.status = 'pending' AND oq.attempts < 3
                       LIMIT 20");
$stmt->execute();
$queue = $stmt->fetchAll();

if (empty($queue)) {
    logOnboarding("No pending onboarding emails found.");
    exit;
}

logOnboarding("Processing " . count($queue) . " pending emails...");

// WhatsApp Group Links Map
$groupLinks = [
    'MA' => 'https://chat.whatsapp.com/BTVMD4cv4MfBCLmBO0uqLr',
    'PGDT' => 'https://chat.whatsapp.com/FeuLpi2DcHf7eFubOBUDNx',
    'B.DIV' => 'https://chat.whatsapp.com/GENZ5tMDLoeAN27dGwYsT9',
    'DIPLOMA' => 'https://chat.whatsapp.com/GENZ5tMDLoeAN27dGwYsT9',
    'CERTIFICATE' => 'https://chat.whatsapp.com/GENZ5tMDLoeAN27dGwYsT9'
];

foreach ($queue as $item) {
    $userId = $item['user_id'];
    $queueId = $item['id'];
    $fullName = $item['first_name'] . ' ' . $item['last_name'];
    $email = $item['email'];
    $programCode = strtoupper($item['program']);
    
    $groupLink = $groupLinks[$programCode] ?? 'https://chat.whatsapp.com/GENZ5tMDLoeAN27dGwYsT9'; // Default to certificate/general if not found
    
    $subject = "Congratulations! Welcome to the June 2026 Session – Your Class Group Link & Next Steps";
    
    // HTML Email Body with styling consistent with existing templates
    $body = "
    <div style='font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif; max-width:600px; margin:auto; padding:30px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:16px; color:#374151;'>
        <div style='text-align:center; margin-bottom:30px;'>
            <h2 style='color:#6B21A8; font-size:24px; font-weight:800; margin:0;'>RCN Theological Seminary – Adullam</h2>
            <p style='color:#9CA3AF; font-size:14px; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; margin-top:5px;'>Online Student Onboarding</p>
        </div>

        <p style='font-size:16px; line-height:1.6;'>Dear <strong>{$fullName}</strong>,</p>
        
        <p style='font-size:16px; line-height:1.6;'>Grace and peace to you in the name of our Lord Jesus Christ.</p>
        
        <p style='font-size:16px; line-height:1.6;'>On behalf of the <strong>Remnant Christian Network Theological Seminary—Adullam</strong>, we warmly congratulate you on your successful admission and timely registration for the <strong>June 2026 academic session</strong>!</p>
        
        <p style='font-size:16px; line-height:1.6;'>We have verified your uploaded receipt confirming payment of at least 60% of your first semester fees. This allows you to commence preparations and join the active student community. Your commitment to pursuing theological training—equipping yourself with deep biblical roots, spiritual fire, and accurate witness of Christ—is a testament to God's calling on your life, and we are honored to partner with you in this journey.</p>

        <div style='background-color:#F5F3FF; padding:25px; border-radius:12px; margin:30px 0; border-left:4px solid #6B21A8;'>
            <h3 style='color:#1E1B4B; margin-top:0; font-size:18px;'>🚀 Next Important Step: Join Your Class Group</h3>
            <p style='font-size:15px; line-height:1.6; margin-bottom:20px;'>To ensure a smooth start and full engagement in your program, please join the dedicated June 2026 Class Group using the link below:</p>
            
            <div style='text-align:center;'>
                <a href='{$groupLink}' style='display:inline-block; background-color:#25D366; color:#ffffff; padding:14px 30px; text-decoration:none; border-radius:50px; font-weight:bold; font-size:16px; box-shadow:0 4px 15px rgba(37, 211, 102, 0.3);'>Join WhatsApp Group</a>
            </div>
        </div>

        <p style='font-size:15px; line-height:1.6;'>In this group, you will:</p>
        <ul style='font-size:15px; line-height:1.6; color:#4B5563;'>
            <li>Receive official orientation and welcome messages from the Academic Office and faculty.</li>
            <li>Get guidance on class schedules, lecture platforms, participation expectations, and spiritual formation activities.</li>
            <li>Connect with fellow students, course coordinators, and support staff.</li>
            <li>Stay updated on important announcements, resource access, and upcoming events.</li>
        </ul>

        <div style='background-color:#FFFBEB; padding:20px; border-radius:12px; margin:30px 0; border:1px solid #FEF3C7;'>
            <h4 style='color:#92400E; margin-top:0; margin-bottom:10px;'>📌 Important Reminders:</h4>
            <ul style='font-size:14px; line-height:1.5; color:#92400E; margin:0; padding-left:20px;'>
                <li>Log in to your Student Dashboard to download your official admission letter and view your fee breakdown.</li>
                <li>Ensure your profile is up-to-date with current contact details.</li>
                <li>Remain prayerful and expectant—your time at Adullam is designed for transformation.</li>
            </ul>
        </div>

        <p style='font-size:15px; line-height:1.6;'>If you encounter any issues joining the group or have questions, kindly reply to this email or contact the Registry Department directly:</p>
        
        <p style='font-size:14px; line-height:1.6; color:#6B7280;'>
            📞 Phone: +234 802 216 4432 | +234 816 221 7805<br>
            📧 Email: adullamadmissions@gmail.com
        </p>

        <p style='font-size:16px; line-height:1.6; margin-top:30px;'>We look forward to seeing you thrive as Christ-like leaders contending for the truth. May the Lord grant you grace, wisdom, and fire for the studies ahead.</p>

        <div style='border-top:1px solid #F3F4F6; margin-top:40px; padding-top:20px; text-align:center; color:#9CA3AF; font-size:12px;'>
            &copy; " . date('Y') . " RCNTS Adullam. All rights reserved.
        </div>
    </div>
    ";

    // Send the email
    $success = sendMail($email, $fullName, $subject, $body);

    if ($success) {
        $pdo->prepare("UPDATE onboarding_queue SET status = 'sent', updated_at = NOW() WHERE id = ?")->execute([$queueId]);
        logOnboarding("✅ Sent to {$email}");
    } else {
        $pdo->prepare("UPDATE onboarding_queue SET status = 'failed', attempts = attempts + 1, updated_at = NOW() WHERE id = ?")->execute([$queueId]);
        logOnboarding("❌ Failed for {$email}");
    }
}
logOnboarding("Batch processing completed.");
