<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    die("Unauthorized access.");
}

// 🔄 Fetch sender info from settings using prepared statements
$getSetting = function ($key) use ($pdo) {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
};
$senderName = $getSetting('sender_name') ?? 'Adullam Seminary';
$senderEmail = $getSetting('sender_email') ?? 'adullamadmissions@gmail.com';

// Fetch cohorts
$currentCohort = trim($getSetting('current_cohort') ?: 'January 2026');
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);

// Fetch mode_of_study options
$mode_of_study_options = $pdo->query("SELECT DISTINCT mode_of_study FROM application_details WHERE mode_of_study IS NOT NULL AND mode_of_study != ''")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $body = trim($_POST['message']);
    $target = $_POST['audience'];

    if (!$subject || !$body || !$target) {
        $error = "Please fill all fields.";
    } else {
        $cohort = trim($_POST['cohort'] ?? '');
        $mode_of_study = trim($_POST['mode_of_study'] ?? '');

        // Build base query and params
        $params = [];
        $where = ["u.role = 'student'"];
        $joins = [];
        
        switch ($target) {
            case 'admitted':
                $joins[] = "INNER JOIN applications a ON u.id = a.user_id";
                $where[] = "a.status = 'admitted'";
                break;
            case 'submitted':
                $joins[] = "INNER JOIN applications a ON u.id = a.user_id";
                $where[] = "a.submitted = 1 AND (a.status IS NULL OR a.status != 'admitted')";
                break;
            case 'rejected':
                $joins[] = "INNER JOIN applications a ON u.id = a.user_id";
                $where[] = "a.status = 'rejected'";
                break;
            case 'draft':
                $joins[] = "INNER JOIN applications a ON u.id = a.user_id";
                $where[] = "a.submitted = 0";
                break;
            case 'unstarted':
                $where[] = "u.id NOT IN (SELECT user_id FROM applications)";
                break;
            case 'all':
            default:
                break;
        }

        if (!empty($cohort)) {
            if (!in_array('INNER JOIN applications a ON u.id = a.user_id', $joins)) {
                $joins[] = "INNER JOIN applications a ON u.id = a.user_id";
            }
            $where[] = "a.cohort = ?";
            $params[] = $cohort;
        }
        
        if (!empty($mode_of_study)) {
            $joins[] = "INNER JOIN application_details ad ON u.id = ad.user_id";
            $where[] = "ad.mode_of_study = ?";
            $params[] = $mode_of_study;
        }

        $query = "SELECT DISTINCT u.first_name, u.last_name, u.email FROM users u";
        if (!empty($joins)) {
            $query .= " " . implode(" ", array_unique($joins));
        }
        $query .= " WHERE " . implode(" AND ", $where);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $logo = 'https://adullam.ng/assets/images/logo1.png';
        $wrappedBody = nl2br(htmlspecialchars($body));

        $formattedBody = "
<div style='font-family:Segoe UI, sans-serif; padding: 20px; max-width: 600px; margin:auto; background:#ffffff; border:1px solid #e0e0e0; border-radius:8px;'>
  <div style='text-align:center; margin-bottom: 20px;'>
    <img src='$logo' alt='Adullam Logo' style='height: 60px; margin-bottom: 10px;'>
    <h2 style='color:#6B21A8;'>Broadcast from $senderName</h2>
  </div>

  <div style='background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #ddd; font-size:15px; color:#333; line-height:1.6;'>
    $wrappedBody
  </div>

  <p style='font-size:15px; color:#333; line-height:1.6; margin-top: 20px;'>
    Thank you for choosing Adullam.
  </p>

  <p style='font-size:15px; color:#333; line-height:1.6; margin-bottom:30px;'>
    Warm regards,<br />
    <strong>Adullam Admissions Committee</strong>
  </p>

  <hr style='margin:20px 0; border:none; border-top:1px solid #ddd;' />

  <p style='font-size:13px; color:#888; text-align:center;'>
    For questions, contact <a href='mailto:admissions@adullam.ng' style='color:#6B21A8;'>admissions@adullam.ng</a><br />
    &copy; " . date('Y') . " Adullam Seminary – All rights reserved.
  </p>
</div>
";

        // Queue each message
        $insert = $pdo->prepare("INSERT INTO email_queue (to_email, to_name, subject, body, from_email, from_name, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

        $queued = 0;
        foreach ($recipients as $r) {
            $to = $r['email'];
            $toName = $r['first_name'] . ' ' . $r['last_name'];
            if ($insert->execute([$to, $toName, $subject, $formattedBody, $senderEmail, $senderName])) {
                $queued++;
            }
        }

        echo "
        <html><head>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <meta http-equiv='refresh' content='4;url=broadcast_email' />
        </head><body>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Emails Queued!',
                    text: 'Successfully queued $queued emails. They’ll be sent in the background shortly.',
                    timer: 4000,
                    showConfirmButton: false
                });
            </script>
        </body></html>";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Broadcast Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-6 max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-purple-800 mb-6">📧 Broadcast Email</h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow p-6 rounded-xl space-y-6">
                <div>
                    <label class="block mb-1 font-semibold">Target Audience</label>
                    <select name="audience" required class="w-full p-2 border rounded">
                        <option value="">-- Select Audience --</option>
                        <option value="all">All Applicants</option>
                        <option value="admitted">Only Admitted</option>
                        <option value="submitted">Submitted (Pending Review)</option>
                        <option value="rejected">Rejected Applicants</option>
                        <option value="draft">Incomplete Applications (In progress)</option>
                        <option value="unstarted">Registered but Not Started</option>
                    </select>
                </div>

                <!-- Cohort Filter -->
                <div>
                    <label class="block mb-1 font-semibold">Filter by Cohort (Optional)</label>
                    <select name="cohort" class="w-full p-2 border rounded">
                        <option value="">All Cohorts</option>
                        <?php foreach ($cohorts as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mode of Study Filter -->
                <div>
                    <label class="block mb-1 font-semibold">Filter by Mode of Study (Optional)</label>
                    <select name="mode_of_study" class="w-full p-2 border rounded">
                        <option value="">All Modes</option>
                        <?php foreach ($mode_of_study_options as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 font-semibold">Subject</label>
                    <input type="text" name="subject" class="w-full p-2 border rounded" required>
                </div>

                <div>
                    <label class="block mb-1 font-semibold">Message</label>
                    <textarea name="message" rows="8" class="w-full p-2 border rounded"
                        placeholder="Dear Applicant, orientation starts on Monday..." required></textarea>
                    <p class="text-sm text-gray-500 mt-1">You can type normally. We’ll format it into a beautiful email.</p>
                </div>

                <div>
                    <button type="submit" class="bg-purple-700 text-white px-6 py-2 rounded hover:bg-purple-800">
                        🚀 Send Broadcast
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
