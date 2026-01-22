<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin','admin'])) {
    die('Unauthorized access.');
}

$search  = $_GET['search'] ?? '';
$success = false;
$error   = '';

/* ───────────  SEARCH  ─────────── */
$results = [];
if ($search) {
    $stmt = $pdo->prepare(
        "SELECT id, first_name, last_name, email
         FROM users
         WHERE role='student' AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
         ORDER BY first_name LIMIT 50"
    );
    $q = '%' . $search . '%';
    $stmt->execute([$q, $q, $q]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ───────────  EMAIL UPDATE  ─────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_email'])) {
    $uid       = (int)$_POST['user_id'];
    $new_email = trim($_POST['new_email']);

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $check->execute([$uid]);
            $user = $check->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "User not found.";
            } elseif ($user['email'] === $new_email) {
                $error = "Same email provided, no changes made.";
            } else {
                $update = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update->execute([$new_email, $uid]);

                $log = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, detail, created_at)
                                      VALUES (?, 'update_email', ?, NOW())");
                $log->execute([
                    $_SESSION['user_id'],
                    "Changed email for user ID $uid to $new_email"
                ]);

                $success = true;
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error updating email: " . $e->getMessage();
        }
    }

    if ($search !== '') {
        header("Location: ?search=" . urlencode($search) . "&saved=" . ($success ? '1' : '0') . "&error=" . urlencode($error));
        exit;
    }
}

if (isset($_GET['saved'])) {
    $success = ($_GET['saved'] === '1');
    $error = $_GET['error'] ?? '';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Change Applicant Email</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="icon" type="image/png" href="../assets/img/favicon.png">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 min-h-screen">
<?php include 'components/navbar.php'; ?>
<div class="flex">
<?php include 'components/sidebar.php'; ?>

<main class="flex-1 p-6 max-w-4xl mx-auto">
  <h1 class="text-2xl font-bold text-purple-800 mb-4">✉️ Change Applicant Email</h1>

  <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="<?=htmlspecialchars($search)?>"
             placeholder="Search by name or email"
             class="flex-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
      <button class="bg-purple-700 text-white px-4 py-2 rounded hover:bg-purple-800">Search</button>
  </form>

  <?php if ($results): ?>
  <div class="bg-white rounded-lg shadow p-4">
   <h2 class="text-lg font-semibold text-purple-700 mb-2">Search Results:</h2>

   <?php foreach ($results as $r): ?>
    <div class="border-b py-3 last:border-0">
      <form method="POST" class="grid gap-3 lg:grid-cols-3 items-center">
        <input type="hidden" name="user_id" value="<?=$r['id']?>">
        <div>
          <strong><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></strong><br>
          <span class="text-sm text-gray-600">Current: <?=htmlspecialchars($r['email'])?></span>
        </div>
        <input type="email" name="new_email" placeholder="Enter new email" required
               class="p-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
        <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">💾 Update</button>
      </form>
    </div>
   <?php endforeach; ?>
  </div>
  <?php elseif ($search): ?>
    <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded">No users found for that query.</div>
  <?php endif; ?>
</main>
</div>

<script>
<?php if ($success): ?>
Swal.fire({
  icon: 'success',
  title: 'Email Updated!',
  text: 'The user\'s email was successfully changed.',
  confirmButtonColor: '#6366F1'
});
<?php elseif (!empty($error)): ?>
Swal.fire({
  icon: 'error',
  title: 'Error!',
  text: <?= json_encode($error) ?>,
  confirmButtonColor: '#EF4444'
});
<?php endif; ?>
</script>

</body>
</html>
