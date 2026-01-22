<?php
session_start();

// DEBUG - Show PHP errors during development
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require 'db.php';
require 'mailer.php';

// ✅ Ensure only logged-in students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Prevent duplicate final submission
$check = $pdo->prepare("SELECT admission_no FROM applications WHERE user_id = ? AND submitted = 1");
$check->execute([$user_id]);
if ($check->fetchColumn()) {
    header("Location: dashboard");
    exit;
}

// ✅ Generate unique admission number
function generateUniqueAdmissionNo($pdo) {
    do {
        $last = $pdo->query("SELECT COUNT(*) FROM applications WHERE admission_no IS NOT NULL")->fetchColumn();
        $nextSerial = str_pad((int)$last + rand(1, 999), 4, '0', STR_PAD_LEFT);
        $admissionNo = "ADM/JUN/2026/" . $nextSerial;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE admission_no = ?");
        $stmt->execute([$admissionNo]);
        $exists = $stmt->fetchColumn();
    } while ($exists);

    return $admissionNo;
}

$admissionNo = generateUniqueAdmissionNo($pdo);

// ✅ Fetch Current Active Cohort
$cohortStmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'");
$activeCohort = $cohortStmt->fetchColumn() ?: 'January 2026'; // Default fallback

// ✅ Mark as submitted and assign admission number
$pdo->prepare("
    UPDATE applications 
    SET submitted = 1, status = 'submitted', admission_no = ?, submitted_at = NOW(), cohort = ? 
    WHERE user_id = ?
")->execute([$admissionNo, $activeCohort, $user_id]);

// 📧 Fetch user info
$stmt = $pdo->prepare("SELECT first_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 📧 Fetch program details
$progStmt = $pdo->prepare("SELECT program, ma_focus, mode_of_study, res_country FROM application_details WHERE user_id = ?");
$progStmt->execute([$user_id]);
$progData = $progStmt->fetch();

if (!$progData) {
        $_SESSION['error'] = "Program details not found.";
        header("Location: dashboard");
        exit;
    }

    $program   = $progData['program'];
    $ma_focus  = $progData['ma_focus'] ?? '';
    $study_mode = $progData['mode_of_study'];
    $country   = $progData['res_country'];

    // ✅ Normalize for comparisons (safe, but display original values)
    $normalizedProgram = strtolower(trim($program));
    $normalizedMode    = strtolower(trim($study_mode));
    $isNigerian        = (strtolower(trim($country)) === 'nigeria');

    // 📬 Email subject
    $subject = "✅ Application Submission Confirmation - RCN Theological Seminary";
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    // 📬 Email variables
    $first = htmlspecialchars($user['first_name']);
    $admissionNo = htmlspecialchars($admissionNo);
    $program_display = htmlspecialchars($program);
    $program_display .= ($normalizedProgram === 'ma') ? " ($ma_focus)" : "";
    $study_mode_display = htmlspecialchars($study_mode);
    $portalLink = '<a href="https://adullam.ng/dashboard/applicant_login" style="color:#6B21A8;text-decoration:underline;">Login Here</a>';

// --- Start Dynamic Fees Document Logic ---
$feeDocUrl = '';

switch ($normalizedProgram) {
    case 'ma':
        $feeDocUrl = $isNigerian
            ? ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_MA_Local_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_MA_Local_Fees.pdf')
            : ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_MA_International_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_MA_International_Fees.pdf');
        break;

    case 'pgdt':
        $feeDocUrl = $isNigerian
            ? ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_PGDT_Local_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_PGDT_Local_Fees.pdf')
            : ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_PGDT_International_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_PGDT_International_Fees.pdf');
        break;

    case 'b.div':
        $feeDocUrl = $isNigerian
            ? ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_BACHELOR_Local_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_BACHELOR_Local_Fees.pdf')
            : ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_BACHELOR_International_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_BACHELOR_International_Fees.pdf');
        break;

    case 'diploma':
        $feeDocUrl = $isNigerian
            ? ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_DIPLOMA_Local_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_DIPLOMA_Local_Fees.pdf')
            : ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_DIPLOMA_International_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_DIPLOMA_International_Fees.pdf');
        break;

    case 'certificate':
        $feeDocUrl = $isNigerian
            ? ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_CERTIFICATE_Local_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_CERTIFICATE_Local_Fees.pdf')
            : ($normalizedMode === 'online'
                ? 'https://adullam.ng/fees/Online_CERTIFICATE_International_Fees.pdf'
                : 'https://adullam.ng/fees/Onsite_CERTIFICATE_International_Fees.pdf');
        break;

    default:
        $feeDocUrl = 'https://adullam.ng/fees/fees_structure.pdf';
        break;
}

$feeDocLink   = '<a href="' . htmlspecialchars($feeDocUrl) . '" style="color:#6B21A8;text-decoration:underline;">fee document</a>';
$contactEmail = '<a href="mailto:rcnts.adullam@gmail.com" style="color:#6B21A8;text-decoration:underline;">rcnts.adullam@gmail.com</a>';
// --- End Dynamic Fees Document Logic ---

// 📧 Email body
$body = "
<div style=\"max-width:600px;margin:auto;padding:20px;background-color:#f9fafb;
     font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;border-radius:8px;
     border:1px solid #eee;\">

  <div style=\"text-align:center;margin-bottom:20px;\">
    <img src=\"https://adullam.ng/assets/logo.png\" alt=\"Adullam Seminary\" style=\"height:80px;margin-bottom:10px;\" />
    <h2 style=\"color:#6B21A8;margin:0;\">RCN Theological Seminary - Adullam</h2>
  </div>

  <hr style=\"margin:20px 0;border:none;border-top:1px solid #ddd;\" />

  <p style=\"font-size:16px;color:#111;margin-bottom:15px;\">
    Dear <strong>$first</strong>,
  </p>

  <p style=\"font-size:15px;color:#333;line-height:1.6;\">
    We are pleased to confirm that your application has been successfully submitted to
    <strong>RCN Theological Seminary - Adullam</strong>.
  </p>

  <p style=\"font-size:15px;color:#333;\"><strong>Your Application Number:</strong> $admissionNo</p>
  <p style=\"font-size:15px;color:#333;\"><strong>Program:</strong> $program_display</p>
  <p style=\"font-size:15px;color:#333;\"><strong>Study Option:</strong> $study_mode_display</p>

  <p style=\"font-size:15px;color:#333;line-height:1.6;\">
    The admissions committee will carefully review your application within the next
    <strong>21 days</strong>. Login to your application portal $portalLink or keep an eye on your email for updates on your admission status.
  </p>

  <p style=\"font-size:15px;color:#333;line-height:1.6;\">
    Resumption for the session is scheduled for <strong>Monday, 5th January, 2026</strong>.
  </p>

  <p style=\"font-size:15px;color:#333;line-height:1.6;\">
    Upon receiving the admission notice, you are required to do the following:
  </p>
  <ol style=\"font-size:15px;color:#333;line-height:1.6; padding-left: 20px;\">";

// Add mode-specific content
if ($normalizedMode === 'online') {
    $body .= "
    <li>Pay your fees with at least 60% initial installment of the entire first semester's fees detailed in the $feeDocLink. However, full fee payment at the beginning of the semester is also acceptable.</li>
    <li>On your student portal, upload your fee payment receipt. The onboarding of online students to the learning platform is dependent on the upload of the minimum required fee receipt on the student portal.</li>";
} else {
    $body .= "
    <li>Pay your fees with at least 60% initial installment of the entire first semester's fees detailed in the $feeDocLink. However, full fee payment at the beginning of the semester is also acceptable.</li>
    <li>Students are required to start preparing to travel to Makurdi, Benue State, Nigeria, on time to meet up with the resumption, as late resumption may lead to forfeiture of admission. This is especially critical for international students who may need up to a month to process their Visa.</li>
    <li>Students interested in staying in the school hostel should note that admission into the hostel starts on the day of resumption. However, they may pay and upload to reserve accommodation via their student dashboard once admitted. Very Limited space is available.</li>";
}

$body .= "
  </ol>

  <p style=\"font-size:15px;color:#333;line-height:1.6;\">
    If after 21 days of submitting your application with all the admissions requirements met, but you haven't heard from us, kindly reach out to us via $contactEmail
  </p>

  <p style=\"font-size:15px;color:#333;line-height:1.6;\">
    We trust God will provide all your needs as you embark on this academic journey.
  </p>

  <p style=\"font-size:15px;color:#333;line-height:1.6;margin-bottom:30px;\">
    Warm regards,<br />
    <strong>Adullam Admissions Committee</strong>
  </p>

  <hr style=\"margin:20px 0;border:none;border-top:1px solid #ddd;\" />

  <p style=\"font-size:13px;color:#888;text-align:center;\">
    For questions, contact $contactEmail<br />
    &copy; " . date('Y') . " Adullam Seminary – All rights reserved.
  </p>
</div>
";

// ✅ Send email
sendMail($user['email'], $user['first_name'], $subject, $body);

// ✅ Redirect after success
header("Location: dashboard?submitted=1");
exit;
