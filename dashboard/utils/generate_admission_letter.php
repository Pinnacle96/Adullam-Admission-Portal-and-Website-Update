<?php
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

function generateAdmissionLetter($userId, $pdo)
{
    $stmt = $pdo->prepare("SELECT u.first_name, u.last_name, d.program, d.ma_focus, d.mode_of_study, a.admission_no
                            FROM users u
                            JOIN application_details d ON u.id = d.user_id
                            JOIN applications a ON a.user_id = u.id
                            WHERE u.id = ?");
    $stmt->execute([$userId]);
    $data = $stmt->fetch();

    if (!$data) return false;

    $fullName = $data['first_name'] . ' ' . $data['last_name'];
    $programCode = strtoupper($data['program']);
    $maFocus = $data['ma_focus'];
    $modeOfStudy = ucfirst($data['mode_of_study'] ?? 'Online');
    $admissionNo = $data['admission_no'];

    // Program mappings
    $programMap = [
        'MA' => ['name' => 'Master of Arts', 'duration' => '24 months'],
        'PGDT' => ['name' => 'Postgraduate Diploma', 'duration' => '10 months'],
        'B.DIV' => ['name' => 'Bachelor of Divinity', 'duration' => '4 years'],
        'DIPLOMA' => ['name' => 'Diploma in Theology', 'duration' => '3 years'],
        'CERTIFICATE' => ['name' => 'Certificate in Theology', 'duration' => '1 year'],
    ];

    $programLabel = $programMap[$programCode]['name'] ?? $programCode;
    $programDuration = $programMap[$programCode]['duration'] ?? 'N/A';

    // Embedded logo
    $logoPath = realpath(__DIR__ . '/../../assets/img/logo1.png');
    $logoSrc = $logoPath && file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : 'https://adullam.ng/assets/img/logo1.png';

    // Registrar Signature
    $signPath = realpath(__DIR__ . '/../../assets/img/signature.png');
    $signSrc = $signPath && file_exists($signPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($signPath))
        : '';

    // Output location
    $outputDir = realpath(__DIR__ . '/../letters/admission_letters');
    if (!$outputDir) {
        $outputDir = __DIR__ . '/../letters/admission_letters';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }
    }

    $letterPath = $outputDir . "/{$userId}.pdf";

    $html = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family:Arial,sans-serif;font-size:13px;line-height:1.7;color:#333;padding:40px;'>
        <div style='text-align:center;margin-bottom:30px;'>
            <img src='$logoSrc' style='height:70px;'><br>
            <h2 style='color:#6B21A8;margin-bottom:5px;'>RCN Theological Seminary – Adullam</h2>
            <p style='font-size:14px;font-weight:bold;margin-top:0;'>Admission Letter ($modeOfStudy)</p>
        </div>

        <p>Dear <strong>$fullName</strong>,</p>
        
        <p>
            We are delighted to inform you that the Admissions Committee of <strong>Remnant Christian Network Theological Seminary - Adullam</strong> has approved your application for provisional admission to the <strong>$programLabel" . ($programCode === 'MA' ? " (<em>$maFocus</em>)" : "") . "</strong> program, commencing on <strong>June 15, 2026</strong>. Please note that this offer of admission is for the <strong>June 2026 academic session</strong> and <strong>may not be deferred</strong>.
        </p>

        <p>
            Congratulations on your academic achievements and welcome to our academic community!
        </p>

        <p>Below are the admission Details:</p>
        <ul style='list-style-type: none; padding-left: 0;'>
            <li style='margin-bottom: 5px;'>·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Admission Number:</strong> $admissionNo</li>
            <li style='margin-bottom: 5px;'>·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Program:</strong> $programLabel" . ($programCode === 'MA' ? " ($maFocus)" : "") . "</li>
            <li style='margin-bottom: 5px;'>·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Learning Option:</strong> $modeOfStudy</li>
            <li style='margin-bottom: 5px;'>·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Duration:</strong> $programDuration</li>
            <li style='margin-bottom: 5px;'>·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Start Date:</strong> June 15, 2026</li>
        </ul>
        
        <p>
            Please note that you may forfeit this admission <strong>if you fail to register by documenting your academic credentials, uploading/filing your fee receipt (at least 60% of the semester’s fees), and other required documents before the day of matriculation</strong>, which is held immediately after the start date.
        </p>

        <p>Once again, <strong>congratulations on your admission</strong>.</p>

        <div style='margin-top:50px;'>
            " . ($signSrc ? "<img src='$signSrc' style='height:50px;'><br>" : "") . "
            <strong>Olajide Bakare</strong><br>
            Registrar<br>
            RCNTS – Adullam
        </div>
    </body>
    </html>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    file_put_contents($letterPath, $dompdf->output());

    return true;
}
