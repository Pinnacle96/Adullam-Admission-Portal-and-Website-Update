<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$semester = isset($payload['semester']) ? trim((string)$payload['semester']) : '';

$allowed = ['First Semester', 'Second Semester', '__all__'];
if (!in_array($semester, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid semester']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($semester === '__all__') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_registrations_archive LIKE hostel_registrations");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_allocations_archive LIKE hostel_allocations");

        $insAlloc = $pdo->exec("INSERT INTO hostel_allocations_archive SELECT * FROM hostel_allocations");
        $delAlloc = $pdo->exec("DELETE FROM hostel_allocations");

        $insReg = $pdo->exec("INSERT INTO hostel_registrations_archive SELECT * FROM hostel_registrations");
        $delReg = $pdo->exec("DELETE FROM hostel_registrations");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_registrations_archive LIKE hostel_registrations");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_allocations_archive LIKE hostel_allocations");

        $insAllocStmt = $pdo->prepare("INSERT INTO hostel_allocations_archive SELECT * FROM hostel_allocations WHERE semester = ?");
        $insAllocStmt->execute([$semester]);
        $insAlloc = $insAllocStmt->rowCount();

        $delAllocStmt = $pdo->prepare("DELETE FROM hostel_allocations WHERE semester = ?");
        $delAllocStmt->execute([$semester]);
        $delAlloc = $delAllocStmt->rowCount();

        $insRegStmt = $pdo->prepare("INSERT INTO hostel_registrations_archive SELECT * FROM hostel_registrations WHERE semester = ?");
        $insRegStmt->execute([$semester]);
        $insReg = $insRegStmt->rowCount();

        $delRegStmt = $pdo->prepare("DELETE FROM hostel_registrations WHERE semester = ?");
        $delRegStmt->execute([$semester]);
        $delReg = $delRegStmt->rowCount();
    }

    $pdo->commit();

    $label = $semester === '__all__' ? 'all semesters' : $semester;
    echo json_encode([
        'success' => true,
        'message' => "Archived hostel registrations ({$insReg}) and allocations ({$insAlloc}) for {$label}."
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to clear hostel records.']);
}
