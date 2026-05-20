<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

function trace_login($requestId, $step, $data = [])
{
    $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'login_trace.log';
    $entry = [
        'ts' => date('c'),
        'request_id' => $requestId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'step' => $step,
        'data' => $data,
    ];
    file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function verify_password_compat($inputPassword, $storedPassword)
{
    $storedPassword = (string)($storedPassword ?? '');

    if ($storedPassword === '') {
        return ['ok' => false, 'method' => 'empty'];
    }

    // Modern password_hash formats.
    if (preg_match('/^\$2y\$|^\$2a\$|^\$argon2id\$|^\$argon2i\$/', $storedPassword)) {
        return [
            'ok' => password_verify($inputPassword, $storedPassword),
            'method' => 'password_hash',
        ];
    }

    // Legacy md5 storage support.
    if (preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
        return [
            'ok' => hash_equals(strtolower($storedPassword), md5($inputPassword)),
            'method' => 'md5',
        ];
    }

    // Legacy plain text storage support.
    return [
        'ok' => hash_equals($storedPassword, $inputPassword),
        'method' => 'plain_text',
    ];
}

// ✅ Respond Helper
function respond($status, $message, $data = [])
{
    global $requestId;
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'request_id' => $requestId ?? null,
        ...$data
    ]);
    exit;
}

$requestId = bin2hex(random_bytes(8));
trace_login($requestId, 'request_received', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'has_login_email' => array_key_exists('login_email', $_POST),
    'password_len' => isset($_POST['password']) ? strlen((string)$_POST['password']) : null,
]);

try {
    $email = trim($_POST['login_email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        trace_login($requestId, 'validation_failed', [
            'email_present' => $email !== '',
            'password_present' => $password !== '',
        ]);
        respond('error', 'Email and password are required.');
    }

    trace_login($requestId, 'lookup_user_start', [
        'email' => $email,
    ]);

    $stmt = $pdo->prepare("SELECT id, first_name, email, role, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        trace_login($requestId, 'user_not_found', [
            'email' => $email,
        ]);
        respond('error', 'Invalid email or password.');
    }

    trace_login($requestId, 'user_found', [
        'user_id' => $user['id'] ?? null,
        'role' => $user['role'] ?? null,
    ]);

    $passwordCheck = verify_password_compat($password, $user['password'] ?? '');
    if (!$passwordCheck['ok']) {
        trace_login($requestId, 'password_invalid', [
            'user_id' => $user['id'] ?? null,
            'password_storage_type' => $passwordCheck['method'],
        ]);
        respond('error', 'Invalid email or password.');
    }

    trace_login($requestId, 'password_valid', [
        'user_id' => $user['id'] ?? null,
        'password_storage_type' => $passwordCheck['method'],
    ]);

    // Upgrade old hashes/plain text to secure hash immediately after successful auth.
    if ($passwordCheck['method'] !== 'password_hash') {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upgradeStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upgradeStmt->execute([$newHash, $user['id']]);
            trace_login($requestId, 'password_upgraded', [
                'user_id' => $user['id'] ?? null,
                'from' => $passwordCheck['method'],
            ]);
        } catch (Throwable $e) {
            trace_login($requestId, 'password_upgrade_failed', [
                'user_id' => $user['id'] ?? null,
                'from' => $passwordCheck['method'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    $verifiedFromOtp = null;
    $verifiedFromUsers = null;

    try {
        $verifyStmt = $pdo->prepare("SELECT MAX(verified) AS verified FROM email_verification_otp WHERE user_id = ?");
        $verifyStmt->execute([$user['id']]);
        $verifiedFromOtp = $verifyStmt->fetchColumn();
    } catch (Throwable $e) {
        trace_login($requestId, 'verification_otp_query_failed', [
            'user_id' => $user['id'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }

    try {
        $usersVerifyStmt = $pdo->prepare("SELECT verified FROM users WHERE id = ?");
        $usersVerifyStmt->execute([$user['id']]);
        $verifiedFromUsers = $usersVerifyStmt->fetchColumn();
    } catch (Throwable $e) {
        trace_login($requestId, 'verification_users_query_failed', [
            'user_id' => $user['id'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }

    $isVerified = false;
    if ($verifiedFromUsers !== null) {
        $isVerified = ((int)$verifiedFromUsers) === 1;
    } elseif ($verifiedFromOtp !== null) {
        $isVerified = ((int)$verifiedFromOtp) === 1;
    }

    trace_login($requestId, 'verification_checked', [
        'user_id' => $user['id'] ?? null,
        'verified_from_users' => $verifiedFromUsers,
        'verified_from_otp' => $verifiedFromOtp,
        'is_verified' => $isVerified ? 1 : 0,
    ]);

    if (!$isVerified) {
        respond('error', 'Email not verified. Please verify your email first.');
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['first_name'];
    $_SESSION['role'] = $user['role'] ?? 'student';

    switch ($user['role']) {
        case 'admin':
            $redirect = 'admin_dashboard.php';
            break;

        case 'superadmin':
            $redirect = 'superadmin_dashboard.php';
            break;

        case 'student':
        default:
            $appStmt = $pdo->prepare("SELECT current_level, submitted FROM applications WHERE user_id = ?");
            $appStmt->execute([$user['id']]);
            $application = $appStmt->fetch();

            $_SESSION['has_application'] = $application ? true : false;
            $_SESSION['current_level'] = $application['current_level'] ?? 1;

            $submitted = $application['submitted'] ?? 0;
            $level = $application['current_level'] ?? 1;

            if ($submitted) {
                $redirect = 'dashboard.php';
            } elseif ($level == 1) {
                $redirect = 'student_dashboard.php';
            } else {
                $maxFormLevel = 6;
                $redirect = "form_level" . min($level, $maxFormLevel) . ".php";
            }
            break;
    }

    trace_login($requestId, 'login_success', [
        'user_id' => $user['id'] ?? null,
        'role' => $user['role'] ?? null,
        'redirect' => $redirect ?? null,
    ]);

    respond('success', 'Login successful. Redirecting...', ['redirect' => $redirect]);
} catch (Throwable $e) {
    trace_login($requestId, 'login_exception', [
        'error' => $e->getMessage(),
    ]);
    respond('error', 'Login error. Please try again.');
}
