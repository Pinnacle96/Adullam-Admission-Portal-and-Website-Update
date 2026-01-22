<?php
/* ──────────────────────────────────────────────────────────────
   Hostel Management Dashboard
   ────────────────────────────────────────────────────────────── */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../db.php';

// Display success message if it exists
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']); // Clear the message after displaying
}

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index');
    exit;
}

try {
    // Get room statistics
    // Get room statistics with proper occupancy counting
$rooms_sql = "
SELECT
    hr.*,
    (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = hr.id) AS occupied,
    hr.active,
    hr.capacity - (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = hr.id) AS available
FROM hostel_rooms hr
ORDER BY hr.gender, hr.hostel_name, CAST(hr.room_number AS UNSIGNED)
";
$rooms = $pdo->query($rooms_sql)->fetchAll(PDO::FETCH_ASSOC);
    // Get summary statistics
   // Get accurate summary statistics
$stats_sql = "
SELECT 
    COUNT(DISTINCT hr.id) AS total_rooms,
    SUM(hr.capacity) AS total_capacity,
    (SELECT COUNT(*) FROM hostel_allocations) AS total_occupied,
    SUM(hr.capacity) - (SELECT COUNT(*) FROM hostel_allocations) AS total_available,
    COUNT(DISTINCT hr.hostel_name) AS total_hostels,
    SUM(CASE WHEN hr.gender = 'Male' THEN 1 ELSE 0 END) AS male_rooms,
    SUM(CASE WHEN hr.gender = 'Female' THEN 1 ELSE 0 END) AS female_rooms
FROM hostel_rooms hr
WHERE hr.active = 1
";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
    // Get recent allocations
    $recent_sql = "
    SELECT 
        ha.*, 
        hr.hostel_name,
        hr.room_number,
        hr.gender,
        (SELECT full_name FROM hostel_registrations WHERE id = ha.registration_id) AS student_name
    FROM hostel_allocations ha
    JOIN hostel_rooms hr ON ha.room_id = hr.id
    ORDER BY ha.allocated_at DESC
    LIMIT 5
    ";
    $recent_allocations = $pdo->query($recent_sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Hostel Management Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'components/navbar.php'; ?>
<div class="flex flex-col md:flex-row">
  <?php include 'components/sidebar.php'; ?>

  <main class="flex-1 p-4 sm:p-6 lg:p-8">
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p class="font-bold">Success!</p>
            <p><?= htmlspecialchars($success_message) ?></p>
        </div>
    <?php endif; ?>
    <!-- Header with quick actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-purple-800">
          <i class="fas fa-home mr-2"></i>Hostel Management Dashboard
        </h1>
        <p class="text-gray-600">Overview of hostel occupancy and management</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="add_hostel_room" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow flex items-center">
          <i class="fas fa-plus mr-2"></i> Add Room
        </a>
        <a href="manual_reassign" class="bg-purple-700 hover:bg-purple-800 text-white font-semibold py-2 px-4 rounded shadow flex items-center">
          <i class="fas fa-people-arrows mr-2"></i> Reassign
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <?php if ($stats): ?>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500">Total Rooms</p>
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($stats['total_rooms']) ?></h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <i class="fas fa-door-open text-blue-500 text-xl"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500">Total Capacity</p>
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($stats['total_capacity']) ?></h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <i class="fas fa-bed text-green-500 text-xl"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500">Occupied Beds</p>
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($stats['total_occupied']) ?></h3>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
              <i class="fas fa-user-check text-purple-500 text-xl"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500">Available Beds</p>
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($stats['total_available']) ?></h3>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
              <i class="fas fa-bed text-yellow-500 text-xl"></i>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="col-span-4 bg-red-100 border-l-4 border-red-500 p-4">
          <p class="text-red-700">Could not load statistics. Please check your database connection.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent Allocations -->
    <div class="bg-white rounded-xl shadow mb-6">
      <div class="p-4 border-b">
        <h3 class="font-semibold text-lg flex items-center">
          <i class="fas fa-clock mr-2 text-blue-600"></i> Recent Allocations
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="py-3 px-4 text-left">Student</th>
              <th class="py-3 px-4 text-left">Hostel</th>
              <th class="py-3 px-4 text-left">Room</th>
              <th class="py-3 px-4 text-left">Gender</th>
              <th class="py-3 px-4 text-left">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if (!empty($recent_allocations)): ?>
              <?php foreach ($recent_allocations as $alloc): ?>
              <tr class="hover:bg-gray-50">
                <td class="py-3 px-4"><?= htmlspecialchars($alloc['student_name'] ?? 'N/A') ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($alloc['hostel_name']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($alloc['room_number']) ?></td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 rounded-full text-xs <?= $alloc['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' ?>">
                    <?= htmlspecialchars($alloc['gender']) ?>
                  </span>
                </td>
                <td class="py-3 px-4"><?= date('M d, Y', strtotime($alloc['allocated_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="py-4 text-center text-gray-500">
                  No recent allocations found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Rooms Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="font-semibold text-lg flex items-center">
          <i class="fas fa-list mr-2 text-purple-600"></i> All Rooms
        </h3>
        <div class="flex items-center space-x-2">
          <div class="relative">
            <select id="hostelFilter" class="block appearance-none bg-gray-100 border border-gray-300 text-gray-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-purple-500 text-sm">
              <option value="">All Hostels</option>
              <option value="Bethel">Bethel</option>
              <option value="Zion">Zion</option>
            </select>
          </div>
          <div class="relative">
            <select id="statusFilter" class="block appearance-none bg-gray-100 border border-gray-300 text-gray-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-purple-500 text-sm">
              <option value="">All Status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
          <button id="applyFilter" class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-3 rounded text-sm flex items-center">
            <i class="fas fa-filter mr-1"></i> Filter
          </button>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="py-3 px-4 text-left">Hostel</th>
              <th class="py-3 px-4 text-left">Room No.</th>
              <th class="py-3 px-4 text-left">Gender</th>
              <th class="py-3 px-4 text-left">Semester</th>
              <th class="py-3 px-4 text-center">Capacity</th>
              <th class="py-3 px-4 text-center">Occupied</th>
              <th class="py-3 px-4 text-center">Available</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200" id="roomsTableBody">
            <?php if (!empty($rooms)): ?>
              <?php foreach ($rooms as $r):
                $available = $r['capacity'] - $r['occupied'];
                $badge = $available === 0   ? 'bg-red-100 text-red-800'
                       : ($available <= 2   ? 'bg-yellow-100 text-yellow-800'
                       :                      'bg-green-100 text-green-800');
                $status_badge = $r['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
              ?>
              <tr class="hover:bg-gray-50">
                <td class="py-3 px-4"><?= htmlspecialchars($r['hostel_name']) ?></td>
                <td class="py-3 px-4 font-medium"><?= htmlspecialchars($r['room_number']) ?></td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 rounded-full text-xs <?= $r['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' ?>">
                    <?= htmlspecialchars($r['gender']) ?>
                  </span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 rounded-full text-xs <?= $r['semester'] === 'First Semester' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' ?>">
                    <?= htmlspecialchars($r['semester']) ?>
                  </span>
                </td>
                <!--<td class="py-3 px-4 text-center"><//?= htmlspecialchars($r['semester']) ?></td>-->
                <td class="py-3 px-4 text-center"><?= htmlspecialchars($r['capacity']) ?></td>
                <td class="py-3 px-4 text-center"><?= htmlspecialchars($r['occupied']) ?></td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2 py-1 rounded-full text-xs <?= $badge ?>">
                    <?= htmlspecialchars($available) ?>
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2 py-1 rounded-full text-xs <?= $status_badge ?>">
                    <?= $r['active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex justify-center space-x-2">
                    <a href="edit_hostel_room?id=<?= $r['id'] ?>" 
                       class="text-blue-500 hover:text-blue-700 p-1 rounded-full hover:bg-blue-50"
                       title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="confirmDelete(<?= $r['id'] ?>)" 
                            class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50"
                            title="Delete">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                   <button onclick="toggleStatus(<?= $r['id'] ?>, <?= $r['active'] ? 1 : 0 ?>, <?= $r['occupied'] ?>)" 
        class="<?= $r['active'] ? 'text-green-500 hover:text-green-700' : 'text-gray-500 hover:text-gray-700' ?> p-1 rounded-full hover:bg-gray-50"
        title="<?= $r['active'] ? 'Deactivate' : 'Activate' ?>"
        <?= ($r['active'] && $r['occupied'] > 0) ? 'data-occupied="true"' : '' ?>>
    <i class="fas <?= $r['active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
</button>
                    <!--<a href="room_occupants.php?room_id=<?= $r['id'] ?>" -->
                    <!--   class="text-purple-500 hover:text-purple-700 p-1 rounded-full hover:bg-purple-50"-->
                    <!--   title="View Occupants">-->
                    <!--  <i class="fas fa-users"></i>-->
                    <!--</a>-->
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="py-4 text-center text-gray-500">
                  No rooms found. <a href="add_hostel_room" class="text-blue-600 hover:underline">Add your first room</a>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script>
// Confirmation dialogs
function confirmDelete(roomId) {
  Swal.fire({
    title: 'Delete Room?',
    text: "This will permanently remove the room record. Any allocations will be lost!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `delete_hostel_room?id=${roomId}`;
    }
  });
}

function toggleStatus(roomId, currentStatus, currentOccupancy) {
    if (currentStatus && currentOccupancy > 0) {
        Swal.fire({
            title: 'Cannot Deactivate Room',
            html: `<div class="text-left">
                     <p>This room currently has <strong>${currentOccupancy} occupant(s)</strong>.</p>
                     <p class="mt-2">Please reassign all students before deactivating.</p>
                   </div>`,
            icon: 'error',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return;
    }

    Swal.fire({
        title: `${currentStatus ? 'Deactivate' : 'Activate'} Room?`,
        html: currentStatus 
            ? `<div class="text-left">
                 <p>Deactivating this room will make it unavailable for new allocations.</p>
                 ${currentOccupancy > 0 ? '<p class="text-red-500 font-semibold mt-2">Warning: Room has current occupants!</p>' : ''}
               </div>`
            : "Activating this room will make it available for student allocations.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${currentStatus ? 'deactivate' : 'activate'} it!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `toggle_room_status?id=${roomId}`;
        }
    });
}

// Filter functionality
document.getElementById('applyFilter').addEventListener('click', function() {
  const hostelFilter = document.getElementById('hostelFilter').value;
  const statusFilter = document.getElementById('statusFilter').value;
  
  // Get all room rows
  const rows = document.querySelectorAll('#roomsTableBody tr');
  
  rows.forEach(row => {
    const hostelName = row.cells[0].textContent.trim();
    const statusBadge = row.cells[6].querySelector('span').textContent.trim();
    const statusValue = statusBadge === 'Active' ? '1' : '0';
    
    const hostelMatch = hostelFilter === '' || hostelName === hostelFilter;
    const statusMatch = statusFilter === '' || statusValue === statusFilter;
    
    if (hostelMatch && statusMatch) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});
</script>
</body>
</html>