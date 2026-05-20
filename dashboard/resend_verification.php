<?php
session_start();
require 'db.php';
require 'mailer.php';

// 📝 Log helper
function logError(string $message): void {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/resend_verification.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Please enter a valid email.';
        logError("Invalid email submitted: " . $email);
        header("Location: applicant_login");
        exit;
    }

    try {
        // Find user
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, verified, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        logError("Database error while checking user: " . $e->getMessage());
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Database error. Please try again.';
        header("Location: applicant_login");
        exit;
    }

    if (!$user) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'No account found with that email.';
        logError("No user found with email: $email");
        header("Location: applicant_login");
        exit;
    }

    // ✅ Case 1: Already verified but no password set → send password setup link
    if ($user['verified'] && empty($user['password'])) {
        $token = bin2hex(random_bytes(32));
        try {
            $stmt = $pdo->prepare("
                INSERT INTO email_verification_tokens (user_id, token)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token)
            ");
            $stmt->execute([$user['id'], $token]);
        } catch (Exception $e) {
            logError("DB error while saving password setup token for $email: " . $e->getMessage());
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Database error. Please try again.';
            header("Location: applicant_login");
            exit;
        }

        $setup_url = "https://adullam.ng/dashboard/verify_link?token=" . $token;

        $subject = "Set Your Password for Adullam Seminary";
        $body = "
            <p>Dear {$user['first_name']} {$user['last_name']},</p>
            <p>Your email is already verified, but you still need to set your password to log in.</p>
            <p><a href='$setup_url'>$setup_url</a></p>
        ";

        try {
            if (sendMail($email, "{$user['first_name']} {$user['last_name']}", $subject, $body)) {
                $_SESSION['status'] = 'success';
                $_SESSION['message'] = 'We sent you a link to set your password.';
                logError("Password setup email sent to already verified user: $email");
            } else {
                $_SESSION['status'] = 'error';
                $_SESSION['message'] = 'Could not send the password setup email. Please try again.';
                logError("sendMail() failed for verified user needing password setup: $email");
            }
        } catch (Exception $e) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Mailer error. Please try again later.';
            logError("Mailer exception for password setup ($email): " . $e->getMessage());
        }

        header("Location: applicant_login");
        exit;
    }

    // ✅ Case 2: Verified and password exists → normal login message
    if ($user['verified'] && !empty($user['password'])) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'This account is already verified. Please log in.';
        logError("Attempted resend for already verified account with password: $email");
        header("Location: applicant_login");
        exit;
    }

    // ✅ Case 3: Not verified → resend verification link
    $token = bin2hex(random_bytes(32));
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_verification_tokens (user_id, token)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE token = VALUES(token)
        ");
        $stmt->execute([$user['id'], $token]);
    } catch (Exception $e) {
        logError("Database error while saving token for $email: " . $e->getMessage());
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Database error. Please try again.';
        header("Location: applicant_login");
        exit;
    }

    $verification_url = "https://adullam.ng/dashboard/verify_link?token=" . $token;

    $subject = "Resend: Verify Your Email Address for Adullam Seminary";
    $body = "
        <p>Dear {$user['first_name']} {$user['last_name']},</p>
        <p>You requested a new verification link. Please click below to verify your email and set your password:</p>
        <p><a href='$verification_url'>$verification_url</a></p>
        <p>If you did not request this, please ignore.</p>
    ";

    try {
        if (sendMail($email, "{$user['first_name']} {$user['last_name']}", $subject, $body)) {
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'A new verification link has been sent to your email.';
            logError("Verification email successfully sent to: $email");
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Could not send the verification email. Please try again.';
            logError("sendMail() returned false for email: $email");
        }
    } catch (Exception $e) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Mailer error. Please try again later.';
        logError("Mailer exception for $email: " . $e->getMessage());
    }

    header("Location: applicant_login");
    exit;

} else {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header("Location: applicant_login");
    exit;
}
