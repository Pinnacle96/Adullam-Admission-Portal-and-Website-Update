<?php
/* ──────────────────────────────────────────────────────────────
   admin/manual_reassign.php   –   Manual Hostel Reassignment
   ────────────────────────────────────────────────────────────── */
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit;
}

/* Rooms + live occupancy */
$rooms = $pdo->query("
  SELECT hr.*,
         COUNT(ha.id) AS occupants
  FROM hostel_rooms hr
  LEFT JOIN hostel_allocations ha ON ha.room_id = hr.id
  WHERE hr.active = 1
  GROUP BY hr.id
  ORDER BY hr.gender, hr.hostel_name, CAST(hr.room_number AS UNSIGNED)
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manual Room Reassignment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'components/navbar.php'; ?>
<div class="flex flex-col md:flex-row">
  <?php include 'components/sidebar.php'; ?>

  <main class="flex-1 p-4 sm:p-6 lg:p-8">
    <h1 class="text-2xl font-bold text-purple-800 mb-6">🔄 Manual Hostel&nbsp;Reassignment</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($rooms as $r):
            $available = $r['capacity'] - $r['occupants'];
            $badge = $available === 0 ? 'bg-red-600'
                   : ($available <= 2 ? 'bg-yellow-500' : 'bg-green-600');
      ?>
        <div class="bg-white rounded-xl shadow p-4">
          <h2 class="text-lg font-semibold text-purple-700 mb-2">
            <?= htmlspecialchars($r['hostel_name']) ?> — Room <?= htmlspecialchars($r['room_number']) ?>
          </h2>
          <p class="text-sm text-gray-700 mb-1">
            <strong>Gender:</strong> <?= $r['gender'] ?> |
            <strong>Semester:</strong> <?= $r['semester'] ?>
          </p>
          <p class="text-sm text-gray-700 mb-3">
            <strong>Capacity:</strong> <?= $r['capacity'] ?> |
            <strong>Occupied:</strong> <?= $r['occupants'] ?> |
            <strong>Available:</strong>
            <span class="inline-block px-2 py-1 text-white text-xs rounded <?= $badge ?>">
              <?= $available ?>
            </span>
          </p>
          <button
            class="move-btn bg-purple-700 hover:bg-purple-800 text-white font-semibold py-2 px-4 rounded shadow w-full"
            data-room-id="<?= $r['id'] ?>">
            🔁 Move / Reassign Students
          </button>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Modal -->
    <div id="moveModal"
         class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl overflow-y-auto max-h-[90vh]">
        <div id="moveContent" class="p-6"></div>
        <div class="border-t p-4 text-right">
          <button id="closeModal"
                  class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded">
            Close
          </button>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
/* open modal and load occupants */
$('.move-btn').on('click', function () {
  const roomId = $(this).data('room-id');
  $.post('load_room_students.php', {room_id: roomId}, function (html) {
    $('#moveContent').html(html);
    $('#moveModal').removeClass('hidden').addClass('flex');
  });
});

/* close modal */
$(document).on('click', '#closeModal', function () {
  $('#moveModal').addClass('hidden').removeClass('flex');
});
</script>
</body>
</html>
