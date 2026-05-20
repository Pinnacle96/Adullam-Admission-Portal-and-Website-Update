<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit;
}

// Get room ID from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch room details
$room = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM hostel_rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        $_SESSION['error'] = "Room not found";
        header('Location: hostel_rooms_overview.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch all distinct hostel names from the database
try {
    $hostels_stmt = $pdo->query("SELECT DISTINCT hostel_name FROM hostel_rooms ORDER BY hostel_name");
    $hostel_names = $hostels_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching hostel names: " . $e->getMessage());
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostel_name = $_POST['hostel_name'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validate inputs
    $errors = [];
    if (empty($hostel_name)) $errors[] = "Hostel name is required";
    if (empty($room_number)) $errors[] = "Room number is required";
    if (!in_array($gender, ['Male', 'Female'])) $errors[] = "Invalid gender selection";
    if (!in_array($semester, ['First Semester', 'Second Semester'])) $errors[] = "Invalid semester selection";
    if (!is_numeric($capacity) || $capacity < 1) $errors[] = "Capacity must be a positive number";
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE hostel_rooms 
                SET hostel_name = ?, 
                    room_number = ?, 
                    gender = ?, 
                    semester = ?, 
                    capacity = ?, 
                    active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $hostel_name,
                $room_number,
                $gender,
                $semester,
                $capacity,
                $active,
                $room_id
            ]);
            
            $_SESSION['success'] = "Room updated successfully";
            header('Location: hostel_rooms_overview.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Edit Hostel Room</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'components/navbar.php'; ?>
<div class="flex flex-col md:flex-row">
  <?php include 'components/sidebar.php'; ?>

  <main class="flex-1 p-4 sm:p-6 lg:p-8">
    <div class="max-w-2xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-purple-800">
          <i class="fas fa-edit mr-2"></i>Edit Hostel Room
        </h1>
        <a href="hostel_rooms_overview.php" class="text-gray-600 hover:text-purple-800">
          <i class="fas fa-arrow-left mr-1"></i> Back to Rooms
        </a>
      </div>
      
      <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
          <p class="font-bold">Error</p>
          <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <form method="POST">
          <div class="p-6 space-y-6">
            <div>
              <label for="hostel_name" class="block text-sm font-medium text-gray-700 mb-1">Hostel Name</label>
             <select id="hostel_name" name="hostel_name" required
    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
    <option value="">Select Hostel</option>
    <?php foreach ($hostel_names as $hostel): ?>
        <option value="<?= htmlspecialchars($hostel) ?>" 
            <?= $room['hostel_name'] === $hostel ? 'selected' : '' ?>>
            <?= htmlspecialchars($hostel) ?>
        </option>
    <?php endforeach; ?>
</select>
            </div>
            
            <div>
              <label for="room_number" class="block text-sm font-medium text-gray-700 mb-1">Room Number</label>
              <input type="text" id="room_number" name="room_number" required
                value="<?= htmlspecialchars($room['room_number']) ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select id="gender" name="gender" required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                  <option value="">Select Gender</option>
                  <option value="Male" <?= $room['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                  <option value="Female" <?= $room['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
              </div>
              
              <div>
                <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                <select id="semester" name="semester" required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                  <option value="">Select Semester</option>
                  <option value="First Semester" <?= $room['semester'] === 'First Semester' ? 'selected' : '' ?>>First Semester</option>
                  <option value="Second Semester" <?= $room['semester'] === 'Second Semester' ? 'selected' : '' ?>>Second Semester</option>
                </select>
              </div>
            </div>
            
            <div>
              <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
              <input type="number" id="capacity" name="capacity" min="1" required
                value="<?= htmlspecialchars($room['capacity']) ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
            </div>
            
            <div class="flex items-center">
              <input type="checkbox" id="active" name="active" 
                <?= $room['active'] ? 'checked' : '' ?>
                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
              <label for="active" class="ml-2 block text-sm text-gray-700">Active (available for allocations)</label>
            </div>
          </div>
          
          <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
            <button type="submit" 
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
              <i class="fas fa-save mr-2"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
      
      <!-- Current Occupants Section -->
<div class="mt-8 bg-white rounded-xl shadow overflow-hidden">
  <div class="p-4 border-b">
    <h3 class="font-semibold text-lg flex items-center">
      <i class="fas fa-users mr-2 text-blue-600"></i> Current Occupants
    </h3>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="py-3 px-4 text-left">Student Name</th>
          <th class="py-3 px-4 text-left">Email</th>
          <th class="py-3 px-4 text-left">Bed No.</th>
          <th class="py-3 px-4 text-left">Allocated On</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php
        $occupants_sql = "
        SELECT 
            ha.*,
            hr.full_name,
            hr.email
        FROM hostel_allocations ha
        JOIN hostel_registrations hr ON ha.registration_id = hr.id
        WHERE ha.room_id = ?
        ORDER BY ha.bed_no
        ";
        $stmt = $pdo->prepare($occupants_sql);
        $stmt->execute([$room_id]);
        $occupants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($occupants)): ?>
          <?php foreach ($occupants as $occ): ?>
          <tr class="hover:bg-gray-50">
            <td class="py-3 px-4"><?= htmlspecialchars($occ['full_name']) ?></td>
            <td class="py-3 px-4"><?= htmlspecialchars($occ['email']) ?></td>
            <td class="py-3 px-4"><?= htmlspecialchars($occ['bed_no'] ?? 'N/A') ?></td>
            <td class="py-3 px-4"><?= date('M d, Y', strtotime($occ['allocated_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" class="py-4 text-center text-gray-500">
              No current occupants in this room
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
    </div>
  </main>
</div>

<script>
// Client-side validation
document.querySelector('form').addEventListener('submit', function(e) {
    const capacity = document.getElementById('capacity').value;
    if (capacity < 1) {
        e.preventDefault();
        alert('Capacity must be at least 1');
        return false;
    }
    
    // Add more validation as needed
    return true;
});
</script>
</body>
</html>