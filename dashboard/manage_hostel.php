<?php
session_start();
// Include the database connection and mailer utility
require_once 'db.php';
require_once 'mailer.php';
require_once 'functions.php';

// --- Session and Role-based Access Control ---
// Check if user is logged in and has 'admin' or 'superadmin' role.
// If not, redirect to the login page (index.php is assumed to be your login or home page).
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index');
    exit;
}

$hostelRegistrationOpen = (getSettingValue($pdo, 'hostel_registration_open', '1') === '1');

// --- Initialize Filters ---
// Get filter parameters from the URL query string. Default to empty if not set.
$filters = [
    'status'   => $_GET['status'] ?? '',
    'semester' => $_GET['semester'] ?? '',
    'program'  => $_GET['program'] ?? '',
    'gender'   => $_GET['gender'] ?? '',
    'hostel'   => $_GET['hostel'] ?? '',
    'start'    => $_GET['start'] ?? '',
    'end'      => $_GET['end'] ?? ''
];

// --- Load Dropdown Options from Database ---
try {
    // Fetch distinct semesters for filter dropdown
    $semesters = $pdo->query("SELECT DISTINCT semester FROM hostel_registrations ORDER BY semester")->fetchAll(PDO::FETCH_COLUMN);
    // Fetch distinct programs for filter dropdown
    $programs  = $pdo->query("SELECT DISTINCT program FROM hostel_registrations ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
    // Fetch distinct non-empty hostels for filter dropdown
    $hostels   = $pdo->query("SELECT DISTINCT hostel FROM hostel_registrations WHERE hostel IS NOT NULL AND hostel != '' ORDER BY hostel")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Basic error handling for database issues during option loading
    error_log("Error loading filter options: " . $e->getMessage());
    $semesters = [];
    $programs = [];
    $hostels = [];
    // You might want to display a user-friendly error message here
}


// --- Build Dynamic SQL Query for Registrations ---
$query = "SELECT * FROM hostel_registrations WHERE 1=1"; // 1=1 is a trick to easily append WHERE clauses
$params = []; // Array to store parameters for the prepared statement

// Add conditions based on filters
if ($filters['status'] && in_array($filters['status'], ['approved', 'rejected', 'pending'])) {
    // Map status strings to integer values stored in the database
    $map = ['approved' => 1, 'rejected' => -1, 'pending' => 0];
    $query .= " AND is_approved = ?";
    $params[] = $map[$filters['status']];
}

if ($filters['semester']) {
    $query .= " AND semester = ?";
    $params[] = $filters['semester'];
}

if ($filters['program']) {
    $query .= " AND program = ?";
    $params[] = $filters['program'];
}

if ($filters['gender']) {
    $query .= " AND gender = ?";
    $params[] = $filters['gender'];
}

if ($filters['hostel']) {
    $query .= " AND hostel = ?";
    $params[] = $filters['hostel'];
}

// Filter by creation date range
if ($filters['start']) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $filters['start'];
}
if ($filters['end']) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $filters['end'];
}

// Order results by creation date in descending order
$query .= " ORDER BY created_at DESC";

// --- Execute Query and Fetch Registrations ---
$registrations = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
} catch (PDOException $e) {
    // Basic error handling for database query issues
    error_log("Error fetching registrations: " . $e->getMessage());
    // You might want to display a user-friendly error message here
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Hostel Registrations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <style>
        /* Custom styles for the table for better appearance */
        .table-container {
            border-radius: 0.75rem; /* rounded-xl */
            overflow: hidden; /* Ensures rounded corners apply to content */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); /* shadow */
        }
        /* Style for table rows on hover */
        tbody tr:hover {
            background-color: #f9fafb; /* hover:bg-gray-50 */
        }
        /* Style for pagination buttons when active */
        .pagination-btn.active {
            background-color: #6b46c1; /* bg-purple-700 */
            color: white;
            border-color: #6b46c1; /* border-purple-700 */
        }
        /* Base style for pagination buttons */
        .pagination-btn {
            padding: 0.25rem 0.75rem; /* px-3 py-1 */
            border-radius: 0.25rem; /* rounded */
            border: 1px solid #6b46c1; /* border-purple-700 */
            color: #6b46c1; /* text-purple-700 */
            font-size: 0.875rem; /* text-sm */
            transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }
        /* Hover style for pagination buttons */
        .pagination-btn:not(.active):hover {
            background-color: #ede9fe; /* hover:bg-purple-100 */
            color: #6b46c1; /* hover:text-purple-800 */
        }

        /* --- Responsive Table Styling (Mobile-first) --- */
        @media screen and (max-width: 767px) { /* Styles apply below md breakpoint */
            .table-container table {
                border: 0; /* Remove table borders */
                width: 100%;
            }

            .table-container thead {
                display: none; /* Hide table header on small screens */
            }

            .table-container table,
            .table-container tbody,
            .table-container tr {
                display: block; /* Make table, tbody, tr behave like blocks */
                width: 100%; /* Ensure they take full width */
            }

            .table-container tr {
                margin-bottom: 1rem; /* Add space between stacked rows */
                border: 1px solid #e5e7eb; /* Add a border around each stacked row */
                border-radius: 0.5rem; /* Slightly rounded corners for stacked rows */
                overflow: hidden; /* Ensure content stays within rounded corners */
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Subtle shadow */
                background-color: #ffffff; /* Ensure white background */
            }

            .table-container td {
                display: flex; /* Use flexbox for better alignment */
                justify-content: space-between; /* Space between label and value */
                text-align: right; /* Align content to the right */
                padding: 0.75rem 1rem; /* py-3 px-4 equivalent */
                border-bottom: 1px solid #e5e7eb; /* Separator for each data cell */
                position: relative; /* For pseudo-element positioning */
                word-wrap: break-word; /* Prevent long words from overflowing */
                min-height: 44px; /* Minimum touch target size */
            }

            .table-container td:last-child {
                border-bottom: 0; /* No border for the last cell in a row */
            }

            .table-container td::before {
                /* Display the data-label as the "header" for each cell */
                content: attr(data-label);
                position: relative;
                left: 0;
                font-weight: bold; /* Make the label bold */
                text-align: left; /* Align label to the left */
                color: #4b5563; /* text-gray-700 */
                margin-right: 1rem;
                flex: 1;
            }

            /* Specific styling for the Action column on mobile */
            .table-container td.action-column {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                padding: 1rem;
            }
            
            .table-container td.action-column::before {
                display: none;
            }
            
            .table-container td.action-column button {
                width: 100%;
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            
            /* Adjust status badge for mobile */
            .table-container td .badge {
                display: inline-flex;
                justify-content: center;
                width: 100%;
            }
        }

        /* Responsive filter form adjustments */
        @media screen and (max-width: 640px) {
            .filter-form {
                grid-template-columns: 1fr !important;
            }
            
            .date-filters {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .filter-button {
                width: 100%;
            }
        }
        
        /* Adjust sidebar and main content layout */
        @media screen and (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .summary-grid {
                grid-template-columns: 1fr 1fr !important;
            }
        }
        
        @media screen and (max-width: 480px) {
            .summary-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
<?php include 'components/navbar.php'; ?>
<div class="flex flex-col md:flex-row">
    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 main-content">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-purple-800 mb-4 sm:mb-6 flex items-center gap-2">
            <span role="img" aria-label="hostel">🏨</span> Hostel Registration Management
        </h1>

        <div class="mb-6 bg-white p-4 sm:p-5 rounded-lg shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-sm sm:text-base">
                <span class="font-semibold text-gray-800">Hostel Registration:</span>
                <?php if ($hostelRegistrationOpen): ?>
                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">OPEN</span>
                <?php else: ?>
                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">CLOSED</span>
                <?php endif; ?>
            </div>
            <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                <button type="button" onclick="toggleHostelRegistration(<?= $hostelRegistrationOpen ? '0' : '1' ?>)"
                    class="w-full sm:w-auto px-4 py-2 rounded-md text-sm font-semibold <?= $hostelRegistrationOpen ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white' ?>">
                    <?= $hostelRegistrationOpen ? 'Lock Registration' : 'Open Registration' ?>
                </button>
                <button type="button" onclick="clearHostelSemester()"
                    class="w-full sm:w-auto px-4 py-2 rounded-md text-sm font-semibold bg-gray-900 text-white hover:bg-gray-800">
                    Clear Semester
                </button>
            </div>
        </div>

        <div class="max-w-screen-xl mx-auto">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 bg-white p-4 sm:p-6 rounded-lg shadow-md">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>All Status</option>
                        <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Semester</label>
                    <select name="semester" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 focus:ring-purple-500 focus:border-purple-500">
                        <option value="" <?= $filters['semester'] === '' ? 'selected' : '' ?>>All Semesters</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= htmlspecialchars($sem) ?>" <?= $filters['semester'] === $sem ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Program</label>
                    <select name="program" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 focus:ring-purple-500 focus:border-purple-500">
                        <option value="" <?= $filters['program'] === '' ? 'selected' : '' ?>>All Programs</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?= htmlspecialchars($prog) ?>" <?= $filters['program'] === $prog ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prog) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="" <?= $filters['gender'] === '' ? 'selected' : '' ?>>All Genders</option>
                        <option value="Male" <?= $filters['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $filters['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hostel</label>
                    <select name="hostel" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="" <?= $filters['hostel'] === '' ? 'selected' : '' ?>>All Hostels</option>
                        <?php foreach ($hostels as $h): ?>
                            <option value="<?= htmlspecialchars($h) ?>" <?= $filters['hostel'] === $h ? 'selected' : '' ?>>
                                <?= htmlspecialchars($h) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-span-full flex justify-end gap-3 mt-2">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 text-sm font-medium transition">
                        Apply Filters
                    </button>
                    <a href="?" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 text-sm font-medium transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <a href="export_hostel_pdf?<?= http_build_query($filters) ?>"
               class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 text-sm font-medium transition duration-150 ease-in-out w-full sm:w-auto justify-center">
                <span role="img" aria-label="export">📄</span> Export Filtered PDF
            </a>
            
            <button id="bulkDeleteBtn"
                    class="hidden bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-sm font-medium transition duration-150 ease-in-out w-full sm:w-auto justify-center">
                Delete Selected
            </button>
            
            <div class="text-sm text-gray-600 w-full sm:w-auto text-center sm:text-right">
                Showing <?= count($registrations) ?> record(s)
            </div>
        </div>

        <?php
        $totalPaid = 0;
        $count = ['approved' => 0, 'rejected' => 0, 'pending' => 0];
        foreach ($registrations as $r) {
            $totalPaid += (float)$r['amount_paid'];
            $count['approved'] += $r['is_approved'] == 1 ? 1 : 0;
            $count['rejected'] += $r['is_approved'] == -1 ? 1 : 0;
            $count['pending'] += $r['is_approved'] == 0 ? 1 : 0;
        }
        ?>
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm summary-grid">
            <div class="p-3 bg-purple-50 rounded-md flex flex-col">
                <span class="text-gray-600">Total Applicants</span>
                <span class="font-semibold text-purple-800 text-lg"><?= count($registrations) ?></span>
            </div>
            <div class="p-3 bg-green-50 rounded-md flex flex-col">
                <span class="text-gray-600">Approved</span>
                <span class="font-semibold text-green-800 text-lg"><?= $count['approved'] ?></span>
            </div>
            <div class="p-3 bg-red-50 rounded-md flex flex-col">
                <span class="text-gray-600">Rejected</span>
                <span class="font-semibold text-red-800 text-lg"><?= $count['rejected'] ?></span>
            </div>
            <div class="p-3 bg-yellow-50 rounded-md flex flex-col">
                <span class="text-gray-600">Pending</span>
                <span class="font-semibold text-yellow-800 text-lg"><?= $count['pending'] ?></span>
            </div>
            <div class="p-3 bg-blue-50 rounded-md flex flex-col lg:col-span-4">
                <span class="text-gray-600">Total Amount Paid</span>
                <span class="font-semibold text-blue-800 text-lg">₦<?= number_format($totalPaid, 0) ?></span>
            </div>
        </div>

        <div class="table-container bg-white overflow-x-auto">
            <table class="min-w-full text-sm text-left divide-y divide-gray-200" id="regTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-2 text-center">
                            <input type="checkbox" id="selectAllCheckboxes" class="form-checkbox h-4 w-4 text-purple-600 transition duration-150 ease-in-out" />
                        </th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="7" class="py-4 px-6 text-center text-gray-500" data-label="No Data">No registrations found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $r):
                        // Determine status string and badge color based on is_approved value
                        $status = '';
                        $badge = '';
                        if ($r['is_approved'] == 1) {
                            $status = 'approved';
                            $badge = 'bg-green-600';
                        } elseif ($r['is_approved'] == -1) {
                            $status = 'rejected';
                            $badge = 'bg-red-600';
                        } else {
                            $status = 'pending';
                            $badge = 'bg-yellow-500';
                        }
                    ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-2 text-center">
                                <input type="checkbox" name="selected_ids[]" value="<?= (int)$r['id'] ?>" class="row-checkbox form-checkbox h-4 w-4 text-purple-600 transition duration-150 ease-in-out" />
                            </td>
                            <td class="py-3 px-4 font-medium text-gray-900" data-label="Full Name"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td class="py-3 px-4 text-gray-700" data-label="Email"><?= htmlspecialchars($r['email']) ?></td>
                            <td class="py-3 px-4 text-gray-700" data-label="Program"><?= htmlspecialchars($r['program']) ?></td>
                            <td class="py-3 px-4 text-gray-700" data-label="Semester"><?= htmlspecialchars($r['semester']) ?></td>
                            <td class="py-3 px-4" data-label="Status">
                                <span class="px-2 py-1 rounded-full text-white text-xs font-semibold <?= $badge ?> badge">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 action-column" data-label="Action">
                                <div class="flex flex-col sm:flex-row gap-2 justify-center items-center">
                                    <button onclick="viewDetails(<?= (int)$r['id'] ?>)" class="bg-blue-600 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-700 transition duration-150 ease-in-out w-full sm:w-auto">View</button>
                                    <?php if ($r['is_approved'] == 0): // Only show Approve/Reject if pending ?>
                                        <button onclick="processApproval('approve', <?= (int)$r['id'] ?>)" class="bg-green-600 text-white px-3 py-1 rounded-md text-xs hover:bg-green-700 transition duration-150 ease-in-out w-full sm:w-auto">Approve</button>
                                        <button onclick="processApproval('reject', <?= (int)$r['id'] ?>)" class="bg-red-600 text-white px-3 py-1 rounded-md text-xs hover:bg-red-700 transition duration-150 ease-in-out w-full sm:w-auto">Reject</button>
                                    <?php endif; ?>
                                    <button onclick="deleteRegistration(<?= (int)$r['id'] ?>)" class="bg-gray-700 text-white px-3 py-1 rounded-md text-xs hover:bg-gray-800 transition duration-150 ease-in-out w-full sm:w-auto">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="pagination" class="mt-6 flex flex-wrap justify-center gap-2"></div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('regTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const rowsPerPage = 10;
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    const paginationContainer = document.getElementById('pagination');

    // Caches the current page number in a variable.
    let currentPage = 1;

    // A function to display a specific page of rows.
    function showPage(page) {
        currentPage = page;
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
        buildPagination();
    }

    // This function builds the pagination buttons dynamically.
    // It only displays a limited set of pages around the current page.
    function buildPagination() {
        paginationContainer.innerHTML = ''; // Clear existing buttons
        if (totalPages <= 1) return;

        // Create Previous button
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '&laquo;';
        prevBtn.className = 'pagination-btn';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                showPage(currentPage - 1);
            }
        });
        paginationContainer.appendChild(prevBtn);

        // Always show the first page button
        if (totalPages > 0) {
            createPageButton(1);
        }

        // Add ellipsis if needed (e.g., if current page is far from the start)
        if (currentPage > 3 && totalPages > 5) {
            addEllipsis();
        }

        // Determine the range of pages to display around the current page
        let startPage = Math.max(2, currentPage - 1);
        let endPage = Math.min(totalPages - 1, currentPage + 1);

        // Adjust the range if we are close to the beginning or end
        if (currentPage <= 3) {
            endPage = Math.min(totalPages - 1, 4);
        }
        if (currentPage >= totalPages - 2) {
            startPage = Math.max(2, totalPages - 3);
        }

        for (let i = startPage; i <= endPage; i++) {
            createPageButton(i);
        }

        // Add ellipsis if needed (e.g., if current page is far from the end)
        if (currentPage < totalPages - 2 && totalPages > 5) {
            addEllipsis();
        }
        
        // Always show the last page button if there's more than one page
        if (totalPages > 1) {
            createPageButton(totalPages);
        }

        // Create Next button
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '&raquo;';
        nextBtn.className = 'pagination-btn';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                showPage(currentPage + 1);
            }
        });
        paginationContainer.appendChild(nextBtn);
    }
    
    // Helper function to create a single page button
    function createPageButton(pageNumber) {
        const btn = document.createElement('button');
        btn.textContent = pageNumber;
        btn.className = 'pagination-btn';
        if (pageNumber === currentPage) {
            btn.classList.add('active');
        }
        btn.addEventListener('click', () => showPage(pageNumber));
        paginationContainer.appendChild(btn);
    }

    // Helper function to create an ellipsis
    function addEllipsis() {
        const span = document.createElement('span');
        span.textContent = '...';
        span.className = 'px-2 py-1 text-gray-500 text-sm';
        paginationContainer.appendChild(span);
    }

    // Initial load
    showPage(1);

    // --- Bulk Delete Logic ---
    const selectAllCheckbox = document.getElementById('selectAllCheckboxes');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove('hidden');
        } else {
            bulkDeleteBtn.classList.add('hidden');
        }
    }

    selectAllCheckbox.addEventListener('change', () => {
        rowCheckboxes.forEach(cb => {
            cb.checked = selectAllCheckbox.checked;
        });
        updateBulkDeleteButton();
    });

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateBulkDeleteButton();
            // If any checkbox is unchecked, uncheck the "select all" one
            if (!cb.checked) {
                selectAllCheckbox.checked = false;
            }
            // If all checkboxes are checked, check the "select all" one
            if (document.querySelectorAll('.row-checkbox:checked').length === rowCheckboxes.length) {
                selectAllCheckbox.checked = true;
            }
        });
    });
/**
 * Fetches and displays detailed registration information in a SweetAlert modal.
 * This function uses a GET request, as `get_registration_details.php` expects a URL parameter.
 * @param {number} id The ID of the registration to view.
 */
window.viewDetails = async function(id) {
    try {
        // Corrected: Use a GET request with the ID in the URL.
        const response = await fetch(`ajax/get_registration_details?id=${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();

        if (result.success) {
            const r = result.data;
            
            // Construct the HTML for the modal using the fetched data
            const passportLink = r.passport_file ? `<a href="${r.passport_file}" target="_blank" class="text-blue-600 hover:underline">View Passport</a>` : 'Not uploaded';
            const paymentProofLink = r.payment_proof_file ? `<a href="${r.payment_proof_file}" target="_blank" class="text-blue-600 hover:underline">View Proof</a>` : 'Not uploaded';
            
            // Define the status badge color
            let statusBadge = '';
            if (r.is_approved == 1) {
                statusBadge = '<span class="px-2 py-1 rounded-full text-white text-xs font-semibold bg-green-600">Approved</span>';
            } else if (r.is_approved == -1) {
                statusBadge = '<span class="px-2 py-1 rounded-full text-white text-xs font-semibold bg-red-600">Rejected</span>';
            } else {
                statusBadge = '<span class="px-2 py-1 rounded-full text-white text-xs font-semibold bg-yellow-500">Pending</span>';
            }

            const detailsHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-left max-h-[65vh] overflow-y-auto p-2">
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Full Name:</p>
                        <p class="text-gray-900">${r.full_name}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Email:</p>
                        <p class="text-gray-900">${r.email}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Phone:</p>
                        <p class="text-gray-900">${r.phone || 'N/A'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Gender:</p>
                        <p class="text-gray-900">${r.gender}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">DOB:</p>
                        <p class="text-gray-900">${r.dob}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Age:</p>
                        <p class="text-gray-900">${r.age}</p>
                    </div>
                      <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Nationality:</p>
                        <p class="text-gray-900">${r.nationality}</p>
                    </div>
                     <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">State of Origin:</p>
                        <p class="text-gray-900">${r.state_of_origin}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Program:</p>
                        <p class="text-gray-900">${r.program}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Semester:</p>
                        <p class="text-gray-900">${r.semester}</p>
                    </div>
                     <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Student Type:</p>
                        <p class="text-gray-900">${r.student_type}</p>
                    </div>
                  
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Hostel Applied:</p>
                        <p class="text-gray-900">${r.hostel || 'N/A'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Room Type:</p>
                        <p class="text-gray-900">${r.room_type || 'N/A'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Amount Paid:</p>
                        <p class="text-gray-900">₦${(parseFloat(r.amount_paid)).toLocaleString('en-US')}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Payment Reference:</p>
                        <p class="text-gray-900">${r.payment_reference || 'N/A'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded col-span-full">
                        <p class="font-semibold text-gray-700">Status:</p>
                        <p class="text-gray-900">${statusBadge}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Passport:</p>
                        <p class="text-gray-900">${passportLink}</p>
                    </div>
                     
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Amount Paid:</p>
                        <p class="text-gray-900">₦${r.amount_paid}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Payment Date:</p>
                        <p class="text-gray-900">${r.payment_date}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Payment Proof:</p>
                        <p class="text-gray-900">${paymentProofLink}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Residential Address</p>
                        <p class="text-gray-900">${r.res_address}, ${r.res_city}, ${r.res_state}, ${r.res_country}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Permanent Address:</p>
                        <p class="text-gray-900">${r.perm_address}, ${r.perm_city}, ${r.perm_state}, ${r.perm_country}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Guardian:</p>
                        <p class="text-gray-900">${r.guardian_name} (${r.guardian_relation}) - ${r.guardian_contact}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Allergies/Illness:</p>
                        <p class="text-gray-900">${r.allergies || 'None'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Mattress Present:</p>
                        <p class="text-gray-900">${r.mattress_present === 'Yes' ? 'Yes' : 'No'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700">Born Again:</p>
                        <p class="text-gray-900"> ${Number(r.declaration_agreed) === 1 ? 'Yes' : 'No'}</p>
                    </div>
                </div>
            `;
            
               Swal.fire({
                title: 'Registration Details',
                html: detailsHTML,
                width: '60em',
                confirmButtonText: 'Close',
                confirmButtonColor: '#6B21A8',
                showCloseButton: true
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Unable to load details.',
                confirmButtonColor: '#6B21A8'
            });
        }
    } catch (error) {
        console.error('Fetch error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not connect to the server or parse the response. Please check your network and the server logs.',
            confirmButtonColor: '#6B21A8'
        });
    }
};

    bulkDeleteBtn.addEventListener('click', () => {
        const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} registration(s). This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#4b5563',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send the selected IDs to the bulk delete endpoint
                fetch('ajax/bulk_delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: selectedIds })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        icon: data.status,
                        title: data.status === 'success' ? 'Deleted!' : 'Error',
                        text: data.message,
                        confirmButtonColor: '#6B21A8'
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred while deleting the registrations.', 'error');
                });
            }
        });
    });
    
    window.processApproval = function(action, id) {
    Swal.fire({
        title: action === 'approve' ? 'Approve Registration?' : 'Reject Registration?',
        input: 'textarea',
        inputLabel: 'Approval Note (optional)',
        inputPlaceholder: 'Enter a note...',
        showCancelButton: true,
        confirmButtonColor: action === 'approve' ? '#16a34a' : '#dc2626',
        confirmButtonText: action === 'approve' ? 'Approve' : 'Reject'
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch('ajax/hostel_approval_action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                id: id,
                note: result.value || ''
            })
        })
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                icon: data.status,
                title: data.status === 'success' ? 'Success' : 'Error',
                text: data.message,
                confirmButtonColor: '#6B21A8'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.reload();
                }
            });
        })
        .catch(err => {
            console.error(err);
            Swal.fire(
                'Error',
                'Network or server error occurred.',
                'error'
            );
        });
    });
};

    // Individual delete function remains the same
    window.deleteRegistration = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete this registration. This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#4b5563',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/delete_registration', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: data.status === 'success' ? 'Deleted!' : 'Error!',
                        text: data.message,
                        icon: data.status,
                        confirmButtonColor: '#6B21A8'
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire('Error', 'Something went wrong.', 'error');
                });
            }
        });
    };
});

function toggleHostelRegistration(nextValue) {
    const actionText = nextValue === 1 || nextValue === '1' ? 'open' : 'lock';
    Swal.fire({
        title: actionText === 'open' ? 'Open Hostel Registration?' : 'Lock Hostel Registration?',
        text: actionText === 'open'
            ? 'Students will be able to register for hostel again.'
            : 'Students will not be able to register for hostel until you reopen it.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionText === 'open' ? '#16a34a' : '#dc2626',
        confirmButtonText: actionText === 'open' ? 'Yes, Open' : 'Yes, Lock'
    }).then((result) => {
        if (!result.isConfirmed) return;
        fetch('ajax/hostel_toggle_registration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ value: String(nextValue) })
        })
        .then(r => r.json())
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Done' : 'Error',
                text: data.message,
                confirmButtonColor: '#6B21A8'
            }).then(() => {
                if (data.success) window.location.reload();
            });
        })
        .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
    });
}

function clearHostelSemester() {
    Swal.fire({
        title: 'Clear Hostel For Semester',
        text: 'This will archive all hostel registrations and allocations for the selected semester. Students will need to register again.',
        input: 'select',
        inputOptions: {
            'First Semester': 'First Semester',
            'Second Semester': 'Second Semester',
            '__all__': 'All Semesters'
        },
        inputValue: 'First Semester',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Archive & Clear',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        const semester = result.value;
        Swal.fire({
            title: 'Confirm Clearing',
            input: 'text',
            inputLabel: 'Type ARCHIVE to confirm',
            inputPlaceholder: 'ARCHIVE',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, Archive'
        }).then((confirmRes) => {
            if (!confirmRes.isConfirmed) return;
            if ((confirmRes.value || '').trim().toUpperCase() !== 'ARCHIVE') {
                Swal.fire('Cancelled', 'Confirmation text did not match.', 'info');
                return;
            }
            fetch('ajax/clear_hostel_semester.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ semester })
            })
            .then(r => r.json())
            .then(data => {
                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Cleared' : 'Error',
                    text: data.message,
                    confirmButtonColor: '#6B21A8'
                }).then(() => {
                    if (data.success) window.location.reload();
                });
            })
            .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
        });
    });
}
</script>
</body>
</html>
