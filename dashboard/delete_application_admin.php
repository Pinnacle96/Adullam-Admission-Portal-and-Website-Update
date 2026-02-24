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
                if (!empty($docs[$field]) && file_exists($docs[$field])) {
                    unlink($docs[$field]);
                }
            }
        }

        // 2. Delete from related tables (Cascading delete would be better, but doing it manually for safety)
        $tables = [
            'application_details',
            'application_personal',
            'application_church',
            'application_autobiography',
            'application_references',
            'application_recommendations',
            'application_documents',
            'applications'
        ];

        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->execute([$applicant_id]);
        }

        // 3. Optional: Delete user account? 
        // User instruction said "delete applications", usually means the application data.
        // We'll keep the user record for now to prevent breaking session logic if they are logged in,
        // but if they aren't, it might be cleaner to delete the user too.
        // For safety, let's JUST delete the application.

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Application deleted successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
