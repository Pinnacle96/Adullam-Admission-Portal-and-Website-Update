<?php
/* ----------------------------------------------------------
    ajax/hostel_approval_action.php
    ---------------------------------------------------------- */
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../mailer.php';

define('HOST_BASE', 'https://adullam.ng/'); //  <— adjust once

/* ---------- auth ---------- */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unauthorised']); // Corrected response
    exit;
}

/* ---------- input ---------- */
$data = json_decode(file_get_contents('php://input'), true);
$registrationId = intval($data['id'] ?? 0);
$action         = $data['action'] ?? '';
$note           = trim($data['note'] ?? '');

if (!$registrationId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid payload']); // Corrected response
    exit;
}

/* ---------- fetch registration ---------- */
$stmt = $pdo->prepare("SELECT * FROM hostel_registrations WHERE id=?");
$stmt->execute([$registrationId]);
$reg = $stmt->fetch();

if (!$reg) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Registration not found']); // Corrected response
    exit;
}
if ($reg['status'] !== 'pending') {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Already processed']); // Corrected response
    exit;
}

/* ---------- helper: generate unique code ---------- */
function makeCode(PDO $pdo): string {
    do {
        $code = 'ADU/'.date('Y').'/'.strtoupper(bin2hex(random_bytes(3))); // e.g. ADU/2025/F1A9CC
        $check = $pdo->prepare("SELECT 1 FROM hostel_allocations WHERE allocation_code=?");
        $check->execute([$code]);
    } while ($check->fetchColumn());
    return $code;
}
/* ---------- helper: first empty bed in a room ---------- */
function firstFreeBed(PDO $pdo, int $roomId, int $capacity): ?int {
    $q = $pdo->prepare("SELECT COUNT(*) FROM hostel_allocations WHERE room_id=?");
    $q->execute([$roomId]);
    $taken = (int) $q->fetchColumn();
    return ($taken < $capacity) ? $taken + 1 : null;
}

/* ---------- APPROVAL PATH ---------- */
if ($action === 'approve') {

    /* ---- decide hostel/room/bed ---------------------------------- */
    // 1. fetch all rooms (seeded earlier)
    $rooms = $pdo->query("SELECT * FROM hostel_rooms WHERE active=1 ORDER BY hostel_name, room_number")
                ->fetchAll(PDO::FETCH_ASSOC);

    $gender   = $reg['gender'];
    $semester = $reg['semester'];
    $age      = (int)$reg['age'];
    $intl     = ($reg['nationality'] === 'International');
    $isNew    = ($reg['student_type'] === 'new');

    // filter rooms by semester + gender
    $eligible = array_filter($rooms, fn($r) =>
        $r['gender']    === $gender &&
        $r['semester']  === $semester
    );

    // rule: elderly (≥45) OR new-intl-first-semester ⇒ Bethel
    if ($age >= 45 || ($intl && $isNew && $semester === 'First Semester')) {
        $eligible = array_filter($eligible, fn($r) => $r['hostel_name'] === 'Bethel');
    }

    $roomFound = null; $bedNo = null;

    foreach ($eligible as $room) {
        $free = firstFreeBed($pdo, $room['id'], $room['capacity']);
        if ($free) { $roomFound = $room; $bedNo = $free; break; }
    }

    if (!$roomFound) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => "No available bed matches the rules"]); exit; // Corrected response
    }

    /* ---- DB TRANSACTION ------------------------------------------ */
    $pdo->beginTransaction();

    // allocation_code + token
    $allocationCode = makeCode($pdo);
    $downloadToken  = hash('sha256', uniqid('', true));

    // 1. insert into hostel_allocations
    $ins = $pdo->prepare("
     INSERT INTO hostel_allocations
     (registration_id,user_id,semester,gender,hostel_name,room_no,bed_no,
      room_id,allocated_by,allocation_note,allocation_code,download_token)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $ins->execute([
        $registrationId,
        $reg['user_id'],
        $semester,$gender,
        $roomFound['hostel_name'],$roomFound['room_number'],$bedNo,
        $roomFound['id'],
        $_SESSION['user_id'],
        $note,
        $allocationCode,
        $downloadToken
    ]);

    // 2. update registration row
    $up = $pdo->prepare("
      UPDATE hostel_registrations SET
        is_approved = 1,
        status      = 'approved',
        approved_by = ?,
        approved_at = NOW(),
        hostel      = ?, room = ?, bed_number = ?,
        approval_note = ?
      WHERE id = ?
    ");
    $up->execute([
        $_SESSION['user_id'],
        $roomFound['hostel_name'],$roomFound['room_number'],$bedNo,
        $note,
        $registrationId
    ]);

    $pdo->commit();

    /* ---- EMAIL ---------------------------------------------------- */
    $downloadLink = HOST_BASE.'generate_allocation_pdf.php?token='.$downloadToken;
    $subject = "✅ Hostel Allocation Approved – {$allocationCode}";
    $body = "
      <div style='font-family:Segoe UI,Roboto,Arial,sans-serif;max-width:600px;margin:auto;
                  padding:20px;background:#f9fafb;border:1px solid #eee;border-radius:8px'>
        <h2 style='color:#6B21A8;text-align:center;margin-top:0'>Hostel Allocation Approved</h2>
        <p>Dear <strong>{$reg['full_name']}</strong>,</p>
        <p>Your hostel reservation has been approved. Details:</p>
        <ul>
          <li><b>Allocation Code:</b> {$allocationCode}</li>
          <li><b>Hostel:</b> {$roomFound['hostel_name']}</li>
          <li><b>Room:</b> {$roomFound['room_number']}</li>
        </ul>
        <p style='text-align:center'>
          <a href='{$downloadLink}' style='background:#6B21A8;color:#fff;
            padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold'>
            Download Allocation Slip (PDF)
          </a>
        </p>
        <p style='font-size:13px;color:#666'>Blessings,<br>Adullam Seminary Team</p>
      </div>";
    sendMail($reg['email'], "Adullam Seminary", $subject, $body);

    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Allocation approved and email sent.']); // Corrected response
    exit;
}

/* ---------- REJECTION PATH ---------- */
if ($action === 'reject') {

    $pdo->prepare("
      UPDATE hostel_registrations SET
        status='rejected',
        approval_note = ?,
        approved_by   = ?,
        approved_at   = NOW()
      WHERE id = ?
    ")->execute([$note, $_SESSION['user_id'], $registrationId]);

    /* ---- email ---- */
    $subject = "❌ Hostel Reservation Rejected";
    $body = "
      <div style='font-family:Segoe UI,Roboto,Arial,sans-serif;max-width:600px;margin:auto;
                   padding:20px;background:#f9fafb;border:1px solid #eee;border-radius:8px'>
        <h2 style='color:#dc2626;text-align:center;margin-top:0'>Reservation Rejected</h2>
        <p>Dear <strong>{$reg['full_name']}</strong>,</p>
        <p>We regret to inform you that your hostel reservation has been <b>rejected</b>.</p>
        ".($note ? "<p><b>Reason:</b> {$note}</p>" : "")."
        <p>You may contact the Accommodation Office for further assistance.</p>
        <p style='font-size:13px;color:#666'>Blessings,<br>Adullam Seminary Team</p>
      </div>";
    sendMail($reg['email'], "Adullam Seminary", $subject, $body);

    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Registration rejected.']); // Corrected response
    exit;
}
?>