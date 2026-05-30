<?php
// ================= ERROR & SUCCESS LOGGING =================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload_error.log');

function log_message(string $message, string $type = 'INFO'): void {
    $log_file = __DIR__ . '/upload_trace.log';
    $timestamp = date('[Y-m-d H:i:s T]');
    file_put_contents($log_file, "$timestamp [$type] $message\n", FILE_APPEND);
}
// ==========================================================

session_start();
require 'db.php';

log_message("New request received for user_id: " . ($_SESSION['user_id'] ?? 'N/A'), 'INFO');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    log_message("Access denied. User not logged in or role is not 'student'.", 'ALERT');
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

// --- Get user program ---
try {
    $stmt = $pdo->prepare("SELECT program FROM application_details WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $userProgram = trim($stmt->fetchColumn() ?: '');

    if ($userProgram === '') {
        log_message("No program found for user_id $user_id", 'WARNING');
        $_SESSION['error'] = "Program information not found. Please contact support.";
        header("Location: form_level6");
        exit;
    }
} catch (PDOException $e) {
    log_message("DB error fetching program for user_id $user_id: " . $e->getMessage(), 'ERROR');
    $_SESSION['error'] = "A database error occurred. Please try again later.";
    header("Location: form_level6");
    exit;
}

$isPGDTorMA = in_array(strtoupper($userProgram), ['MA', 'PGDT'], true);

// ================= FILE UPLOAD FUNCTION =================
function saveFile(array $file, string $field): array {
    log_message("Debug for $field => tmp_name: {$file['tmp_name']}, size: {$file['size']}, type: {$file['type']}", 'DEBUG');

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $php_errors = [
            UPLOAD_ERR_INI_SIZE   => "File exceeds the max size defined in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "File exceeds the max size defined in the HTML form.",
            UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
        ];
        $errorMessage = $php_errors[$file['error']] ?? "Unknown PHP upload error.";
        log_message("PHP Upload Error for $field: " . $errorMessage, 'ERROR');
        return ['success' => false, 'message' => "An upload error occurred for $field: " . $errorMessage];
    }

    $maxFileSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => "File size for $field exceeds 5MB limit."];
    }

    $targetDir = "uploads/documents/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

    $fileName = basename($file['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => "Invalid file type for $field. Allowed: " . implode(', ', $allowed)];
    }

    $newName = uniqid($field . "_") . "." . $ext;
    $targetFile = $targetDir . $newName;

    if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $targetFile)) {
        log_message("File '$fileName' saved as '$newName'.", 'SUCCESS');
        return ['success' => true, 'path' => $targetFile];
    }

    return ['success' => false, 'message' => "Failed to upload $field. Try reselecting the file (avoid Google Drive/WhatsApp shortcuts)."];
}
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filePaths = [];
    $uploadErrors = [];

    $allFields = ['passport', 'ssce_cert', 'ssce_cert2', 'birth_cert', 'origin_cert', 'recommendation', 'payment_proof'];
    if ($isPGDTorMA) {
        $allFields[] = 'degree_cert';
        $allFields[] = 'transcript';
    }

    foreach ($allFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = saveFile($_FILES[$field], $field);
            if ($result['success']) {
                $filePaths[$field] = $result['path'];
            } else {
                $uploadErrors[] = $result['message'];
            }
        }
    }

    if (isset($_POST['continue'])) {
        $requiredFields = ['passport', 'ssce_cert', 'birth_cert', 'origin_cert', 'recommendation', 'payment_proof'];
        if ($isPGDTorMA) $requiredFields[] = 'degree_cert';

        foreach ($requiredFields as $requiredField) {
            // Check if file exists in new upload OR existing database records
            $hasNewFile = isset($filePaths[$requiredField]);
            $hasExistingFile = !empty($existingDocs[$requiredField]);
            
            if (!$hasNewFile && !$hasExistingFile) {
                $uploadErrors[] = "The " . str_replace('_', ' ', $requiredField) . " file is required.";
            }
        }
    }

    if (!empty($uploadErrors)) {
        $_SESSION['upload_errors'] = $uploadErrors;
        header("Location: form_level6");
        exit;
    }

    if (!empty($filePaths)) {
        $columns = implode(", ", array_keys($filePaths));
        $placeholders = implode(", ", array_fill(0, count($filePaths), '?'));
        $updates = implode(", ", array_map(fn($f) => "$f = VALUES($f)", array_keys($filePaths)));

        $sql = "INSERT INTO application_documents (user_id, $columns) VALUES (?, $placeholders) ON DUPLICATE KEY UPDATE $updates";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$user_id], array_values($filePaths)));
    }

    if (isset($_POST['continue'])) {
        $pdo->prepare("UPDATE applications SET current_level = 7 WHERE user_id = ?")->execute([$user_id]);
        $_SESSION['success_continue'] = "Documents saved. Redirecting to preview...";
        header("Location: form_level6");
    } elseif (isset($_POST['save'])) {
        $_SESSION['success_save'] = "Your documents have been saved.";
        header("Location: form_level6");
    } elseif (isset($_POST['previous'])) {
        header("Location: form_level5");
    }
    exit;
}

// Display messages
$error = $_SESSION['error'] ?? null;
$successSave = $_SESSION['success_save'] ?? null;
$successContinue = $_SESSION['success_continue'] ?? null;
$uploadErrors = $_SESSION['upload_errors'] ?? [];
unset($_SESSION['error'], $_SESSION['success_save'], $_SESSION['success_continue'], $_SESSION['upload_errors']);

// ... (same code as before)

// === Fetch existing uploaded documents ===
$stmt = $pdo->prepare("SELECT * FROM application_documents WHERE user_id = ?");
$stmt->execute([$user_id]);
$existingDocs = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Documents</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
  <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-3xl">
    <h2 class="text-xl font-bold text-purple-800 text-center mb-4">Step 6 of 6: Upload Supporting Documents</h2>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <?php
      $inputs = [
        'passport' => ['label' => 'Passport Photograph (JPG, PNG, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png'],
        'ssce_cert' => ['label' => 'SSCE Certificate/Equivalent - First Sitting (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'ssce_cert2' => ['label' => 'SSCE Certificate/Equivalent - Second Sitting (Optional, JPG, PNG, PDF, < 5mb)', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'birth_cert' => ['label' => 'Birth Certificate (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'origin_cert' => ['label' => 'Proof of Nationality ( E.g. Intl Passport, Local Gov. of Origin Cert., or National ID.) (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'recommendation' => ['label' => 'Recommendation Letter from Clergy (PDF, DOCX, DOC, < 5mb)', 'required' => true, 'accept' => '.pdf,.doc,.docx'],
        'payment_proof' => ['label' => 'Application Fees Proof (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
      ];
      if ($isPGDTorMA) {
        $inputs['degree_cert'] = ['label' => 'Degree Certificate (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'];
        $inputs['transcript'] = ['label' => 'Transcript (Optional, JPG, PNG, PDF, < 5mb)', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.pdf'];
      }
      foreach ($inputs as $name => $details): ?>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"><?= $details['label'] ?></label>
          <input type="file" name="<?= $name ?>" accept="<?= $details['accept'] ?>" class="w-full border p-2 rounded-md" <?= ($details['required'] && empty($existingDocs[$name])) ? 'required' : '' ?> />

          <?php if (!empty($existingDocs[$name])): ?>
            <p class="text-xs text-gray-600 mt-1">
              ✅ Already uploaded: 
              <a href="<?= htmlspecialchars($existingDocs[$name]) ?>" target="_blank" class="text-purple-600 underline">
                <?= basename($existingDocs[$name]) ?>
              </a>
            </p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="flex flex-col sm:flex-row justify-between gap-4 pt-6">
        <button type="submit" name="previous" class="w-full sm:w-auto bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow">⬅ Previous</button>
        <button type="submit" name="save" class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-white px-6 py-2 rounded-lg shadow">💾 Save for Later</button>
        <button type="submit" name="continue" class="w-full sm:w-auto bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg shadow">Next ➡</button>
      </div>
    </form>
  </div>

 

  <script>
  <?php if ($error): ?>
    Swal.fire({ icon: 'error', title: 'Error', text: '<?= htmlspecialchars($error) ?>', confirmButtonColor: '#6B21A8' });
  <?php endif; ?>

  <?php if ($successSave): ?>
    Swal.fire({ icon: 'success', title: 'Saved!', text: '<?= htmlspecialchars($successSave) ?>', confirmButtonColor: '#6B21A8' });
  <?php endif; ?>

  <?php if ($successContinue): ?>
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: '<?= htmlspecialchars($successContinue) ?>',
      confirmButtonColor: '#6B21A8',
      timer: 2000,
      timerProgressBar: true,
      didClose: () => { window.location.href = 'preview_application'; }
    });
  <?php endif; ?>

  <?php if (!empty($uploadErrors)): ?>
    Swal.fire({
      icon: 'warning',
      title: 'Upload Issues',
      html: '<?= implode("<br>", array_map("htmlspecialchars", $uploadErrors)) ?>',
      confirmButtonColor: '#6B21A8'
    });
  <?php endif; ?>
  </script>
</body>
</html>
