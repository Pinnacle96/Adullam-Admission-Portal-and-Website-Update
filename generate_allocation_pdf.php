<?php
/* ───────────────────────────────────────────────────────────────
   generate_allocation_pdf.php  –  Downloadable hostel slip
   Adds an Allocation-Officer signature / stamp box for clearance
   ─────────────────────────────────────────────────────────────── */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    /* ── validate token ───────────────────────────── */
    $token = $_GET['token'] ?? '';
    if (!$token || strlen($token) !== 64) {
        throw new Exception("Invalid or missing token.");
    }

    /* ── fetch allocation + student data ──────────── */
    $stmt = $pdo->prepare("
        SELECT ha.*, hr.room_number  AS room_no, hr.hostel_name,
               r.full_name, r.email, r.phone,
               r.blood_group, r.genotype, r.nationality,
               CONCAT_WS(', ', r.res_address, r.res_city, r.res_state, r.res_country) AS residence,
               r.program, r.program_year, r.semester, r.gender,
               r.marital_status, r.emergency_contact,
               r.amount_paid, r.passport_file
        FROM hostel_allocations ha
        JOIN hostel_registrations r ON r.id = ha.registration_id
        LEFT JOIN hostel_rooms hr    ON hr.id = ha.room_id
        WHERE ha.download_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $alloc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alloc) {
        throw new Exception("Allocation not found or token expired.");
    }

    /* ── logo / passport / registrar signature ───── */
    $logoSrc       = file_exists(__DIR__.'/assets/img/logo1.png')
        ? 'data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/assets/img/logo1.png'))
        : '';

    $passportImg   = '';
    if (!empty($alloc['passport_file'])) {
        $passPath = __DIR__.'/dashboard/uploads/'.basename($alloc['passport_file']);
        if (file_exists($passPath)) {
            $passportImg = '<img src="data:image/jpeg;base64,'.base64_encode(file_get_contents($passPath)).'" style="width:100px;height:100px;border:1px solid #ccc;border-radius:6px;object-fit:cover;">';
        }
    }

    $signatureImg  = file_exists(__DIR__.'/assets/img/signature.png')
        ? '<img src="data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/assets/img/signature.png')).'" style="height:40px">'
        : '';

    $₦ = '₦';
    $amount = number_format((float)$alloc['amount_paid'], 0);
    $year   = date('Y');

    /* ── HTML content ─────────────────────────────── */
    $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
 body{font-family:'DejaVu Sans',sans-serif;font-size:12px;color:#333;}
 table{width:100%;border-collapse:collapse;margin-bottom:18px;}
 td{padding:6px 10px;vertical-align:top;}
 .header{text-align:center;margin-bottom:20px;}
 .title{font-size:14px;font-weight:bold;color:#6B21A8;margin:10px 0;border-bottom:1px solid #ccc;padding-bottom:5px;}
 .notice{margin-top:15px;font-size:12px;color:#dc2626;font-weight:bold;}
 .footer{text-align:center;font-size:11px;color:#777;margin-top:35px;}
 .watermark{position:fixed;top:35%;left:10%;width:100%;text-align:center;opacity:.06;transform:rotate(-30deg);font-size:80px;color:#6B21A8;z-index:-1;}
 .box{width:240px;height:75px;border:1px dashed #999;border-radius:4px;margin-top:8px;text-align:center;line-height:75px;color:#555;font-style:italic;}
</style>
</head><body>
<div class="watermark">Adullam</div>

<div class="header">
  <img src="{$logoSrc}" style="height:60px"><br>
  <h2 style="margin:5px 0;color:#6B21A8;">RCN Theological Seminary – Adullam</h2>
  <p style="margin:0;">Family House Allocation Slip</p>
</div>

<table>
  <tr>
    <td>
      <strong>Name:</strong> {$alloc['full_name']}<br>
      <strong>Gender:</strong> {$alloc['gender']}<br>
      <strong>Email:</strong> {$alloc['email']}<br>
      <strong>WhatsApp No.:</strong> {$alloc['phone']}<br>
      <strong>Blood Group:</strong> {$alloc['blood_group']}<br>
      <strong>Genotype:</strong> {$alloc['genotype']}<br>
      <strong>Nationality:</strong> {$alloc['nationality']}<br>
      <strong>Residential Addr.:</strong> {$alloc['residence']}
    </td>
    <td align="right">{$passportImg}</td>
  </tr>
</table>

<div class="title">Academic Details</div>
<table>
  <tr><td><strong>Program / Year</strong></td><td>{$alloc['program']} ({$alloc['program_year']})</td></tr>
  <tr><td><strong>Semester</strong></td><td>{$alloc['semester']}</td></tr>
  <tr><td><strong>Marital Status</strong></td><td>{$alloc['marital_status']}</td></tr>
  <tr><td><strong>Emergency Contact</strong></td><td>{$alloc['emergency_contact']}</td></tr>
  <tr><td><strong>Amount Paid</strong></td><td>{$₦}{$amount}</td></tr>
</table>

<div class="title">Room Allocation</div>
<table>
  <tr><td><strong>Hostel</strong></td><td>{$alloc['hostel_name']}</td></tr>
  <tr><td><strong>Room Assigned</strong></td><td>{$alloc['room_no']}</td></tr>
  <tr><td><strong>Date of Allocation</strong></td><td>{$alloc['allocated_at']}</td></tr>
</table>



<div class="notice">
  Management reserves the right to reassign rooms when necessary.<br>
  Please keep this slip very safe – it is your ticket to claim the hostel space.<br>
  This slip must be Signed and Stamped by the Accommodations officer for it to be authentic
</div>

<!-- NEW: allocation-officer clearance box -->
<div class="title" style="margin-top:25px">Allocation-Officer Clearance (Sign &amp; Stamp)</div>
<table style="width:100%;margin-top:10px;">
  <tr>
    
    <td style="padding-left:30px;vertical-align:middle;">
      <strong>Officer's Name:</strong><br><br>
      <div style="border-bottom:1px solid #666;height:20px;width:100%;margin-top:4px;"></div>
    </td>
    <td style="width:260px;">
      <div class="box" style="height:75px;line-height:75px;">Official Sign / Stamp Here</div>
    </td>
  </tr>
</table>


<!--<div style="margin-top:25px">
  <strong>Registrar's Signature:</strong><br>
  {$signatureImg}
  <p style="font-size:12px;color:#555;margin:4px 0 0;">Adullam Seminary Registrar</p>
</div>-->



<div class="footer">&copy; {$year} Adullam Seminary – All rights reserved.</div>
</body></html>
HTML;

    /* ── Render with Dompdf ───────────────────────── */
    $opt = new Options();
    $opt->set('isRemoteEnabled', true);
    
    $opt->set('defaultFont', 'DejaVu Sans'); // Supports Naira symbol

    $dompdf = new Dompdf($opt);
    $dompdf->loadHtml($html,'UTF-8');
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream("Hostel_Allocation_{$alloc['allocation_code']}.pdf", ['Attachment'=>false]);
    exit;

} catch (Exception $e) {
    echo "<h2 style='color:red;padding:20px;'>Error: ".htmlspecialchars($e->getMessage())."</h2>";
    exit;
}
