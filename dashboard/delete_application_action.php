<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    exit('Access denied');
}

$applicantId = $_POST['id'] ?? null;

if (!$applicantId || !is_numeric($applicantId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    $tables = [
        'application_documents',
        'application_references',
        'application_autobiography',
        'application_church',
        'application_personal',
        'application_details',
        'email_verification_otp',
        'hostel_applications',
        'applications'
    ];

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
        $stmt->execute([$applicantId]);
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$applicantId]);

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    $errorMsg = "Delete error for user_id $applicantId: " . $e->getMessage();
    error_log($errorMsg); // Log to server error log
    echo json_encode(['success' => false, 'message' => $errorMsg]); // Show on frontend for now
}

