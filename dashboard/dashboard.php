<?php
include 'dashboard_logic.php';

/** @var PDO $pdo */
/** @var array<string, mixed> $appData */
/** @var string $passport */
/** @var string $name */
/** @var string $addmissionNo */
/** @var string $currentCohort */
/** @var string $program */
/** @var string $transcriptUploaded */
/** @var string $focus */
/** @var string $mode */
/** @var bool|int $isSubmitted */
/** @var bool $isAdmitted */
/** @var string $admissionLetterPath */
/** @var array<int, string> $availableCohorts */
/** @var array<string, mixed>|null $pendingDeferralRequest */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <?php include 'components/student_sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col items-center mb-6 gap-4">
            <!-- Profile Picture -->
            <div>
                <img src="<?= $passport ?: 'https://ui-avatars.com/api/?name=' . urlencode($name) ?>" alt="Avatar"
                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border-2 border-purple-600">
            </div>
            <!-- Welcome Text and Admission Number -->
            <div class="text-center">
                <h1 class="text-xl sm:text-2xl font-bold text-purple-800">Welcome, <?= htmlspecialchars($name) ?>
                </h1>
                <p class="text-sm sm:text-base mt-1 text-purple-600 font-medium bg-purple-100 px-3 py-1 rounded">
                    Application No: <?= ucfirst($addmissionNo) ?>
                </p>
            </div>
        </div>
        <?php
        $banner = $pdo->query("SELECT value FROM settings WHERE `key` = 'notice_banner'")->fetchColumn();
        if ($banner && !$isSubmitted):
        ?>
            <div class="bg-yellow-100 text-yellow-800 text-sm p-3 rounded mb-4 border border-yellow-300 shadow">
                <?= htmlspecialchars($banner) ?>
            </div>
        <?php endif; ?>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
            <div class="bg-white shadow p-4 sm:p-5 rounded-xl">
                <p class="text-xs sm:text-sm text-gray-600 mb-1">📌 Application Status</p>
                <p class="text-base sm:text-lg font-bold text-purple-700">
                    <?= ucfirst($appData['status'] ?? 'Not Started') ?></p>
            </div>

            <div class="bg-white shadow p-4 sm:p-5 rounded-xl">
                <p class="text-xs sm:text-sm text-gray-600 mb-1">📅 Cohort</p>
                <p class="text-base sm:text-lg font-bold text-purple-700">
                    <?= htmlspecialchars($currentCohort ?: 'N/A') ?>
                </p>
            </div>

            <?php if (in_array($program, ['MA', 'PGDT'])): ?>
                <div class="bg-white shadow p-4 sm:p-5 rounded-xl">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">📎 Transcript Uploaded</p>
                    <p class="text-base sm:text-lg font-bold text-purple-700"><?= $transcriptUploaded ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white shadow p-4 sm:p-5 rounded-xl">
                <p class="text-xs sm:text-sm text-gray-600 mb-1">📄 Program</p>
                <p class="text-base sm:text-lg font-bold text-purple-700"><?= ucfirst($program) ?></p>
            </div>
            <?php if (in_array($program, ['MA'])): ?>
                <div class="bg-white shadow p-4 sm:p-5 rounded-xl">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">📄 MA Focus</p>
                    <p class="text-base sm:text-lg font-bold text-purple-700"><?= ucfirst($focus) ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white shadow p-4 sm:p-5 rounded-xl">
                <p class="text-xs sm:text-sm text-gray-600 mb-1">📄 Study Mode</p>
                <p class="text-base sm:text-lg font-bold text-purple-700"><?= ucfirst($mode) ?></p>
            </div>
        </div>

        <?php if (isset($_SESSION['deferral_status'], $_SESSION['deferral_message'])): ?>
            <div class="bg-white p-4 rounded-xl shadow mb-6">
                <p class="text-sm text-gray-800"><?= htmlspecialchars($_SESSION['deferral_message']) ?></p>
            </div>
            <?php unset($_SESSION['deferral_status'], $_SESSION['deferral_message']); ?>
        <?php endif; ?>

        <!-- Actions Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow space-y-4">
            <h2 class="text-lg sm:text-xl font-bold text-purple-800 mb-2">🧾 Actions</h2>
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <a href="/dashboard/application_form" class="inline-block bg-blue-600 text-white px-4 sm:px-5 py-2 rounded hover:bg-blue-700 text-sm sm:text-base text-center">
                <?= $isSubmitted ? '📑 View Submitted Application' : '📑 Continue Application' ?>
            </a>
            <a href="/dashboard/download_application"
                    class="inline-block bg-green-600 text-white px-4 sm:px-5 py-2 rounded hover:bg-green-700 text-sm sm:text-base text-center">⬇️
                    Download Application Form</a>
                <?php if ($isAdmitted && file_exists($admissionLetterPath)): ?>
                    <a href="<?= $admissionLetterPath ?>" target="_blank"
                        class="inline-block bg-purple-700 text-white px-4 sm:px-5 py-2 rounded hover:bg-purple-800 text-sm sm:text-base text-center">🎓
                        Download Admission Letter</a>
                <?php endif; ?>
            </div>

            <?php if ($isAdmitted): ?>
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Defer Admission</h3>

                    <?php if ($pendingDeferralRequest): ?>
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-3 rounded">
                            Your deferral request is pending review.
                            Requested cohort: <?= htmlspecialchars($pendingDeferralRequest['to_cohort'] ?? '') ?>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <input type="hidden" name="action" value="request_deferral">
                            <div class="sm:col-span-1">
                                <label class="block text-xs text-gray-500 mb-1">Defer to</label>
                                <select name="to_cohort" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Select cohort</option>
                                    <?php foreach ($availableCohorts as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sm:col-span-1">
                                <label class="block text-xs text-gray-500 mb-1">Reason (optional)</label>
                                <input type="text" name="reason" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Reason for deferral">
                            </div>
                            <div class="sm:col-span-1">
                                <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                                    Request Deferral
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    </div>

    <script>
        // Sidebar toggle for mobile
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');

        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !toggleSidebar.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
    </body>

</html>
