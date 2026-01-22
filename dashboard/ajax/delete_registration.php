<?php
// Set the Content-Type header to application/json
header('Content-Type: application/json');

// Include the database connection and start the session
require '../db.php';
session_start();

// --- Role-based Access Control ---
// Ensure the user is logged in and has the necessary permissions to delete.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403); // Set HTTP response code to 403 (Forbidden)
    // Corrected response to include 'success' key for JS check
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// --- Get the JSON data from the request body ---
$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

// --- Validate the ID ---
if ($id <= 0) {
    // Corrected response to include 'success' key for JS check
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid or missing registration ID.']);
    exit;
}

try {
    // Optional: Remove uploaded files before deleting the database record
    $fetch = $pdo->prepare("SELECT passport_file, payment_proof_file FROM hostel_registrations WHERE id = ?");
    $fetch->execute([$id]);
    $files = $fetch->fetch(PDO::FETCH_ASSOC);

    if ($files) {
        // Iterate through the files and delete them if they exist
        foreach (['passport_file', 'payment_proof_file'] as $f) {
            // Check if the file path is not empty and the file exists on the server
            if (!empty($files[$f]) && file_exists('../' . $files[$f])) {
                unlink('../' . $files[$f]); // Use unlink() to delete the file
            }
        }
    }

    // --- Delete the record using a prepared statement ---
    $stmt = $pdo->prepare("DELETE FROM hostel_registrations WHERE id = ?");
    $success = $stmt->execute([$id]);
    $rowCount = $stmt->rowCount();

    // --- Prepare the JSON response ---
    if ($success && $rowCount > 0) {
        // Deletion was successful and a row was affected
        echo json_encode([
            'success' => true,      // Key for JavaScript check
            'status' => 'success',  // Key for SweetAlert icon
            'message' => 'Registration deleted successfully.'
        ]);
    } else {
        // No rows were affected, meaning the ID didn't exist or was already deleted
        echo json_encode([
            'success' => false,
            'status' => 'info', // Use 'info' for SweetAlert icon for a non-critical message
            'message' => 'No registration found with this ID to delete.'
        ]);
    }

} catch (PDOException $e) {
    // Log the database error for debugging purposes
    error_log("Database Error: " . $e->getMessage());
    
    // Return a generic database error message to the client
    http_response_code(500); // Set HTTP response code to 500 (Internal Server Error)
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Database error: Unable to delete the record.'
    ]);
}
?>