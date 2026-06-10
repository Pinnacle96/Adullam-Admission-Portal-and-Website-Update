<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    die("Unauthorized");
}

// 🧠 Fetch all current settings
$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}

// Fetch cohorts
$currentCohort = $settings['current_cohort'] ?? 'January 2026';
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'registration_open' => isset($_POST['registration_open']) ? '1' : '0',
        'sender_name'       => trim($_POST['sender_name']),
        'sender_email'      => trim($_POST['sender_email']),
        'admission_email'   => trim($_POST['admission_email']),
        'admission_deadline' => trim($_POST['admission_deadline']),
        'terms_link'        => trim($_POST['terms_link']),
        'notice_banner' => trim($_POST['notice_banner']),
        'current_cohort' => trim($_POST['current_cohort']),
        'recaptcha_site_key' => trim($_POST['recaptcha_site_key']),
        'recaptcha_secret_key' => trim($_POST['recaptcha_secret_key'])
    ];

    // Update or insert each setting
    foreach ($fields as $key => $value) {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
        $check_stmt->execute([$key]);
        $exists = $check_stmt->fetchColumn() > 0;

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }

    // ✅ Show SweetAlert
    echo "
    <html><head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <meta http-equiv='refresh' content='2;URL=system_settings.php' />
    </head><body>
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Settings Updated',
            text: 'System settings have been saved.',
            timer: 2000,
            showConfirmButton: false
        });
        </script>
    </body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-6 max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-purple-800 mb-6">⚙️ System Settings</h1>

            <form method="POST" class="bg-white p-6 rounded-xl shadow space-y-6">
                <!-- Registration Toggle -->
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="registration_open" class="w-5 h-5"
                            <?= isset($settings['registration_open']) && $settings['registration_open'] == 1 ? 'checked' : '' ?>>
                        <span class="font-medium">Registration Open</span>
                    </label>
                    <p class="text-sm text-gray-500 ml-8">If unchecked, new users cannot register.</p>
                </div>

                <!-- Current Cohort -->
                <div>
                    <label class="block font-semibold mb-1">Current Cohort</label>
                    <select name="current_cohort" required class="w-full p-2 border rounded">
                        <?php foreach ($cohorts as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" 
                                <?= (isset($settings['current_cohort']) && $settings['current_cohort'] === $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Notice Banner -->
                <div>
                    <label class="block font-semibold mb-1">Dashboard Notice Banner</label>
                    <textarea name="notice_banner" rows="3"
                        class="w-full p-2 border rounded"><?= htmlspecialchars($settings['notice_banner'] ?? '') ?></textarea>
                    <p class="text-sm text-gray-500">This will appear on the student dashboard and login page.</p>
                </div>

                <!-- Sender Name -->
                <div>
                    <label class="block font-semibold mb-1">Sender Name</label>
                    <input type="text" name="sender_name"
                        value="<?= htmlspecialchars($settings['sender_name'] ?? '') ?>"
                        class="w-full p-2 border rounded">
                </div>

                <!-- Sender Email -->
                <div>
                    <label class="block font-semibold mb-1">Sender Email</label>
                    <input type="email" name="sender_email"
                        value="<?= htmlspecialchars($settings['sender_email'] ?? '') ?>"
                        class="w-full p-2 border rounded">
                </div>

                <!-- Admission Email -->
                <div>
                    <label class="block font-semibold mb-1">Admissions Email</label>
                    <input type="email" name="admission_email"
                        value="<?= htmlspecialchars($settings['admission_email'] ?? '') ?>"
                        class="w-full p-2 border rounded">
                </div>

                <!-- Admission Deadline -->
                <div>
                    <label class="block font-semibold mb-1">Admission Deadline</label>
                    <input type="date" name="admission_deadline"
                        value="<?= htmlspecialchars($settings['admission_deadline'] ?? '') ?>"
                        class="w-full p-2 border rounded">
                </div>

                <!-- Terms Link -->
                <div>
                    <label class="block font-semibold mb-1">Terms & Conditions Link</label>
                    <input type="url" name="terms_link" value="<?= htmlspecialchars($settings['terms_link'] ?? '') ?>"
                        class="w-full p-2 border rounded">
                </div>

                <!-- reCAPTCHA Site Key -->
                <div>
                    <label class="block font-semibold mb-1">reCAPTCHA Site Key</label>
                    <input type="text" name="recaptcha_site_key" 
                           value="<?= htmlspecialchars($settings['recaptcha_site_key'] ?? '') ?>" 
                           class="w-full p-2 border rounded">
                </div>

                <!-- reCAPTCHA Secret Key -->
                <div>
                    <label class="block font-semibold mb-1">reCAPTCHA Secret Key</label>
                    <input type="password" name="recaptcha_secret_key" 
                           value="<?= htmlspecialchars($settings['recaptcha_secret_key'] ?? '') ?>" 
                           class="w-full p-2 border rounded">
                </div>

                <div>
                    <button type="submit" class="bg-purple-700 text-white px-6 py-2 rounded hover:bg-purple-800">
                        💾 Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>