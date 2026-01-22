<?php
require_once __DIR__ . '/../mailer.php';

function sendAdmissionEmail($user_id, $pdo)
{
    $stmt = $pdo->prepare("
        SELECT
            u.first_name, u.email,
            a.admission_no, ad.program, ad.ma_focus, ad.mode_of_study
        FROM users u
        JOIN applications a ON u.id = a.user_id
        JOIN application_details ad ON u.id = ad.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        throw new Exception("Could not find complete admission data for user ID: $user_id");
    }

    $first         = htmlspecialchars($data['first_name']);
    $email         = $data['email'];
    $programCode   = strtoupper($data['program']);
    $ma_focus      = $data['ma_focus'] ?? '';
    $admission_no  = $data['admission_no'];
    $mode_of_study = $data['mode_of_study'];

    $letter_link   = 'https://adullam.ng/dashboard/letters/admission_letters/' . $user_id . '.pdf';
    $logo          = 'https://adullam.ng/assets/img/logo1.png';
    $resumption_date = 'January 5, 2026';
    $contact_email = 'rcnts.adullam@gmail.com';

    $programMap = [
        'MA'         => ['name' => 'Master of Arts', 'duration' => '24 months'],
        'PGDT'       => ['name' => 'Postgraduate Diploma', 'duration' => '10 months'],
        'B.DIV'      => ['name' => 'Bachelor of Divinity', 'duration' => '4 years'],
        'DIPLOMA'    => ['name' => 'Diploma in Theology', 'duration' => '3 years'],
        'CERTIFICATE'=> ['name' => 'Certificate in Theology', 'duration' => '1 year'],
    ];
    $programLabel = $programMap[$programCode]['name'] ?? $programCode;

    $subject = '';
    $body = '';

    if ($mode_of_study === 'onsite') {
        $subject = "🎉 Congratulations $first – You Have Been Admitted! (On-site)";
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
                Please proceed to your student dashboard to <strong>download your admission letter</strong> and <strong>reserve your hostel space immediately</strong> if interested. A very limited number of spaces are available.
            </p>
            <div style='text-align:center;margin:20px 0;'>
                <a href='$letter_link' style='background:#6B21A8;color:white;padding:12px 24px;
                    text-decoration:none;border-radius:6px;font-weight:bold;'>📃 View Admission Letter</a>
            </div>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                Kindly note that you are expected to resume on campus on the resumption date scheduled for <strong>$resumption_date</strong>.<br/>
                <strong>Address:</strong> No4. Remnant Avenue, Opposite Benue State Library, Wurukum, Makurdi. Benue State. Nigeria.
            </p>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                Hostel residents are required to come along with a student bed, cooking gas and other basic utensils.
            </p>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                Documentation of academic credentials, fee payment receipt (minimum of <strong>60% of the total fee per semester</strong>), and filing of other required documents at the registration desk will commence immediately. Students who miss the documentation process and matriculation will not be considered.
            </p>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                We are excited to walk this spiritual and academic journey with you! We look forward to seeing you on campus.
            </p>
            <hr style='margin:30px 0;'>
            <p style='font-size:13px;color:#888;text-align:center;'>
                For questions, contact <a href='mailto:$contact_email' style='color:#6B21A8;'>$contact_email</a><br/>
                &copy; " . date('Y') . " Adullam Seminary. All rights reserved.
            </p>
        </div>";
    } else {
        $subject = "🎉 Congratulations $first – You Have Been Admitted! (Online)";
        $body = "
        <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:auto;padding:24px;background:#f9fafb;border-radius:10px;border:1px solid #e0e0e0;'>
            <div style='text-align:center;'>
                <img src='$logo' alt='Adullam Seminary' style='height:70px;margin-bottom:10px;' />
                <h2 style='color:#6B21A8;'>Adullam Seminary Admissions</h2>
            </div>
            <p style='font-size:16px;color:#111;'>Dear <strong>$first</strong>,</p>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                Congratulations! You have been <strong>admitted</strong> into the <strong>$programLabel</strong> program at <strong>RCN Theological Seminary – Adullam</strong>.
            </p>
            <p style='font-size:15px;line-height:1.6;color:#333;'>
                <strong>Admission Number:</strong> $admission_no<br/>
                <strong>Program:</strong> $programLabel" .
                ($programCode === 'MA' ? "<br/><strong>Focus Area:</strong> {$ma_focus}" : "") . "
            </p>
            <p style='font-size:15px;color:#333;line-height:1.6;margin:20px 0;'>
                Please proceed to your student dashboard to <strong>download your admission letter</strong> and to complete your admission process.
            </p>
            <div style='text-align:center;margin:20px 0;'>
                <a href='$letter_link' style='background:#6B21A8;color:white;padding:12px 24px;
                    text-decoration:none;border-radius:6px;font-weight:bold;'>📃 View Admission Letter</a>
            </div>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                Kindly note, to be onboarded into the learning platform, you are expected to pay your fees (minimum of <strong>60% of your total fee per Semester</strong>) and upload Proof of payment on your student dashboard on or before the resumption date which is scheduled for <strong>$resumption_date</strong>.
            </p>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                The matriculation and onboarding exercise is scheduled immediately after the resumption date, and students who miss the onboarding schedule may not be considered.
            </p>
            <p style='font-size:15px;color:#333;line-height:1.6;'>
                We are excited to walk this spiritual and academic journey with you!
            </p>
            <hr style='margin:30px 0;'>
            <p style='font-size:13px;color:#888;text-align:center;'>
                For questions, contact <a href='mailto:$contact_email' style='color:#6B21A8;'>$contact_email</a><br/>
                &copy; " . date('Y') . " Adullam Seminary. All rights reserved.
            </p>
        </div>";
    }

    // Ensure subject is UTF-8 encoded for emojis and special chars
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    return sendMail($email, $first, $encodedSubject, $body);
}
