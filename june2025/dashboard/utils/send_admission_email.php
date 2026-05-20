<?php
require_once __DIR__ . '/../mailer.php';

function sendAdmissionEmail($user_id, $pdo)
{
    $userStmt = $pdo->prepare("SELECT first_name, email FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch();

    $appStmt = $pdo->prepare("SELECT admission_no FROM applications WHERE user_id = ?");
    $appStmt->execute([$user_id]);
    $admission_no = $appStmt->fetchColumn();

    $detailsStmt = $pdo->prepare("SELECT program, ma_focus FROM application_details WHERE user_id = ?");
    $detailsStmt->execute([$user_id]);
    $details = $detailsStmt->fetch();

    if (!$user || !$admission_no || !$details) return false;

    $first = htmlspecialchars($user['first_name']);
    $email = $user['email'];
    $programCode = strtoupper($details['program']);
    $ma_focus = $details['ma_focus'] ?? '';
    $letter_link = 'https://adullam.ng/dashboard/letters/admission_letters/' . $user_id . '.pdf';
    $logo = 'https://adullam.ng/assets/img/logo1.png';

    // Program map
    $programMap = [
    'MA' => ['name' => 'Master of Arts', 'duration' => '24 months'],
    'PGDT' => ['name' => 'Postgraduate Diploma', 'duration' => '10 months'],
    'B.DIV' => ['name' => 'Bachelor of Divinity', 'duration' => '4 years'],
    'DIPLOMA' => ['name' => 'Diploma in Theology', 'duration' => '3 years'],
    'CERTIFICATE' => ['name' => 'Certificate in Theology', 'duration' => '1 year'],
];

$programLabel = $programMap[$programCode]['name'] ?? $programCode;

    $subject = "🎉 Congratulations $first – You Have Been Admitted!";
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $body = "
    <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:auto;padding:24px;background:#f9fafb;border-radius:10px;border:1px solid #e0e0e0;'>
      <div style='text-align:center;'>
        <img src='$logo' alt='Adullam Seminary' style='height:70px;margin-bottom:10px;' />
        <h2 style='color:#6B21A8;'>Adullam Seminary Admissions</h2>
      </div>

      <p style='font-size:16px;color:#111;'>Dear <strong>$first</strong>,</p>

      <p style='font-size:15px;color:#333;line-height:1.6;'>
        Congratulations! You have been <strong>admitted</strong> into
        <strong>$programLabel</strong> program at <strong>RCN Theological Seminary – Adullam</strong>.
      </p>

     <p style='font-size:15px;line-height:1.6;color:#333;'>
    <strong>Admission Number:</strong> $admission_no<br/>
    <strong>Program:</strong> $programLabel" .
    ($programCode === 'MA' ? "<br/><strong>Focus Area:</strong> {$ma_focus}" : "") . "
</p>


      <p style='font-size:15px;color:#333;line-height:1.6;margin:20px 0;'>
        Please proceed to your student dashboard to <strong>download your admission letter</strong>
        and begin your onboarding process.
      </p>

      <div style='text-align:center;margin:20px 0;'>
        <a href='$letter_link' style='background:#6B21A8;color:white;padding:12px 24px;
            text-decoration:none;border-radius:6px;font-weight:bold;'>📃 View Admission Letter</a>
      </div>

      <p style='font-size:15px;color:#333;line-height:1.6;'>
        Kindly note, you are expected to pay a minimum of <strong>60% of your total fee per Semester and upload Proof of payment on your student dashboard</strong> before resumption date.<br>
        scheduled for <strong>June 26, 2025.</strong>.
      </p>

      <p style='font-size:15px;color:#333;line-height:1.6;'>
        We are excited to walk this spiritual and academic journey with you!
      </p>
      <p style='font-size:15px;color:#333;line-height:1.6;'>
        Onsite students who wish to reside in the school hostel facility can now reserve hostel space via their dashboard.
      </p>
      <hr style='margin:30px 0;'>

      <p style='font-size:13px;color:#888;text-align:center;'>
        For questions, contact <a href='mailto:rcnts.adullam@gmail.com' style='color:#6B21A8;'>rcnts.adullam@gmail.com</a><br/>
        &copy; " . date('Y') . " Adullam Seminary. All rights reserved.
      </p>
    </div>
    ";

    return sendMail($email, $first, $subject, $body);
}
