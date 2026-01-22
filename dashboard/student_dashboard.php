<?php
session_start();
require_once 'db.php';

// 🔐 Only logged-in students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
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
            <a href="logout" class="inline-block bg-purple-700 text-white px-6 py-2 rounded-full hover:bg-purple-800 transition duration-300">Logout</a>
        </div>

    <?php else: ?>
        <!-- Open Window UI -->
        <div class="bg-white rounded-lg shadow-lg p-6 sm:p-10 max-w-xl w-full text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-purple-800 mb-4">Welcome,
                <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
            <p class="text-gray-600 text-sm sm:text-base">Redirecting you to your application form...</p>
        </div>

        <?php if (!$hasApp): ?>
            <!-- Show modal if application not started -->
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({
                        title: '📘 Application Requirements',
                        width: window.innerWidth < 600 ? '90%' : '800px',
                        html: `
        <div class="text-left text-sm text-gray-800 max-h-96 overflow-y-auto px-2">
          <h3 class="font-semibold text-purple-700 mb-1">🎓 UNDERGRADUATE PROGRAMMES</h3>
          <h4>The following are the requirements for undergraduate admissions:</h4>
          <ul class="list-disc ml-5 space-y-1">
            <li>A recent passport photograph</li>
            <li>💵 ₦15,000 (Local Students) or $30 (International Students)non-refundable application fee proof of payment (<a href="Account Details New.pdf" target="_blank" class="text-purple-600 underline">Download account details</a>)</li>
            <li><strong>Academic Credentials</strong>
              <ul class="list-disc ml-5 mt-1">
                <li><strong>Certificate</strong> – SSCE (or its equivalent) with 5 credits including English</li>
                <li><strong>Diploma</strong> – SSCE (or its equivalent) with 5 credits including English</li>
                <li><strong>B.Div</strong> – SSCE (or its equivalent) with 5 credits including English </li>
              </ul>
            </li>
            <li>📱 Phone numbers and email of two referees</li>
            <li>📜 One recommendation letter from a clergy (<a href="clergy_template.pdf" target="_blank" class="text-purple-600 underline">Download Template</a>)</li>
            <li>🌍 International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact ‪+2348022164432‬ for more details)
</li>
          </ul>

          <h3 class="font-semibold text-purple-700 mt-4 mb-1">🎓 POSTGRADUATE PROGRAMMES</h3>
           <h4>The following are the requirements for post-graduate admissions:</h4>
          <ul class="list-disc ml-5 space-y-1">
            <li>A recent passport photograph</li>
            <li>💵 ₦25,000 (Local Students) or $40 (International Students)
 non-refundable application fee proof of payment (<a href="Account Details New.pdf" target="_blank" class="text-purple-600 underline">Download account details</a>)</li>
            <li><strong>Academic Credentials</strong>
              <ul class="list-disc ml-5 mt-1">
                <li><strong>PGDT</strong> – Bachelor’s degree or HND in any field</li>
                <li><strong>MA</strong> – BA or PGD official transcript from a recognized Theological Seminary.</li>
              </ul>
            </li>
            <li>📱 Phone numbers and email of two referees</li>
            <li>📜 One recommendation letter from a clergy (<a href="clergy_template.pdf" target="_blank" class="text-purple-600 underline">Download Template</a>)</li>
            <li>🌍 International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact ‪+2348022164432‬ for more details).</li>
          </ul>
        </div>
      `,
                        confirmButtonText: 'Begin Application',
                        confirmButtonColor: '#6B21A8'
                    }).then(() => {
                        window.location.href = 'form_level1';
                    });
                });
            </script>
        <?php else: ?>
            <!-- Auto resume if already started -->
            <script>
                window.location.href = "form_level<?= intval($currentLevel) ?>";
            </script>
        <?php endif; ?>

    <?php endif; ?>

</body>

</html>
