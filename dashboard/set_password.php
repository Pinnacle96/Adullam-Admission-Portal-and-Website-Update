<?php
session_start();
require 'db.php';

// 📝 Log helper
function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/set_password.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}

// 🛡️ Security: only allow if email is in session
if (!isset($_SESSION['email'])) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = 'Session expired. Please request a new verification link.';
    logError("Attempt to access set_password.php without email in session.");
    header("Location: applicant_login");
    exit;
}

$email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    // 🔑 Password validation
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/\d/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.";
        logError("Weak password attempt for $email");
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
        logError("Password mismatch for $email");
    } else {
        try {
            // ✅ Update password in DB
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);

            // 🔒 Clear session (prevent reuse)
            logError("Password successfully updated for $email");
            unset($_SESSION['email']);
            unset($_SESSION['status'], $_SESSION['message']);

            // 🔔 Redirect with success alert
            echo "<script>
                localStorage.setItem('pwSet', '1');
                window.location.href = 'applicant_login';
            </script>";
            exit;
        } catch (Exception $e) {
            $error = "Database error while updating password. Please try again.";
            logError("DB error updating password for $email: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Set Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <style>
        .strength-meter { height: 6px; border-radius: 4px; margin-top: 6px; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md">
        <h2 class="text-xl font-bold text-purple-800 text-center mb-4">Set Your Password</h2>

        <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'success'): ?>
            <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
                <?= htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['status'], $_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-700">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600 pr-10">
                    <button type="button" id="togglePassword"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-sm text-purple-600 hover:underline">Show</button>
                </div>
                <div id="strengthText" class="text-xs mt-1 text-gray-600"></div>
                <div id="strengthBar" class="strength-meter bg-gray-200">
                    <div id="strengthFill" class="h-full bg-red-400 w-0 rounded"></div>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" required
                    class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
            </div>

            <button type="submit" class="w-full bg-purple-700 hover:bg-purple-800 text-white py-2 rounded-lg shadow">
                Set Password
            </button>
        </form>
    </div>

    <!-- SweetAlert after password set -->
    <script>
        // if (localStorage.getItem('pwSet')) {
        //     Swal.fire({
        //         icon: 'success',
        //         title: 'Password Set!',
        //         text: 'You can now log in.',
        //         confirmButtonColor: '#6B21A8'
        //     });
        //     localStorage.removeItem('pwSet');
        // }
         // Case 1: User just verified
    <?php if (isset($_SESSION['just_verified']) && $_SESSION['just_verified']): ?>
        Swal.fire({
            icon: 'success',
            title: 'Email Verified!',
            text: 'Please set your password to complete your account.',
            confirmButtonColor: '#6B21A8'
        });
        <?php unset($_SESSION['just_verified']); ?>
    <?php endif; ?>

    // Case 2: Password successfully set
    if (localStorage.getItem('pwSet')) {
        Swal.fire({
            icon: 'success',
            title: 'Password Set!',
            text: 'You can now log in.',
            confirmButtonColor: '#6B21A8'
        });
        localStorage.removeItem('pwSet');
    }

        const passwordInput = document.getElementById('password');
        const strengthText = document.getElementById('strengthText');
        const strengthBar = document.getElementById('strengthFill');
        const toggleBtn = document.getElementById('togglePassword');

        const criteria = {
            length: val => val.length >= 8,
            lowercase: val => /[a-z]/.test(val),
            uppercase: val => /[A-Z]/.test(val),
            number: val => /\d/.test(val),
            symbol: val => /[!@#$%^&*(),.?":{}|<>]/.test(val),
        };

        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;

            for (const check of Object.values(criteria)) {
                if (check(val)) score++;
            }

            const percent = (score / Object.keys(criteria).length) * 100;
            strengthBar.style.width = percent + '%';
            strengthBar.style.backgroundColor =
                percent >= 80 ? '#16a34a' :
                percent >= 60 ? '#facc15' :
                '#ef4444';

            strengthText.textContent = score === 5 ?
                "✅ Strong password" :
                "Password must include uppercase, lowercase, number, symbol, and be at least 8 characters.";
        });

        toggleBtn.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            toggleBtn.textContent = type === 'password' ? 'Show' : 'Hide';
        });
    </script>
</body>
</html>
