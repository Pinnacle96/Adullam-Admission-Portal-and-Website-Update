<?php
require_once '../vendor/autoload.php';
require_once 'export_helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Increase memory limit and execution time for large PDF generation
ini_set('memory_limit', '2048M');
set_time_limit(300);

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true); // allow local images

$dompdf = new Dompdf($options);

// Absolute path to the logo
$logoPath = realpath(__DIR__ . '/../assets/img/logo1.png');
$logoData = base64_encode(file_get_contents($logoPath));
$logoMime = mime_content_type($logoPath);
$logoSrc = "data:$logoMime;base64,$logoData";

ob_start();
?>

<style>
    body { font-family: Helvetica, sans-serif; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #000; padding: 4px; word-wrap: break-word; overflow-wrap: break-word; }
    th { background-color: #522d80; color: white; }
</style>

<div style="text-align: center; margin-bottom: 20px;">
    <img src="<?= $logoSrc ?>" alt="Adullam Logo" style="height: 60px;"><br>
    <h2 style="margin: 10px 0 0;">Adullam Seminary - Applicants Export</h2>
</div>

<table width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th style="width: 20%;">Name</th>
            <th style="width: 20%;">Email</th>
            <th style="width: 12%;">Phone</th>
            <th style="width: 10%;">Program</th>
            <th style="width: 15%;">MA Focus</th>
            <th style="width: 10%;">Mode</th>
            <th style="width: 13%;">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($applicants as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['full_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['phone'] ?? '') ?></td> <!-- ✅ NEW CELL -->
                <td><?= htmlspecialchars($a['program'] ?? '') ?></td>
                <td><?= ($a['program'] ?? '') === 'MA' ? htmlspecialchars($a['ma_focus'] ?? '') : '-' ?></td>
                <td><?= htmlspecialchars($a['mode_of_study'] ?? '') ?></td>
                <td><?= ucfirst($a['status'] ?? '') ?></td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("applicants_export_" . date("Ymd") . ".pdf", ["Attachment" => true]);
exit;
