<?php
session_start();
require 'db.php';
require 'mailer.php';
require_once 'utils/send_admission_email_new.php';

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
        "SELECT u.id,u.first_name,u.last_name,u.email,
                d.program,d.ma_focus,d.mode_of_study
         FROM users u
         JOIN application_details d ON d.user_id = u.id
         WHERE u.role='student'
           AND (u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
         ORDER BY u.first_name LIMIT 50"
    );
    $q = '%' . $search . '%';
    $stmt->execute([$q, $q, $q]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ───────────  UPDATE  ─────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $uid      = (int)$_POST['user_id'];
    $newProg  = trim($_POST['program'] ?? '');
    $newFocus = trim($_POST['ma_focus'] ?? '');
    $newMode  = trim($_POST['mode_of_study'] ?? '');

    $cur = $pdo->prepare("SELECT program,ma_focus,mode_of_study,email,first_name
                          FROM application_details
                          JOIN users ON users.id = application_details.user_id
                          WHERE user_id = ?");
    $cur->execute([$uid]);
    $cur = $cur->fetch(PDO::FETCH_ASSOC);

    if (!$cur) {
        $error = 'Applicant not found.';
    } else {
        $set  = [];
        $bind = [':id' => $uid];

        if ($newProg && $newProg !== $cur['program']) {
            $set[] = "program=:pr";
            $bind[':pr'] = $newProg;
            if ($newProg !== 'MA') {
                $set[] = "ma_focus=NULL";
            }
        }

        if ($newProg === 'MA' && $newFocus && $newFocus !== $cur['ma_focus']) {
            $set[] = "ma_focus=:mf";
            $bind[':mf'] = $newFocus;
        }

        if ($newMode && $newMode !== $cur['mode_of_study']) {
            $set[] = "mode_of_study=:md";
            $bind[':md'] = $newMode;
        }

        if ($set) {
            $sql = "UPDATE application_details SET " . implode(', ', $set) . " WHERE user_id=:id";
            $pdo->prepare($sql)->execute($bind);

            // ✅ Fetch updated values for email (fixes MA Focus logic)
            $updated = $pdo->prepare("SELECT program, ma_focus, mode_of_study, email, first_name
                                      FROM application_details
                                      JOIN users ON users.id = application_details.user_id
                                      WHERE user_id = ?");
            $updated->execute([$uid]);
            $updated = $updated->fetch(PDO::FETCH_ASSOC);

            $email   = $updated['email'];
            $name    = $updated['first_name'];
            $progCode= $updated['program'];
            $focus   = $updated['ma_focus'];
            $mode    = $updated['mode_of_study'];

           /* -------------- build & send e-mail -------------- */
$programNames = [
    'MA'         => 'Master of Arts',
    'B.Div'      => 'Bachelor of Divinity',
    'PGDT'       => 'Post-graduate Diploma in Theology',
    'Diploma'    => 'Diploma in Theology',
    'Certificate'=> 'Certificate in Theology'
];

$progTxt   = $programNames[$progCode] ?? $progCode;
$modeTxt   = $mode;
$focusRow  = '';

if ($progCode === 'MA') {
    $focusTxt = $focus ?: '—';
    $focusRow = "<br><strong>MA Focus:</strong> $focusTxt";
}

$subject = "🎓 Your Adullam study details were updated";
$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$logo = "https://adullam.ng/assets/img/logo1.png";

$body = "
<div style='font-family:Segoe UI,Arial,Helvetica,sans-serif;max-width:600px;
            margin:auto;padding:24px;background:#fff;border:1px solid #ddd;border-radius:10px'>
  <div style='text-align:center;margin-bottom:20px'>
    <img src='$logo' alt='Adullam Seminary' style='height:60px'><br>
    <h2 style='color:#6B21A8;margin-top:10px'>Adullam Seminary Admissions</h2>
  </div>

  <p style='font-size:16px;color:#111'>Dear <strong>$name</strong>,</p>

  <p style='font-size:15px;line-height:1.6;color:#333'>
    Your study details were updated by an administrator:
    <br><strong>Program:</strong> $progTxt
    $focusRow
    <br><strong>Mode:</strong> $modeTxt
  </p>

  <p style='font-size:15px;color:#333;line-height:1.6'>
    If you did not expect this change, please contact the admissions office immediately.
  </p>

  <div style='text-align:center;margin:24px 0'>
    <a href='https://adullam.ng/dashboard/' style='background:#6B21A8;color:#fff;padding:12px 24px;
       border-radius:6px;text-decoration:none;font-weight:bold'>
      🔗 Open Student Portal
    </a>
  </div>

  <hr style='border:none;border-top:1px solid #ddd;margin:20px 0'>
  <p style='font-size:13px;color:#888;text-align:center'>&copy; " . date('Y') . " Adullam Seminary</p>
</div>";

sendMail($email, $name, $subject, $body);

            // ✅ Automatically generate and send a new admission letter with updated info
            sendAdmissionEmail($uid, $pdo);
        }

        $success = true;
        if ($search !== '') {
            header("Location: ?search=" . urlencode($search) . "&saved=1");
            exit;
        }
    }
}

if (isset($_GET['saved'])) {
    $success = true;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Update Applicant Program</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" type="image/png" href="../assets/img/favicon.png">
<style>
  .ma-focus-container {
    display: none;
  }
</style>
</head>

<body class="bg-gray-100 min-h-screen">
<?php include 'components/navbar.php'; ?>
<div class="flex">
<?php include 'components/sidebar.php'; ?>

<main class="flex-1 p-6 max-w-4xl mx-auto">
  <h1 class="text-2xl font-bold text-purple-800 mb-4">📝 Update Applicant Program / Mode</h1>

<?php if ($success): ?>
<script>
Swal.fire({icon:'success',title:'Saved',text:'Details updated & e-mail sent.',timer:2500,showConfirmButton:false});
</script>
<?php elseif ($error): ?>
<div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

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
    <form method="POST" class="grid gap-2 lg:grid-cols-4 items-center">
      <input type="hidden" name="user_id" value="<?=$r['id']?>">
      <div class="lg:col-span-1 font-medium">
        <?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?><br>
        <span class="text-sm text-gray-600"><?=htmlspecialchars($r['email'])?></span>
      </div>

      <!-- Program -->
      <!--<label class="text-sm font-medium text-gray-700">Program</label>-->
      <select name="program" class="p-2 border rounded w-full program-select focus:outline-none focus:ring-2 focus:ring-purple-500" required>
        <option value="">(keep: <?= htmlspecialchars($r['program'] ?: 'N/A') ?>)</option>
        <option value="MA">Master of Arts</option>
        <option value="B.Div">Bachelor of Divinity</option>
        <option value="PGDT">Postgraduate Diploma in Theology</option>
        <option value="Diploma">Diploma in Theology</option>
        <option value="Certificate">Certificate in Theology</option>
      </select>

      <!-- MA Focus -->
      <div class="ma-focus-container mt-2">
        <!--<label class="text-sm font-medium text-gray-700">MA Focus</label>-->
        <select name="ma_focus" class="p-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
          <option value="">(keep: <?= htmlspecialchars($r['ma_focus'] ?: 'N/A') ?>)</option>
          <option value="MA Christian Apologetics">MA Christian Apologetics</option>
          <option value="MA Biblical Studies (OT/NT)">MA Biblical Studies (OT/NT)</option>
        </select>
      </div>

      <!-- Mode -->
      <select name="mode_of_study" class="p-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-purple-500">
        <option value="">(keep: <?=htmlspecialchars($r['mode_of_study'] ?: 'N/A')?>)</option>
        <option value="online">online</option>
        <option value="onsite">onsite</option>
      </select>

      <button class="mt-2 lg:mt-0 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">💾 Save</button>
    </form>
  </div>
 <?php endforeach; ?>
</div>
<?php elseif ($search): ?>
  <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded">No applicants found for that query.</div>
<?php endif; ?>
</main>
</div>

<script>
document.querySelectorAll('form').forEach(form => {
  const programSelect = form.querySelector('.program-select');
  const maFocusContainer = form.querySelector('.ma-focus-container');

  const toggleMaFocus = () => {
    if (programSelect && maFocusContainer) {
      if (programSelect.value === 'MA') {
        maFocusContainer.style.display = 'block';
      } else {
        maFocusContainer.style.display = 'none';
        const focusSelect = maFocusContainer.querySelector('select');
        if (focusSelect) focusSelect.value = '';
      }
    }
  };

  if (programSelect && maFocusContainer) {
    programSelect.addEventListener('change', toggleMaFocus);
    toggleMaFocus(); // initial check
  }
});
</script>

</body>
</html>
