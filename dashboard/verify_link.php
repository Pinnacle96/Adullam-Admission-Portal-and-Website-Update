<?php
session_start();
require 'db.php';

// 📝 Log helper
function logVerify($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/verify_link.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}

// 🛡️ Helper function to handle redirection and messages
function handle_error($message) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = $message;
    header('Location: applicant_login');
    exit();
}

// 🧼 Get the token from the URL
$token = $_GET['token'] ?? '';
if (!$token) {
    logVerify("Missing verification token in request.");
    handle_error('Verification token is missing.');
}

// 🔄 Start verification process
try {
    // 🔍 Look up the token
    $stmt = $pdo->prepare("SELECT user_id FROM email_verification_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $result = $stmt->fetch();

    if (!$result) {
        $tokenPreview = substr($token, 0, 8) . '...';
        logVerify("Invalid or expired token used: $tokenPreview");
        handle_error('Invalid or expired verification link. Please request a new verification email.');
    }

    $user_id = $result['user_id'];

    // 🔍 Load user record
    $stmt = $pdo->prepare("SELECT email, verified, password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        logVerify("Token matched user_id=$user_id but user not found.");
        handle_error('User not found.');
    }

    // ⚡ Case 1: User already verified
    if ($user['verified']) {
        if (empty($user['password'])) {
            // ✅ Verified but no password → force to set password
$_SESSION['status'] = 'success';
$_SESSION['message'] = 'Please set your password to complete your account.';
$_SESSION['email'] = $user['email'];
$_SESSION['just_verified'] = true; // 👈 NEW FLAG

// Delete this token since it has been used
$stmt = $pdo->prepare("DELETE FROM email_verification_tokens WHERE token = ?");
$stmt->execute([$token]);

logVerify("Verified user {$user['email']} accessed link but no password set → redirecting to set_password.php");
header('Location: set_password');
exit();
        } else {
            // ✅ Verified + has password → just log in
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'Your account is already verified. Please log in.';
            logVerify("Verified user {$user['email']} already has password → redirecting to login.");
            header('Location: applicant_login');
            exit();
        }
    }

    // ⚡ Case 2: Not verified yet → mark verified and continue
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE users SET verified = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    // Clean up all tokens for this user
    $stmt = $pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();

    // ✅ Success: send to password setup
   // ✅ Success: send to password setup
$_SESSION['status'] = 'success';
$_SESSION['message'] = 'Email verified successfully. Please set your password.';
$_SESSION['email'] = $user['email'];
$_SESSION['just_verified'] = true; // 👈 NEW FLAG

logVerify("User {$user['email']} successfully verified and redirected to set_password.php");
header('Location: set_password');
exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logVerify("Database error during verification: " . $e->getMessage());
    handle_error('An error occurred during verification. Please try again.');
}
