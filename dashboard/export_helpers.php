<?php
require_once 'db.php';

$search = $_GET['search'] ?? '';
$program = $_GET['program'] ?? '';
$mode_of_study = $_GET['mode_of_study'] ?? '';
$ma_focus = $_GET['ma_focus'] ?? '';

$params = [];
$where = ["u.role = 'student'"];

if (!empty($search)) {
    $where[] = "(u.first_name LIKE :first OR u.last_name LIKE :last OR u.email LIKE :email)";
    $params[':first'] = "%$search%";
    $params[':last'] = "%$search%";
    $params[':email'] = "%$search%";
}

if (!empty($program)) {
    $where[] = "ad.program = :program";
    $params[':program'] = $program;
}

if (!empty($mode_of_study)) {
    $where[] = "ad.mode_of_study = :mode_of_study";
    $params[':mode_of_study'] = $mode_of_study;
}

if (!empty($ma_focus) && $program === 'MA') {
    $where[] = "ad.ma_focus = :ma_focus";
    $params[':ma_focus'] = $ma_focus;
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$cohort = $_GET['cohort'] ?? '';

// Fetch Current Active Cohort (for default logic if needed, though export usually reflects exactly what is filtered)
// However, to match the dashboard "Clean View" logic:
// If a cohort is selected, we should strictly filter by it.

if (!empty($cohort)) {
        $where[] = "a.cohort = :cohort";
        $params[':cohort'] = $cohort;
        
        // "Clean Dashboard" logic: If filtering by specific cohort, show ALL (drafts + submitted)
        // This matches fetch_applicants.php logic where selecting a cohort removes the default "submitted=1" filter.
    }

if (!empty($start_date) && !empty($end_date)) {
    $end_date_full = $end_date . ' 23:59:59';
    $start_date_full = $start_date . ' 00:00:00';
    
    // Filter by submitted_at for submitted apps, or created_at for drafts
    $where[] = "(
        (a.submitted = 1 AND a.submitted_at BETWEEN :start_date AND :end_date) 
        OR 
        (a.submitted = 0 AND a.created_at BETWEEN :start_date AND :end_date)
    )";
    $params[':start_date'] = $start_date_full;
    $params[':end_date'] = $end_date_full;
}

$status = $_GET['status'] ?? '';

if (!empty($status)) {
    if ($status === 'submitted') {
        $where[] = "a.submitted = 1 AND a.status IS NULL";
    } else {
        $where[] = "a.status = :status";
        $params[':status'] = $status;
    }
}


$sql = "SELECT u.id,
               CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name) AS full_name,
               u.email,
               u.phone,  -- ✅ NEW LINE
               a.status,
               ad.mode_of_study,
               ad.program,
               ad.ma_focus
        FROM users u
        LEFT JOIN applications a ON u.id = a.user_id
        LEFT JOIN application_details ad ON u.id = ad.user_id";

if (count($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.submitted_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
