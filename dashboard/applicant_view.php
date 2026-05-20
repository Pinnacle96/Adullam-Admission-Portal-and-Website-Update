<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';
require_once 'utils/generate_admission_letter.php';
require_once 'utils/send_admission_email.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: ../index");
    exit;
}

$adminId   = $_SESSION['user_id'];
$adminName = $_SESSION['name'];
$role      = $_SESSION['role'];
$applicantId = $_GET['id'] ?? null;

if (!$applicantId) {
    echo "<p class='text-center mt-10 text-red-600 font-bold'>No applicant ID provided.</p>";
    exit;
}

function ensureDeferralRequestsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deferral_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_cohort VARCHAR(100) NULL,
            to_cohort VARCHAR(100) NOT NULL,
            reason TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            review_comment TEXT NULL,
            INDEX idx_user_status (user_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function ensureCohortsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cohorts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function sendDeferralDecisionEmail(PDO $pdo, int $studentId, string $decision, ?string $fromCohort, ?string $toCohort, string $reviewComment): void {
    try {
        $u = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $u->execute([(int)$studentId]);
        $user = $u->fetch(PDO::FETCH_ASSOC) ?: [];
        $email = trim((string)($user['email'] ?? ''));
        $name = trim((string)(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        if ($name === '') {
            $name = 'Student';
        }

        if ($email === '') {
            return;
        }

        $decisionText = $decision === 'approved' ? 'Approved' : 'Rejected';
        $subject = "Deferral Request {$decisionText} - Adullam Seminary";
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $body = "
        <div style='font-family:Segoe UI,sans-serif;max-width:650px;margin:auto;padding:24px;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;'>
          <h2 style='margin:0 0 12px;color:#6B21A8;'>Deferral Request {$decisionText}</h2>
          <p style='margin:0 0 10px;color:#111827;font-size:15px;'>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
          <p style='margin:0 0 14px;color:#374151;font-size:14px;line-height:1.6;'>
            Your deferral request has been <strong>{$decisionText}</strong>.
          </p>
          <div style='background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:12px;'>
            <div style='color:#111827;font-size:14px;'><strong>From:</strong> " . htmlspecialchars($fromCohort ?: 'N/A') . "</div>
            <div style='color:#111827;font-size:14px;'><strong>To:</strong> " . htmlspecialchars($toCohort ?: 'N/A') . "</div>
            " . ($reviewComment !== '' ? "<div style='color:#111827;font-size:14px;margin-top:6px;'><strong>Admin Note:</strong> " . nl2br(htmlspecialchars($reviewComment)) . "</div>" : "") . "
          </div>
        </div>";

        sendMail($email, $name, $subject, $body);
    } catch (Throwable $ignored) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string)$_POST['action'];

    if (in_array($action, ['admit', 'reject'], true)) {
        $newStatus = $action === 'admit' ? 'admitted' : 'rejected';
        $comment   = $_POST['comment'] ?? '';

        $update = $pdo->prepare("UPDATE applications SET status = ? WHERE user_id = ?");
        $update->execute([$newStatus, $applicantId]);

        $log = $pdo->prepare("INSERT INTO reviews_audit (reviewer_id, reviewer_name, applicant_id, decision, comment)
                              VALUES (?, ?, ?, ?, ?)");
        $log->execute([$adminId, $adminName, $applicantId, $newStatus, $comment]);

        if ($newStatus === 'admitted') {
            generateAdmissionLetter($applicantId, $pdo);
            sendAdmissionEmail($applicantId, $pdo);
        } else {
            $getUser = $pdo->prepare("SELECT first_name, email FROM users WHERE id = ?");
            $getUser->execute([$applicantId]);
            $user = $getUser->fetch();

            if ($user) {
                $first = htmlspecialchars($user['first_name']);
                $email = $user['email'];

                $subject = "Application Decision - Adullam Seminary";
                $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

                $body = "
                <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:auto;padding:24px;background:#f9fafb;border-radius:10px;border:1px solid #e0e0e0;'>
                  <div style='text-align:center;'>
                    <img src='https://adullam.ng/assets/logo.png' alt='Adullam Seminary' style='height:70px;margin-bottom:10px;' />
                    <h2 style='color:#6B21A8;'>Adullam Seminary Admissions</h2>
                  </div>
                  <p style='font-size:16px;color:#111;'>Dear <strong>$first</strong>,</p>
                  <p style='font-size:15px;color:#333;line-height:1.6;'>
                    We regret to inform you that your application has not been successful at this time.
                  </p>
                  <p style='font-size:15px;color:#333;line-height:1.6;'>
                    You may reach out to us for further clarification or consider applying again in the future.
                  </p>
                  <hr style='margin:30px 0;'>
                  <p style='font-size:13px;color:#888;text-align:center;'>
                    For questions, contact <a href='mailto:admissions@adullam.ng' style='color:#6B21A8;'>admissions@adullam.ng</a><br/>
                    &copy; " . date('Y') . " Adullam Seminary. All rights reserved.
                  </p>
                </div>";

                sendMail($email, $first, $subject, $body);
            }
        }

        header("Location: applicant_view?id=$applicantId&status_updated=1");
        exit;
    }

    if ($action === 'change_cohort') {
        $newCohort = trim((string)($_POST['new_cohort'] ?? ''));
        $note = trim((string)($_POST['comment'] ?? ''));

        if ($newCohort !== '') {
            $old = $pdo->prepare("SELECT cohort FROM applications WHERE user_id = ?");
            $old->execute([$applicantId]);
            $oldCohort = $old->fetchColumn();

            $upd = $pdo->prepare("UPDATE applications SET cohort = ? WHERE user_id = ?");
            $upd->execute([$newCohort, $applicantId]);

            $log = $pdo->prepare("INSERT INTO reviews_audit (reviewer_id, reviewer_name, applicant_id, decision, comment)
                                  VALUES (?, ?, ?, ?, ?)");
            $message = "Cohort changed from " . ($oldCohort ?: 'N/A') . " to " . $newCohort . ($note !== '' ? " | " . $note : "");
            $log->execute([$adminId, $adminName, $applicantId, 'cohort_changed', $message]);
        }

        header("Location: applicant_view?id=$applicantId&cohort_updated=1");
        exit;
    }

    if (in_array($action, ['approve_deferral', 'reject_deferral'], true)) {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $reviewComment = trim((string)($_POST['review_comment'] ?? ''));

        if ($requestId > 0) {
            ensureDeferralRequestsTable($pdo);

            $reqStmt = $pdo->prepare("SELECT * FROM deferral_requests WHERE id = ? AND user_id = ? LIMIT 1");
            $reqStmt->execute([$requestId, $applicantId]);
            $req = $reqStmt->fetch();

            if ($req && ($req['status'] ?? '') === 'pending') {
                if ($action === 'approve_deferral') {
                    $toCohort = $req['to_cohort'] ?? '';
                    if ($toCohort !== '') {
                        $old = $pdo->prepare("SELECT cohort FROM applications WHERE user_id = ?");
                        $old->execute([$applicantId]);
                        $oldCohort = $old->fetchColumn();

                        $upd = $pdo->prepare("UPDATE applications SET cohort = ? WHERE user_id = ?");
                        $upd->execute([$toCohort, $applicantId]);

                        $done = $pdo->prepare("UPDATE deferral_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_comment = ? WHERE id = ?");
                        $done->execute([$adminId, $reviewComment ?: null, $requestId]);

                        $log = $pdo->prepare("INSERT INTO reviews_audit (reviewer_id, reviewer_name, applicant_id, decision, comment)
                                              VALUES (?, ?, ?, ?, ?)");
                        $message = "Deferral approved: " . ($oldCohort ?: 'N/A') . " -> " . $toCohort . ($reviewComment !== '' ? " | " . $reviewComment : "");
                        $log->execute([$adminId, $adminName, $applicantId, 'deferral_approved', $message]);
                        sendDeferralDecisionEmail($pdo, $applicantId, 'approved', $oldCohort ?: ($req['from_cohort'] ?? ''), $toCohort, $reviewComment);
                    }
                } else {
                    $done = $pdo->prepare("UPDATE deferral_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_comment = ? WHERE id = ?");
                    $done->execute([$adminId, $reviewComment ?: null, $requestId]);

                    $log = $pdo->prepare("INSERT INTO reviews_audit (reviewer_id, reviewer_name, applicant_id, decision, comment)
                                          VALUES (?, ?, ?, ?, ?)");
                    $message = "Deferral rejected" . ($reviewComment !== '' ? " | " . $reviewComment : "");
                    $log->execute([$adminId, $adminName, $applicantId, 'deferral_rejected', $message]);
                    sendDeferralDecisionEmail($pdo, $applicantId, 'rejected', $req['from_cohort'] ?? '', $req['to_cohort'] ?? '', $reviewComment);
                }
            }
        }

        header("Location: applicant_view?id=$applicantId&deferral_updated=1");
        exit;
    }

    header("Location: applicant_view?id=$applicantId");
    exit;
}

// ---- Fetch Applicant Data ----
function fetchData(PDO $pdo, string $table, int $userId): array|false {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

$appStmt = $pdo->prepare("SELECT status, cohort, admission_no FROM applications WHERE user_id = ?");
$appStmt->execute([$applicantId]);
$applicationRow = $appStmt->fetch() ?: [];
$appStatus = $applicationRow['status'] ?? 'in_progress';
$appCohort = $applicationRow['cohort'] ?? '';
$admissionNo = $applicationRow['admission_no'] ?? '';

$pendingDeferralRequest = null;
try {
    ensureDeferralRequestsTable($pdo);
    $reqStmt = $pdo->prepare("SELECT * FROM deferral_requests WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $reqStmt->execute([$applicantId]);
    $pendingDeferralRequest = $reqStmt->fetch() ?: null;
} catch (Throwable $e) {
    $pendingDeferralRequest = null;
}

$cohorts = [];
try {
    ensureCohortsTable($pdo);
    $currentCohortSetting = trim((string)($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: ''));
    if ($currentCohortSetting !== '') {
        $ins = $pdo->prepare("INSERT IGNORE INTO cohorts (name) VALUES (?)");
        $ins->execute([$currentCohortSetting]);
    }
    if ($appCohort !== '') {
        $ins = $pdo->prepare("INSERT IGNORE INTO cohorts (name) VALUES (?)");
        $ins->execute([$appCohort]);
    }
    $cohorts = $pdo->query("SELECT name FROM cohorts ORDER BY name DESC")->fetchAll(PDO::FETCH_COLUMN);
    $cohorts = array_values(array_unique(array_map('trim', $cohorts)));
} catch (Throwable $e) {
    try {
        $cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
        $cohorts = array_values(array_unique(array_map('trim', $cohorts)));
        $currentCohortSetting = trim((string)($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: ''));
        if ($currentCohortSetting !== '' && !in_array($currentCohortSetting, $cohorts, true)) {
            array_unshift($cohorts, $currentCohortSetting);
        }
        if ($appCohort !== '' && !in_array($appCohort, $cohorts, true)) {
            array_unshift($cohorts, $appCohort);
        }
        rsort($cohorts);
    } catch (Throwable $e2) {
        $cohorts = [];
    }
}

$appDetails   = fetchData($pdo, 'application_details', $applicantId);
$personalInfo = fetchData($pdo, 'application_personal', $applicantId);
$churchInfo   = fetchData($pdo, 'application_church', $applicantId);
$autoInfo     = fetchData($pdo, 'application_autobiography', $applicantId);
$reference    = fetchData($pdo, 'application_references', $applicantId);
$docs         = fetchData($pdo, 'application_documents', $applicantId);

$passportUrl = $docs['passport'] ?? '';

// Document list (optional ones only show if uploaded)
$docFiles = [
    'SSCE Certificate (First Sitting)'  => $docs['ssce_cert'] ?? '',
    'SSCE Certificate (Second Sitting)' => $docs['ssce_cert2'] ?? '',
    'Birth Certificate'     => $docs['birth_cert'] ?? '',
    'Origin Certificate'    => $docs['origin_cert'] ?? '',
    'Recommendation Letter' => $docs['recommendation'] ?? '',
    'Payment Proof'         => $docs['payment_proof'] ?? '',
    'Degree Certificate'    => $docs['degree_cert'] ?? '',
    'Transcript'            => $docs['transcript'] ?? ''
];

// Render helper
function renderTable(string $title, array $data): void {
    echo "<div class='mt-8'>
        <h3 class='text-lg font-bold text-purple-700 mb-2'>$title</h3>
        <div class='grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 bg-gray-50 p-4 rounded-lg shadow'>";
    foreach ($data as $key => $value) {
        if (in_array($key, ['id', 'user_id'])) continue;
        echo "<div>
                <p class='text-xs text-gray-500 mb-1'>" . ucwords(str_replace('_', ' ', $key)) . "</p>
                <p class='text-sm font-medium text-gray-800 bg-white border px-3 py-1 rounded'>" . nl2br(htmlspecialchars($value ?? '')) . "</p>
              </div>";
    }
    echo "</div></div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Applicant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-6 max-w-6xl mx-auto">
            <h1 class="text-2xl font-bold text-purple-800 mb-4 text-center">📋 Applicant Review Panel</h1>

            <?php if (isset($_GET['status_updated'])): ?>
                <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4 text-center">
                    Application status updated and email sent.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['cohort_updated'])): ?>
                <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4 text-center">
                    Cohort updated.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['deferral_updated'])): ?>
                <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4 text-center">
                    Deferral request updated.
                </div>
            <?php endif; ?>

            <div class="bg-white shadow p-6 rounded-xl">
                <div class="flex flex-col sm:flex-row gap-6 items-center">
                    <div class="w-40 h-40 overflow-hidden rounded-full border border-gray-300">
                        <?php if ($passportUrl): ?>
                            <img src="<?= $passportUrl ?>" alt="Passport Photo" class="object-cover w-full h-full">
                        <?php else: ?>
                            <div class="text-sm text-gray-400 flex items-center justify-center h-full">No Photo</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">
                            <?= htmlspecialchars($appDetails['full_name'] ?? 'Applicant') ?>
                        </h2>
                        <p class="text-sm text-gray-600">
                            Status: <span class="uppercase text-purple-700 font-semibold"><?= $appStatus ?></span>
                        </p>
                        <?php if ($admissionNo): ?>
                            <p class="text-sm text-gray-600">
                                Admission No: <span class="text-purple-700 font-semibold"><?= htmlspecialchars($admissionNo) ?></span>
                            </p>
                        <?php endif; ?>
                        <p class="text-sm text-gray-600">
                            Cohort: <span class="text-purple-700 font-semibold"><?= htmlspecialchars($appCohort ?: 'N/A') ?></span>
                        </p>
                    </div>
                </div>

                <?php
                if ($appDetails)   renderTable('Program Details', $appDetails);
                if ($personalInfo) renderTable('Personal Information', $personalInfo);
                if ($churchInfo)   renderTable('Church Information', $churchInfo);
                if ($autoInfo)     renderTable('Autobiography', $autoInfo);
                if ($reference)    renderTable('References', $reference);
                ?>

                <!-- Uploaded Documents -->
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-purple-700 mb-2">📎 Uploaded Documents</h3>
                    <ul class="space-y-2">
                        <?php foreach ($docFiles as $label => $path): ?>
                            <?php if ($path): ?>
                                <li class="flex justify-between items-center border-b py-2">
                                    <span><?= $label ?></span>
                                    <div class="space-x-2">
                                        <a href="<?= $path ?>" target="_blank"
                                           class="bg-blue-600 text-white px-3 py-1 text-sm rounded hover:bg-blue-700">View</a>
                                        <a href="<?= $path ?>" download
                                           class="bg-purple-700 text-white px-3 py-1 text-sm rounded hover:bg-purple-800">Download</a>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-bold text-purple-700 mb-3">Cohort Management</h3>
                    <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <input type="hidden" name="action" value="change_cohort">
                        <div class="sm:col-span-1">
                            <label class="block text-xs text-gray-500 mb-1">New Cohort</label>
                            <select name="new_cohort" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Select cohort</option>
                                <?php foreach ($cohorts as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>" <?= $c === $appCohort ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-xs text-gray-500 mb-1">Note (optional)</label>
                            <input type="text" name="comment" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Reason for change">
                        </div>
                        <div class="sm:col-span-1">
                            <button type="submit" class="w-full bg-purple-700 text-white px-4 py-2 rounded hover:bg-purple-800">
                                Update Cohort
                            </button>
                        </div>
                    </form>
                </div>

                <?php if ($pendingDeferralRequest): ?>
                    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="text-lg font-bold text-yellow-800 mb-2">Pending Deferral Request</h3>
                        <div class="text-sm text-yellow-900 space-y-1">
                            <div>From: <?= htmlspecialchars($pendingDeferralRequest['from_cohort'] ?? 'N/A') ?></div>
                            <div>To: <?= htmlspecialchars($pendingDeferralRequest['to_cohort'] ?? '') ?></div>
                            <?php if (!empty($pendingDeferralRequest['reason'])): ?>
                                <div>Reason: <?= htmlspecialchars($pendingDeferralRequest['reason']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($pendingDeferralRequest['created_at'])): ?>
                                <div>Requested: <?= htmlspecialchars($pendingDeferralRequest['created_at']) ?></div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <input type="hidden" name="request_id" value="<?= (int)($pendingDeferralRequest['id'] ?? 0) ?>">
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-yellow-700 mb-1">Review comment (optional)</label>
                                <input type="text" name="review_comment" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-300" placeholder="Comment">
                            </div>
                            <div class="sm:col-span-1 flex gap-2">
                                <button name="action" value="approve_deferral" class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                    Approve
                                </button>
                                <button name="action" value="reject_deferral" class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    Reject
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Action Form (Only show if NOT admitted) -->
                <?php if ($appStatus !== 'admitted'): ?>
                <form method="POST" class="mt-10 space-y-4">
                    <div>
                        <label for="comment" class="text-sm font-medium text-gray-700">📜 Review Comments</label>
                        <textarea name="comment" id="comment" rows="4" class="w-full border rounded p-3 mt-1"
                                  placeholder="Write any notes or comments..."></textarea>
                    </div>
                    <div class="flex gap-4 justify-center">
                        <button name="action" value="admit"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                            ✅ Admit Applicant
                        </button>
                        <button name="action" value="reject"
                                class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                            ❌ Reject Applicant
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="mt-10 text-center">
                    <a href="applicants_list"
                       class="inline-block bg-purple-700 text-white px-6 py-2 rounded-lg shadow hover:bg-purple-800">
                        ⬅ Back to Applicants List
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
