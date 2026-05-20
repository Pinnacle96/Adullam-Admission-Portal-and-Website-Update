<?php
session_start();
require 'db.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

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

function getAdminRecipients(PDO $pdo): array {
    $recipients = [];
    try {
        $stmt = $pdo->query("SELECT first_name, last_name, email FROM users WHERE role IN ('admin', 'superadmin') AND email IS NOT NULL AND email != ''");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $email = trim((string)($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $name = trim((string)(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
            $recipients[] = ['email' => $email, 'name' => $name !== '' ? $name : 'Admin'];
        }
    } catch (Throwable $ignored) {
        $recipients = [];
    }

    try {
        $settingsEmail = trim((string)($pdo->query("SELECT value FROM settings WHERE `key` = 'sender_email'")->fetchColumn() ?: ''));
        if ($settingsEmail !== '') {
            $recipients[] = ['email' => $settingsEmail, 'name' => 'Admin'];
        }
    } catch (Throwable $ignored) {
    }

    $seen = [];
    $deduped = [];
    foreach ($recipients as $r) {
        $email = strtolower(trim((string)($r['email'] ?? '')));
        if ($email === '') {
            continue;
        }
        if (isset($seen[$email])) {
            continue;
        }
        $seen[$email] = true;
        $deduped[] = $r;
    }
    $recipients = $deduped;

    if (!$recipients) {
        $recipients[] = ['email' => 'adullamadmissions@gmail.com', 'name' => 'Admin'];
    }

    return $recipients;
}

function sendDeferralSubmittedEmails(PDO $pdo, int $studentId, ?string $fromCohort, string $toCohort, string $reason): void {
    try {
        $u = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $u->execute([$studentId]);
        $user = $u->fetch(PDO::FETCH_ASSOC) ?: [];
        $studentEmail = trim((string)($user['email'] ?? ''));
        $studentName = trim((string)(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        if ($studentName === '') {
            $studentName = 'Student';
        }

        if ($studentEmail !== '') {
            $subject = "Deferral Request Submitted - Adullam Seminary";
            $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:650px;margin:auto;padding:24px;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;'>
              <h2 style='margin:0 0 12px;color:#6B21A8;'>Deferral Request Submitted</h2>
              <p style='margin:0 0 10px;color:#111827;font-size:15px;'>Dear <strong>" . htmlspecialchars($studentName) . "</strong>,</p>
              <p style='margin:0 0 14px;color:#374151;font-size:14px;line-height:1.6;'>
                Your request to defer your admission has been received and is now <strong>waiting for admin approval</strong>.
              </p>
              <div style='background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:12px;'>
                <div style='color:#111827;font-size:14px;'><strong>From:</strong> " . htmlspecialchars($fromCohort ?: 'N/A') . "</div>
                <div style='color:#111827;font-size:14px;'><strong>To:</strong> " . htmlspecialchars($toCohort) . "</div>
                " . ($reason !== '' ? "<div style='color:#111827;font-size:14px;margin-top:6px;'><strong>Reason:</strong> " . nl2br(htmlspecialchars($reason)) . "</div>" : "") . "
              </div>
              <p style='margin:14px 0 0;color:#6B7280;font-size:12px;'>You will receive another email once it has been reviewed.</p>
            </div>";

            sendMail($studentEmail, $studentName, $subject, $body);
        }

        $adminSubject = "New Deferral Request - Pending Approval";
        $adminSubject = '=?UTF-8?B?' . base64_encode($adminSubject) . '?=';
        $adminBody = "
        <div style='font-family:Segoe UI,sans-serif;max-width:650px;margin:auto;padding:24px;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;'>
          <h2 style='margin:0 0 12px;color:#6B21A8;'>New Deferral Request</h2>
          <p style='margin:0 0 14px;color:#374151;font-size:14px;line-height:1.6;'>
            A student has submitted a deferral request and it is waiting for approval.
          </p>
          <div style='background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:12px;'>
            <div style='color:#111827;font-size:14px;'><strong>Student ID:</strong> " . (int)$studentId . "</div>
            <div style='color:#111827;font-size:14px;'><strong>Student:</strong> " . htmlspecialchars($studentName) . "</div>
            <div style='color:#111827;font-size:14px;'><strong>From:</strong> " . htmlspecialchars($fromCohort ?: 'N/A') . "</div>
            <div style='color:#111827;font-size:14px;'><strong>To:</strong> " . htmlspecialchars($toCohort) . "</div>
            " . ($reason !== '' ? "<div style='color:#111827;font-size:14px;margin-top:6px;'><strong>Reason:</strong> " . nl2br(htmlspecialchars($reason)) . "</div>" : "") . "
          </div>
          <p style='margin:14px 0 0;color:#6B7280;font-size:12px;'>Review the request in the admin dashboard.</p>
        </div>";

        foreach (getAdminRecipients($pdo) as $admin) {
            sendMail($admin['email'], $admin['name'], $adminSubject, $adminBody);
        }
    } catch (Throwable $ignored) {
    }
}

// 🧠 Fetch application data
$app = $pdo->prepare("SELECT admission_no, status, submitted, cohort FROM applications WHERE user_id = ?");
$app->execute([$user_id]);
$appData = $app->fetch();
$addmissionNo = $appData['admission_no'] ?? '';
$status = $appData['status'] ?? 'incomplete';
$isSubmitted = $appData['submitted'] ?? 0;
$currentCohort = $appData['cohort'] ?? '';

$detail = $pdo->prepare("SELECT program, ma_focus, mode_of_study FROM application_details WHERE user_id = ?");
$detail->execute([$user_id]);
$student = $detail->fetch();
$program = $student['program'] ?? '';
$focus = $student['ma_focus'] ?? '';
$mode = $student['mode_of_study'] ?? 'online';

$docsStmt = $pdo->prepare("SELECT passport, transcript FROM application_documents WHERE user_id = ?");
$docsStmt->execute([$user_id]);
$docs = $docsStmt->fetch();
$passport = $docs['passport'] ?? '';
$transcriptUploaded = ($docs['transcript'] ?? '') !== '' ? 'Yes' : 'No';

$isAdmitted = $status === 'admitted';
$admissionLetterPath = "letters/admission_letters/{$user_id}.pdf";

$pendingDeferralRequest = null;
$availableCohorts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_deferral') {
    $toCohort = trim((string)($_POST['to_cohort'] ?? ''));
    $reason = trim((string)($_POST['reason'] ?? ''));

    if (!$isAdmitted) {
        $_SESSION['deferral_status'] = 'error';
        $_SESSION['deferral_message'] = 'Only admitted applicants can request a deferral.';
        header('Location: dashboard');
        exit;
    }

    if ($toCohort === '') {
        $_SESSION['deferral_status'] = 'error';
        $_SESSION['deferral_message'] = 'Please select the cohort you want to defer to.';
        header('Location: dashboard');
        exit;
    }

    if ($currentCohort !== '' && strcasecmp($toCohort, $currentCohort) === 0) {
        $_SESSION['deferral_status'] = 'error';
        $_SESSION['deferral_message'] = 'You are already in that cohort.';
        header('Location: dashboard');
        exit;
    }

    ensureDeferralRequestsTable($pdo);

    $check = $pdo->prepare("SELECT id FROM deferral_requests WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $check->execute([$user_id]);
    $existing = $check->fetchColumn();
    if ($existing) {
        $_SESSION['deferral_status'] = 'info';
        $_SESSION['deferral_message'] = 'Your deferral request is already pending review.';
        header('Location: dashboard');
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO deferral_requests (user_id, from_cohort, to_cohort, reason, status) VALUES (?, ?, ?, ?, 'pending')");
    $insert->execute([$user_id, $currentCohort ?: null, $toCohort, $reason ?: null]);
    sendDeferralSubmittedEmails($pdo, $user_id, $currentCohort ?: null, $toCohort, $reason);

    $_SESSION['deferral_status'] = 'success';
    $_SESSION['deferral_message'] = 'Deferral request submitted. Please wait for admin approval.';
    header('Location: dashboard');
    exit;
}

try {
    ensureDeferralRequestsTable($pdo);
    $reqStmt = $pdo->prepare("SELECT * FROM deferral_requests WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $reqStmt->execute([$user_id]);
    $pendingDeferralRequest = $reqStmt->fetch() ?: null;
} catch (Throwable $e) {
    $pendingDeferralRequest = null;
}

try {
    ensureCohortsTable($pdo);
    if ($currentCohort !== '') {
        $ins = $pdo->prepare("INSERT IGNORE INTO cohorts (name) VALUES (?)");
        $ins->execute([$currentCohort]);
    }
    $cohorts = $pdo->query("SELECT name FROM cohorts ORDER BY name DESC")->fetchAll(PDO::FETCH_COLUMN);
    $cohorts = array_values(array_unique(array_map('trim', $cohorts)));
    $availableCohorts = array_values(array_filter($cohorts, function ($c) use ($currentCohort) {
        if ($c === '') return false;
        if ($currentCohort !== '' && strcasecmp($c, $currentCohort) === 0) return false;
        return true;
    }));
} catch (Throwable $e) {
    try {
        $cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
        $cohorts = array_values(array_unique(array_map('trim', $cohorts)));
        rsort($cohorts);
        $availableCohorts = array_values(array_filter($cohorts, function ($c) use ($currentCohort) {
            if ($c === '') return false;
            if ($currentCohort !== '' && strcasecmp($c, $currentCohort) === 0) return false;
            return true;
        }));
    } catch (Throwable $e2) {
        $availableCohorts = [];
    }
}
