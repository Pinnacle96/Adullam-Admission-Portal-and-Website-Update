<?php
/* ─────────────────────────────────────────────────────────────
   register_hostel.php  –  NEW-Student Hostel Registration Form
   ───────────────────────────────────────────────────────────── */
session_start();

require 'db.php';
require 'dashboard_logic.php';
require 'functions.php';          // ✅ contains hostelIsFull()

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

/* ── avoid duplicate registration (same semester) ─────────── */
$semester = 'First Semester';
$chk = $pdo->prepare("SELECT COUNT(*) FROM hostel_registrations
                      WHERE user_id = ? AND semester = ? AND student_type = 'new'");
$chk->execute([$user_id, $semester]);
$alreadyRegistered = $chk->fetchColumn() > 0;

/* ── hostel-capacity check (semester-wide, both genders) ──── */
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hostel Registration – New Students</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!--  SweetAlert for ?success / ?error / ?full ----------------------- -->
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
  }
});
</script>

</head>

<body class="bg-gray-100 min-h-screen">
<?php include 'components/student_sidebar.php'; ?>

<main class="max-w-5xl mx-auto p-6 bg-white shadow rounded mt-6">
<h1 class="text-2xl font-bold text-purple-800 mb-4">🏨 Family-House Registration – New Students</h1>

<?php
/* ───────────  INFO / BLOCK MESSAGES  ─────────── */
if ($alreadyRegistered): ?>
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

    <!-- PERSONAL INFO -->
    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">👤 Personal Info</legend>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <input type="text" name="full_name" value="<?=htmlspecialchars($full_name)?>"
               readonly class="bg-gray-100 px-4 py-2 border rounded w-full" placeholder="Full Name">

        <input type="email" name="email" value="<?=htmlspecialchars($email)?>"
               readonly class="bg-gray-100 px-4 py-2 border rounded w-full" placeholder="Email">

        <input type="text"  name="contact"  value="<?=htmlspecialchars($phone)?>" placeholder="WhatsApp Number"
               required class="px-4 py-2 border rounded w-full">

        <input type="text"  name="econtact" placeholder="Emergency Contact Number"
               required class="px-4 py-2 border rounded w-full">

        <select name="gender" required class="px-4 py-2 border rounded w-full">
          <option value="">Select Gender</option><option>Male</option><option>Female</option>
        </select>

        <!-- blood & genotype select -->
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

        <input type="text" name="allergy" placeholder="Allergies / Illness / Disability"
               class="px-4 py-2 border rounded w-full">

        <!-- DOB with label -->
        <div class="flex flex-col w-full">
          <label for="dob" class="text-xs text-gray-600 mb-1">Date of Birth</label>
          <input type="date" name="dob" id="dob" value="<?=htmlspecialchars($dob)?>"
                 required class="px-4 py-2 border rounded w-full">
        </div>

        <input type="text" name="age" id="age" value="<?=$age?>" readonly
               class="bg-gray-100 px-4 py-2 border rounded w-full" placeholder="Age">

        <!-- Nationality & origin -->
        <select name="nationality" id="nationality" required class="px-4 py-2 border rounded w-full">
          <option value="">Select Nationality</option>
          <option>Local</option><option>International</option>
        </select>

        <input type="text" name="state_origin" id="state_origin" placeholder="State of Origin"
               required class="px-4 py-2 border rounded w-full">
      </div>
    </fieldset>

   <!-- ADDRESS DETAILS -->
<fieldset class="border border-gray-300 p-4 rounded">
 <legend class="font-semibold text-purple-700">🏠 Address Details</legend>

 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
   <input type="text" name="res_address" id="res_address" placeholder="Residential Address"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="res_city" id="res_city" placeholder="Residential City"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="res_state" id="res_state" placeholder="Residential State"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="res_country" id="res_country" placeholder="Residential Country"
          required class="px-4 py-2 border rounded w-full">

   <div class="sm:col-span-2">
     <label class="inline-flex items-center">
       <input type="checkbox" id="same_address" class="mr-2">
       Use same address as residential for permanent address
     </label>
   </div>

   <input type="text" name="perm_address" id="perm_address" placeholder="Permanent Address"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="perm_city" id="perm_city" placeholder="Permanent City"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="perm_state" id="perm_state" placeholder="Permanent State"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="perm_country" id="perm_country" placeholder="Permanent Country"
          required class="px-4 py-2 border rounded w-full">
 </div>
</fieldset>

<!-- GUARDIAN INFO (unchanged) -->
<fieldset class="border border-gray-300 p-4 rounded">
 <legend class="font-semibold text-purple-700">👪 Guardian Information</legend>
 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
   <input type="text" name="gname" placeholder="Guardian's Full Name"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="grelation" placeholder="Relationship (e.g., Father, Aunt)"
          required class="px-4 py-2 border rounded w-full">
   <input type="text" name="gcontact" placeholder="Guardian's Contact Phone"
          required class="px-4 py-2 border rounded w-full">
 </div>
</fieldset>

<!-- MARITAL -->
<fieldset class="border border-gray-300 p-4 rounded">
 <legend class="font-semibold text-purple-700">💍 Marital Info</legend>

 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
   <select name="marital" id="marital" required class="px-4 py-2 border rounded w-full">
     <option value="">Marital Status</option><option>Single</option><option>Married</option>
   </select>

   <input type="text" name="sname" id="sname" placeholder="Spouse Name (if married)"
          class="px-4 py-2 border rounded w-full hidden">
   <input type="text" name="scont" id="scont" placeholder="Spouse Contact (if married)"
          class="px-4 py-2 border rounded w-full hidden">
 </div>
</fieldset>

<!-- STUDENTSHIP -->
<fieldset class="border border-gray-300 p-4 rounded">
 <legend class="font-semibold text-purple-700">🎓 Studentship Info</legend>

 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
   <select name="program" required class="px-4 py-2 border rounded w-full">
     <option value="">-- Select Program --</option>
     <option>Certificate</option><option>Diploma</option>
     <option>B.Div</option><option>PGDT</option>
   </select>

   <input type="text" name="semester" value="First Semester" readonly
          class="bg-gray-100 px-4 py-2 border rounded w-full">
 </div>
</fieldset>

<!-- UPLOADS -->
<fieldset class="border border-gray-300 p-4 rounded">
 <legend class="font-semibold text-purple-700">🧾 Uploads</legend>

 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
   <div class="w-full">
     <label class="block text-xs text-gray-600 mb-1">Passport Photo (min 1 MB)</label>
     <input type="file" name="passport" accept=".jpg,.jpeg,.png,.pdf"
            required class="w-full px-4 py-2 border rounded">
   </div>

   <div class="w-full">
     <label class="block text-xs text-gray-600 mb-1">Proof of Hostel Fee Payment</label>
     <input type="file" name="fees" accept=".jpg,.jpeg,.png,.pdf"
            required class="w-full px-4 py-2 border rounded">
   </div>

   <input type="text" name="fee" placeholder="Amount Paid (₦)"
          required class="px-4 py-2 border rounded w-full">

   <div class="flex flex-col w-full">
     <label class="text-xs text-gray-600 mb-1">Date of Payment</label>
     <input type="date" name="payment_date" required
            class="px-4 py-2 border rounded w-full">
   </div>
 </div>
</fieldset>

<!-- DECLARATION -->
<fieldset class="border border-gray-300 p-4 rounded">
 <legend class="font-semibold text-purple-700">📝 Declaration</legend>

 <div class="space-y-2">
   <label class="inline-flex items-center">
     <input type="checkbox" name="has_mattress" value="yes" class="mr-2" required>
     I can present a student mattress upon resumption.
   </label>

   <p class="text-sm text-gray-700">
          <strong>Note:</strong> Students occupying Adullam hostel facilities are
          required to provide their own student-sized mattress.
        </p>

   <label class="inline-flex items-center">
     <input type="checkbox" name="confirm_born_again" value="yes" class="mr-2" required>
     I am born again and agree to all hostel rules (fees non-refundable).
   </label>
 </div>
</fieldset>


    <input type="hidden" name="student_type" value="new">
    <input type="hidden" name="year" value="1st Year">

    <button type="submit" name="submit"
            class="w-full bg-purple-700 text-white py-3 rounded shadow hover:bg-purple-800">
      📨 Submit Hostel Registration
    </button>
  </form>
<?php endif; /* (alreadyRegistered / hostelFull / form) */ ?>

</main>

<!-- ───────────── Enhancements (unchanged JS) ───────────── -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  /* age auto-calc */
  const dob  = document.getElementById('dob');
  const age  = document.getElementById('age');
  if (dob && age) {
    dob.addEventListener('change', () => {
      const d = new Date(dob.value), t = new Date();
      let a = t.getFullYear() - d.getFullYear();
      if (t.getMonth() < d.getMonth() || (t.getMonth() === d.getMonth() && t.getDate() < d.getDate())) a--;
      age.value = (a >= 0 && a <= 120) ? a : '';
    });
  }

  /* nationality placeholder swap */
  const nat    = document.getElementById('nationality');
  const origin = document.getElementById('state_origin');
  if (nat && origin) {
    const swap = () =>
      origin.placeholder = nat.value === 'International' ? 'Country of Origin' : 'State of Origin';
    nat.addEventListener('change', swap);
    swap();
  }

  /* copy address */
  const same = document.getElementById('same_address');
  if (same) {
    same.addEventListener('change', e => {
      const c = e.target.checked;
      ['address','city','state','country'].forEach(f => {
        const src = document.getElementById(`res_${f}`);
        const dst = document.getElementById(`perm_${f}`);
        if (src && dst) dst.value = c ? src.value : '';
      });
    });
  }

  /* marital toggle */
  const marital = document.getElementById('marital');
  const sname   = document.getElementById('sname');
  const scont   = document.getElementById('scont');
  if (marital && sname && scont) {
    const toggle = () => {
      const married = marital.value === 'Married';
      [sname, scont].forEach(el => {
        el.classList.toggle('hidden', !married);
        el.required = married;
      });
    };
    marital.addEventListener('change', toggle);
    toggle();
  }
});
</script>
</body>
</html>
