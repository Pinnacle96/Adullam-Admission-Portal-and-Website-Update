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
                    <div style='font-family:Segoe UI,Arial,sans-serif;max-width:560px;margin:auto;padding:20px;background:#f9fafb;border:1px solid #eee;border-radius:8px;'>
                        <h2 style='color:#6B21A8;margin-top:0;'>Password Change Confirmation</h2>
                        <p>Hello <strong>{$safeName}</strong>,</p>
                        <p>Use this OTP to confirm your dashboard password change. It expires in 15 minutes.</p>
                        <div style='text-align:center;margin:24px 0;'>
                            <span style='display:inline-block;background:#6B21A8;color:#fff;font-size:26px;letter-spacing:4px;padding:12px 22px;border-radius:8px;font-weight:bold;'>{$otp}</span>
                        </div>
                        <p style='font-size:13px;color:#555;'>If you did not request this, keep your current password and contact an administrator.</p>
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
            <div class="flex flex-col gap-2 mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-purple-800">My Profile</h1>
                <p class="text-sm text-gray-600">Manage your contact details and secure your account password.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="bg-white rounded-xl shadow border border-gray-100 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 rounded-full overflow-hidden border bg-purple-100 text-purple-800 flex items-center justify-center text-xl font-bold">
                            <?php if ($role === 'student' && !empty($docs['passport'])): ?>
                                <img src="<?= htmlspecialchars($docs['passport']) ?>" alt="Profile Photo" class="object-cover w-full h-full">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper($initials ?: 'U')) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($fullName ?: $user['first_name'] ?: 'User') ?></h2>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars(ucfirst($role)) ?></p>
                            <?php if ($role === 'student'): ?>
                                <p class="text-sm text-gray-500">Admission No: <strong><?= htmlspecialchars($appDetails['admission_no'] ?? 'N/A') ?></strong></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" class="space-y-4 mt-6">
                        <input type="hidden" name="action" value="save_profile">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Phone</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <button type="submit" class="bg-purple-700 text-white px-4 py-2 rounded hover:bg-purple-800">Save Changes</button>
                    </form>
                </section>

                <section class="bg-white rounded-xl shadow border border-gray-100 p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-purple-800 mb-4">Change Password</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="request_password_otp">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Current Password</label>
                                <input type="password" name="current_password" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            <button type="submit" class="w-full sm:w-auto bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">Send Email OTP</button>
                        </form>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Email OTP</label>
                                <input type="text" name="otp" maxlength="6" pattern="\d{6}" <?= $otpPending ? '' : 'disabled' ?> class="w-full px-4 py-2 border rounded text-center tracking-widest focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" <?= $otpPending ? '' : 'disabled' ?> class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" <?= $otpPending ? '' : 'disabled' ?> class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:bg-gray-100">
                            </div>
                            <p class="text-xs text-gray-500">Use at least 8 characters with uppercase, lowercase, number, and symbol.</p>
                            <button type="submit" <?= $otpPending ? '' : 'disabled' ?> class="w-full sm:w-auto bg-purple-700 text-white px-4 py-2 rounded hover:bg-purple-800 disabled:bg-gray-400">Change Password</button>
                        </form>
                    </div>
                </section>

                <?php if ($role === 'student'): ?>
                <section class="bg-white rounded-xl shadow border border-gray-100 p-6 lg:col-span-3">
                    <h3 class="text-lg font-semibold text-purple-800 mb-4">Academic Info</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
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

        <?php if ($feedback): ?>
            Swal.fire({
                icon: '<?= $feedback['type'] === 'success' ? 'success' : 'error' ?>',
                title: '<?= $feedback['type'] === 'success' ? 'Success' : 'Error' ?>',
                text: '<?= addslashes($feedback['message']) ?>',
                confirmButtonColor: '#6B21A8'
            });
        <?php endif; ?>
    </script>
</body>
</html>
