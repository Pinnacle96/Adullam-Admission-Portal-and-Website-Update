<?php
require '../db.php';

// Get cohort from GET or default to current
$cohort = $_GET['cohort'] ?? null;
if (!$cohort) {
    $cohort = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026';
}

// Total per month (past 6 months)
$sql = "SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month, COUNT(*) AS count
        FROM applications
        WHERE submitted = 1 AND cohort = :cohort
        GROUP BY month
        ORDER BY month DESC
        LIMIT 6";
$stmt = $pdo->prepare($sql);
$stmt->execute([':cohort' => $cohort]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = array_reverse(array_column($rows, 'month'));
$data = array_reverse(array_column($rows, 'count'));

echo json_encode(['labels' => $labels, 'data' => $data]);
