<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header("Location: index");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'];

// Fetch Current Active Cohort (Default Selection)
$defaultCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');

// Capture GET parameters for pre-filtering
$selectedCohort = $_GET['cohort'] ?? $defaultCohort;
$selectedStatus = $_GET['status'] ?? '';
$selectedProgram = $_GET['program'] ?? '';
$selectedMode = $_GET['mode_of_study'] ?? '';
$selectedSearch = $_GET['search'] ?? '';

// Fetch Distinct Cohorts from Applications
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);

// Ensure Current Active Cohort is in the list (if not already present)
if (!in_array($defaultCohort, $cohorts)) {
    array_unshift($cohorts, $defaultCohort);
}
rsort($cohorts);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Applicants List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
            <h1 class="text-xl sm:text-2xl font-bold text-purple-800 mb-4 sm:mb-6">
                Applicants List (<?= ucfirst($role) ?> View)
            </h1>

            <!-- Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <input type="text" id="search" placeholder="Search by name or email" value="<?= htmlspecialchars($selectedSearch) ?>"
                    class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                
                <select id="cohort"
                    class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500 bg-purple-50 text-purple-900 font-medium">
                    <option value="">All Cohorts</option>
                    <?php foreach ($cohorts as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $c === $selectedCohort ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="program"
                    class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Filter by Program</option>
                    <option value="MA" <?= $selectedProgram === 'MA' ? 'selected' : '' ?>>MA</option>
                    <option value="PGDT" <?= $selectedProgram === 'PGDT' ? 'selected' : '' ?>>PGDT</option>
                    <option value="B.Div" <?= $selectedProgram === 'B.Div' ? 'selected' : '' ?>>B.Div</option>
                    <option value="Diploma" <?= $selectedProgram === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                    <option value="Certificate" <?= $selectedProgram === 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                </select>

                <select id="mode_of_study"
                    class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Filter by Mode</option>
                    <option value="online" <?= $selectedMode === 'online' ? 'selected' : '' ?>>Online</option>
                    <option value="onsite" <?= $selectedMode === 'onsite' ? 'selected' : '' ?>>Onsite</option>
                </select>

                <select id="status"
                    class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Filter by Status</option>
                    <option value="submitted" <?= $selectedStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="admitted" <?= $selectedStatus === 'admitted' ? 'selected' : '' ?>>Admitted</option>
                    <option value="rejected" <?= $selectedStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="in_progress" <?= $selectedStatus === 'in_progress' ? 'selected' : '' ?>>Not Started</option>
                     <option value="draft" <?= $selectedStatus === 'draft' ? 'selected' : '' ?>>In Progress</option>
                </select>

                <select id="ma_focus"
                    class="px-4 py-2 border rounded w-full hidden focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Filter by MA Focus</option>
                    <option value="MA Christian Apologetics">MA Christian Apologetics</option>
                    <option value="MA Biblical Studies (OT/NT)">MA Biblical Studies (OT/NT)</option>
                </select>
            </div>

            <!-- Date Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div class="flex flex-col">
                    <label class="text-xs text-gray-500 mb-1">From Date</label>
                    <input type="date" id="start_date" class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-xs text-gray-500 mb-1">To Date</label>
                    <input type="date" id="end_date" class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="flex items-end">
                     <button onclick="fetchApplicants()" class="px-4 py-2 bg-purple-700 text-white rounded w-full hover:bg-purple-800">Apply Filter</button>
                </div>
            </div>

            <!-- Export -->
            <div class="flex justify-end gap-4 mb-4">
                <a id="exportExcel" href="#"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">⬇️ Export to Excel</a>
                <a id="exportPDF" href="#"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">📄 Export to PDF</a>
            </div>

            <!-- Table Results -->
            <div id="results">
                <!-- AJAX table here -->
            </div>
        </main>
    </div>

   <script>
function fetchApplicants(page = 1) {
    const search   = $('#search').val();
    const program  = $('#program').val();
    const mode_of_study = $('#mode_of_study').val();
    const status   = $('#status').val();
    const ma_focus = $('#ma_focus').val();
    const start_date = $('#start_date').val();
    const end_date = $('#end_date').val();
    const cohort   = $('#cohort').val();

    $.get('ajax/fetch_applicants.php', {
        search: search,
        program: program,
        mode_of_study: mode_of_study,
        status: status,
        ma_focus: ma_focus,
        start_date: start_date,
        end_date: end_date,
        cohort: cohort,
        page: page
    }, function (data) {
        $('#results').html(data);
    });
}

function updateExportLinks() {
    const search   = encodeURIComponent($('#search').val());
    const program  = encodeURIComponent($('#program').val());
    const mode_of_study = encodeURIComponent($('#mode_of_study').val());
    const status   = encodeURIComponent($('#status').val());
    const ma_focus = encodeURIComponent($('#ma_focus').val());
    const start_date = encodeURIComponent($('#start_date').val());
    const end_date = encodeURIComponent($('#end_date').val());
    const cohort   = encodeURIComponent($('#cohort').val());

    const query = `?search=${search}&program=${program}&mode_of_study=${mode_of_study}&status=${status}&ma_focus=${ma_focus}&start_date=${start_date}&end_date=${end_date}&cohort=${cohort}`;
    $('#exportExcel').attr('href', 'export_excel' + query);
    $('#exportPDF').attr('href', 'export_pdf' + query);
}

$(document).ready(function () {
    // Initial load
    fetchApplicants();
    updateExportLinks();

    // Filters
    $('#search, #program, #mode_of_study, #status, #ma_focus, #start_date, #end_date, #cohort').on('input change', function () {
        fetchApplicants();
        updateExportLinks();
    });

    // MA focus toggle
    $('#program').on('change', function () {
        if ($(this).val() === 'MA') {
            $('#ma_focus').removeClass('hidden');
        } else {
            $('#ma_focus').val('').addClass('hidden');
        }
    });

    // ✅ Pagination click handler (delegated, works on dynamically loaded buttons)
    $(document).on('click', '.pagination-btn', function () {
        const page = $(this).data('page');
        fetchApplicants(page);
    });
});
</script>

</body>

</html>
