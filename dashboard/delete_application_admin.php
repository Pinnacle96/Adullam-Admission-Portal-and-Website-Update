<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicant_id = $_POST['id'] ?? null;

    if (!$applicant_id) {
        die(json_encode(['success' => false, 'message' => 'Invalid ID']));
    }

    try {
        $pdo->beginTransaction();

        // 1. Delete associated files first (Cleanup disk)
        // Get all possible file paths from application_documents table
        $stmt = $pdo->prepare("SELECT * FROM application_documents WHERE user_id = ?");
        $stmt->execute([$applicant_id]);
        $docs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($docs) {
            $file_fields = ['passport', 'ssce_cert', 'ssce_cert2', 'birth_cert', 'origin_cert', 'recommendation', 'payment_proof', 'degree_cert', 'transcript'];
            foreach ($file_fields as $field) {
                if (!empty($docs[$field])) {
                    $filePath = $docs[$field];
                    // Handle relative paths
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    } elseif (file_exists('../' . $filePath)) {
                        unlink('../' . $filePath);
                    }
                }
            }
        }

        // 2. Delete from related tables
        $tables = [
            'application_details',
            'application_personal',
            'application_church',
            'application_autobiography',
            'application_references',
            'application_recommendations',
            'application_documents',
            'applications',
            'tuition_payment',
            'onboarding_queue'
        ];

        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->execute([$applicant_id]);
        }

        // 3. Delete user account
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$applicant_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Applicant deleted successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
