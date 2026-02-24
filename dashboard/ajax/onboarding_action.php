<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$ids = $data['ids'] ?? [];

if (empty($ids) || !in_array($action, ['delete', 'send_email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    if ($action === 'delete') {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM onboarding_queue WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = "Selected students removed from onboarding list.";
    } else if ($action === 'send_email') {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE onboarding_queue SET status = 'pending', attempts = 0 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = "Emails queued for processing.";
    }

    echo json_encode(['status' => 'success', 'message' => $message]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
