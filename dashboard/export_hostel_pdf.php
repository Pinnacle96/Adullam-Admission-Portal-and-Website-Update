<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$filters = [
    'status'   => $_GET['status'] ?? '',
    'semester' => $_GET['semester'] ?? '',
    'program'  => $_GET['program'] ?? '',
    'gender'   => $_GET['gender'] ?? '',
    'hostel'   => $_GET['hostel'] ?? ''
];

try {
    $query = "SELECT full_name, email, phone, gender, age, program, program_year,
                     semester, student_type, hostel, room, guardian_name,
                     guardian_contact, amount_paid, status
              FROM hostel_registrations
              WHERE 1=1";

    $params = [];

    if ($filters['status']) {
        $map = ['approved' => 1, 'rejected' => -1, 'pending' => 0];
        if (isset($map[$filters['status']])) {
            $query .= " AND is_approved = ?";
            $params[] = $map[$filters['status']];
        }
    }

    if ($filters['semester']) {
        $query .= " AND semester = ?";
        $params[] = $filters['semester'];
    }

    if ($filters['program']) {
        $query .= " AND program = ?";
        $params[] = $filters['program'];
    }

    if ($filters['gender']) {
        $query .= " AND gender = ?";
        $params[] = $filters['gender'];
    }

    if ($filters['hostel']) {
        $query .= " AND hostel = ?";
        $params[] = $filters['hostel'];
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        throw new Exception("No applicants match the selected filters.");
    }

    $₦ = '₦';
    $chunkSize = 40;

    $logoSrc = file_exists(__DIR__ . '/../assets/img/logo1.png')
        ? 'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/../assets/img/logo1.png'))
        : '';

    $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
body {
  font-family: DejaVu Sans, sans-serif;
  font-size: 10px;
  color: #333;
  margin: 0;
  padding: 0;
}
.page {
  page-break-after: always;
  padding: 20px;
}
.header {
  text-align: center;
  margin-bottom: 10px;
}
h2 {
  color: #6B21A8;
  margin: 5px 0;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  table-layout: fixed;
}
th, td {
  border: 1px solid #ccc;
  padding: 4px 5px;
  text-align: left;
  font-size: 9px;
  word-wrap: break-word;
  white-space: normal;
  max-width: 120px;
}
th {
  background-color: #f3f3f3;
  font-weight: bold;
}
.watermark {
  position: fixed;
  top: 35%;
  left: 10%;
  width: 100%;
  text-align: center;
  opacity: 0.06;
  transform: rotate(-30deg);
  font-size: 80px;
  color: #6B21A8;
  z-index: -1;
}
</style>
</head><body>
<div class="watermark">Adullam</div>
HTML;

    $chunks = array_chunk($rows, $chunkSize);

    foreach ($chunks as $pageIndex => $chunk) {
        $html .= "<div class='page'>";
        $html .= "<div class='header'>
                    <img src='{$logoSrc}' style='height:60px'><br>
                    <h2>RCN Theological Seminary – Adullam</h2>
                    <p style='margin:0;'>Hostel Applicants List – Page " . ($pageIndex + 1) . "</p>
                  </div>";

        $html .= "<table><thead><tr>
            <th>Full Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Age</th>
            <th>Program</th><th>Year</th><th>Semester</th><th>Student Type</th><th>Hostel</th>
            <th>Room</th><th>Guardian Name</th><th>Guardian Contact</th><th>Amount Paid</th><th>Status</th>
        </tr></thead><tbody>";

        foreach ($chunk as $row) {
            $html .= "<tr>";
            foreach ($row as $key => $value) {
                if ($key === 'amount_paid') {
                    $value = $₦ . number_format((float)$value, 0);
                }
                $html .= "<td>" . htmlspecialchars($value ?? '-') . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table></div>";
    }

    $html .= "</body></html>";

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("hostel_applicants_export.pdf", ['Attachment' => true]);
    exit;

} catch (Exception $e) {
    echo "<h2 style='color:red;padding:20px;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    exit;
}
