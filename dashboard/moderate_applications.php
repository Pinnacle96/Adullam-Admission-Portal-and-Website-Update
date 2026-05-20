<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}

$normalizeText = function ($value) {
    $value = (string)$value;
    $value = str_replace("\xC2\xA0", ' ', $value);
    $value = str_replace("\xA0", ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim($value);
};

// Fetch Distinct Cohorts from Applications
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
$cohorts = array_values(array_unique(array_map($normalizeText, $cohorts)));

// Fetch Current Active Cohort (Default Selection)
$currentCohort = $normalizeText($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');

// Ensure Current Active Cohort is in the list
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Moderate Applications</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>
        <main class="flex-1 p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-purple-800 mb-4">🧾 Moderate Applications</h1>

            <!-- Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <input type="text" id="search" placeholder="Search name/email"
                    class="px-4 py-2 border rounded w-full focus:ring-purple-500" />
                
                <select id="cohort"
                    class="px-4 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500 bg-purple-50 text-purple-900 font-medium">
                    <option value="">All Cohorts</option>
                    <?php foreach ($cohorts as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $c === $currentCohort ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="program" class="px-4 py-2 border rounded w-full focus:ring-purple-500">
                    <option value="">All Programs</option>
                    <option value="MA">MA</option>
                    <option value="PGDT">PGDT</option>
                    <option value="B.Div">B.Div</option>
                    <option value="Diploma">Diploma</option>
                    <option value="Certificate">Certificate</option>
                </select>
                <select id="ma_focus" class="px-4 py-2 border rounded w-full hidden focus:ring-purple-500">
                    <option value="">MA Focus</option>
                    <option value="MA Christian Apologetics">MA Christian Apologetics</option>
                    <option value="MA Biblical Studies (OT/NT)">MA Biblical Studies (OT/NT)</option>
                </select>
            </div>

            <!-- Export -->
            <div class="flex justify-end gap-4 mb-4">
                <a id="exportExcel" href="#"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">⬇ Export Excel</a>
                <a id="exportPDF" href="#" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">📄
                    Export PDF</a>
            </div>

            <!-- Results -->
            <div id="results" class="overflow-x-auto bg-white shadow-md rounded-xl">
                <div class="text-center text-gray-500 py-6">Loading applications...</div>
            </div>
        </main>
    </div>

    <script>
        function fetchApplications() {
            const search = $('#search').val();
            const program = $('#program').val();
            const ma_focus = $('#ma_focus').val();
            const cohort = $('#cohort').val();

            $.get('ajax/fetch_applicants', {
                search: search,
                program: program,
                ma_focus: ma_focus,
                cohort: cohort,
                moderate: 'true'
            }, function(data) {
                $('#results').html(data);
            }).fail(function(xhr) {
                $('#results').html('<div class="text-red-600 p-4">Error loading data. Check console.</div>');
                console.error('Fetch error:', xhr.responseText);
            });
        }

        $('#search, #program, #ma_focus, #cohort').on('input change', function() {
            fetchApplications();
            updateExportLinks();
        });

        $('#program').on('change', function() {
            if ($(this).val() === 'MA') {
                $('#ma_focus').removeClass('hidden');
            } else {
                $('#ma_focus').val('').addClass('hidden');
            }
        });

        function updateExportLinks() {
            const search = encodeURIComponent($('#search').val());
            const program = encodeURIComponent($('#program').val());
            const ma_focus = encodeURIComponent($('#ma_focus').val());
            const cohort = encodeURIComponent($('#cohort').val());
            const query = `?search=${search}&program=${program}&ma_focus=${ma_focus}&cohort=${cohort}`;
            $('#exportExcel').attr('href', 'export_excel.php' + query);
            $('#exportPDF').attr('href', 'export_pdf.php' + query);
        }

        $(document).ready(() => {
            fetchApplications();
            updateExportLinks();
        });
    </script>
</body>

</html>
