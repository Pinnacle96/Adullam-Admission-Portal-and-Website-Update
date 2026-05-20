<?php
session_start();
require_once '../db.php';

// Check authentication and authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit;
}

// Get room ID from request
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($room_id <= 0) {
    $_SESSION['error'] = "Invalid room ID";
    header('Location: hostel_rooms_overview.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Get room details for logging
    $room_stmt = $pdo->prepare("SELECT hostel_name, room_number FROM hostel_rooms WHERE id = ?");
    $room_stmt->execute([$room_id]);
    $room = $room_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        $_SESSION['error'] = "Room not found";
        header('Location: hostel_rooms_overview.php');
        exit;
    }

    // Check for current occupants
    $occupancy_stmt = $pdo->prepare("SELECT COUNT(*) FROM hostel_allocations WHERE room_id = ?");
    $occupancy_stmt->execute([$room_id]);
    $occupancy_count = $occupancy_stmt->fetchColumn();

    if ($occupancy_count > 0) {
        $_SESSION['error'] = "Cannot delete room with active occupants. Reassign students first.";
        header('Location: hostel_rooms_overview.php');
        exit;
    }

    // Log the deletion (before actually deleting)
    $log_stmt = $pdo->prepare("
        INSERT INTO room_status_logs 
        (room_id, hostel_name, room_number, changed_by, old_status, new_status, change_reason)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $room_id,
        $room['hostel_name'],
        $room['room_number'],
        $_SESSION['user_id'],
        1, // Old status (active)
        0, // New status (deleted)
        'Room deleted'
    ]);

    // Delete the room
    $delete_stmt = $pdo->prepare("DELETE FROM hostel_rooms WHERE id = ?");
    $delete_stmt->execute([$room_id]);

    $pdo->commit();

    $_SESSION['success'] = "Room deleted successfully";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header('Location: hostel_rooms_overview.php');
exit;