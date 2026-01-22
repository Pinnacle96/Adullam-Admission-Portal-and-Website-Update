<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Admin';

// Fetch cohorts
$currentCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);

$selectedCohort = $_GET['cohort'] ?? $currentCohort;

// Fetch dashboard stats (Filtered by Cohort)
// Total Applicants (Applications in this cohort)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE cohort = ?");
$stmt->execute([$selectedCohort]);
$total = $stmt->fetchColumn();

// Submitted Applications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE submitted = 1 AND cohort = ?");
$stmt->execute([$selectedCohort]);
$submitted = $stmt->fetchColumn();

// Admitted Applicants
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'admitted' AND cohort = ?");
$stmt->execute([$selectedCohort]);
$admitted = $stmt->fetchColumn();

// Rejected Applicants
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'rejected' AND cohort = ?");
$stmt->execute([$selectedCohort]);
$rejected = $stmt->fetchColumn();

// Pending Review
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE submitted = 1 AND (status IS NULL OR status = '' OR status = 'submitted') AND cohort = ?");
$stmt->execute([$selectedCohort]);
$pendingReview = $stmt->fetchColumn();

// Not Yet Submitted
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE (submitted = 0 OR submitted IS NULL) AND cohort = ?");
$stmt->execute([$selectedCohort]);
$notSubmitted = $stmt->fetchColumn();

// Pending Recommendations (Linked to applications in this cohort)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM application_recommendations ar
    JOIN applications a ON ar.user_id = a.user_id
    WHERE ar.submitted = 0 AND a.cohort = ?
");
$stmt->execute([$selectedCohort]);
$pendingRec = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="../assets/img/favicon.png" />
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-6 max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-purple-800">👋 Welcome, <?= htmlspecialchars($name) ?></h1>
                
                <form method="GET" class="flex items-center gap-2 mt-4 md:mt-0">
                    <label class="font-semibold text-gray-700">Cohort:</label>
                    <select name="cohort" onchange="this.form.submit()" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 bg-white">
                        <?php foreach ($cohorts as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $c === $selectedCohort ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Total Applicants -->
                <a href="applicants_list?cohort=<?= urlencode($selectedCohort) ?>" class="block transform hover:scale-105 transition duration-300">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg flex items-center gap-4">
                        <div class="p-3 bg-purple-100 rounded-xl text-purple-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Applicants</p>
                            <h2 class="text-2xl font-bold text-gray-800"><?= $total ?></h2>
                        </div>
                    </div>
                </a>

                <!-- Submitted Applications -->
                <a href="applicants_list?cohort=<?= urlencode($selectedCohort) ?>&status=submitted" class="block transform hover:scale-105 transition duration-300">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl text-blue-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Submitted</p>
                            <h2 class="text-2xl font-bold text-gray-800"><?= $submitted ?></h2>
                        </div>
                    </div>
                </a>

                <!-- Admitted Applicants -->
                <a href="applicants_list?cohort=<?= urlencode($selectedCohort) ?>&status=admitted" class="block transform hover:scale-105 transition duration-300">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg flex items-center gap-4">
                        <div class="p-3 bg-green-100 rounded-xl text-green-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Admitted</p>
                            <h2 class="text-2xl font-bold text-gray-800"><?= $admitted ?></h2>
                        </div>
                    </div>
                </a>

                <!-- Rejected Applicants -->
                <a href="applicants_list?cohort=<?= urlencode($selectedCohort) ?>&status=rejected" class="block transform hover:scale-105 transition duration-300">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg flex items-center gap-4">
                        <div class="p-3 bg-red-100 rounded-xl text-red-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Rejected</p>
                            <h2 class="text-2xl font-bold text-gray-800"><?= $rejected ?></h2>
                        </div>
                    </div>
                </a>

                <!-- Not Yet Submitted -->
                <a href="applicants_list?cohort=<?= urlencode($selectedCohort) ?>&status=draft" class="block transform hover:scale-105 transition duration-300">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg flex items-center gap-4">
                        <div class="p-3 bg-yellow-100 rounded-xl text-yellow-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">In Progress</p>
                            <h2 class="text-2xl font-bold text-gray-800"><?= $notSubmitted ?></h2>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Quick Access Links -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="applicants_list?cohort=<?= urlencode($selectedCohort) ?>"
                    class="bg-purple-700 hover:bg-purple-800 text-white px-4 py-3 rounded-xl shadow text-center font-medium transition">
                    📋 Manage Applicants
                </a>
                <a href="recommendation_list?cohort=<?= urlencode($selectedCohort) ?>"
                    class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-3 rounded-xl shadow text-center font-medium transition">
                    📨 View Recommendations
                </a>
                <a href="reports_export?cohort=<?= urlencode($selectedCohort) ?>"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-xl shadow text-center font-medium transition">
                    📊 Export Reports
                </a>
            </div>
            <div class="bg-white p-4 rounded-xl shadow mt-8">
                <h2 class="text-lg font-semibold mb-4 text-purple-800">📈 Monthly Submissions Trend</h2>
                <canvas id="submissionChart" height="100"></canvas>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-xl font-bold text-purple-800 mb-4">📊 Application Trends & Insights</h2>
                    <canvas id="appTrendChart" height="200"></canvas>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-xl font-bold text-purple-800 mb-4">🎓 Program Distribution</h2>
                    <canvas id="programDistChart" height="200"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <!-- Gender -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-bold text-purple-800 mb-4">🚻 Gender Distribution</h2>
                    <canvas id="genderChart" height="200"></canvas>
                </div>
                <!-- Mode of Study -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-bold text-purple-800 mb-4">💻 Mode of Study</h2>
                    <canvas id="modeChart" height="200"></canvas>
                </div>
                <!-- Age -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-bold text-purple-800 mb-4">🎂 Age Demographics</h2>
                    <canvas id="ageChart" height="200"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 mt-6 mb-10">
                <!-- Country -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-bold text-purple-800 mb-4">🌍 Top Applicants Locations</h2>
                    <canvas id="countryChart" height="100"></canvas>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Wait for DOM to load
        document.addEventListener('DOMContentLoaded', () => {
            // Function to create charts
            const createChart = (canvasId, config) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas) {
                    console.error(`Canvas element with ID '${canvasId}' not found`);
                    return;
                }
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.error(`Failed to get 2D context for '${canvasId}'`);
                    return;
                }
                return new Chart(ctx, config);
            };

            // 1. Submission Chart (Monthly Trend) - existing
            fetch(`ajax/admin_chart_data?cohort=<?= urlencode($selectedCohort) ?>`)
                .then(res => res.json())
                .then(data => {
                    createChart('submissionChart', {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Submitted Applications',
                                data: data.data,
                                borderColor: '#6B21A8',
                                backgroundColor: 'rgba(107, 33, 168, 0.1)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: true }, tooltip: { mode: 'index', intersect: false } },
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                        }
                    });
                })
                .catch(error => console.error('Error loading submission chart:', error));

            // 2. Application Trend Chart (metrics)
            fetch(`ajax/dashboard_metrics?cohort=<?= urlencode($selectedCohort) ?>`)
                .then(res => res.json())
                .then(stats => {
                    createChart('appTrendChart', {
                        type: 'line',
                        data: {
                            labels: stats.labels,
                            datasets: [{
                                label: 'Applications Submitted',
                                data: stats.data,
                                borderColor: '#6B21A8',
                                backgroundColor: 'rgba(107, 33, 168, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                })
                .catch(error => console.error('Error loading trend chart:', error));

            // 3. Advanced Analytics (Gender, Mode, Age, Country, Program)
            fetch(`ajax/advanced_analytics?cohort=<?= urlencode($selectedCohort) ?>`)
                .then(res => res.json())
                .then(data => {
                    // Program Distribution
                    if (data.program_distribution) {
                        createChart('programDistChart', {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(data.program_distribution),
                                datasets: [{
                                    data: Object.values(data.program_distribution),
                                    backgroundColor: ['#6B21A8', '#9333EA', '#A855F7', '#C084FC', '#E9D5FF'],
                                    borderWidth: 1
                                }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                        });
                    }
                    // Gender Distribution
                    if (data.gender_distribution) {
                        createChart('genderChart', {
                            type: 'pie',
                            data: {
                                labels: Object.keys(data.gender_distribution),
                                datasets: [{
                                    data: Object.values(data.gender_distribution),
                                    backgroundColor: ['#3B82F6', '#EC4899', '#9CA3AF'],
                                    borderWidth: 1
                                }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                        });
                    }
                    // Mode of Study
                    if (data.mode_distribution) {
                        createChart('modeChart', {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(data.mode_distribution),
                                datasets: [{
                                    data: Object.values(data.mode_distribution),
                                    backgroundColor: ['#10B981', '#F59E0B'],
                                    borderWidth: 1
                                }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                        });
                    }
                    // Age Demographics
                    if (data.age_distribution) {
                        createChart('ageChart', {
                            type: 'bar',
                            data: {
                                labels: Object.keys(data.age_distribution),
                                datasets: [{
                                    label: 'Applicants',
                                    data: Object.values(data.age_distribution),
                                    backgroundColor: '#8B5CF6',
                                    borderRadius: 5
                                }]
                            },
                            options: { 
                                responsive: true, 
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true } }
                            }
                        });
                    }
                    // Country Distribution
                    if (data.country_distribution) {
                        createChart('countryChart', {
                            type: 'bar',
                            data: {
                                labels: Object.keys(data.country_distribution),
                                datasets: [{
                                    label: 'Applicants',
                                    data: Object.values(data.country_distribution),
                                    backgroundColor: '#6366F1',
                                    borderRadius: 5
                                }]
                            },
                            options: { 
                                indexAxis: 'y', // Horizontal bar chart
                                responsive: true, 
                                plugins: { legend: { display: false } },
                                scales: { x: { beginAtZero: true } }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error loading advanced analytics:', error));
        });
    </script>

</body>

</html>