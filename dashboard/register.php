<?php
session_start();
date_default_timezone_set('Africa/Lagos');

// Include your DB connection & PHPMailer
require 'db.php';
require 'mailer.php';

// 🛡️ Return JSON helper
function respond($status, $message, $redirect = null)
{
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'redirect' => $redirect]);
    exit;
}

// 🌐 reCAPTCHA Secret Key
$recaptcha_secret_key = '6LckELErAAAAAEX6sZUeY6MybRwhq-XweFFMHiNh';

// 🤖 ReCAPTCHA Verification
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptcha_response)) {
    respond('error', 'Please complete the reCAPTCHA verification.');
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret' => $recaptcha_secret_key,
    'response' => $recaptcha_response
];

$recaptcha_options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($recaptcha_data)
    ]
];
$recaptcha_context  = stream_context_create($recaptcha_options);
$recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
$recaptcha_json = json_decode($recaptcha_result, true);

if (!$recaptcha_json['success']) {
    // Log the error for debugging
    error_log('reCAPTCHA verification failed. Result: ' . print_r($recaptcha_json, true));
    respond('error', 'reCAPTCHA verification failed. Please try again.');
}

// Check if registration is open after reCAPTCHA verification
$regOpen = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();
if ($regOpen != 1) {
    respond('error', '🚫 Registration is currently closed by the Administrator.');
}

// 🧼 Get and clean inputs from form
$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// 🧪 Basic validation
if (!$first || !$last || !$email || !$phone) {
    respond('error', 'Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('error', 'Invalid email address.');
}

// 🔍 Check if user already exists
$stmt = $pdo->prepare("SELECT id, verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $user['verified']) {
    respond('error', 'Email is already registered and verified. Please log in.');
}

// ✅ Save user to database
if (!$user) {
    $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, email, phone, verified) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->execute([$first, $middle, $last, $email, $phone]);
    $user_id = $pdo->lastInsertId();
} else {
    $user_id = $user['id']; // Unverified user re-attempting
}

// 🔐 Generate secure verification token
$token = bin2hex(random_bytes(32)); // Creates a 64-character hex token

// 💾 Save or Update the verification token
$stmt = $pdo->prepare("
    INSERT INTO email_verification_tokens (user_id, token)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE
        token = VALUES(token)
");
$stmt->execute([$user_id, $token]);

// 💌 Send verification link via email
$subject = "Verify Your Email Address for Adullam Seminary";

// 🌐 Construct the verification URL
$verification_url = "https://adullam.ng/dashboard/verify_link.php?token=" . $token;

$body = "
  <div style='font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: auto; border: 1px solid #ccc; border-radius: 10px;'>
    <h2 style='color: #673A8B;'>Dear $first,</h2>

    <p>Thank you for starting your journey with <strong>RCN Theological Seminary - Adullam</strong>.</p>

    <p>To complete your registration, please click the link below to verify your email address and set your password:</p>

    <div style='text-align: center; padding: 20px 0;'>
      <a href='$verification_url' style='background-color: #673A8B; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
        Verify My Email
      </a>
    </div>

    <p>If you cannot click the button, please copy and paste the following URL into your browser:</p>
    <p><a href='$verification_url'>$verification_url</a></p>

    <p>If you did not initiate this registration, please disregard this message.</p>

    <p style='margin-top: 30px;'>Sincerely,<br/>
    <strong>Adullam Seminary Admissions Team</strong></p>
  </div>
";

if (!sendMail($email, "$first $last", $subject, $body)) {
    respond('error', 'Could not send verification email. Please try again.');
}

// The registration page handles the redirect, so we just return success
respond('success', 'A verification link has been sent to your email. Please check your inbox, and remember to check your spam folder as well.');