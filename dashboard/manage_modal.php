<?php
session_start();
require 'db.php';

// Auth Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Admin';

$message = "";
$msgType = "";

function ensureCohortsTable(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cohorts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// Fetch Current Data
$stmt = $pdo->prepare("SELECT * FROM tblpage WHERE PageType = 'home_modal'");
$stmt->execute();
$row = $stmt->fetch();

// Default values if not found (should be found if setup was run)
$pageTitle = $row['PageTitle'] ?? 'Application Portal Now Open!';
$pageContent = $row['PageDescription'] ?? '';
$status = $row['Email'] ?? 'inactive';

// Extract current image src for preview and preservation
preg_match('/<img[^>]+src="([^">]+)"/', $pageContent, $matches);
$currentImage = $matches[1] ?? '';

// Load Deadline Settings
$deadlineFile = 'modal_settings.json';
$deadlineDate = '';
if (file_exists($deadlineFile)) {
    $settings = json_decode(file_get_contents($deadlineFile), true);
    $deadlineDate = $settings['deadline'] ?? '';
}

// Fetch Current Cohort
$stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'current_cohort'");
$stmt->execute();
$currentCohort = $stmt->fetchColumn() ?: 'January 2026';

// Prepare content for editor (Strip Image, Countdown, Apply Button, and Closing Date info)
$editorContent = preg_replace('/<img[^>]+>/i', '', $pageContent);
$editorContent = preg_replace('/<a[^>]*>.*?Apply Now.*?<\/a>/is', '', $editorContent);
$editorContent = preg_replace('/(Time Remaining|Loading\.{3})/is', '', $editorContent);
// Robustly remove Closing Date and Application Deadline text (case insensitive, handling various spaces)
$editorContent = preg_replace('/Closing\s*Date\s*:.*?(?=<br>|<\/p>|<div>|<\/div>|$)/i', '', $editorContent);
$editorContent = preg_replace('/Application\s*Deadline\s*:.*?(?=<br>|<\/p>|<div>|<\/div>|$)/i', '', $editorContent);

// Remove common containers for these elements if they exist (simple divs with specific content)
$editorContent = preg_replace('/<div[^>]*>\s*(Time Remaining|Loading\.{3})\s*<\/div>/is', '', $editorContent);
$editorContent = preg_replace('/<div[^>]*id="countdown"[^>]*>.*?<\/div>/is', '', $editorContent);

// Remove the specific countdown container generated in index.php (bg-purple-50 with Closing Date)
$editorContent = preg_replace('/<div[^>]*bg-purple-50[^>]*>.*?<\/div>/is', '', $editorContent);

// Remove specific artifacts (Red Box & Old Countdown Box)
$editorContent = preg_replace('/<div[^>]*bg-red-100[^>]*>.*?<\/div>/is', '', $editorContent);
$editorContent = preg_replace('/<div[^>]*id="countdownBox"[^>]*>.*?<\/div>/is', '', $editorContent);
$editorContent = preg_replace('/<p[^>]*>\s*&nbsp;\s*<\/p>/is', '', $editorContent);

// Aggressively strip standalone "Time Remaining" and "Loading..." text that might be outside divs
$editorContent = str_ireplace('Time Remaining', '', $editorContent);
$editorContent = str_ireplace('Loading...', '', $editorContent);

// Strip any empty divs that might have been left behind (recursive cleanup could be better but this helps)
$editorContent = preg_replace('/<div[^>]*>\s*<\/div>/is', '', $editorContent);


// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'save_modal');

    if ($action === 'add_cohort_only') {
        $newCohortName = trim((string)($_POST['cohort_name'] ?? ''));

        if ($newCohortName === '') {
            $message = "Please enter a cohort name.";
            $msgType = "error";
        } else {
            try {
                ensureCohortsTable($pdo);
                $ins = $pdo->prepare("INSERT IGNORE INTO cohorts (name) VALUES (?)");
                $ins->execute([$newCohortName]);
                $message = "Cohort saved.";
                $msgType = "success";
            } catch (Throwable $e) {
                $message = "Could not save cohort.";
                $msgType = "error";
            }
        }
    } else {
    $newTitle = $_POST['pagetitle'];
    $newContent = $_POST['pagedes']; // Content from editor (cleaned)
    $newStatus = $_POST['status'];
    $newDeadline = $_POST['deadline'];
    $newCohort = $_POST['cohort'];

    // Save Cohort
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('current_cohort', ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->execute([$newCohort, $newCohort]);
    $currentCohort = $newCohort;
    try {
        ensureCohortsTable($pdo);
        $ins = $pdo->prepare("INSERT IGNORE INTO cohorts (name) VALUES (?)");
        $ins->execute([$newCohort]);
    } catch (Throwable $e) {
    }
    
    // Save Deadline
    file_put_contents($deadlineFile, json_encode(['deadline' => $newDeadline]));
    $deadlineDate = $newDeadline;
    
    // Determine Final Image URL
    $finalImageUrl = $currentImage; // Default to existing

    // Handle Image Upload
    if (isset($_FILES['modal_image']) && $_FILES['modal_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['modal_image']['tmp_name'];
        $fileName = $_FILES['modal_image']['name'];
        $fileSize = $_FILES['modal_image']['size'];
        $fileType = $_FILES['modal_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory in which the uploaded file will be moved
            $uploadFileDir = '../assets/img/';
            $newFileName = 'modal_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Image upload successful
                $finalImageUrl = "assets/img/" . $newFileName;
            } else {
                $message = 'There was some error moving the file to upload directory.';
                $msgType = "error";
            }
        } else {
            $message = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions);
            $msgType = "error";
        }
    }

    // Reconstruct Content with Image (Prepend)
    if (empty($message)) { 
        if ($finalImageUrl) {
            $imgTag = '<img src="' . $finalImageUrl . '" class="w-full mx-auto mb-6 rounded-lg shadow-md object-contain max-h-72 sm:max-h-80 lg:max-h-[420px]">';
            $newContent = $imgTag . $newContent;
        }

        // Update Database
        $updateStmt = $pdo->prepare("UPDATE tblpage SET PageTitle = ?, PageDescription = ?, Email = ? WHERE PageType = 'home_modal'");
        if ($updateStmt->execute([$newTitle, $newContent, $newStatus])) {
            $message = "Modal settings updated successfully!";
            $msgType = "success";
            
            // Refresh data
            $pageTitle = $newTitle;
            $pageContent = $newContent;
            $status = $newStatus;
            
             // Refresh image preview
            preg_match('/<img[^>]+src="([^">]+)"/', $pageContent, $matches);
            $currentImage = $matches[1] ?? '';
        } else {
            $message = "Database update failed.";
            $msgType = "error";
        }
    }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Manage Home Modal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea[name="pagedes"]',
        document_base_url: '../',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        height: 400,
        menubar: false,
        branding: false,
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
      });
    </script>
    <link rel="icon" href="../assets/img/favicon.png" />
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="flex">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-purple-800 mb-6">📢 Manage Home Modal</h1>

            <?php if ($message): ?>
                <div class="p-4 mb-4 rounded-lg <?= $msgType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow p-6">
                <form method="post" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="save_modal">
                    
                    <!-- Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Modal Status</label>
                            <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                                <option value="inactive" <?= $status !== 'active' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">Select 'Active' to show the modal on the homepage.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Active Cohort</label>
                            <input type="text" name="cohort" value="<?= htmlspecialchars($currentCohort) ?>" placeholder="e.g. January 2026" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border" required>
                            <p class="text-sm text-gray-500 mt-1">This cohort tag will be applied to all NEW applications.</p>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Internal Title (Admin Reference)</label>
                        <input type="text" name="pagetitle" value="<?= htmlspecialchars($pageTitle) ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                    </div>

                    <!-- Image -->
                    <div>
                         <label class="block text-sm font-medium text-gray-700 mb-2">Modal Image</label>
                         
                         <?php if ($currentImage): ?>
                            <div class="mb-2">
                                <p class="text-xs text-gray-500 mb-1">Current Image:</p>
                                <img src="<?= strpos($currentImage, 'http') === 0 ? $currentImage : '../' . $currentImage ?>" alt="Current Modal Image" class="h-32 object-contain border rounded p-1">
                            </div>
                         <?php endif; ?>

                         <input type="file" name="modal_image" accept="image/*" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                         <p class="text-sm text-gray-500 mt-1">Upload a new image to replace the current one. Leave empty to keep the current image.</p>
                    </div>

                    <!-- Deadline -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Application Deadline (Countdown)</label>
                        <input type="date" name="deadline" value="<?= htmlspecialchars($deadlineDate) ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                        <p class="text-sm text-gray-500 mt-1">Set a deadline to display a countdown timer on the modal. Clear to disable.</p>
                    </div>

                    <!-- Content (HTML) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Modal Content 
                            <?php if(!empty($deadlineDate)): ?>
                                <span class="text-purple-600 font-normal ml-2">(Closing Date: <?= date('F j, Y', strtotime($deadlineDate)) ?>)</span>
                            <?php endif; ?>
                        </label>
                        <textarea name="pagedes" rows="10" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border font-mono text-sm"><?= htmlspecialchars($editorContent) ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">
                            Tip: To position the Closing Date/Countdown manually, add <code>{{countdown}}</code> anywhere in the text. 
                            Otherwise, it will appear before "Begin your journey..." or at the top.
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4">
                        <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white font-bold py-2 px-6 rounded-xl shadow transition duration-200">
                            Save Changes
                        </button>
                    </div>

                </form>
                <div class="mt-8 border-t pt-6">
                    <h2 class="text-lg font-bold text-purple-800 mb-3">Add Upcoming Cohort (No Opening)</h2>
                    <form method="post" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <input type="hidden" name="action" value="add_cohort_only">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cohort Name</label>
                            <input type="text" name="cohort_name" placeholder="e.g. January 2027" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                            <p class="text-sm text-gray-500 mt-1">This adds the cohort to the system so students can pick it for deferral, without changing the current active cohort.</p>
                        </div>
                        <div class="sm:col-span-1">
                            <button type="submit" class="w-full bg-purple-700 hover:bg-purple-800 text-white font-bold py-2 px-6 rounded-xl shadow transition duration-200">
                                Add Cohort
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
