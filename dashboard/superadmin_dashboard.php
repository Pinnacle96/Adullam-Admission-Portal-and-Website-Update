<?php
session_start();
require_once 'db.php';

// Redirect if not logged in or not a superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Fetch cohorts
$currentCohort = trim($pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026');
$cohorts = $pdo->query("SELECT DISTINCT cohort FROM applications WHERE cohort IS NOT NULL AND cohort != ''")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentCohort, $cohorts)) {
    array_unshift($cohorts, $currentCohort);
}
rsort($cohorts);

$selectedCohort = $_GET['cohort'] ?? $currentCohort;

// Stats filtered by cohort
$total = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE cohort = ?");
$total->execute([$selectedCohort]);
$total = $total->fetchColumn();

$submitted = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE submitted = 1 AND cohort = ?");
$submitted->execute([$selectedCohort]);
$submitted = $submitted->fetchColumn();

$admitted = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'admitted' AND cohort = ?");
$admitted->execute([$selectedCohort]);
$admitted = $admitted->fetchColumn();

$rejected = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'rejected' AND cohort = ?");
$rejected->execute([$selectedCohort]);
$rejected = $rejected->fetchColumn();

$pendingReview = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE submitted = 1 AND (status IS NULL OR status = '' OR status = 'submitted') AND cohort = ?");
$pendingReview->execute([$selectedCohort]);
$pendingReview = $pendingReview->fetchColumn();

$pendingRec = $pdo->prepare("SELECT COUNT(*) FROM application_recommendations ar JOIN applications a ON ar.user_id = a.user_id WHERE ar.submitted = 0 AND a.cohort = ?");
$pendingRec->execute([$selectedCohort]);
$pendingRec = $pendingRec->fetchColumn();

$notSubmitted = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE (submitted = 0 OR submitted IS NULL) AND cohort = ?");
$notSubmitted->execute([$selectedCohort]);
$notSubmitted = $notSubmitted->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Superadmin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

            // Submission Chart
            fetch(`ajax/admin_chart_data?cohort=<?= urlencode($selectedCohort) ?>`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    if (!data.labels || !data.data) {
                        throw new Error('Invalid data format from admin_chart_data.php');
                    }

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
                            plugins: {
                                legend: {
                                    display: true
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading submission chart:', error);
                });

            // Sidebar Toggle
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');

            if (toggleSidebar && sidebar) {
                toggleSidebar.addEventListener('click', () => {
                    sidebar.classList.toggle('open');
                });
            } else {
                console.warn('Sidebar toggle elements not found');
            }

            // Application Trend Chart
            fetch(`ajax/dashboard_metrics?cohort=<?= urlencode($selectedCohort) ?>`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(stats => {
                    if (!stats.labels || !stats.data) {
                        throw new Error('Invalid data format from dashboard_metrics.php');
                    }

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
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 90,
                                        minRotation: 45
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading trend chart:', error);
                });

            // Advanced Analytics (Gender, Mode, Age, Country, Program)
            fetch(`ajax/advanced_analytics?cohort=<?= urlencode($selectedCohort) ?>`)
                .then(res => res.json())
                .then(data => {
                    // 1. Program Distribution (Replaces previous separate call)
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

                    // 2. Gender Distribution
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

                    // 3. Mode of Study
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

                    // 4. Age Demographics
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

                    // 5. Country Distribution
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