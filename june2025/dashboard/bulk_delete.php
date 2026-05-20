<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userIds = $_POST['ids'] ?? [];

if (!is_array($userIds) || empty($userIds)) {
    echo json_encode(['success' => false, 'message' => 'No users selected']);
    exit;
}

// Strict validation: allow only numeric
$filteredIds = array_map('intval', array_filter($userIds, 'is_numeric'));

if (empty($filteredIds)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user IDs']);
    exit;
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($filteredIds), '?'));

    $tables = [
        'application_documents',
        'application_references',
        'application_autobiography',
        'application_church',
        'application_personal',
        'application_details',
        'applications',
        'hostel_applications'
    ];

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id IN ($placeholders)");
        $stmt->execute($filteredIds);
    }

    // Final user delete
    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role = 'student'");
    $stmt->execute($filteredIds);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => count($filteredIds) . ' applicants deleted']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Bulk delete failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bulk delete failed']);
}
?>
