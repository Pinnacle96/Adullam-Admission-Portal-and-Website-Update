<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

// Check registration status
$regOpen = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();
if (!$regOpen) {
    header("Location: student_dashboard");
    exit;
}

$user_id = $_SESSION['user_id'];

// === Fetch saved data (if any) ===
$stmt = $pdo->prepare("SELECT church_name, caddress FROM application_church WHERE user_id = ?");
$stmt->execute([$user_id]);
$savedData = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['church_name' => '', 'caddress' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $church_name = trim($_POST['church_name'] ?? '');
    $caddress = trim($_POST['caddress'] ?? '');

    // Save or update if data is filled
    if ($church_name && $caddress) {
        $stmt = $pdo->prepare("INSERT INTO application_church (user_id, church_name, caddress) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            church_name = VALUES(church_name), 
            caddress = VALUES(caddress)");
        $stmt->execute([$user_id, $church_name, $caddress]);
    }

    // Handle buttons
    if (isset($_POST['continue'])) {
        $pdo->prepare("UPDATE applications SET current_level = 4 WHERE user_id = ?")->execute([$user_id]);
        echo "<script>
            localStorage.setItem('form3_done', '1');
            window.location.href = 'form_level4';
        </script>";
    } elseif (isset($_POST['save'])) {
        echo "<script>
            localStorage.setItem('form3_saved', '1');
            window.location.href = 'form_level3';
        </script>";
    } elseif (isset($_POST['previous'])) {
        echo "<script>window.location.href = 'form_level2';</script>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Application - Step 3</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
  <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-3xl">
    <h2 class="text-xl font-bold text-purple-800 mb-2 text-center">Step 3 of 6: Church Information</h2>
    <p class="text-sm text-center text-gray-500 mb-6">
      Please fill out information about your church below.<br>
      You will be able to send your pastor a recommendation request on another page.
    </p>

    <form method="POST" class="space-y-6">
      <div class="grid sm:grid-cols-2 gap-4">
      
  <div>
    <label class="text-sm text-gray-700">Church Name<span class="text-red-500">*</span></label>
    <input type="text" name="church_name"
      value="<?= htmlspecialchars($savedData['church_name']) ?>"
      class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
  </div>
  <div>
    <label class="text-sm text-gray-700">Church Address<span class="text-red-500">*</span></label>
    <textarea name="caddress" rows="3"
      class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600"><?= htmlspecialchars($savedData['caddress']) ?></textarea>
  </div>
</div>


      <div class="flex flex-col sm:flex-row justify-between gap-4">
        <button type="submit" name="previous"
          class="w-full sm:w-auto bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow">
          ⬅ Previous
        </button>
        <button type="submit" name="save"
          class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-white px-6 py-2 rounded-lg shadow">
          💾 Save for Later
        </button>
        <button type="submit" name="continue"
          class="w-full sm:w-auto bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg shadow">
          Next ➡
        </button>
      </div>
    </form>
  </div>

  <script>
    if (localStorage.getItem('form3_done')) {
      Swal.fire({
        icon: 'success',
        title: 'Step 3 Complete!',
        text: 'You can now continue your application.',
        confirmButtonColor: '#6B21A8'
      });
      localStorage.removeItem('form3_done');
    }

    if (localStorage.getItem('form3_saved')) {
      Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: 'Your church information has been saved.',
        confirmButtonColor: '#6B21A8'
      });
      localStorage.removeItem('form3_saved');
    }
  </script>
</body>
</html>
