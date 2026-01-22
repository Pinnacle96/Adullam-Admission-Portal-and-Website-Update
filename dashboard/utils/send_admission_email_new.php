<?php
require_once __DIR__ . '/../mailer.php';
// Include the file containing the PDF generation function
require_once __DIR__ . '/generate_admission_letter.php'; 

function sendAdmissionEmail($user_id, $pdo)
{
    // Step 1: Generate the admission letter PDF
    // The generateAdmissionLetter function saves the PDF and returns true on success.
    if (!generateAdmissionLetter($user_id, $pdo)) {
        return false; // Could not generate the PDF, so don't send the email.
    }
    
    // Step 2: Retrieve user and application data
    $userStmt = $pdo->prepare("SELECT first_name, email FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch();

    $appStmt = $pdo->prepare("SELECT admission_no FROM applications WHERE user_id = ?");
    $appStmt->execute([$user_id]);
    $admission_no = $appStmt->fetchColumn();

    $detailsStmt = $pdo->prepare("SELECT program, ma_focus FROM application_details WHERE user_id = ?");
    $detailsStmt->execute([$user_id]);
    $details = $detailsStmt->fetch();

    if (!$user || !$admission_no || !$details) {
        return false;
    }

    $first = htmlspecialchars($user['first_name']);
    $email = $user['email'];
    $programCode = strtoupper($details['program']);
    $ma_focus = $details['ma_focus'] ?? '';

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
            Please find your official admission letter attached to this email.
        </p>

        <div style='text-align:center;margin:20px 0;'>
            <a href='https://adullam.ng/dashboard' style='background:#6B21A8;color:white;padding:12px 24px;
                text-decoration:none;border-radius:6px;font-weight:bold;'>Go to My Dashboard</a>
        </div>

        <p style='font-size:15px;color:#333;line-height:1.6;'>
            Kindly note, you are expected to pay a minimum of <strong>60% of your total fee per Semester and upload Proof of payment on your student dashboard</strong> before the resumption date, scheduled for <strong>January 5, 2026</strong>.
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

    // Step 3: Define the attachment path
    $attachmentPath = __DIR__ . '/../letters/admission_letters/' . $user_id . '.pdf';
    $attachmentName = "Adullam_Admission_Letter_{$admission_no}.pdf";

    // Step 4: Call the mailer function with the attachment
    return sendMail($email, $first, $subject, $body, $attachmentPath, $attachmentName);
}