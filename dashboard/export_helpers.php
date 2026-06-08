<?php
require_once 'db.php';

$search = $_GET['search'] ?? '';
$program = $_GET['program'] ?? '';
$mode_of_study = $_GET['mode_of_study'] ?? '';
$ma_focus = $_GET['ma_focus'] ?? '';
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$cohort = $_GET['cohort'] ?? '';

$params = [];
$where = ["u.role = 'student'"];

if (!empty($cohort)) {
    $where[] = "a.cohort = :cohort";
    $params[':cohort'] = $cohort;
}

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

if (!empty($status)) {
    if ($status === 'in_progress') {
        $where[] = "(a.status IS NULL OR a.status = 'in_progress')";
    } elseif ($status === 'draft') {
        $where[] = "a.submitted = 0 AND (a.status IS NULL OR a.status = '' OR a.status = 'draft')";
    } elseif ($status === 'pending') {
        $where[] = "a.submitted = 1 AND (a.status IS NULL OR a.status = '' OR a.status = 'submitted')";
    } else {
        $where[] = "a.status = :status";
        $params[':status'] = $status;
    }
}

if (!empty($start_date) && !empty($end_date)) {
    // Append time to end_date to cover the full day
    $end_date_full = $end_date . ' 23:59:59';
    $start_date_full = $start_date . ' 00:00:00';

    $where[] = "(
        (a.submitted = 1 AND a.submitted_at BETWEEN :start_date1 AND :end_date1) 
        OR 
        (a.submitted = 0 AND a.created_at BETWEEN :start_date2 AND :end_date2)
    )";
    $params[':start_date1'] = $start_date_full;
    $params[':end_date1'] = $end_date_full;
    $params[':start_date2'] = $start_date_full;
    $params[':end_date2'] = $end_date_full;
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
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);