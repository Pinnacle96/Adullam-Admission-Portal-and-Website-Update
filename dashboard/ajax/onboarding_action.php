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
    $pdo->beginTransaction();

    if ($action === 'delete') {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // 1. Get user_ids before deleting from queue
        $stmt = $pdo->prepare("SELECT user_id FROM onboarding_queue WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($userIds)) {
            // 2. Delete from onboarding_queue
            $stmt = $pdo->prepare("DELETE FROM onboarding_queue WHERE id IN ($placeholders)");
            $stmt->execute($ids);

            // 3. Reset onboarded status in tuition_payment
            $uPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $pdo->prepare("UPDATE tuition_payment SET onboarded = 0 WHERE user_id IN ($uPlaceholders)");
            $stmt->execute($userIds);
        }
        
        $message = "Selected students removed from onboarding list and status reset.";
    } else if ($action === 'send_email') {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE onboarding_queue SET status = 'pending', attempts = 0 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = "Emails queued for processing.";
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => $message]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
