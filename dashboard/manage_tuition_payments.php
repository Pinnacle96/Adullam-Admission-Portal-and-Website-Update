<?php
session_start();
require 'db.php';
require 'mailer.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Admin';

$defaultCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($defaultCohort, $cohorts)) {
    array_unshift($cohorts, $defaultCohort);
}
rsort($cohorts);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search  = trim($_GET['search'] ?? '');
$program = trim($_GET['program'] ?? '');
$mode    = trim($_GET['mode'] ?? '');
$cohort  = isset($_GET['cohort']) ? trim($_GET['cohort']) : $defaultCohort;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR d.program LIKE ? OR tp.status LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($program) {
    $where[] = "d.program = ?";
    $params[] = $program;
}

if ($mode) {
    $where[] = "d.mode_of_study = ?";
    $params[] = $mode;
}

if ($cohort) {
    $where[] = "a.cohort = ?";
    $params[] = $cohort;
}

$whereSQL = implode(" AND ", $where);

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM tuition_payment tp
    JOIN users u ON tp.user_id = u.id
    JOIN application_details d ON tp.user_id = d.user_id
    LEFT JOIN applications a ON tp.user_id = a.user_id
    WHERE $whereSQL
");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalRecords / $limit);

$sql = "
    SELECT tp.*, u.first_name, u.last_name, d.program, d.mode_of_study, d.ma_focus, a.cohort
    FROM tuition_payment tp
    JOIN users u ON tp.user_id = u.id
    JOIN application_details d ON tp.user_id = d.user_id
    LEFT JOIN applications a ON tp.user_id = a.user_id
    WHERE $whereSQL
    ORDER BY tp.updated_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tuition Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
<?php include 'components/navbar.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
            <div class="flex flex-col gap-2 mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-purple-800">Tuition Payment Management</h1>
                <p class="text-sm text-gray-600">Review submitted tuition payments, approve valid proofs, and onboard eligible online students.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
                    <p class="text-xs text-gray-500">Showing</p>
                    <p class="text-2xl font-bold text-purple-800"><?= number_format($totalRecords) ?></p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
                    <p class="text-xs text-gray-500">Cohort</p>
                    <p class="text-lg font-semibold text-gray-800"><?= $cohort ? htmlspecialchars($cohort) : 'All Cohorts' ?></p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
                    <p class="text-xs text-gray-500">Page</p>
                    <p class="text-lg font-semibold text-gray-800"><?= $totalPages ? $page . ' of ' . $totalPages : 'No records' ?></p>
                </div>
            </div>

            <form method="GET" action="" id="filterForm" class="bg-white rounded-xl shadow p-4 mb-6 border border-gray-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <input name="search" value="<?= htmlspecialchars($search) ?>" type="text" placeholder="Search by name, program, mode, or status"
                           class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500 lg:col-span-2">

                    <select name="cohort" onchange="this.form.submit()" class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500 bg-purple-50 text-purple-900 font-medium">
                        <option value="">All Cohorts</option>
                        <?php foreach ($cohorts as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $c === $cohort ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="program" onchange="this.form.submit()" class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">All Programs</option>
                        <option value="MA" <?= $program === 'MA' ? 'selected' : '' ?>>MA</option>
                        <option value="PGDT" <?= $program === 'PGDT' ? 'selected' : '' ?>>PGDT</option>
                        <option value="B.Div" <?= $program === 'B.Div' ? 'selected' : '' ?>>B.Div</option>
                        <option value="Diploma" <?= $program === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                        <option value="Certificate" <?= $program === 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                    </select>
                    <select name="mode" onchange="this.form.submit()" class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">All Modes</option>
                        <option value="onsite" <?= $mode === 'onsite' ? 'selected' : '' ?>>Onsite</option>
                        <option value="online" <?= $mode === 'online' ? 'selected' : '' ?>>Online</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 mt-4">
                    <button type="button" onclick="bulkAction('delete')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium">Delete Selected</button>
                    <button type="button" onclick="bulkAction('onboard')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium">Onboard Selected</button>
                </div>
            </form>

            <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[760px]" id="paymentTable">
                        <thead class="text-gray-700 border-b bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 w-10">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                </th>
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Program</th>
                                <th class="py-3 px-4">Focus (MA)</th>
                                <th class="py-3 px-4">Mode</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Proof</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr class="border-b hover:bg-gray-50" data-cohort="<?= htmlspecialchars($p['cohort'] ?? '') ?>">
                                <td class="py-3 px-4">
                                    <input type="checkbox" name="payment_ids[]" value="<?= $p['id'] ?>" data-mode="<?= $p['mode_of_study'] ?>" class="payment-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-900 whitespace-nowrap"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars(strtoupper($p['program'])) ?></td>
                                <td class="py-3 px-4"><?= $p['program'] === 'MA' ? htmlspecialchars($p['ma_focus'] ?? '') : '-' ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars(ucfirst($p['mode_of_study'])) ?></td>
                                <td class="py-3 px-4 whitespace-nowrap">NGN <?= number_format($p['amount']) ?></td>
                                <td class="py-3 px-4">
                                    <?php if (!empty($p['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($p['file_path']) ?>" target="_blank" class="text-blue-600 underline">View</a>
                                    <?php else: ?>
                                        <span class="text-gray-400">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-white text-xs <?=
                                        $p['status'] === 'approved' ? 'bg-green-600' :
                                        ($p['status'] === 'rejected' ? 'bg-red-600' : 'bg-yellow-500') ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($p['onboarded']): ?>
                                        <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-bold border border-green-200 flex items-center gap-1 w-fit">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            Onboarded
                                        </span>
                                    <?php elseif ($p['status'] === 'pending'): ?>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <button onclick="handleAction('approve', <?= $p['id'] ?>)"
                                                    class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">Approve</button>
                                            <button onclick="handleAction('reject', <?= $p['id'] ?>)"
                                                    class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Reject</button>
                                        </div>
                                    <?php elseif ($p['status'] === 'approved' && $p['mode_of_study'] === 'online'): ?>
                                        <button onclick="bulkAction('onboard', <?= $p['id'] ?>)"
                                                class="bg-purple-600 text-white px-3 py-1 rounded text-xs hover:bg-purple-700">Onboard</button>
                                    <?php else: ?>
                                        <span class="bg-blue-600 text-white px-3 py-1 rounded text-xs">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$payments): ?>
                            <tr>
                                <td colspan="9" class="py-8 px-4 text-center text-gray-500">No tuition payments match the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= $totalRecords ? $offset + 1 : 0 ?></span>
                            to <span class="font-medium"><?= min($offset + $limit, $totalRecords) ?></span>
                            of <span class="font-medium"><?= $totalRecords ?></span> results
                        </p>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&mode=<?= urlencode($mode) ?>" class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&mode=<?= urlencode($mode) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?= $i === $page ? 'text-purple-700 bg-purple-50 z-10' : 'bg-white text-gray-700 hover:bg-gray-50' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&mode=<?= urlencode($mode) ?>" class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <div class="flex items-center justify-between sm:hidden w-full">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&mode=<?= urlencode($mode) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&cohort=<?= urlencode($cohort) ?>&program=<?= urlencode($program) ?>&mode=<?= urlencode($mode) ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
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

        function handleAction(type, id) {
            let title = type === 'approve' ? 'Approve Payment' : 'Reject Payment';
            let inputLabel = type === 'approve' ? 'Optional approval note:' : 'Reason for rejection:';
            let isRequired = type === 'reject';

            Swal.fire({
                title,
                input: 'textarea',
                inputLabel,
                inputPlaceholder: 'Write your message here... (optional)',
                inputAttributes: { required: isRequired },
                showCancelButton: true,
                confirmButtonText: type === 'approve' ? 'Approve' : 'Reject',
                confirmButtonColor: type === 'approve' ? '#16a34a' : '#dc2626',
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('ajax/tuition_action', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id,
                            action: type,
                            remarks: result.value || ''
                        })
                    }).then(res => res.json()).then(data => {
                        Swal.fire({
                            icon: data.status,
                            title: data.status === 'success' ? 'Done!' : 'Error',
                            text: data.message,
                            confirmButtonColor: '#6B21A8'
                        }).then(() => window.location.reload());
                    });
                }
            });
        }

        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.payment-checkbox').forEach(cb => cb.checked = this.checked);
        });

        function bulkAction(type, singleId = null) {
            const selected = singleId ? [singleId] : Array.from(document.querySelectorAll('.payment-checkbox:checked')).map(cb => cb.value);
            const modes = singleId ? [] : Array.from(document.querySelectorAll('.payment-checkbox:checked')).map(cb => cb.dataset.mode);

            if (selected.length === 0) {
                Swal.fire('Error', 'Please select at least one entry.', 'error');
                return;
            }

            if (type === 'onboard' && !singleId) {
                const hasOnsite = modes.some(m => m === 'onsite');
                if (hasOnsite) {
                    Swal.fire('Restricted', 'Onboarding is only available for Online students.', 'warning');
                    return;
                }
            }

            const title = type === 'delete' ? 'Delete Selected Entries?' : 'Onboard Selected Students?';
            const text = type === 'delete' ? 'This action is permanent!' : 'These students will be moved to the onboarding list.';

            Swal.fire({
                title,
                text,
                icon: type === 'delete' ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: type === 'delete' ? '#d33' : '#16a34a',
                confirmButtonText: 'Yes, proceed'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('ajax/tuition_bulk_action', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: selected, action: type })
                    }).then(res => res.json()).then(data => {
                        Swal.fire(data.status === 'success' ? 'Success' : 'Error', data.message, data.status)
                        .then(() => window.location.reload());
                    });
                }
            });
        }
    </script>
</body>
</html>
