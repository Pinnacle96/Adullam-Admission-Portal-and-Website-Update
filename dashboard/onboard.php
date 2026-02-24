<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Admin';

// Fetch Current Active Cohort
$defaultCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');

// Pagination & Filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search  = trim($_GET['search'] ?? '');
$program = trim($_GET['program'] ?? '');
$cohort  = trim($_GET['cohort'] ?? '');
$status  = trim($_GET['status'] ?? ''); // Queue status: pending, sent, failed

// Fetch all distinct cohorts for filter
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL ORDER BY cohort DESC")->fetchAll(PDO::FETCH_COLUMN);

// Build Query
$where = ["d.mode_of_study = 'online'"]; // Crucial: Only online students
$params = [];

if ($search) {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($program) {
    $where[] = "d.program = ?";
    $params[] = $program;
}

if ($cohort) {
    $where[] = "a.cohort = ?";
    $params[] = $cohort;
}

if ($status) {
    $where[] = "oq.status = ?";
    $params[] = $status;
}

$whereSQL = implode(" AND ", $where);

// Count Total
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM onboarding_queue oq
    JOIN users u ON oq.user_id = u.id
    JOIN application_details d ON oq.user_id = d.user_id
    LEFT JOIN applications a ON oq.user_id = a.user_id
    WHERE $whereSQL
");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch Records
$sql = "
    SELECT oq.*, u.first_name, u.last_name, u.email, d.program, d.ma_focus, a.cohort
    FROM onboarding_queue oq
    JOIN users u ON oq.user_id = u.id
    JOIN application_details d ON oq.user_id = d.user_id
    LEFT JOIN applications a ON oq.user_id = a.user_id
    WHERE $whereSQL
    ORDER BY oq.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Onboarding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex flex-col md:flex-row">
        <?php include 'components/sidebar.php'; ?>
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-purple-800">🚀 Online Student Onboarding</h1>
                <div class="flex gap-2">
                    <button onclick="bulkOnboardAction('delete')" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 transition">Remove Selected</button>
                    <button onclick="bulkOnboardAction('send_email')" class="bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-purple-800 transition">Send Onboarding Email</button>
                </div>
            </div>

            <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <input name="search" value="<?= htmlspecialchars($search) ?>" type="text" placeholder="Search name or email" class="px-4 py-2 border rounded-lg">
                <select name="cohort" class="px-4 py-2 border rounded-lg" onchange="this.form.submit()">
                    <option value="">All Cohorts</option>
                    <?php foreach ($cohorts as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $cohort === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="program" class="px-4 py-2 border rounded-lg" onchange="this.form.submit()">
                    <option value="">All Programs</option>
                    <option value="MA" <?= $program === 'MA' ? 'selected' : '' ?>>MA</option>
                    <option value="PGDT" <?= $program === 'PGDT' ? 'selected' : '' ?>>PGDT</option>
                    <option value="B.Div" <?= $program === 'B.Div' ? 'selected' : '' ?>>B.Div</option>
                    <option value="Diploma" <?= $program === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                    <option value="Certificate" <?= $program === 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                </select>
                <select name="status" class="px-4 py-2 border rounded-lg" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
                <button type="submit" class="bg-purple-600 text-white rounded-lg font-bold">Filter</button>
            </form>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="p-4 w-10"><input type="checkbox" id="selectAll" class="rounded"></th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4">Cohort</th>
                                <th class="p-4">Program</th>
                                <th class="p-4">Email</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Attempts</th>
                                <th class="p-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-4"><input type="checkbox" value="<?= $s['id'] ?>" class="student-checkbox rounded"></td>
                                <td class="p-4 font-medium"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                <td class="p-4 text-xs text-gray-600 font-bold"><?= htmlspecialchars($s['cohort'] ?? 'N/A') ?></td>
                                <td class="p-4 uppercase"><?= htmlspecialchars($s['program']) ?></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($s['email']) ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded text-xs text-white <?= $s['status'] === 'sent' ? 'bg-green-600' : ($s['status'] === 'failed' ? 'bg-red-600' : 'bg-yellow-500') ?>">
                                        <?= ucfirst($s['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center"><?= $s['attempts'] ?></td>
                                <td class="p-4">
                                    <button onclick="triggerSingleEmail(<?= $s['id'] ?>)" class="text-purple-600 font-bold hover:underline">Resend Email</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600">
                    Showing <span class="font-bold"><?= $offset + 1 ?></span> to <span class="font-bold"><?= min($offset + $limit, $totalRecords) ?></span> of <span class="font-bold"><?= $totalRecords ?></span> students
                </p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&status=<?= urlencode($status) ?>" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&status=<?= urlencode($status) ?>" class="px-4 py-2 bg-purple-600 text-white rounded-lg font-bold">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Auto-submit form on filter change
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => {
                document.getElementById('filterForm').submit();
            }, 500));
        }

        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = this.checked);
        });

        function bulkOnboardAction(action) {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                Swal.fire('Error', 'Please select students.', 'error');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: action === 'delete' ? 'This removes them from the onboarding list.' : 'This will queue emails for all selected students.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, proceed'
            }).then(result => {
                if (result.isConfirmed) {
                    performAction(action, selected);
                }
            });
        }

        function triggerSingleEmail(id) {
            performAction('send_email', [id]);
        }

        function performAction(action, ids) {
            fetch('ajax/onboarding_action', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ids })
            }).then(res => res.json()).then(data => {
                Swal.fire(data.status === 'success' ? 'Success' : 'Error', data.message, data.status)
                .then(() => window.location.reload());
            });
        }
    </script>
</body>
</html>
