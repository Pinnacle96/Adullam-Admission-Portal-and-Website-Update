<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the Content-Type header to application/json
header('Content-Type: application/json');

require '../db.php';

// Check if the 'id' GET parameter is set and is a valid number
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID provided.']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Prepare a secure SQL statement using a prepared statement
    $stmt = $pdo->prepare("SELECT * FROM hostel_registrations WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if a row was found
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        // No registration found with that ID
        echo json_encode(['success' => false, 'message' => 'Registration with ID ' . $id . ' not found.']);
    }
} catch (PDOException $e) {
    // Handle database errors
    http_response_code(500); // Set HTTP status code to 500 for server errors
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>