<?php
session_start();
require_once '../db.php';

// Logging setup
function log_debug($message) {
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/hostel_debug.log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, FILE_APPEND);
}

log_debug("CONFIRMING ACTIVE FILE - HIT");

// Authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index');
    exit;
}

// Initialize
$errors = [];
$formData = [
    'hostel_name' => '',
    'room_number' => '',
    'gender' => '',
    'semester' => '',
    'capacity' => 4,
    'active' => 1
];

// Fetch hostel names
try {
    $hostel_stmt = $pdo->query("SELECT DISTINCT hostel_name FROM hostel_rooms ORDER BY hostel_name");
    $hostel_names = $hostel_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $errors[] = "Error fetching hostel names.";
    log_debug("DB Error fetching hostel names: " . $e->getMessage());
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_debug("POST Data: " . print_r($_POST, true));

    $formData['hostel_name'] = trim($_POST['final_hostel_name'] ?? '');
    $formData['room_number'] = trim($_POST['room_number'] ?? '');
    $formData['gender'] = $_POST['gender'] ?? '';
    $formData['semester'] = $_POST['semester'] ?? '';
    $formData['capacity'] = intval($_POST['capacity'] ?? 4);
    $formData['active'] = isset($_POST['active']) ? 1 : 0;

    log_debug("FormData Before Validation: " . print_r($formData, true));

    // Validation
    if (empty($formData['hostel_name'])) {
        $errors[] = "Hostel name is required";
    }
    if (empty($formData['room_number'])) {
        $errors[] = "Room number is required";
    }
    if (!in_array($formData['gender'], ['Male', 'Female'])) {
        $errors[] = "Invalid gender selection";
    }
    if (!in_array($formData['semester'], ['First Semester', 'Second Semester'])) {
        $errors[] = "Invalid semester selection";
    }
    if ($formData['capacity'] < 1 || $formData['capacity'] > 10) {
        $errors[] = "Capacity must be between 1 and 10";
    }

    if (!empty($errors)) {
        log_debug("Validation Failed: " . implode(', ', $errors));
    }

    // Insert
    if (empty($errors)) {
        log_debug("Inside DB insert block");
        try {
            $pdo->beginTransaction();

            $values = [
                $formData['hostel_name'],
                $formData['room_number'],
                $formData['gender'],
                $formData['semester'],
                $formData['capacity'],
                $formData['active']
            ];

            log_debug("Executing INSERT with values: " . json_encode($values));

            $insert_stmt = $pdo->prepare("
                INSERT INTO hostel_rooms 
                (hostel_name, room_number, gender, semester, capacity, active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute($values);

            $room_id = $pdo->lastInsertId();
            log_debug("Room successfully inserted with ID: " . $room_id);

            $log_stmt = $pdo->prepare("
                INSERT INTO room_status_logs 
                (room_id, hostel_name, room_number, changed_by, old_status, new_status, change_reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $log_stmt->execute([
                $room_id,
                $formData['hostel_name'],
                $formData['room_number'],
                $_SESSION['user_id'],
                0,
                1,
                'Room created'
            ]);
            log_debug("Room log saved.");
            $pdo->commit();
            log_debug("Transaction committed successfully.");

            $_SESSION['success'] = "Room added successfully!";
            header('Location: hostel_rooms_overview');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error on insert.";
            log_debug("INSERT failed, rolling back.");
            log_debug("DB INSERT EXCEPTION: " . $e->getMessage());
        }
    }
}
?>
<!-- HTML Below -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Hostel Room</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'components/navbar.php'; ?>
<div class="flex flex-col md:flex-row">
  <?php include 'components/sidebar.php'; ?>
  <main class="flex-1 p-4 sm:p-6 lg:p-8">
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold text-purple-800 mb-4">Add New Hostel Room</h1>

      <?php if (!empty($errors)): ?>
        <div class="bg-red-100 text-red-700 p-4 mb-4 border-l-4 border-red-500">
          <strong>Error:</strong>
          <ul class="list-disc ml-5">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" id="roomForm" class="bg-white p-6 rounded-xl shadow space-y-6">
        <!-- Hostel Selector -->
        <div>
          <label for="hostel_name" class="block text-sm font-medium text-gray-700">Hostel Name</label>
          <select name="hostel_name" id="hostel_name" required class="w-full border mt-1 p-2 rounded">
            <option value="">Select Hostel</option>
            <?php foreach ($hostel_names as $hostel): ?>
              <option value="<?= htmlspecialchars($hostel) ?>" <?= ($formData['hostel_name'] === $hostel) ? 'selected' : '' ?>>
                <?= htmlspecialchars($hostel) ?>
              </option>
            <?php endforeach; ?>
            <option value="__new__">+ Add New Hostel</option>
          </select>
        </div>

        <!-- Hidden final value -->
        <input type="hidden" name="final_hostel_name" id="final_hostel_name">

        <!-- New hostel input -->
        <div id="new_hostel_container" class="hidden">
          <label for="new_hostel_name" class="block text-sm font-medium text-gray-700">New Hostel Name</label>
          <input type="text" id="new_hostel_name" class="w-full border mt-1 p-2 rounded">
        </div>

        <!-- Room Number -->
        <div>
          <label for="room_number" class="block text-sm font-medium text-gray-700">Room Number</label>
          <input type="text" name="room_number" id="room_number" value="<?= htmlspecialchars($formData['room_number']) ?>" required class="w-full border mt-1 p-2 rounded">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Gender -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Gender</label>
            <select name="gender" required class="w-full border mt-1 p-2 rounded">
              <option value="">Select Gender</option>
              <option value="Male" <?= ($formData['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= ($formData['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
            </select>
          </div>

          <!-- Semester -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Semester</label>
            <select name="semester" required class="w-full border mt-1 p-2 rounded">
              <option value="">Select Semester</option>
              <option value="First Semester" <?= ($formData['semester'] === 'First Semester') ? 'selected' : '' ?>>First Semester</option>
              <option value="Second Semester" <?= ($formData['semester'] === 'Second Semester') ? 'selected' : '' ?>>Second Semester</option>
            </select>
          </div>
        </div>

        <!-- Capacity -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Capacity</label>
          <input type="number" name="capacity" min="1" max="10" value="<?= htmlspecialchars($formData['capacity']) ?>" required class="w-full border mt-1 p-2 rounded">
        </div>

        <!-- Active -->
        <div class="flex items-center">
          <input type="checkbox" name="active" id="active" <?= $formData['active'] ? 'checked' : '' ?> class="mr-2">
          <label for="active">Active (available for allocations)</label>
        </div>

        <div class="text-right">
          <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Save Room</button>
        </div>
      </form>
    </div>
  </main>
</div>

<!-- JavaScript Logic -->
<script>
document.getElementById('hostel_name').addEventListener('change', function () {
  const isNew = this.value === '__new__';
  document.getElementById('new_hostel_container').classList.toggle('hidden', !isNew);
  document.getElementById('new_hostel_name').required = isNew;
});

document.getElementById('roomForm').addEventListener('submit', function () {
  const hostelSelect = document.getElementById('hostel_name');
  const finalInput = document.getElementById('final_hostel_name');

  if (hostelSelect.value === '__new__') {
    const newHostel = document.getElementById('new_hostel_name').value.trim();
    if (!newHostel) {
      alert('Please enter a new hostel name');
      return false;
    }
    finalInput.value = newHostel;
  } else {
    finalInput.value = hostelSelect.value;
  }

  return true;
});
</script>
</body>
</html>
