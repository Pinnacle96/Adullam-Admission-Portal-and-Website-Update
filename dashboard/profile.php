<?php
session_start();
require 'db.php';
require 'mailer.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: index");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$feedback = $_SESSION['profile_feedback'] ?? null;
unset($_SESSION['profile_feedback']);

function profile_feedback(string $type, string $message): void
{
    $_SESSION['profile_feedback'] = ['type' => $type, 'message' => $message];
}

function verify_password_compat_profile(string $inputPassword, $storedPassword): bool
{
    $storedPassword = (string)($storedPassword ?? '');
    if ($storedPassword === '') {
        return false;
    }
    if (preg_match('/^\$2y\$|^\$2a\$|^\$argon2id\$|^\$argon2i\$/', $storedPassword)) {
        return password_verify($inputPassword, $storedPassword);
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
        return hash_equals(strtolower($storedPassword), md5($inputPassword));
    }
    return hash_equals($storedPassword, $inputPassword);
}

function log_profile_audit(PDO $pdo, int $userId, string $action, string $details): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    } catch (Throwable $e) {
        error_log('[PROFILE_AUDIT] ' . $e->getMessage());
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index");
    exit;
}

$appDetails = null;
$docs = [];
if ($role === 'student') {
    $details = $pdo->prepare("SELECT d.*, a.admission_no FROM application_details d
        JOIN applications a ON d.user_id = a.user_id WHERE d.user_id = ?");
    $details->execute([$user_id]);
    $appDetails = $details->fetch();

    $doc = $pdo->prepare("SELECT * FROM application_documents WHERE user_id = ?");
    $doc->execute([$user_id]);
    $docs = $doc->fetch() ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_profile') {
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            profile_feedback('error', 'Please enter a valid email address.');
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
            $check->execute([$email, $user_id]);
            if ($check->fetch()) {
                profile_feedback('error', 'That email is already used by another account.');
            } else {
                $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?")->execute([$email, $phone, $user_id]);
                $_SESSION['email'] = $email;
                profile_feedback('success', 'Profile updated successfully.');
            }
        }
        header("Location: profile");
        exit;
    }

    if ($action === 'request_password_otp') {
        $currentPassword = (string)($_POST['current_password'] ?? '');

        if (!verify_password_compat_profile($currentPassword, $user['password'] ?? '')) {
            profile_feedback('error', 'Your current password is incorrect.');
        } elseif (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            profile_feedback('error', 'A valid email address is required before changing your password.');
        } else {
            $rate = $pdo->prepare("SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
            $rate->execute([$user_id]);

            if ((int)$rate->fetchColumn() >= 5) {
                profile_feedback('error', 'Too many OTP requests. Please try again in an hour.');
            } else {
                $otp = (string)random_int(100000, 999999);
                $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, NOW() + INTERVAL 15 MINUTE)")
                    ->execute([$user_id, $otp]);
                $otpResetId = (int)$pdo->lastInsertId();

                $safeName = htmlspecialchars($user['first_name'] ?: 'there');
                $subject = 'Password Change OTP - Adullam Seminary';
                $body = "
                    <div style='margin:0;padding:0;background:#f4f5f7;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#111827;'>
                        <div style='max-width:620px;margin:0 auto;padding:28px 16px;'>
                            <div style='background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;'>
                                <div style='padding:28px 28px 18px;text-align:center;background:#ffffff;'>
                                    <img src='https://adullam.ng/assets/img/logo1.png' alt='Adullam Seminary' style='height:64px;width:auto;margin-bottom:14px;' />
                                    <h2 style='margin:0;color:#4c1d95;font-size:22px;font-weight:700;'>Password Change Confirmation</h2>
                                </div>
                                <div style='padding:0 32px 28px;'>
                                    <p style='font-size:16px;line-height:1.65;margin:0 0 14px;'>Hello <strong>{$safeName}</strong>,</p>
                                    <p style='font-size:15px;line-height:1.65;margin:0;color:#374151;'>
                                        We received a request to change your dashboard password. Use the OTP below to confirm this action.
                                    </p>
                                    <div style='text-align:center;margin:28px 0;'>
                                        <span style='display:inline-block;background:#6B21A8;color:#ffffff;font-size:30px;letter-spacing:6px;padding:14px 28px;border-radius:10px;font-weight:700;'>{$otp}</span>
                                    </div>
                                    <p style='font-size:14px;line-height:1.6;margin:0;color:#4b5563;'>This code expires in 15 minutes. If you did not request this change, keep your current password and contact an administrator.</p>
                                </div>
                                <div style='border-top:1px solid #e5e7eb;background:#fafafa;padding:18px 28px;text-align:center;font-size:13px;color:#6b7280;'>
                                    <p style='margin:0 0 6px;'>RCN Theological Seminary - Adullam</p>
                                    <p style='margin:0;'>
                                        <a href='mailto:adullamadmissions@gmail.com' style='color:#6B21A8;text-decoration:none;'>adullamadmissions@gmail.com</a>
                                        &nbsp;|&nbsp;
                                        <a href='https://adullam.ng' style='color:#6B21A8;text-decoration:none;'>adullam.ng</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>";

                $sent = sendMail($user['email'], trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')), $subject, $body);
                if ($sent === true) {
                    $_SESSION['profile_password_otp_user_id'] = $user_id;
                    $_SESSION['profile_password_otp_reset_id'] = $otpResetId;
                    profile_feedback('success', 'OTP sent to your email. Enter it below to set your new password.');
                    log_profile_audit($pdo, $user_id, 'Profile Password OTP Sent', 'User requested password change OTP');
                } else {
                    profile_feedback('error', 'Could not send OTP email right now. Please try again.');
                }
            }
        }
        header("Location: profile");
        exit;
    }

    if ($action === 'change_password') {
        $otp = trim($_POST['otp'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (($_SESSION['profile_password_otp_user_id'] ?? null) !== $user_id) {
            profile_feedback('error', 'Please request an OTP before changing your password.');
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            profile_feedback('error', 'Enter the 6 digit OTP sent to your email.');
        } elseif ($newPassword !== $confirmPassword) {
            profile_feedback('error', 'Passwords do not match.');
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
            profile_feedback('error', 'Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.');
        } else {
            $resetId = (int)($_SESSION['profile_password_otp_reset_id'] ?? 0);
            $reset = $pdo->prepare("SELECT * FROM password_resets
                WHERE id = ? AND user_id = ? AND token = ? AND used = 0 AND expires_at >= NOW()
                ORDER BY id DESC LIMIT 1");
            $reset->execute([$resetId, $user_id, $otp]);
            $row = $reset->fetch();

            if (!$row) {
                profile_feedback('error', 'Invalid or expired OTP.');
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
                $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$row['id']]);
                unset($_SESSION['profile_password_otp_user_id'], $_SESSION['profile_password_otp_reset_id']);
                profile_feedback('success', 'Password changed successfully.');
                log_profile_audit($pdo, $user_id, 'Profile Password Changed', 'User changed password after OTP confirmation');
            }
        }
        header("Location: profile");
        exit;
    }
}

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$initials = trim(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$otpPending = ($_SESSION['profile_password_otp_user_id'] ?? null) === $user_id;
$feedbackJson = $feedback ? json_encode($feedback, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) : 'null';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile - Adullam Seminary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <?php if ($role === 'student'): ?>
        <?php include 'components/student_sidebar.php'; ?>
    <?php else: ?>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
    <?php endif; ?>

        <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
            <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-purple-900 via-purple-800 to-indigo-800 px-6 py-8 text-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-2xl overflow-hidden border border-white/30 bg-white/10 text-white flex items-center justify-center text-2xl font-bold shadow-sm">
                            <?php if ($role === 'student' && !empty($docs['passport'])): ?>
                                <img src="<?= htmlspecialchars($docs['passport']) ?>" alt="Profile Photo" class="object-cover w-full h-full">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper($initials ?: 'U')) ?>
                            <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm text-purple-100"><?= htmlspecialchars(ucfirst($role)) ?> Account</p>
                                <h1 class="text-2xl sm:text-3xl font-bold"><?= htmlspecialchars($fullName ?: $user['first_name'] ?: 'User') ?></h1>
                                <p class="text-sm text-purple-100 mt-1"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-3">
                                <p class="text-purple-100">Phone</p>
                                <p class="font-semibold"><?= htmlspecialchars($user['phone'] ?: 'Not set') ?></p>
                            </div>
                            <?php if ($role === 'student'): ?>
                                <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-3">
                                    <p class="text-purple-100">Admission No</p>
                                    <p class="font-semibold"><?= htmlspecialchars($appDetails['admission_no'] ?? 'N/A') ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-1">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-gray-900">Contact Details</h2>
                        <p class="text-sm text-gray-500 mt-1">Keep your reachable email and phone number current.</p>
                    </div>
                    <form method="POST" class="space-y-4 mt-6">
                        <input type="hidden" name="action" value="save_profile">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <button type="submit" class="w-full bg-purple-700 text-white px-4 py-2.5 rounded-lg hover:bg-purple-800 font-medium">Save Changes</button>
                    </form>
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Security</h2>
                            <p class="text-sm text-gray-500 mt-1">Password changes are protected with email OTP confirmation.</p>
                        </div>
                        <span class="inline-flex w-fit px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-medium border border-green-100">OTP protected</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form method="POST" class="space-y-4 bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-800">1. Verify Current Password</h3>
                            <input type="hidden" name="action" value="request_password_otp">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <div class="relative">
                                    <input type="password" name="current_password" id="currentPassword" required class="js-password-field w-full px-4 py-2.5 pr-11 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <button type="button" class="js-toggle-password absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-purple-700" data-target="currentPassword" aria-label="Show current password">
                                        <svg class="h-5 w-5 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg class="h-5 w-5 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.63A9.86 9.86 0 0112 5.4c6 0 9.75 6.6 9.75 6.6a18.7 18.7 0 01-3.15 3.99M6.1 6.7C3.68 8.54 2.25 12 2.25 12s3.75 6.6 9.75 6.6c1.25 0 2.41-.28 3.45-.75" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-blue-700 text-white px-4 py-2.5 rounded-lg hover:bg-blue-800 font-medium">Send Email OTP</button>
                        </form>

                        <form method="POST" class="space-y-4 bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-800">2. Confirm New Password</h3>
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email OTP</label>
                                <input type="text" name="otp" maxlength="6" pattern="\d{6}" <?= $otpPending ? '' : 'disabled' ?> class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-center tracking-widest focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <div class="relative">
                                    <input type="password" name="new_password" id="newPassword" <?= $otpPending ? '' : 'disabled' ?> class="js-password-field w-full px-4 py-2.5 pr-11 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-100">
                                    <button type="button" class="js-toggle-password absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-purple-700 disabled:text-gray-300" data-target="newPassword" <?= $otpPending ? '' : 'disabled' ?> aria-label="Show new password">
                                        <svg class="h-5 w-5 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg class="h-5 w-5 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.63A9.86 9.86 0 0112 5.4c6 0 9.75 6.6 9.75 6.6a18.7 18.7 0 01-3.15 3.99M6.1 6.7C3.68 8.54 2.25 12 2.25 12s3.75 6.6 9.75 6.6c1.25 0 2.41-.28 3.45-.75" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirmPassword" <?= $otpPending ? '' : 'disabled' ?> class="js-password-field w-full px-4 py-2.5 pr-11 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-100">
                                    <button type="button" class="js-toggle-password absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-purple-700 disabled:text-gray-300" data-target="confirmPassword" <?= $otpPending ? '' : 'disabled' ?> aria-label="Show confirmed password">
                                        <svg class="h-5 w-5 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg class="h-5 w-5 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.63A9.86 9.86 0 0112 5.4c6 0 9.75 6.6 9.75 6.6a18.7 18.7 0 01-3.15 3.99M6.1 6.7C3.68 8.54 2.25 12 2.25 12s3.75 6.6 9.75 6.6c1.25 0 2.41-.28 3.45-.75" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Use at least 8 characters with uppercase, lowercase, number, and symbol.</p>
                            <button type="submit" <?= $otpPending ? '' : 'disabled' ?> class="w-full bg-purple-700 text-white px-4 py-2.5 rounded-lg hover:bg-purple-800 disabled:bg-gray-400 font-medium">Change Password</button>
                        </form>
                    </div>
                </section>

                <?php if ($role === 'student'): ?>
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-3">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-gray-900">Academic Profile</h2>
                        <p class="text-sm text-gray-500 mt-1">Your submitted academic and application information.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 text-sm">
                        <p><strong>Program:</strong> <?= htmlspecialchars($appDetails['program'] ?? 'N/A') ?></p>
                        <?php if (($appDetails['program'] ?? '') === 'MA'): ?>
                            <p><strong>MA Focus:</strong> <?= htmlspecialchars($appDetails['ma_focus'] ?? 'N/A') ?></p>
                        <?php endif; ?>
                        <p><strong>Mode of Study:</strong> <?= htmlspecialchars($appDetails['mode_of_study'] ?? 'N/A') ?></p>
                        <p><strong>Permanent Address:</strong> <?= htmlspecialchars($appDetails['perm_address'] ?? 'N/A') ?></p>
                        <p><strong>Residential Address:</strong> <?= htmlspecialchars($appDetails['res_address'] ?? 'N/A') ?></p>
                    </div>

                    <?php if ($docs): ?>
                        <hr class="my-4">
                        <h4 class="text-md font-medium text-purple-700 mb-2">Documents</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <?php foreach ($docs as $docName => $path): ?>
                                <?php if (in_array($docName, ['user_id', 'created_at', 'passport']) || empty($path)) continue; ?>
                                <div class="flex justify-between items-center border rounded px-3 py-2">
                                    <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $docName))) ?></span>
                                    <a href="<?= htmlspecialchars($path) ?>" download class="text-purple-700 underline">Download</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        toggleSidebar?.addEventListener('click', () => sidebar?.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && sidebar?.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !toggleSidebar?.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        document.querySelectorAll('.js-toggle-password').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                if (!input || input.disabled) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                button.querySelector('.icon-eye')?.classList.toggle('hidden', isPassword);
                button.querySelector('.icon-eye-off')?.classList.toggle('hidden', !isPassword);
            });
        });

        const profileFeedback = <?= $feedbackJson ?>;
        if (profileFeedback) {
            window.addEventListener('load', () => {
                const icon = profileFeedback.type === 'success' ? 'success' : 'error';
                if (window.Swal) {
                    Swal.fire({
                        icon,
                        title: icon === 'success' ? 'Success' : 'Error',
                        text: profileFeedback.message,
                        confirmButtonColor: '#6B21A8'
                    });
                } else {
                    alert(profileFeedback.message);
                }
            });
        }
    </script>
</body>
</html>
