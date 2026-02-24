<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    http_response_code(403);
    exit('Access denied');
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$program = $_GET['program'] ?? '';
$mode_of_study = $_GET['mode_of_study'] ?? '';
$ma_focus = $_GET['ma_focus'] ?? '';
$status = $_GET['status'] ?? '';
$moderate = isset($_GET['moderate']) && $_GET['moderate'] === 'true';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$cohort = $_GET['cohort'] ?? '';

// Pagination variables
$limit = 20; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the WHERE clause
$params = [];
$where = ["u.role = 'student'"];

// Fetch Current Active Cohort
$stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'current_cohort'");
$stmt->execute();
$currentActiveCohort = $stmt->fetchColumn();

if (!empty($cohort)) {
    $where[] = "a.cohort = :cohort";
    $params[':cohort'] = $cohort;
}

if (!empty($search)) {
    $where[] = "(u.first_name LIKE :first OR u.last_name LIKE :last OR LOWER(u.email) LIKE LOWER(:email))";
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
} else {
    // Dynamic Application Window Check
    $deadlineFile = '../modal_settings.json';
    $isWindowOpen = false;

    if (file_exists($deadlineFile)) {
        $settings = json_decode(file_get_contents($deadlineFile), true);
        $deadline = $settings['deadline'] ?? '';
        if (!empty($deadline)) {
            // Check if current time is BEFORE or ON the deadline day (end of day)
            if (time() <= strtotime($deadline . ' 23:59:59')) {
                $isWindowOpen = true;
            }
        }
    }

    // Determine Visibility based on Cohort and Window
    $isPastCohort = (!empty($cohort) && $cohort !== $currentActiveCohort);

    if (!empty($cohort)) {
        // If a cohort is explicitly selected, show ALL applications (submitted + drafts).
        // No default filter applied.
    } elseif (!$isWindowOpen) {
        // Current Cohort (or All) AND Window is Closed: Only show submitted
        $where[] = "a.submitted = 1";
    }
    // Else: Current Cohort (default) AND Window Open -> Show All (Drafts included)
}

if ($moderate) {
    $where[] = "(a.status = 'submitted' OR (a.status IS NULL AND a.submitted = 1))";
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

$whereClause = ' WHERE ' . implode(' AND ', $where);

// 1. Get total record count for pagination
$count_sql = "SELECT COUNT(*) FROM users u
              LEFT JOIN applications a ON u.id = a.user_id
              LEFT JOIN application_details ad ON u.id = ad.user_id "
           . $whereClause;

$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 2. Get paginated results
$sql = "SELECT u.id, u.first_name, u.last_name, u.email, 
               a.status, a.submitted, a.submitted_at, a.created_at, a.cohort,
               ad.program, ad.ma_focus
        FROM users u
        LEFT JOIN applications a ON u.id = a.user_id
        LEFT JOIN application_details ad ON u.id = ad.user_id "
     . $whereClause
     . " ORDER BY a.submitted_at DESC, a.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Output HTML Table
if (count($applicants) > 0) {
    echo '<div class="overflow-x-auto bg-white rounded-lg shadow">';
    echo '<table class="min-w-full divide-y divide-gray-200">';
    echo '<thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cohort</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
            </tr>
          </thead>';
    echo '<tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($applicants as $app) {
        // Status Badge Logic
        $status_color = 'bg-gray-100 text-gray-800';
        $status_text = 'Unknown';
        
        if ($app['status'] === 'admitted') {
            $status_color = 'bg-green-100 text-green-800';
            $status_text = 'Admitted';
        } elseif ($app['status'] === 'rejected') {
            $status_color = 'bg-red-100 text-red-800';
            $status_text = 'Rejected';
        } elseif ($app['submitted'] == 1) {
            $status_color = 'bg-blue-100 text-blue-800';
            $status_text = 'Submitted';
        } else {
            $status_color = 'bg-yellow-100 text-yellow-800';
            $status_text = 'In Progress';
        }

        // Date Logic
        $submittedDate = $app['submitted'] == 1 ? $app['submitted_at'] : $app['created_at'];
        $date = !empty($submittedDate) ? date('M j, Y', strtotime($submittedDate)) : 'N/A';

        echo "<tr>";
        echo "<td class='px-6 py-4 whitespace-nowrap'>
                <div class='flex items-center'>
                    <div class='flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-bold'>
                        " . strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1)) . "
                    </div>
                    <div class='ml-4'>
                        <div class='text-sm font-medium text-gray-900'>" . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . "</div>
                        <div class='text-sm text-gray-500'>" . htmlspecialchars($app['email']) . "</div>
                    </div>
                </div>
              </td>";
        echo "<td class='px-6 py-4 whitespace-nowrap'>
                <div class='text-sm text-gray-900'>" . htmlspecialchars($app['program'] ?? 'N/A') . "</div>
                " . ($app['program'] === 'MA' ? "<div class='text-xs text-gray-500'>" . htmlspecialchars($app['ma_focus'] ?? '') . "</div>" : "") . "
              </td>";
        echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>
                " . htmlspecialchars($app['cohort'] ?? 'N/A') . "
              </td>";
        echo "<td class='px-6 py-4 whitespace-nowrap'>
                <span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full $status_color'>
                    $status_text
                </span>
              </td>";
        echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>
                $date
              </td>";
        echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center gap-3'>
                <a href='applicant_view?id=" . $app['id'] . "' class='text-purple-600 hover:text-purple-900'>View</a>
                <button onclick='deleteApplication(" . $app['id'] . ")' class='text-red-600 hover:text-red-900'>Delete</button>
              </td>";
        echo "</tr>";
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // Pagination Controls
    if ($total_pages > 1) {
        echo '<div class="mt-4 flex flex-wrap justify-center gap-2 w-full">';
        
        // Previous Button
        if ($page > 1) {
            echo "<button onclick='fetchApplicants(" . ($page - 1) . ")' class='px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50'>&laquo; Prev</button>";
        }

        // Page Numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = $i === $page ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
            echo "<button onclick='fetchApplicants($i)' class='px-3 py-1 border rounded $active'>$i</button>";
        }

        // Next Button
        if ($page < $total_pages) {
            echo "<button onclick='fetchApplicants(" . ($page + 1) . ")' class='px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50'>Next &raquo;</button>";
        }
        
        echo '</div>';
    }

} else {
    echo '<div class="text-center py-10 bg-white rounded-lg shadow">';
    echo '<p class="text-gray-500 text-lg">No applicants found matching your criteria.</p>';
    echo '</div>';
}
?>