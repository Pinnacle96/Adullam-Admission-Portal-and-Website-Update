<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}

$currentCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);

$selectedCohort = trim($_GET['cohort'] ?? $currentCohort);
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-purple-800">Analytics Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1">Track application activity and applicant demographics by cohort.</p>
                </div>

                <form method="GET" id="analyticsFilters" class="bg-white border border-gray-100 rounded-xl shadow-sm p-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <select name="cohort" id="cohort" class="px-3 py-2 border rounded-lg bg-purple-50 text-purple-900 font-medium focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <?php foreach ($cohorts as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $c === $selectedCohort ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="start_date" id="startDate" value="<?= htmlspecialchars($startDate) ?>" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <input type="date" name="end_date" id="endDate" value="<?= htmlspecialchars($endDate) ?>" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <button type="submit" class="px-4 py-2 bg-purple-700 text-white rounded-lg hover:bg-purple-800 font-medium">Apply</button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" id="summaryCards">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Selected Cohort</p>
                    <p class="text-lg font-bold text-gray-900"><?= htmlspecialchars($selectedCohort) ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Status Records</p>
                    <p class="text-lg font-bold text-purple-800" id="statusTotal">Loading...</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Date Window</p>
                    <p class="text-lg font-bold text-gray-900"><?= ($startDate && $endDate) ? htmlspecialchars("$startDate to $endDate") : 'All dates' ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold mb-1 text-gray-900">Monthly Application Trend</h2>
                    <p class="text-xs text-gray-500 mb-4">Submitted applications in the selected cohort.</p>
                    <div class="overflow-x-auto">
                        <canvas id="trendChart" height="220"></canvas>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold mb-1 text-gray-900">Application Status</h2>
                    <p class="text-xs text-gray-500 mb-4">Current application status distribution.</p>
                    <div class="overflow-x-auto">
                        <canvas id="statusChart" height="220"></canvas>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold mb-1 text-gray-900">Gender Breakdown</h2>
                    <p class="text-xs text-gray-500 mb-4">Applicant gender records for the selected cohort.</p>
                    <div class="overflow-x-auto">
                        <canvas id="genderChart" height="220"></canvas>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold mb-1 text-gray-900">Program Distribution</h2>
                    <p class="text-xs text-gray-500 mb-4">Applicants grouped by program.</p>
                    <div class="overflow-x-auto">
                        <canvas id="programChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const selectedCohort = <?= json_encode($selectedCohort) ?>;
        const selectedStartDate = <?= json_encode($startDate) ?>;
        const selectedEndDate = <?= json_encode($endDate) ?>;
        let charts = {};

        function chartOptions(type) {
            return {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: type !== 'bar',
                        position: 'bottom'
                    }
                },
                scales: type === 'doughnut' ? {} : {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            };
        }

        function emptyFallback(data) {
            return data && data.length ? data : [0];
        }

        function labelFallback(labels) {
            return labels && labels.length ? labels : ['No data'];
        }

        function loadAnalytics() {
            const params = new URLSearchParams({
                cohort: selectedCohort,
                start_date: selectedStartDate,
                end_date: selectedEndDate
            });

            fetch(`ajax/analytics_data?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('statusTotal').textContent = (data.status.data || []).reduce((sum, value) => sum + Number(value || 0), 0);

                    updateChart('trendChart', 'line', data.trend.labels, [{
                        label: 'Submitted',
                        data: emptyFallback(data.trend.data),
                        borderColor: '#6B21A8',
                        backgroundColor: 'rgba(107,33,168,0.12)',
                        fill: true,
                        tension: 0.4
                    }]);

                    updateChart('statusChart', 'doughnut', data.status.labels, [{
                        data: emptyFallback(data.status.data),
                        backgroundColor: ['#16A34A', '#EF4444', '#3B82F6', '#F59E0B', '#9CA3AF']
                    }]);

                    updateChart('genderChart', 'doughnut', data.gender.labels, [{
                        data: emptyFallback(data.gender.data),
                        backgroundColor: ['#6366F1', '#F472B6', '#D1D5DB', '#10B981']
                    }]);

                    updateChart('programChart', 'bar', data.program.labels, [{
                        label: 'Applicants',
                        data: emptyFallback(data.program.data),
                        backgroundColor: '#6B21A8',
                        borderRadius: 6
                    }]);
                })
                .catch(() => {
                    document.getElementById('statusTotal').textContent = 'Unavailable';
                });
        }

        function updateChart(canvasId, type, labels, datasets) {
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }

            charts[canvasId] = new Chart(document.getElementById(canvasId), {
                type,
                data: { labels: labelFallback(labels), datasets },
                options: chartOptions(type)
            });
        }

        loadAnalytics();
    </script>
</body>

</html>
