<?php
require '../db.php';

// 📅 Trend: last 6 months
$trend = $pdo->query("
  SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month, COUNT(*) AS count
  FROM applications WHERE submitted = 1
  GROUP BY month ORDER BY month DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$data['trend'] = [
    'labels' => array_reverse(array_column($trend, 'month')),
    'data' => array_reverse(array_column($trend, 'count'))
];

// ✅ Date Filters
$startDate = $_GET['start_date'] ?? date('Y-01-01');
$endDate   = $_GET['end_date'] ?? date('Y-12-31');

// � Trend: last 6 months (affected by filter? Usually trend shows recent activity, maybe keep as is or filter)
// Let's keep trend as "Recent Activity" but filter the Status/Program breakdowns by the window.

// �📌 Status breakdown
$statusStmt = $pdo->prepare("
  SELECT status, COUNT(*) AS count 
  FROM applications 
  WHERE (submitted_at BETWEEN ? AND ? OR submitted_at IS NULL)
  GROUP BY status
");
// Note: Incomplete apps (draft) have NULL submitted_at. 
// If we filter by date, we might lose 'draft' counts if we only look at submitted_at.
// However, 'clean metrics' usually refers to the Admitted/Submitted counts for a specific batch.
// If I want to see "Drafts from this window", I'd need to check created_at.
// Let's refine the query logic.

// If filtering by a window (e.g., Jan-June), we likely want:
// 1. Submitted applications within that range.
// 2. Draft applications created within that range (optional, but good for conversion rates).

$statusStmt = $pdo->prepare("
  SELECT status, COUNT(*) AS count 
  FROM applications 
  WHERE (submitted_at BETWEEN ? AND ?) 
     OR (status = 'draft' AND created_at BETWEEN ? AND ?)
  GROUP BY status
");
$statusStmt->execute([$startDate, $endDate, $startDate, $endDate]);
$status = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$data['status'] = [
    'labels' => array_keys($status),
    'data' => array_values($status)
];

// 🚻 Gender breakdown (Join with applications to filter by date)
$genderStmt = $pdo->prepare("
  SELECT ad.gender, COUNT(*) AS count 
  FROM application_details ad
  JOIN applications a ON ad.user_id = a.user_id
  WHERE a.submitted_at BETWEEN ? AND ?
  GROUP BY ad.gender
");
$genderStmt->execute([$startDate, $endDate]);
$gender = $genderStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$data['gender'] = [
    'labels' => array_keys($gender),
    'data' => array_values($gender)
];

// 🎓 Programs
$progStmt = $pdo->prepare("
  SELECT ad.program, COUNT(*) AS count 
  FROM application_details ad
  JOIN applications a ON ad.user_id = a.user_id
  WHERE a.submitted_at BETWEEN ? AND ?
  GROUP BY ad.program
");
$progStmt->execute([$startDate, $endDate]);
$programs = $progStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$data['program'] = [
    'labels' => array_keys($programs),
    'data' => array_values($programs)
];

header('Content-Type: application/json');
echo json_encode($data);
