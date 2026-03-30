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
$value = isset($payload['value']) ? trim((string)$payload['value']) : null;

if ($value !== '0' && $value !== '1') {
    echo json_encode(['success' => false, 'message' => 'Invalid value']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
    $stmt->execute([$value, 'hostel_registration_open']);
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
        $stmt->execute(['hostel_registration_open', $value]);
    }
    echo json_encode(['success' => true, 'message' => $value === '1' ? 'Hostel registration opened.' : 'Hostel registration locked.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update setting.']);
}
