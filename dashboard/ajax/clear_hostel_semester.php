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

$dashboardDir = realpath(__DIR__ . '/..');
if (!$dashboardDir) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server path error']);
    exit;
}

$safeDeleteFile = function (?string $path) use ($dashboardDir) {
    if (!$path) return;
    $path = trim($path);
    if ($path === '') return;

    $candidate = null;
    if (preg_match('/^(\/|[A-Za-z]:\\\\)/', $path)) {
        $candidate = realpath($path);
    } else {
        $candidate = realpath($dashboardDir . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
    }

    if ($candidate && strpos($candidate, $dashboardDir) === 0 && is_file($candidate)) {
        @unlink($candidate);
    }
};

try {
    $pdo->beginTransaction();

    if ($semester === '__all__') {
        $stmt = $pdo->query("SELECT passport_file, payment_proof_file FROM hostel_registrations");
    } else {
        $stmt = $pdo->prepare("SELECT passport_file, payment_proof_file FROM hostel_registrations WHERE semester = ?");
        $stmt->execute([$semester]);
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $safeDeleteFile($row['passport_file'] ?? null);
        $safeDeleteFile($row['payment_proof_file'] ?? null);
    }

    if ($semester === '__all__') {
        $delAlloc = $pdo->exec("DELETE FROM hostel_allocations");
        $delReg   = $pdo->exec("DELETE FROM hostel_registrations");
    } else {
        $delAllocStmt = $pdo->prepare("DELETE FROM hostel_allocations WHERE semester = ?");
        $delAllocStmt->execute([$semester]);
        $delAlloc = $delAllocStmt->rowCount();

        $delRegStmt = $pdo->prepare("DELETE FROM hostel_registrations WHERE semester = ?");
        $delRegStmt->execute([$semester]);
        $delReg = $delRegStmt->rowCount();
    }

    $pdo->commit();

    $label = $semester === '__all__' ? 'all semesters' : $semester;
    echo json_encode([
        'success' => true,
        'message' => "Cleared hostel registrations ({$delReg}) and allocations ({$delAlloc}) for {$label}."
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to clear hostel records.']);
}

