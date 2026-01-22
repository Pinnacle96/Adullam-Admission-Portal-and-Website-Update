<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/scripts/import_processor_web.php';  // New version as function

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $csvPath = $uploadDir . basename($_FILES['csv']['name']);
    if (move_uploaded_file($_FILES['csv']['tmp_name'], $csvPath)) {
        $message = adullam_import_run($csvPath);
    } else {
        $message = "❌ Failed to upload CSV file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Adullam CSV Import</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-6">
  <div class="w-full max-w-lg bg-white rounded-xl shadow-xl p-8">
    <h1 class="text-2xl font-bold mb-6 text-center">Upload Applicants CSV</h1>

    <?php if ($message): ?>
        <div class="mb-6 bg-gray-800 text-white rounded-lg p-4 overflow-auto text-sm whitespace-pre-line h-64">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="file" name="csv" accept=".csv" required
               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                      file:rounded-full file:border-0 file:text-sm file:font-semibold
                      file:bg-indigo-600 file:text-white hover:file:bg-indigo-700" />
        <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg">
            🚀 Import Now
        </button>
    </form>
  </div>
</body>
</html>
