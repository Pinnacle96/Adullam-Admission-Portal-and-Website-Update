<?php
session_start();
require 'db.php';
require 'mailer.php';

// Check for authentication and role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Admin';

// --- Pagination and Filtering Logic ---

// Pagination settings
$records_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$pagination_range = 3; // Number of pages to show around the current page

// Build the dynamic WHERE clause for filtering
$where_clauses = [];
$params = [];

// Search filter
if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR d.program LIKE ? OR d.mode_of_study LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

// Program filter
if (!empty($_GET['program'])) {
    $where_clauses[] = "d.program = ?";
    $params[] = $_GET['program'];
}

// Mode of study filter
if (!empty($_GET['mode'])) {
    $where_clauses[] = "d.mode_of_study = ?";
    $params[] = $_GET['mode'];
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// Count total records with filters for pagination
try {
    $total_records_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tuition_payment tp
        JOIN users u ON tp.user_id = u.id
        JOIN application_details d ON tp.user_id = d.user_id
        " . $where_sql
    );
    $total_records_stmt->execute($params);
    $total_records = $total_records_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $total_records = 0;
    $total_pages = 1;
}

// Fetch tuition payment records with pagination and filters
try {
    $stmt = $pdo->prepare("
        SELECT tp.*, u.first_name, u.last_name, d.program, d.mode_of_study, d.ma_focus
        FROM tuition_payment tp
        JOIN users u ON tp.user_id = u.id
        JOIN application_details d ON tp.user_id = d.user_id
        " . $where_sql . "
        ORDER BY tp.updated_at DESC
        LIMIT ? OFFSET ?
    ");
    
    // Add pagination parameters to the end of the existing parameters
    $final_params = array_merge($params, [$records_per_page, $offset]);
    $stmt->execute($final_params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $payments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tuition Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 text-gray-800 min-h-screen font-sans">
<?php include 'components/navbar.php'; ?>
    <div class="flex flex-col md:flex-row">
        <?php include 'components/sidebar.php'; ?>
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-full">
            <h1 class="text-xl sm:text-2xl font-bold text-purple-800 mb-6">🎓 Tuition Payment Management</h1>

            <form method="GET" action="manage_tuition_payments.php" id="filterForm">
                <div class="flex flex-col gap-4 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <input id="searchInput" name="search" type="text" placeholder="Search by name, program, mode, or status"
                                class="w-full sm:w-64 px-4 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-600"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        
                        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                            <select id="filterProgram" name="program" class="w-full sm:w-auto px-4 py-2 border rounded-lg shadow-sm">
                                <option value="">All Programs</option>
                                <option value="MA" <?= (($_GET['program'] ?? '') === 'MA') ? 'selected' : '' ?>>MA</option>
                                <option value="PGDT" <?= (($_GET['program'] ?? '') === 'PGDT') ? 'selected' : '' ?>>PGDT</option>
                                <option value="B.Div" <?= (($_GET['program'] ?? '') === 'B.Div') ? 'selected' : '' ?>>B.Div</option>
                                <option value="Diploma" <?= (($_GET['program'] ?? '') === 'Diploma') ? 'selected' : '' ?>>Diploma</option>
                                <option value="Certificate" <?= (($_GET['program'] ?? '') === 'Certificate') ? 'selected' : '' ?>>Certificate</option>
                            </select>
                            <select id="filterMode" name="mode" class="w-full sm:w-auto px-4 py-2 border rounded-lg shadow-sm">
                                <option value="">All Modes</option>
                                <option value="onsite" <?= (($_GET['mode'] ?? '') === 'onsite') ? 'selected' : '' ?>>Onsite</option>
                                <option value="online" <?= (($_GET['mode'] ?? '') === 'online') ? 'selected' : '' ?>>Online</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[640px]" id="paymentTable">
                        <thead class="text-gray-700 border-b bg-gray-50">
                            <tr>
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
                        <?php if (count($payments) > 0): ?>
                            <?php foreach ($payments as $p): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium text-gray-900 whitespace-nowrap"> <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?> </td>
                                    <td class="py-3 px-4"> <?= htmlspecialchars(strtoupper($p['program'])) ?> </td>
                                    <td class="py-3 px-4"> <?= $p['program'] === 'MA' ? htmlspecialchars($p['ma_focus']) : '-' ?> </td>
                                    <td class="py-3 px-4"> <?= htmlspecialchars(ucfirst($p['mode_of_study'])) ?> </td>
                                    <td class="py-3 px-4 whitespace-nowrap">₦<?= number_format($p['amount']) ?></td>
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
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <div class="flex flex-col sm:flex-row gap-2">
                                                <button onclick="handleAction('approve', <?= $p['id'] ?>)"
                                                        class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">Approve</button>
                                                <button onclick="handleAction('reject', <?= $p['id'] ?>)"
                                                        class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Reject</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-gray-500">No tuition payments found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center items-center mt-6">
                <?php if ($total_pages > 1): ?>
                    <?php 
                        // Fix to remove 'page' from the query string
                        $filter_params = $_GET;
                        unset($filter_params['page']);
                        $query_string = http_build_query($filter_params);
                        
                        $start_page = max(1, $current_page - $pagination_range);
                        $end_page = min($total_pages, $current_page + $pagination_range);
                    ?>
                    
                    <a href="?page=1<?= ($query_string ? '&' . $query_string : '') ?>" 
                       class="px-4 py-2 mx-1 border rounded-lg bg-white shadow-sm text-gray-600 hover:bg-gray-100 <?= $current_page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">First</a>
                    <a href="?page=<?= max(1, $current_page - 1) . ($query_string ? '&' . $query_string : '') ?>" 
                       class="px-4 py-2 mx-1 border rounded-lg bg-white shadow-sm text-gray-600 hover:bg-gray-100 <?= $current_page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">Previous</a>

                    <?php if ($start_page > 1): ?>
                        <span class="px-2 py-2 mx-1 text-gray-600">...</span>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?= $i . ($query_string ? '&' . $query_string : '') ?>" 
                           class="px-4 py-2 mx-1 border rounded-lg <?= $i === $current_page ? 'bg-purple-600 text-white shadow' : 'bg-white shadow-sm text-gray-600 hover:bg-gray-100' ?>">
                           <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <span class="px-2 py-2 mx-1 text-gray-600">...</span>
                    <?php endif; ?>

                    <a href="?page=<?= min($total_pages, $current_page + 1) . ($query_string ? '&' . $query_string : '') ?>" 
                       class="px-4 py-2 mx-1 border rounded-lg bg-white shadow-sm text-gray-600 hover:bg-gray-100 <?= $current_page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?>">Next</a>
                    <a href="?page=<?= $total_pages . ($query_string ? '&' . $query_string : '') ?>" 
                       class="px-4 py-2 mx-1 border rounded-lg bg-white shadow-sm text-gray-600 hover:bg-gray-100 <?= $current_page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?>">Last</a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const programFilter = document.getElementById('filterProgram');
        const modeFilter = document.getElementById('filterMode');
        
        let typingTimer;
        const doneTypingInterval = 500; // milliseconds

        function submitForm() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                filterForm.submit();
            }, doneTypingInterval);
        }

        searchInput.addEventListener('input', submitForm);
        programFilter.addEventListener('change', submitForm);
        modeFilter.addEventListener('change', submitForm);

        // handleAction function remains the same as it's for AJAX calls
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
                    fetch('ajax/tuition_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id,
                            action: type,
                            note: result.value || ''
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
    </script>
</body>
</html>