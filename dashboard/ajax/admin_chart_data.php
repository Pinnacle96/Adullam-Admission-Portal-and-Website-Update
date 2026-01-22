<?php
require_once '../db.php';

header('Content-Type: application/json');

try {
    // 0. Fetch Current Active Cohort or use GET parameter
    if (isset($_GET['cohort']) && !empty($_GET['cohort'])) {
        $activeCohort = $_GET['cohort'];
    } else {
        $cohortStmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'");
        $activeCohort = $cohortStmt->fetchColumn() ?: 'January 2026';
    }

    // 1. Get the raw data with a single query
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(submitted_at, '%Y-%m') as month_key,
            COUNT(*) as count
        FROM applications
        WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          AND cohort = ?
        GROUP BY month_key
        ORDER BY month_key ASC
    ");
    $stmt->execute([$activeCohort]);
    $raw_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches data as an associative array

    $labels = [];
    $data = [];

    // 2. Loop through the last 12 months to build the final arrays
    for ($i = 11; $i >= 0; $i--) {
        $month_timestamp = strtotime("-$i months");
        $label = date('M Y', $month_timestamp);
        $month_key = date('Y-m', $month_timestamp);

        // 3. Check if we have data for this month, otherwise set to 0
        $count = isset($raw_data[$month_key]) ? (int) $raw_data[$month_key] : 0;

        $labels[] = $label;
        $data[] = $count;
    }

    echo json_encode([
        'labels' => $labels,
        'data' => $data
    ]);

} catch (PDOException $e) {
    // In case of a database error, provide a graceful empty response.
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['labels' => [], 'data' => []]);
}
?>