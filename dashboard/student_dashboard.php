<?php
session_start();
require_once 'db.php';

// 🔐 Only logged-in students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /dashboard/index");
    exit;
}

// Check registration status
$regOpen = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();

// Get app state
$hasApp = $_SESSION['has_application'] ?? false;
$currentLevel = $_SESSION['current_level'] ?? 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adullam Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">

    <?php if (!$regOpen): ?>
        <!-- Closed Window UI -->
        <div class="bg-white rounded-lg shadow-lg p-6 sm:p-10 max-w-xl w-full text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-red-600 mb-4">Admission Window Closed</h1>
            <p class="text-gray-600 text-sm sm:text-base mb-6">
                The admission window is currently closed.
                <?php if ($hasApp): ?>
                    <br><br>Your application progress has been saved.<br>
                    You can resume your application when the next admission window opens.
                <?php else: ?>
                    <br><br>Please check back later for the next admission window.
                <?php endif; ?>
            </p>
            <a href="/dashboard/logout" class="inline-block bg-purple-700 text-white px-6 py-2 rounded-full hover:bg-purple-800 transition duration-300">Logout</a>
        </div>

    <?php else: ?>
        <!-- Open Window UI -->
        <div class="bg-white rounded-lg shadow-lg p-6 sm:p-10 max-w-xl w-full text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-purple-800 mb-4">Welcome,
                <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
            <p class="text-gray-600 text-sm sm:text-base">Redirecting you to your application form...</p>
        </div>

        <?php if (!$hasApp): ?>
            <!-- Redirect directly to form if application not started -->
            <script>
                window.location.href = '/dashboard/application_form?step=1';
            </script>
        <?php else: ?>
            <!-- Auto resume if already started -->
            <script>
                window.location.href = "/dashboard/application_form?step=<?= intval($currentLevel) ?>";
            </script>
        <?php endif; ?>

    <?php endif; ?>

</body>

</html>
