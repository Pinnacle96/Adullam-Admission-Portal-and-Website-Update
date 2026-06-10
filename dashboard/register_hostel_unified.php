<?php
/* ─────────────────────────────────────────────────────────────
   register_hostel_unified.php  –  UNIFIED Hostel Registration
   Serves both NEW and RETURNING students based on cohort status
   ───────────────────────────────────────────────────────────── */
session_start();

require 'db.php';
require 'dashboard_logic.php';
require 'functions.php';

// Fetch reCAPTCHA site key from settings
$recaptcha_site_key = '';
$stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'recaptcha_site_key'");
$stmt->execute();
$result = $stmt->fetchColumn();
if ($result) {
    $recaptcha_site_key = $result;
}

/* ── protect page ──────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index');
    exit;
}

/* ── current user record ───────────────────────────────────── */
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

/* ── compose display values ────────────────────────────────── */
$full_name = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);
$email     = $user['email'] ?? '';
$phone     = $user['phone'] ?? '';
$dob       = $user['dob']   ?? '';

$age = '';
if ($dob) {
    $birth = new DateTime($dob);
    $today = new DateTime();
    $calc  = $today->diff($birth)->y;
    $age   = ($calc >= 0 && $calc <= 120) ? $calc : '';
}

/* ── determine student type based on cohort ──────────────────── */
$studentCohort = $currentCohort ?? '';  // from dashboard_logic.php
$activeCohort = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")
    ->fetchColumn() ?: 'June 2026';

$isCurrentCohort = ($studentCohort === $activeCohort);

if ($isCurrentCohort) {
    // NEW STUDENT (first semester only)
    $semester = 'First Semester';
    $studentType = 'new';
    $hostelRegistrationOpen = isHostelRegistrationOpen($pdo, 'new');
    
    /* ── avoid duplicate registration (same semester) ─────────── */
    $chk = $pdo->prepare("SELECT COUNT(*) FROM hostel_registrations
                          WHERE user_id = ? AND semester = ? AND student_type = 'new'");
    $chk->execute([$user_id, $semester]);
    $alreadyRegistered = $chk->fetchColumn() > 0;
    
    /* ── hostel-capacity check (semester-wide) ──── */
    $isHostelFull = hostelIsFull($pdo, $semester);
    
    /* −− get numbers for info banner −− */
    $capStmt = $pdo->prepare("SELECT COALESCE(SUM(capacity),0) FROM hostel_rooms
                              WHERE active = 1 AND semester = ?");
    $capStmt->execute([$semester]);
    $totalBeds = (int) $capStmt->fetchColumn();

    $occStmt = $pdo->prepare("SELECT COUNT(*) FROM hostel_registrations
                              WHERE semester = ? AND status <> 'rejected'");
    $occStmt->execute([$semester]);
    $takenBeds = (int) $occStmt->fetchColumn();

    $availableBeds = max(0, $totalBeds - $takenBeds);
    $formMode = 'new';
    
} else {
    // RETURNING STUDENT (can choose semester)
    $studentType = 'returning';
    $hostelRegistrationOpen = isHostelRegistrationOpen($pdo, 'returning');
    
    /* ---------- helper: beds still free (per semester & gender) ------ */
    $remainingBeds = function(PDO $pdo, string $semester, string $gender): int {
        $cap = $pdo->prepare("
            SELECT COALESCE(SUM(capacity),0)
            FROM hostel_rooms
            WHERE active = 1 AND semester = ? AND gender = ?
        ");
        $cap->execute([$semester, $gender]);
        $total = (int)$cap->fetchColumn();

        $occ = $pdo->prepare("
            SELECT COUNT(*)
            FROM hostel_registrations
            WHERE semester = ? AND gender = ? AND status <> 'rejected'
        ");
        $occ->execute([$semester, $gender]);
        $taken = (int)$occ->fetchColumn();

        return max($total - $taken, 0);
    };

    /* ---------- gather live numbers for UI & logic ------------------- */
    $semesters = ['First Semester', 'Second Semester'];
    $genders   = ['Male', 'Female'];

    $remaining = [];
    $totalBedsLeft = 0;

    foreach ($semesters as $sem) {
        foreach ($genders as $g) {
            $left = $remainingBeds($pdo, $sem, $g);
            $remaining[$sem][$g] = $left;
            $totalBedsLeft      += $left;
        }
    }

    $fullyFull = ($totalBedsLeft === 0);
    $formMode = 'returning';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hostel Registration</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" type="image/png" href="../assets/img/favicon.png">
<!-- reCAPTCHA v2 script -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const p = new URLSearchParams(window.location.search);

  if (p.get('success') === '1') {
    Swal.fire({
      icon: 'success',
      title: 'Registration Complete',
      text: 'Your Family-House registration has been submitted. Check your email for confirmation!',
      confirmButtonColor: '#6B21A8'
    });
  } else if (p.get('error') === '1') {
    Swal.fire({
      icon: 'warning',
      title: 'Already Registered',
      text: 'You have already submitted a hostel registration for this semester.',
      confirmButtonColor: '#6B21A8'
    });
  } else if (p.get('full') === '1') {
    Swal.fire({
      icon: 'error',
      title: 'Hostel Full',
      text: 'All rooms have been booked for this semester. Please contact admin if you believe this is an error.',
      confirmButtonColor: '#6B21A8'
    });
  } else if (p.get('closed') === '1') {
    Swal.fire({
      icon: 'warning',
      title: 'Registration Closed',
      text: 'Hostel registration is currently closed. Please check back later.',
      confirmButtonColor: '#6B21A8'
    });
  }
});

// Auto-calculate age
function calculateAge() {
  const dob = document.getElementById('dob');
  const age = document.getElementById('age');
  if (dob && dob.value) {
    const birthDate = new Date(dob.value);
    const today = new Date();
    let calculatedAge = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      calculatedAge--;
    }
    if (age) age.value = calculatedAge;
  }
}
</script>

</head>

<body class="bg-gray-100 min-h-screen">
<?php include 'components/student_sidebar.php'; ?>

<main class="max-w-5xl mx-auto p-6 bg-white shadow rounded mt-6">

<?php if ($formMode === 'new'): ?>
    <!-- ═══════════════════ NEW STUDENT MODE ═══════════════════ -->
    
    <h1 class="text-2xl font-bold text-purple-800 mb-4">🏨 Family-House Registration – First Semester</h1>

    <?php
    if (!$hostelRegistrationOpen): ?>
      <div class="bg-red-100 border-l-4 border-red-600 text-red-800 p-4 rounded mb-6">
        <p class="font-semibold">⛔ Registration Closed</p>
        <p>Hostel registration is currently closed. Please check back later or contact the hostel administrator.</p>
      </div>

    <?php elseif ($alreadyRegistered): ?>
      <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-6">
        <p class="font-semibold">⚠️ Notice</p>
        <p>You have already submitted a hostel registration for this semester.
           To amend anything, please contact the hostel administrator.</p>
      </div>

    <?php elseif ($isHostelFull): ?>
      <div class="bg-red-100 border-l-4 border-red-600 text-red-800 p-4 rounded mb-6">
        <p class="font-semibold">🚫 Hostel Full</p>
        <p>Sorry, the Family-House is fully booked for <?=htmlspecialchars($semester)?>.
           Registration is closed at the moment.</p>
      </div>

    <?php else: ?>
      <div class="bg-green-100 border-l-4 border-green-600 text-green-800 p-4 rounded mb-6">
        <p class="font-semibold">✅ Spaces Available</p>
        <p>Good news! <strong><?= $availableBeds ?></strong> out of <strong><?= $totalBeds ?></strong> beds are still available.</p>
      </div>

      <!-- ─────────────────────── FORM ─────────────────────── -->
      <form action="hostel_submit" method="POST" enctype="multipart/form-data" class="space-y-6">

        <p class="text-sm text-gray-700">
          <strong>Note:</strong> Hostel accommodation is limited to bonafide students who have
          completed screening, interview, and financial requirements. Management may re-assign rooms.
        </p>

        <!-- Hidden fields -->        <input type="hidden" name="form_source" value="dashboard" />        <input type="hidden" name="form_source" value="dashboard" />
        <input type="hidden" name="student_type" value="new" />
        <input type="hidden" name="semester" value="<?=htmlspecialchars($semester)?>"

        <!-- Personal Information -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">👤 Personal Information</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="full_name" value="<?=htmlspecialchars($full_name)?>" readonly class="bg-gray-100 px-4 py-2 border rounded w-full" />
            <input type="email" name="email" value="<?=htmlspecialchars($email)?>" readonly class="bg-gray-100 px-4 py-2 border rounded w-full" />
            <input type="text" name="contact" value="<?=htmlspecialchars($phone)?>" readonly class="bg-gray-100 px-4 py-2 border rounded w-full" />
            
            <input type="text" name="econtact" placeholder="Emergency Contact Number" required class="px-4 py-2 border rounded w-full" />
            
            <select name="gender" id="gender" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Gender</option>
              <option>Male</option>
              <option>Female</option>
            </select>

            <input type="text" name="dob" value="<?=htmlspecialchars($dob)?>" readonly class="bg-gray-100 px-4 py-2 border rounded w-full" />
            <input type="text" name="age" id="age" value="<?=htmlspecialchars($age)?>" readonly class="bg-gray-100 px-4 py-2 border rounded w-full" />

            <select name="blood" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Blood Group</option>
              <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
              <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
            </select>

            <select name="genotype" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Genotype</option>
              <option>AA</option><option>AS</option><option>AC</option>
              <option>SS</option><option>SC</option><option>CC</option>
            </select>

            <input type="text" name="allergy" placeholder="Allergies / Illness / Disability" class="px-4 py-2 border rounded w-full" />

            <select name="nationality" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Nationality</option>
              <option value="Local">Local</option>
              <option value="International">International</option>
            </select>

            <input type="text" name="state_origin" placeholder="State of Origin" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Marital Status -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">💑 Marital Status</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select name="marital" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Marital Status</option>
              <option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option>
            </select>
            <input type="text" name="sname" placeholder="Spouse Name (if married)" class="px-4 py-2 border rounded w-full" />
            <input type="text" name="scont" placeholder="Spouse Contact (if married)" class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Address Information -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">🏠 Residential Address</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="res_address" placeholder="Street Address" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="res_city" placeholder="City" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="res_state" placeholder="State" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="res_country" placeholder="Country" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Permanent Address -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">📮 Permanent Address</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="perm_address" placeholder="Street Address" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="perm_city" placeholder="City" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="perm_state" placeholder="State" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="perm_country" placeholder="Country" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Program Info -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">📚 Program Information</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="program" placeholder="Program" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="year" placeholder="Year (leave blank for new students)" class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Guardian Info -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">👨‍👩‍👧 Guardian Information</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="gname" placeholder="Guardian Name" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="grelation" placeholder="Relationship" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="gcont" placeholder="Guardian Contact" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Documents & Fees -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">📄 Documents & Payment</legend>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-semibold mb-2">Passport / ID Photo</label>
              <input type="file" name="passport" accept="image/*" class="px-4 py-2 border rounded w-full" />
            </div>
            <div>
              <label class="block text-sm font-semibold mb-2">Payment Proof</label>
              <input type="file" name="fees" accept="image/*,application/pdf" class="px-4 py-2 border rounded w-full" />
            </div>
            <div>
              <label class="block text-sm font-semibold mb-2">Amount Paid</label>
              <input type="text" name="fee" placeholder="0.00" class="px-4 py-2 border rounded w-full" />
            </div>
            <div>
              <label class="block text-sm font-semibold mb-2">Payment Date</label>
              <input type="date" name="payment_date" class="px-4 py-2 border rounded w-full" />
            </div>
          </div>
        </fieldset>

        <!-- Mattress & Declaration -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">✅ Confirmation</legend>
          <div class="space-y-4">
            <label class="flex items-center">
              <input type="checkbox" name="has_mattress" class="mr-3" />
              <span>I have a student mattress ready for inspection</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="confirm_born_again" required class="mr-3" />
              <span>I confirm I am a born-again Christian</span>
            </label>
          </div>
        </fieldset>

        <?php if ($recaptcha_site_key): ?>
            <!-- reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptcha_site_key) ?>"></div>
        <?php endif; ?>

        <button type="submit" class="w-full bg-purple-700 hover:bg-purple-800 text-white font-bold py-3 px-4 rounded">
          Submit Registration
        </button>
      </form>
    <?php endif; ?>

<?php else: ?>
    <!-- ═══════════════════ RETURNING STUDENT MODE ═══════════════════ -->
    
    <h1 class="text-2xl font-bold text-purple-800 mb-4">🏨 Family-House Registration – Returning Student</h1>

    <?php if (!$hostelRegistrationOpen): ?>
      <div class="bg-red-100 border-l-4 border-red-600 text-red-800 p-4 rounded">
        <p class="font-semibold">⛔ Registration Closed</p>
        <p>Hostel registration is currently closed. Please check back later or contact the hostel administrator.</p>
      </div>

    <?php elseif ($fullyFull): ?>
      <div class="bg-red-100 border-l-4 border-red-600 text-red-800 p-4 rounded">
        <p class="font-semibold">⛔ Hostel Capacity Reached</p>
        <p>All beds for both semesters are currently taken. Please check back
           later or contact the hostel administrator.</p>
      </div>

    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
      <?php foreach ($semesters as $sem): ?>
        <div class="bg-purple-50 border-l-4 border-purple-600 p-3 rounded">
          <p class="font-semibold text-purple-800"><?=$sem?></p>
          <p class="text-sm">
            Male beds left: <strong><?=$remaining[$sem]['Male']?></strong><br>
            Female beds left: <strong><?=$remaining[$sem]['Female']?></strong>
          </p>
        </div>
      <?php endforeach; ?>
      </div>

      <p class="text-sm text-gray-700 mb-6">
        <strong>Note:</strong> Hostel accommodation is strictly limited to bonafide
        students who have completed screening and met financial requirements.
        Management may re-assign rooms whenever necessary.
      </p>

      <form action="hostel_submit" method="POST" enctype="multipart/form-data" class="space-y-6">

        <!-- Hidden fields -->
        <input type="hidden" name="student_type" value="returning" />

        <!-- Semester Selector (VISIBLE FOR RETURNING STUDENTS) -->
        <fieldset class="border border-purple-300 p-4 rounded bg-purple-50">
          <legend class="font-semibold text-purple-700">📅 Select Semester</legend>
          <select name="semester" id="semester" required class="px-4 py-2 border rounded w-full">
            <option value="">Choose Semester</option>
            <option>First Semester</option>
            <option>Second Semester</option>
          </select>
        </fieldset>

        <!-- Personal Information -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">👤 Personal Information</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="full_name" placeholder="Full Name" required class="px-4 py-2 border rounded w-full" />
            <input type="email" name="email" placeholder="Email Address" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="contact" placeholder="WhatsApp Number" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="econtact" placeholder="Emergency Contact Number" required class="px-4 py-2 border rounded w-full" />

            <select name="gender" id="gender" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Gender</option>
              <option>Male</option><option>Female</option>
            </select>

            <select name="blood" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Blood Group</option>
              <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
              <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
            </select>

            <select name="genotype" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Genotype</option>
              <option>AA</option><option>AS</option><option>AC</option>
              <option>SS</option><option>SC</option><option>CC</option>
            </select>

            <input type="text" name="allergy" placeholder="Allergies / Illness / Disability" required class="px-4 py-2 border rounded w-full" />

            <label class="block w-full">
              <span class="text-sm text-gray-600">Date of Birth</span>
              <input type="date" name="dob" id="dob" onchange="calculateAge()" class="px-4 py-2 border rounded w-full mt-1" required />
            </label>

            <label class="block w-full">
              <span class="text-sm text-gray-600">Age</span>
              <input type="text" name="age" id="age" readonly class="bg-gray-100 px-4 py-2 border rounded w-full mt-1" placeholder="Age" />
            </label>

            <select name="nationality" id="nationality" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Nationality</option>
              <option value="Local">Local</option>
              <option value="International">International</option>
            </select>

            <input type="text" name="state_origin" id="stateOrigin" placeholder="State of Origin" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Marital Status -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">💑 Marital Status</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select name="marital" required class="px-4 py-2 border rounded w-full">
              <option value="">Select Marital Status</option>
              <option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option>
            </select>
            <input type="text" name="sname" placeholder="Spouse Name (if married)" class="px-4 py-2 border rounded w-full" />
            <input type="text" name="scont" placeholder="Spouse Contact (if married)" class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Address Information -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">🏠 Residential Address</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="res_address" id="res_address" placeholder="Residential Address" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="res_city" id="res_city" placeholder="Residential City" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="res_state" id="res_state" placeholder="Residential State" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="res_country" id="res_country" placeholder="Residential Country" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Permanent Address -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">📮 Permanent Address</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="perm_address" id="perm_address" placeholder="Permanent Address" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="perm_city" id="perm_city" placeholder="Permanent City" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="perm_state" id="perm_state" placeholder="Permanent State" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="perm_country" id="perm_country" placeholder="Permanent Country" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Program Info -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">📚 Program Information</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="program" placeholder="Program" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="year" placeholder="Year of Study" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Guardian Info -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">👨‍👩‍👧 Guardian Information</legend>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="gname" placeholder="Guardian Name" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="grelation" placeholder="Relationship" required class="px-4 py-2 border rounded w-full" />
            <input type="text" name="gcont" placeholder="Guardian Contact" required class="px-4 py-2 border rounded w-full" />
          </div>
        </fieldset>

        <!-- Documents & Fees -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">📄 Documents & Payment</legend>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-semibold mb-2">Passport / ID Photo</label>
              <input type="file" name="passport" accept="image/*" class="px-4 py-2 border rounded w-full" />
            </div>
            <div>
              <label class="block text-sm font-semibold mb-2">Payment Proof</label>
              <input type="file" name="fees" accept="image/*,application/pdf" class="px-4 py-2 border rounded w-full" />
            </div>
            <div>
              <label class="block text-sm font-semibold mb-2">Amount Paid</label>
              <input type="text" name="fee" placeholder="0.00" class="px-4 py-2 border rounded w-full" />
            </div>
            <div>
              <label class="block text-sm font-semibold mb-2">Payment Date</label>
              <input type="date" name="payment_date" class="px-4 py-2 border rounded w-full" />
            </div>
          </div>
        </fieldset>

        <!-- Mattress & Declaration -->
        <fieldset class="border border-gray-300 p-4 rounded">
          <legend class="font-semibold text-purple-700">✅ Confirmation</legend>
          <div class="space-y-4">
            <label class="flex items-center">
              <input type="checkbox" name="has_mattress" class="mr-3" />
              <span>I have a student mattress ready for inspection</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="confirm_born_again" required class="mr-3" />
              <span>I confirm I am a born-again Christian</span>
            </label>
          </div>
        </fieldset>

        <?php if ($recaptcha_site_key): ?>
            <!-- reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptcha_site_key) ?>"></div>
        <?php endif; ?>

        <button type="submit" class="w-full bg-purple-700 hover:bg-purple-800 text-white font-bold py-3 px-4 rounded">
          Submit Registration
        </button>
      </form>
    <?php endif; ?>

<?php endif; ?>

</main>
</body>
</html>
