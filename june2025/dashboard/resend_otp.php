<?php
session_start();
date_default_timezone_set('Africa/Lagos');

require 'db.php';
require 'mailer.php';

function respond($status, $message)
{
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

$email = $_SESSION['email'] ?? null;
if (!$email) {
    respond('error', 'Session expired. Please register again.');
}

// Get user
$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    respond('error', 'User not found.');
}

$user_id = $user['id'];
$first = $user['first_name'];
$last = $user['last_name'];

// Rate-limit check (resend allowed after 60 seconds)
$stmt = $pdo->prepare("SELECT expires_at FROM email_verification_otp WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing = $stmt->fetch();

if ($existing) {
    $lastOtpTime = strtotime($existing['expires_at']) - (30 * 60); // 30 minutes window // reverse from expiry time
    $now = time();

    if (($now - $lastOtpTime) < 60) {
        respond('error', 'Please wait at least 60 seconds before requesting another OTP.');
    }
}

// Generate new OTP and expiry
$otp = random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// Insert or update OTP (enforcing 1 OTP per user)
$stmt = $pdo->prepare("
    INSERT INTO email_verification_otp (user_id, otp, expires_at, verified)
    VALUES (?, ?, ?, 0)
    ON DUPLICATE KEY UPDATE
        otp = VALUES(otp),
        expires_at = VALUES(expires_at),
        verified = 0
");
$stmt->execute([$user_id, $otp, $expires]);

// Email Content
$subject = "Your One-Time Passcode (OTP) – RCN Theological Seminary - Adullam Verification";
$body = "
  <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
    <h2 style='color: #673A8B;'>Dear $first,</h2>
    <p style='font-size: 16px; color: #333;'>Thank you for choosing <strong>RCN Theological Seminary - Adullam</strong>.</p>
    <p style='font-size: 15px; color: #444;'>Your new One-Time Passcode (OTP) for email verification is:</p>
    <div style='text-align: center; padding: 20px 0;'>
      <h1 style='font-size: 38px; letter-spacing: 6px; color: #673A8B;'>$otp</h1>
      <p style='color: #888;'>This code is valid for <strong>10 minutes</strong>.</p>
    </div>
    <p style='font-size: 15px; color: #555;'>If you didn’t request this, you can safely ignore this message.</p>
    <p style='margin-top: 30px; font-size: 14px;'>
      May God's wisdom and grace guide your journey.<br/>
      <strong>Adullam Seminary Admissions</strong><br/>
      📧 support@adullam.ng<br/>
      🌐 <a href='https://adullam.ng'>https://adullam.ng</a>
    </p>
  </div>
";

// Send email
$sent = sendMail($email, "$first $last", $subject, $body);

if (!$sent) {
    respond('error', 'Failed to resend OTP. Please try again.');
}

respond('success', 'A new OTP has been sent to your email.');
