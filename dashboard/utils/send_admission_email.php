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
        return false;
    }

    $first         = htmlspecialchars($data['first_name']);
    $email         = $data['email'];
    $programCode   = strtoupper($data['program']);
    $ma_focus      = $data['ma_focus'] ?? '';
    $admission_no  = $data['admission_no'];
    $mode_of_study = $data['mode_of_study'];

    $dashboard_link = 'https://adullam.ng/dashboard';
    $logo           = 'https://adullam.ng/assets/img/logo1.png';
    $resumption_date = 'June 15, 2026';
    $contact_email  = 'adullamadmissions@gmail.com';

    $programMap = [
        'MA'         => 'Master of Arts',
        'PGDT'       => 'Postgraduate Diploma in Theology',
        'B.DIV'      => 'Bachelor of Divinity',
        'DIPLOMA'    => 'Diploma in Theology',
        'CERTIFICATE'=> 'Certificate in Theology',
    ];
    $programLabel = $programMap[$programCode] ?? $programCode;

    if ($programCode === 'MA' && !empty($ma_focus)) {
        $programLabel .= " ($ma_focus)";
    }

    $subject = "Congratulations, $first! Admission Confirmed – $programLabel (June 2026 Intake)";
    
    if ($mode_of_study === 'onsite') {
        $body = "
        <div style='font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif; max-width:600px; margin:auto; padding:30px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:16px; color:#374151;'>
            <div style='text-align:center; margin-bottom:30px;'>
                <img src='$logo' alt='Adullam Seminary' style='height:70px;margin-bottom:10px;' />
                <h2 style='color:#6B21A8; font-size:24px; font-weight:800; margin:0;'>RCN Theological Seminary – Adullam</h2>
                <p style='color:#9CA3AF; font-size:14px; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; margin-top:5px;'>Admission Notification (On-site)</p>
            </div>

            <p style='font-size:16px; line-height:1.6;'>Dear <strong>$first</strong>,</p>
            
            <p style='font-size:16px; line-height:1.6;'>Congratulations! You have been successfully admitted into the <strong>$programLabel</strong> program at <strong>Remnant Christian Network Theological Seminary—Adullam</strong>.</p>
            
            <div style='background-color:#F5F3FF; padding:20px; border-radius:12px; margin:20px 0; border-left:4px solid #6B21A8;'>
                <p style='margin:0; font-size:15px; color:#1E1B4B;'>
                    <strong>Admission Number:</strong> $admission_no<br>
                    <strong>Program:</strong> $programLabel
                </p>
            </div>

            <p style='font-size:15px; line-height:1.6;'>Please log in to your student dashboard to download your official admission letter and complete the remaining admission steps.</p>
            
            <div style='text-align:center; margin:25px 0;'>
                <a href='$dashboard_link' style='display:inline-block; background-color:#6B21A8; color:#ffffff; padding:14px 30px; text-decoration:none; border-radius:50px; font-weight:bold; font-size:16px;'>📃 View & Download Admission Letter</a>
            </div>

            <h3 style='color:#6B21A8; font-size:18px; margin-top:30px;'>Accommodation Guidance</h3>
            <p style='font-size:15px; line-height:1.6;'>For your comfort and convenience, we recommend renting a personal apartment within Makurdi city. As an alternative, we offer a basic shared living arrangement with minimum comforts. You can reserve a space using the 'Hostel Reg.' link on your application portal.</p>
            <p style='font-size:15px; line-height:1.6; font-style:italic;'>Hostel residents are required to bring their own student bed, cooking gas, and basic utensils.</p>

            <h3 style='color:#6B21A8; font-size:18px; margin-top:30px;'>Resumption and Onboarding</h3>
            <p style='font-size:15px; line-height:1.6;'>You are expected to resume on campus on <strong>$resumption_date</strong>. <br><strong>Campus Address:</strong> No. 4 Remnant Avenue, Opposite Benue State Library, Wurukum, Makurdi, Benue State, Nigeria.</p>
            
            <p style='font-size:15px; line-height:1.6;'>Documentation of academic credentials, fee payment receipt (minimum 60% of the total semester fee), and submission of other required documents will commence at the registration desk immediately upon resumption. Matriculation follows shortly after. Students who miss the documentation process and matriculation may not be considered for the session.</p>

            <p style='font-size:15px; line-height:1.6; margin-top:25px;'>We are truly excited to journey with you—spiritually and academically—as you equip yourself for greater kingdom impact! We look forward to welcoming you on campus and seeing the Lord work powerfully in your life and ministry.</p>

            <hr style='border:none; border-top:1px solid #F3F4F6; margin:30px 0;'>
            
            <p style='font-size:14px; line-height:1.6; color:#6B7280; text-align:center;'>
                If you have any questions, kindly reply to this email or contact the Registry Department directly.<br>
                &copy; " . date('Y') . " RCNTS Adullam. All rights reserved.
            </p>
        </div>";
    } else {
        $body = "
        <div style='font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif; max-width:600px; margin:auto; padding:30px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:16px; color:#374151;'>
            <div style='text-align:center; margin-bottom:30px;'>
                <img src='$logo' alt='Adullam Seminary' style='height:70px;margin-bottom:10px;' />
                <h2 style='color:#6B21A8; font-size:24px; font-weight:800; margin:0;'>RCN Theological Seminary – Adullam</h2>
                <p style='color:#9CA3AF; font-size:14px; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; margin-top:5px;'>Admission Notification (Online)</p>
            </div>

            <p style='font-size:16px; line-height:1.6;'>Dear <strong>$first</strong>,</p>
            
            <p style='font-size:16px; line-height:1.6;'>Congratulations! You have been successfully admitted into the <strong>$programLabel</strong> program at <strong>Remnant Christian Network Theological Seminary—Adullam</strong>.</p>
            
            <div style='background-color:#F5F3FF; padding:20px; border-radius:12px; margin:20px 0; border-left:4px solid #6B21A8;'>
                <p style='margin:0; font-size:15px; color:#1E1B4B;'>
                    <strong>Admission Number:</strong> $admission_no<br>
                    <strong>Program:</strong> $programLabel
                </p>
            </div>

            <p style='font-size:15px; line-height:1.6;'>Please log in to your student dashboard to download your official admission letter and complete the remaining steps in your admission process.</p>
            
            <div style='text-align:center; margin:25px 0;'>
                <a href='$dashboard_link' style='display:inline-block; background-color:#6B21A8; color:#ffffff; padding:14px 30px; text-decoration:none; border-radius:50px; font-weight:bold; font-size:16px;'>📃 View & Download Admission Letter</a>
            </div>

            <div style='background-color:#FFFBEB; padding:20px; border-radius:12px; margin:30px 0; border:1px solid #FEF3C7;'>
                <p style='font-size:15px; line-height:1.6; color:#92400E; margin:0;'>
                    <strong>Important Note on Onboarding:</strong> To gain access to the learning platform and be fully onboarded, please pay at least 60% of your total semester fees and upload proof of payment via your student dashboard on or before the resumption date of <strong>$resumption_date</strong>.
                </p>
            </div>

            <p style='font-size:15px; line-height:1.6;'>Matriculation and onboarding exercises will take place immediately following resumption. Students who miss this schedule may, unfortunately, not be considered for participation in the session.</p>

            <p style='font-size:15px; line-height:1.6;'>We are truly excited to journey with you—both spiritually and academically—as you deepen your theological knowledge and prepare for greater kingdom impact!</p>

            <div style='background-color:#F0FDF4; padding:25px; border-radius:12px; margin:30px 0; border-left:4px solid #22C55E;'>
                <p style='font-size:15px; line-height:1.6; margin-bottom:15px;'>Kindly join the community of other admitted students using the WhatsApp group link below for essential information:</p>
                <div style='text-align:center;'>
                    <a href='https://chat.whatsapp.com/EwXOoWmpgYUKuP3UuFRWXQ' style='display:inline-block; background-color:#25D366; color:#ffffff; padding:12px 25px; text-decoration:none; border-radius:50px; font-weight:bold;'>Join Class Group</a>
                </div>
            </div>

            <hr style='border:none; border-top:1px solid #F3F4F6; margin:30px 0;'>
            
            <p style='font-size:14px; line-height:1.6; color:#6B7280; text-align:center;'>
                If you have any questions, kindly reply to this email or contact the Registry Department directly.<br>
                &copy; " . date('Y') . " RCNTS Adullam. All rights reserved.
            </p>
        </div>";
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return sendMail($email, $first, $encodedSubject, $body);
}
