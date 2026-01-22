<?php
// revoke_allocation.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db.php';
require_once 'mailer.php';

header('Content-Type: text/html; charset=utf-8');

try {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request');
    }

    $allocationId = (int)($_POST['allocation_id'] ?? 0);
    if (!$allocationId) throw new Exception('Missing allocation ID');

    // Fetch student registration info
    $stmt = $pdo->prepare("SELECT ha.registration_id, r.full_name, r.email
                           FROM hostel_allocations ha
                           JOIN hostel_registrations r ON ha.registration_id = r.id
                           WHERE ha.id = ?");
    $stmt->execute([$allocationId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) throw new Exception('Allocation not found');

    $regId = $data['registration_id'];

    $pdo->beginTransaction();

    // Delete allocation
    $pdo->prepare("DELETE FROM hostel_allocations WHERE id = ?")->execute([$allocationId]);

    // Reset registration record
    $pdo->prepare("UPDATE hostel_registrations
                   SET hostel = NULL,
                       room = NULL,
                       bed_number = NULL,
                       is_approved = 0,
                       approved_by = NULL,
                       approved_at = NULL,
                       status = 'pending'
                   WHERE id = ?")->execute([$regId]);

    $pdo->commit();

    // Send email
    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $subject = 'Hostel Allocation Revoked';
        $body = "
          Dear <strong>{$data['full_name']}</strong>,<br><br>
          Your hostel allocation has been <strong>revoked</strong> and your application status is now <b>PENDING</b>.<br>
          Kindly await further communication or contact the accommodation office.<br><br>
          Regards,<br>Adullam Seminary";
        sendMail($data['email'], 'Adullam Seminary', $subject, $body);
    }

    // SweetAlert (no redirection)
    echo "<!DOCTYPE html><html><head>
      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
      <script>
        Swal.fire({
          icon: 'success',
          title: 'Revoked!',
          text: 'Allocation removed and registration reset to pending.',
          confirmButtonColor: '#6B21A8'
        }).then(() => window.history.back());
      </script>
    </body></html>";
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    $msg = ($_SESSION['role'] ?? '') === 'superadmin'
           ? $e->getMessage()
           : 'Something went wrong.';

    echo "<!DOCTYPE html><html><head>
      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
      <script>
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: " . json_encode($msg) . ",
          confirmButtonColor: '#6B21A8'
        }).then(() => window.history.back());
      </script>
    </body></html>";
}
