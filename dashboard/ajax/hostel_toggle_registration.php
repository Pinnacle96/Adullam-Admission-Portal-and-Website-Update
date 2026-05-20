<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$value = isset($payload['value']) ? trim((string)$payload['value']) : null;
$target = isset($payload['target']) ? strtolower(trim((string)$payload['target'])) : '';

if ($value !== '0' && $value !== '1') {
    echo json_encode(['success' => false, 'message' => 'Invalid value']);
    exit;
}

if (!in_array($target, ['new', 'returning'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid target']);
    exit;
}

try {
    $settingKey = getHostelRegistrationSettingKey($target);
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
    $stmt->execute([$value, $settingKey]);
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
        $stmt->execute([$settingKey, $value]);
    }
    $label = $target === 'returning' ? 'Returning student hostel registration' : 'New student hostel registration';
    echo json_encode(['success' => true, 'message' => $value === '1' ? "{$label} opened." : "{$label} locked."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update setting.']);
}
