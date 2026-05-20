<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Check for authentication and role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Decode the JSON payload from the request body
$data = json_decode(file_get_contents('php://input'), true);

// Check if 'ids' array is present and is not empty
if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No IDs provided for deletion.']);
    exit;
}

$ids_to_delete = $data['ids'];
// Sanitize the IDs to ensure they are all integers
$sanitized_ids = array_map('intval', $ids_to_delete);

// Prepare the SQL query. The `IN` clause is perfect for bulk deletion.
// Use a placeholder for each ID to prevent SQL injection.
$placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
$sql = "DELETE FROM hostel_registrations WHERE id IN ($placeholders)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($sanitized_ids);

    $deleted_count = $stmt->rowCount();
    $message = "Successfully deleted " . $deleted_count . " registration(s).";
    echo json_encode(['status' => 'success', 'message' => $message]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    error_log("Bulk delete error: " . $e->getMessage());
}
?>