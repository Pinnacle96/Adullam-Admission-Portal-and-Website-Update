<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$ids = $data['ids'] ?? [];
$action = $data['action'] ?? '';

if (empty($ids) || !in_array($action, ['delete', 'onboard'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'delete') {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM tuition_payment WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = "Selected payments deleted successfully.";
    } else if ($action === 'onboard') {
        // Onboarding logic:
        // 1. Mark as onboarded in tuition_payment
        // 2. Add to onboarding_queue
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Get user_ids for these payment records
        $stmt = $pdo->prepare("SELECT user_id FROM tuition_payment WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($userIds)) {
            // Update tuition_payment
            $stmt = $pdo->prepare("UPDATE tuition_payment SET onboarded = 1 WHERE id IN ($placeholders)");
            $stmt->execute($ids);

            // Insert into onboarding_queue (ignore if already exists)
            $queueStmt = $pdo->prepare("INSERT IGNORE INTO onboarding_queue (user_id, status) VALUES (?, 'pending')");
            foreach ($userIds as $uid) {
                $queueStmt->execute([$uid]);
            }
        }
        
        $message = "Selected students marked for onboarding.";
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => $message]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
