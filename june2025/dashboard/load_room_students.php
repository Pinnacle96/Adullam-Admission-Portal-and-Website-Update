<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$roomId = intval($_POST['room_id'] ?? 0);
if (!$roomId) {
    exit("Invalid room ID");
}

// Get current occupants of the selected room
$stmt = $pdo->prepare("
  SELECT ha.id AS allocation_id, r.full_name, r.email, ha.bed_no, ha.gender, ha.semester
  FROM hostel_allocations ha
  JOIN hostel_registrations r ON ha.registration_id = r.id
  WHERE ha.room_id = ?
  ORDER BY ha.bed_no
");
$stmt->execute([$roomId]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all rooms with available space
$rooms = $pdo->query("
  SELECT hr.id, hr.hostel_name, hr.room_number, hr.capacity, hr.gender, hr.semester,
         COUNT(ha.id) AS occupied
  FROM hostel_rooms hr
  LEFT JOIN hostel_allocations ha ON ha.room_id = hr.id
  WHERE hr.active = 1 AND hr.id != $roomId
  GROUP BY hr.id
  HAVING occupied < hr.capacity
  ORDER BY hr.hostel_name, hr.room_number
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-xl shadow p-6 max-w-2xl mx-auto">
  <h2 class="text-xl font-bold mb-4 text-purple-800">🏠 Room Occupants</h2>

  <?php if (count($students) === 0): ?>
    <p class="text-red-600">No students currently assigned to this room.</p>
  <?php else: ?>
    <table class="min-w-full text-sm text-left mb-4">
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Bed No</th><th>Reassign</th><th>Revoke</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr class="border-b">
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= $s['bed_no'] ?></td>
            <td>
              <form method="POST" action="reassign_student.php" class="flex gap-1 items-center">
                <input type="hidden" name="allocation_id" value="<?= $s['allocation_id'] ?>">
                <select name="new_room_id" class="border rounded p-1 text-sm" required>
                  <?php
                    $matched = false;
                    foreach ($rooms as $r):
                      if ($r['gender'] === $s['gender'] && $r['semester'] === $s['semester']):
                        $matched = true;
                  ?>
                    <option value="<?= $r['id'] ?>">
                      <?= $r['hostel_name'] ?> Room <?= $r['room_number'] ?> (<?= $r['capacity'] - $r['occupied'] ?> free)
                    </option>
                  <?php
                      endif;
                    endforeach;
                    if (!$matched) {
                      echo '<option disabled>No matching room available</option>';
                    }
                  ?>
                </select>
                <button class="bg-blue-600 text-white px-2 py-1 rounded text-sm" type="submit">Move</button>
              </form>
            </td>
            <td>
              <form method="POST" action="revoke_allocation.php" onsubmit="return confirm('Are you sure?')">
                <input type="hidden" name="allocation_id" value="<?= $s['allocation_id'] ?>">
                <button class="bg-red-600 text-white px-2 py-1 rounded text-sm" type="submit">❌</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
