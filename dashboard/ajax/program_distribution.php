<?php
require '../db.php';

header('Content-Type: application/json');

$cohort = $_GET['cohort'] ?? null;
if (!$cohort) {
    $cohort = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026';
}

try {
    $stmt = $pdo->prepare("
        SELECT ad.program, COUNT(*) as count 
        FROM application_details ad 
        JOIN applications a ON ad.user_id = a.user_id 
        WHERE a.cohort = ? 
        GROUP BY ad.program
    ");
    $stmt->execute([$cohort]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($results, 'program');
    $data = array_column($results, 'count');

    echo json_encode(['labels' => $labels, 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
