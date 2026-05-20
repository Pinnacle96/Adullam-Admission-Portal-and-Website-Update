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
    // Begin transaction
    $pdo->beginTransaction();

    // Get current status and capacity
    $stmt = $pdo->prepare("
        SELECT hr.active, hr.capacity, hr.hostel_name, hr.room_number,
               (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = hr.id) AS current_occupancy
        FROM hostel_rooms hr
        WHERE hr.id = ?
    ");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        $_SESSION['error'] = "Room not found";
        header('Location: hostel_rooms_overview.php');
        exit;
    }
    
    // Calculate new status
    $new_status = $room['active'] ? 0 : 1;
    
    // Prevent deactivation if room is occupied
    if ($room['active'] && $new_status == 0 && $room['current_occupancy'] > 0) {
        $pdo->rollBack();
        $_SESSION['error'] = "Cannot deactivate room {$room['hostel_name']} - {$room['room_number']} with {$room['current_occupancy']} occupant(s). Reassign students first.";
        header('Location: hostel_rooms_overview.php');
        exit;
    }
    
    // Update status
    $update_stmt = $pdo->prepare("UPDATE hostel_rooms SET active = ? WHERE id = ?");
    $update_stmt->execute([$new_status, $room_id]);
    
   // After successful status update
$log_stmt = $pdo->prepare("
    INSERT INTO room_status_logs 
    (room_id, hostel_name, room_number, changed_by, changed_by_name,
     old_status, new_status, occupancy_at_change, change_reason,
     ip_address, user_agent) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Get user details for logging
$user_stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$log_stmt->execute([
    $room_id,
    $room['hostel_name'],
    $room['room_number'],
    $_SESSION['user_id'],
    $user['first_name'],
    $room['active'],
    $new_status,
    $room['current_occupancy'],
    isset($_POST['reason']) ? $_POST['reason'] : null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Room status updated successfully";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header('Location: hostel_rooms_overview.php');
exit;