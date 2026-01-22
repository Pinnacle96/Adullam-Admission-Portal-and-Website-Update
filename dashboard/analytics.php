<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}
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
    <div class="flex flex-col lg:flex-row min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
            <h1 class="text-xl sm:text-2xl font-bold text-purple-800 mb-6">📈 Analytics Dashboard</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-xl shadow">
                    <h2 class="text-lg font-semibold mb-2">📅 Monthly Application Trend</h2>
                    <div class="overflow-x-auto">
                        <canvas id="trendChart" height="220"></canvas>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow">
                    <h2 class="text-lg font-semibold mb-2">📌 Application Status</h2>
                    <div class="overflow-x-auto">
                        <canvas id="statusChart" height="220"></canvas>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow">
                    <h2 class="text-lg font-semibold mb-2">🚻 Gender Breakdown</h2>
                    <div class="overflow-x-auto">
                        <canvas id="genderChart" height="220"></canvas>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow">
                    <h2 class="text-lg font-semibold mb-2">🎓 Programs Distribution</h2>
                    <div class="overflow-x-auto">
                        <canvas id="programChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let charts = {};

        function loadAnalytics() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            fetch(`ajax/analytics_data?start_date=${startDate}&end_date=${endDate}`)
                .then(res => res.json())
                .then(data => {
                    updateChart('trendChart', 'line', data.trend.labels, [{
                        label: 'Submitted',
                        data: data.trend.data,
                        borderColor: '#6B21A8',
                        backgroundColor: 'rgba(107,33,168,0.1)',
                        fill: true,
                        tension: 0.4
                    }]);

                    updateChart('statusChart', 'doughnut', data.status.labels, [{
                        data: data.status.data,
                        backgroundColor: ['#16A34A', '#EF4444', '#3B82F6', '#9CA3AF']
                    }]);

                    updateChart('genderChart', 'doughnut', data.gender.labels, [{
                        data: data.gender.data,
                        backgroundColor: ['#6366F1', '#F472B6', '#D1D5DB']
                    }]);

                    updateChart('programChart', 'bar', data.program.labels, [{
                        label: 'Applicants',
                        data: data.program.data,
                        backgroundColor: '#6B21A8'
                    }]);
                });
        }

        function updateChart(canvasId, type, labels, datasets) {
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }
            charts[canvasId] = new Chart(document.getElementById(canvasId), {
                type: type,
                data: { labels, datasets },
                options: { responsive: true }
            });
        }

        // Initial Load
        loadAnalytics();
    </script>
</body>

</html>