<?php
/* ───────────────────────────────
   reassign_student.php
   Manual hostel room reassignment
   ─────────────────────────────── */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db.php';
require_once 'mailer.php';

header('Content-Type: text/html; charset=utf-8');

try {
    // ── Auth check ──
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
        throw new Exception('Unauthorised access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $allocationId = (int)($_POST['allocation_id'] ?? 0);
    $newRoomId    = (int)($_POST['new_room_id']   ?? 0);
    if (!$allocationId || !$newRoomId) {
        throw new Exception('Missing parameters');
    }

    // ── Get current allocation ──
    $stmt = $pdo->prepare("SELECT * FROM hostel_allocations WHERE id = ?");
    $stmt->execute([$allocationId]);
    $alloc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$alloc) throw new Exception('Allocation not found');
    if ($alloc['room_id'] === $newRoomId) throw new Exception('Already assigned to that room');

    // ── Get new room ──
    $stmt = $pdo->prepare("SELECT * FROM hostel_rooms WHERE id = ?");
    $stmt->execute([$newRoomId]);
    $newRoom = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$newRoom) throw new Exception('Target room not found');

    // ── Check gender and semester match ──
    if ($newRoom['gender'] !== $alloc['gender'] || $newRoom['semester'] !== $alloc['semester']) {
        throw new Exception('Room does not match student gender or semester');
    }

    // ── Check capacity ──
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hostel_allocations WHERE room_id = ?");
    $stmt->execute([$newRoomId]);
    if ($stmt->fetchColumn() >= $newRoom['capacity']) {
        throw new Exception('Target room is full');
    }

    // ── Get next available bed number ──
    $stmt = $pdo->prepare("SELECT MAX(bed_no) FROM hostel_allocations WHERE room_id = ?");
    $stmt->execute([$newRoomId]);
    $newBedNo = (int)$stmt->fetchColumn() + 1;

    $newToken = hash('sha256', uniqid('', true)); // unique token

    $pdo->beginTransaction();

    // ── Update allocation ──
    $pdo->prepare("
        UPDATE hostel_allocations SET
            room_id = ?, hostel_name = ?, room_no = ?, bed_no = ?,
            download_token = ?, allocated_at = NOW()
        WHERE id = ?
    ")->execute([
        $newRoomId,
        $newRoom['hostel_name'],
        $newRoom['room_number'],
        $newBedNo,
        $newToken,
        $allocationId
    ]);

    // ── Sync registration ──
    $pdo->prepare("
        UPDATE hostel_registrations SET
            hostel = ?, room = ?, bed_number = ?
        WHERE id = ?
    ")->execute([
        $newRoom['hostel_name'],
        $newRoom['room_number'],
        $newBedNo,
        $alloc['registration_id']
    ]);

    $pdo->commit();

    // ── Fetch student ──
    $stmt = $pdo->prepare("SELECT full_name, email FROM hostel_registrations WHERE id = ?");
    $stmt->execute([$alloc['registration_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student && filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $link = "https://adullam.ng/generate_allocation_pdf?token={$newToken}";
        $subject = 'Hostel Reassignment Notification';
        $body = "
            Dear <strong>{$student['full_name']}</strong>,<br><br>
            Your hostel room has been <b>reassigned</b> as follows:<br><br>
            <ul>
              <li><b>Hostel:</b> {$newRoom['hostel_name']}</li>
              <li><b>Room:</b> {$newRoom['room_number']}</li>
              <!--<li><b>Bed No:</b> {$newBedNo}</li>-->
            </ul>
            Please download your updated allocation slip using the link below:<br>
            <a href='{$link}'>{$link}</a><br><br>
            <strong>NOTE:</strong> Keep the slip safe – it is your ticket to claim the bed on arrival.<br><br>
            Blessings,<br>Adullam Seminary Accommodation Office
        ";
        sendMail($student['email'], 'Adullam Seminary', $subject, $body);
    }

    // ── Success SweetAlert ──
    echo "<!DOCTYPE html>
    <html>
    <head>
      <meta charset='UTF-8'>
      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
      <script>
        Swal.fire({
          icon: 'success',
          title: 'Student Moved!',
          text: 'Reassignment completed and email sent.',
          confirmButtonColor: '#6B21A8'
        }).then(() => {
          window.location.href = 'manual_reassign';
        });
      </script>
    </body>
    </html>";
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = ($_SESSION['role'] ?? '') === 'superadmin' ? $e->getMessage() : 'Something went wrong, please try again.';
    
    echo "<!DOCTYPE html>
    <html>
    <head>
      <meta charset='UTF-8'>
      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
      <script>
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: " . json_encode($msg) . ",
          confirmButtonColor: '#6B21A8'
        }).then(() => {
          window.history.back();
        });
      </script>
    </body>
    </html>";
    exit;
}
