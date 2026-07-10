<?php
require '../db.php';

header('Content-Type: application/json');

$cohort = trim($_GET['cohort'] ?? '');
if ($cohort === '') {
    $cohort = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026';
}

$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$dateClause = '';
$joinedDateClause = '';
$dateParams = [];
if ($startDate !== '' && $endDate !== '') {
    $dateClause = " AND ((submitted_at BETWEEN ? AND ?) OR (submitted_at IS NULL AND created_at BETWEEN ? AND ?))";
    $joinedDateClause = " AND ((a.submitted_at BETWEEN ? AND ?) OR (a.submitted_at IS NULL AND a.created_at BETWEEN ? AND ?))";
    $dateParams = [$startDate, $endDate, $startDate, $endDate];
}

$trendStmt = $pdo->prepare("
    SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month, COUNT(*) AS count
    FROM applications
    WHERE submitted = 1 AND cohort = ?
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$trendStmt->execute([$cohort]);
$trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$statusStmt = $pdo->prepare("
    SELECT COALESCE(NULLIF(status, ''), 'submitted') AS status, COUNT(*) AS count
    FROM applications
    WHERE cohort = ? $dateClause
    GROUP BY COALESCE(NULLIF(status, ''), 'submitted')
");
$statusStmt->execute(array_merge([$cohort], $dateParams));
$status = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$genderStmt = $pdo->prepare("
    SELECT COALESCE(NULLIF(ad.gender, ''), 'Unknown') AS gender, COUNT(*) AS count
    FROM application_details ad
    JOIN applications a ON ad.user_id = a.user_id
    WHERE a.cohort = ? $joinedDateClause
    GROUP BY COALESCE(NULLIF(ad.gender, ''), 'Unknown')
");
$genderStmt->execute(array_merge([$cohort], $dateParams));
$gender = $genderStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$programStmt = $pdo->prepare("
    SELECT COALESCE(NULLIF(ad.program, ''), 'Unknown') AS program, COUNT(*) AS count
    FROM application_details ad
    JOIN applications a ON ad.user_id = a.user_id
    WHERE a.cohort = ? $joinedDateClause
    GROUP BY COALESCE(NULLIF(ad.program, ''), 'Unknown')
");
$programStmt->execute(array_merge([$cohort], $dateParams));
$programs = $programStmt->fetchAll(PDO::FETCH_KEY_PAIR);

echo json_encode([
    'cohort' => $cohort,
    'trend' => [
        'labels' => array_reverse(array_column($trend, 'month')),
        'data' => array_reverse(array_column($trend, 'count')),
    ],
    'status' => [
        'labels' => array_keys($status),
        'data' => array_values($status),
    ],
    'gender' => [
        'labels' => array_keys($gender),
        'data' => array_values($gender),
    ],
    'program' => [
        'labels' => array_keys($programs),
        'data' => array_values($programs),
    ],
]);
