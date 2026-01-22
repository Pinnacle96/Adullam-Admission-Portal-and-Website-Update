<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index');
    exit;
}

// Fetch Distinct Cohorts from Applications
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
// Fetch Current Active Cohort (Default Selection)
$currentCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');
// Ensure Current Active Cohort is in the list
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);

// Filter by Cohort
$selectedCohort = $_GET['cohort'] ?? $currentCohort;

// Pagination setup
$limit = 10; // records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Date Filters
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

$whereClause = "WHERE u.role = 'student'";
$params = [];

// Apply Cohort Filter (Join with applications table to filter by cohort)
if ($selectedCohort) {
    $whereClause .= " AND a.cohort = :cohort AND a.submitted = 1";
    $params[':cohort'] = $selectedCohort;
}

if ($startDate && $endDate) {
    $whereClause .= " AND ar.created_at BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
    $params[':end_date'] = $endDate . ' 23:59:59';
}

// Count total applicants with recommendations
$countSql = "
    SELECT COUNT(DISTINCT u.id) 
    FROM users u
    JOIN application_recommendations ar ON u.id = ar.user_id
    LEFT JOIN applications a ON u.id = a.user_id
    $whereClause
";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalApplicants = $countStmt->fetchColumn();
$totalPages = ceil($totalApplicants / $limit);

// Fetch applicants with referees (paginated)
$sql = "
    SELECT 
        u.id AS user_id,
        CONCAT_WS(' ', u.first_name, u.last_name) AS full_name,
        ar.referee_name,
        ar.referee_email,
        ar.submitted,
        ar.created_at,
        a.cohort
    FROM users u
    JOIN application_recommendations ar ON u.id = ar.user_id
    LEFT JOIN applications a ON u.id = a.user_id
    $whereClause
    ORDER BY ar.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Group by user
$recommendations = [];
foreach ($rows as $row) {
    $uid = $row['user_id'];
    if (!isset($recommendations[$uid])) {
        $recommendations[$uid] = [
            'full_name' => $row['full_name'],
            'refs' => []
        ];
    }
    $recommendations[$uid]['refs'][] = [
        'name' => $row['referee_name'],
        'email' => $row['referee_email'],
        'submitted' => $row['submitted']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recommendation List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-purple-800 mb-6">📄 Recommendation Submissions</h1>

            <!-- Filter Form -->
            <form method="GET" class="bg-white p-4 rounded-xl shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cohort</label>
                    <select name="cohort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm border p-2">
                        <option value="">All Cohorts</option>
                        <?php foreach ($cohorts as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $c === $selectedCohort ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">From Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm border p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">To Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm border p-2">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-purple-700 text-white px-4 py-2 rounded-md hover:bg-purple-800 transition">Filter Results</button>
                </div>
            </form>

            <!-- Responsive table container -->
            <div class="overflow-x-auto bg-white shadow-md rounded-xl">
                <table class="min-w-full text-sm text-left text-gray-600">
                    <thead class="bg-purple-800 text-white text-xs uppercase hidden md:table-header-group">
                        <tr>
                            <th class="px-4 py-3">Applicant</th>
                            <th class="px-4 py-3">Referees</th>
                            <th class="px-4 py-3">Submitted</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recommendations as $userId => $applicant): ?>
                            <tr class="flex flex-col md:table-row bg-white md:bg-transparent border md:border-0 rounded-lg md:rounded-none shadow-sm md:shadow-none mb-4 md:mb-0">
                                <!-- Applicant -->
                                <td class="px-4 py-3 font-medium text-purple-900"><?= htmlspecialchars($applicant['full_name']) ?></td>

                                <!-- Referees -->
                                <td class="px-4 py-3">
                                    <?php foreach ($applicant['refs'] as $r): ?>
                                        <div class="mb-3">
                                            <p class="font-semibold"><?= htmlspecialchars($r['name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($r['email']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </td>

                                <!-- Submitted -->
                                <td class="px-4 py-3">
                                    <?php foreach ($applicant['refs'] as $r): ?>
                                        <span class="block mb-2">
                                            <?= $r['submitted'] ? "✅ Submitted" : "❌ Not yet" ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>

                                <!-- Action -->
                                <td class="px-4 py-3">
                                    <a href="resend_recommendation?id=<?= $userId ?>"
                                        class="text-purple-700 hover:underline text-sm">
                                        🔁 Resend Link
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recommendations)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray-500 py-4">No recommendation records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <div class="flex flex-wrap justify-center mt-6 gap-2">
                    <?php 
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $queryString = http_build_query($queryParams);
                    $baseUrl = "?" . ($queryString ? $queryString . "&" : "");
                    ?>

                    <?php if ($page > 1): ?>
                        <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= $baseUrl ?>page=<?= $i ?>"
                            class="px-3 py-1 rounded <?= $i == $page ? 'bg-purple-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>
