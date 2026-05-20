<?php
/* ─────────────────────────────────────────────────────────────
   register_hostel_returning.php  –  RETURNING-Student form
   ───────────────────────────────────────────────────────────── */
require 'db.php';          // $pdo
require 'functions.php';   // hostelIsFull()  +  remainingBeds()

/* ---------- helper: beds still free (per semester & gender) ------ */
function remainingBeds(PDO $pdo, string $semester, string $gender): int
{
    // capacity
    $cap = $pdo->prepare(
        "SELECT COALESCE(SUM(capacity),0)
         FROM hostel_rooms
         WHERE active = 1 AND semester = ? AND gender = ?"
    );
    $cap->execute([$semester, $gender]);
    $total = (int)$cap->fetchColumn();

    // already booked  (pending+approved)
    $occ = $pdo->prepare(
        "SELECT COUNT(*)
         FROM hostel_registrations
         WHERE semester = ? AND gender = ? AND status <> 'rejected'"
    );
    $occ->execute([$semester, $gender]);
    $taken = (int)$occ->fetchColumn();

    return max($total - $taken, 0);
}

/* ---------- gather live numbers for UI & logic ------------------- */
$semesters = ['First Semester', 'Second Semester'];
$genders   = ['Male', 'Female'];

$remaining = [];   // $remaining['First Semester']['Male'] = 3 …
$totalBedsLeft = 0;

foreach ($semesters as $sem) {
    foreach ($genders as $g) {
        $left = remainingBeds($pdo, $sem, $g);
        $remaining[$sem][$g] = $left;
        $totalBedsLeft      += $left;
    }
}

$fullyFull = ($totalBedsLeft === 0);   // nothing left at all
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Hostel Registration – Returning Student</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!--  SweetAlert for ?success / ?error / ?full  --------------------- -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const p = new URLSearchParams(window.location.search);

    if (p.get('success') === '1') {
      Swal.fire({
        icon: 'success',
        title: 'Registration Complete',
        text: 'Your Family House registration has been successfully submitted. Check your email for confirmation!',
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
        text: 'Sorry, all available spaces have been reserved for this semester. Please contact admin for assistance.',
        confirmButtonColor: '#6B21A8'
      });
    }
  });
</script>

</head>
<body class="bg-gray-100 min-h-screen">

<main class="max-w-5xl mx-auto p-6 bg-white shadow rounded mt-10">

  <h1 class="text-2xl font-bold text-purple-800 mb-4">
    🏨 Family-House Registration – Returning Students
  </h1>

<?php if ($fullyFull): /* —— hostel TOTALLY full —————————— */ ?>
  <div class="bg-red-100 border-l-4 border-red-600 text-red-800 p-4 rounded">
    <p class="font-semibold">⛔ Hostel Capacity Reached</p>
    <p>All beds for both semesters are currently taken. Please check back
       later or contact the hostel administrator.</p>
  </div>

<?php else: /* —— still space somewhere → show dashboard + form —— */ ?>

  <!-- live capacity dashboard -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
  <?php foreach ($semesters as $sem): ?>
    <div class="bg-purple-50 border-l-4 border-purple-600 p-3 rounded">
      <p class="font-semibold text-purple-800"><?=$sem?></p>
      <p class="text-sm">
        Male beds left:
        <strong><?=$remaining[$sem]['Male']?></strong><br>
        Female beds left:
        <strong><?=$remaining[$sem]['Female']?></strong>
      </p>
    </div>
  <?php endforeach; ?>
  </div>

  <p class="text-sm text-gray-700 mb-6">
    <strong>Note:</strong> Hostel accommodation is strictly limited to bonafide
    students who have completed screening and met financial requirements.
    Management may re-assign rooms whenever necessary.
  </p>

  <!-- ───────────────────────── FORM ───────────────────────── -->
  <form id="regForm"
        action="hostel_submit.php"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6">

    <!-- PERSONAL INFO ------------------------------------------------>
    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">👤 Personal Info</legend>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="text"   name="full_name" placeholder="Full Name" required
               class="px-4 py-2 border rounded w-full" />
        <input type="email"  name="email"     placeholder="Email Address" required
               class="px-4 py-2 border rounded w-full" />
        <input type="text"   name="contact"   placeholder="WhatsApp Number" required
               class="px-4 py-2 border rounded w-full" />
        <input type="text"   name="econtact"  placeholder="Emergency Contact Number" required
               class="px-4 py-2 border rounded w-full" />

        <!-- Gender -->
        <select name="gender" id="gender" required
                class="px-4 py-2 border rounded w-full">
          <option value="">Select Gender</option>
          <option>Male</option><option>Female</option>
        </select>

        <!-- Blood & Genotype -->
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
               required class="px-4 py-2 border rounded w-full" />

        <!-- DOB & Age -->
        <label class="block w-full">
          <span class="text-sm text-gray-600">Date of Birth</span>
          <input type="date" name="dob" id="dob"
                 class="px-4 py-2 border rounded w-full mt-1" required />
        </label>

        <label class="block w-full">
          <span class="text-sm text-gray-600">Age</span>
          <input type="text" name="age" id="age" readonly
                 class="bg-gray-100 px-4 py-2 border rounded w-full mt-1"
                 placeholder="Age" />
        </label>

        <!-- Nationality / Origin -->
        <select name="nationality" id="nationality" required
                class="px-4 py-2 border rounded w-full">
          <option value="">Select Nationality</option>
          <option value="Local">Local</option>
          <option value="International">International</option>
        </select>

        <input type="text" name="state_origin" id="stateOrigin"
               placeholder="State of Origin" required
               class="px-4 py-2 border rounded w-full" />
      </div>
    </fieldset>

    <!-- ADDRESS ------------------------------------------------------->
      <!-- ── ADDRESS INFO ────────────────────────────────────── -->
<fieldset class="border border-gray-300 p-4 rounded">
  <legend class="font-semibold text-purple-700">🏠 Address Information</legend>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Residential Address -->
    <div class="col-span-1 w-full">
      <input type="text" name="res_address" id="res_address" placeholder="Residential Address"
             required class="px-4 py-2 border rounded w-full" />
    </div>
    <div class="col-span-1 w-full">
      <input type="text" name="res_city" id="res_city" placeholder="Residential City"
             required class="px-4 py-2 border rounded w-full" />
    </div>
    <div class="col-span-1 w-full">
      <input type="text" name="res_state" id="res_state" placeholder="Residential State"
             required class="px-4 py-2 border rounded w-full" />
    </div>
    <div class="col-span-1 w-full">
      <input type="text" name="res_country" id="res_country" placeholder="Residential Country"
             required class="px-4 py-2 border rounded w-full" />
    </div>

    <!-- Copy address -->
    <div class="col-span-1 md:col-span-2">
      <label class="inline-flex items-center">
        <input type="checkbox" id="same_address" class="mr-2">
        Use same address as residential for permanent address
      </label>
    </div>

    <!-- Permanent Address -->
    <div class="col-span-1 w-full">
      <input type="text" name="perm_address" id="perm_address" placeholder="Permanent Address"
             required class="px-4 py-2 border rounded w-full" />
    </div>
    <div class="col-span-1 w-full">
      <input type="text" name="perm_city" id="perm_city" placeholder="Permanent City"
             required class="px-4 py-2 border rounded w-full" />
    </div>
    <div class="col-span-1 w-full">
      <input type="text" name="perm_state" id="perm_state" placeholder="Permanent State"
             required class="px-4 py-2 border rounded w-full" />
    </div>
    <div class="col-span-1 w-full">
      <input type="text" name="perm_country" id="perm_country" placeholder="Permanent Country"
             required class="px-4 py-2 border rounded w-full" />
    </div>
  </div>
</fieldset>


    <!-- ── GUARDIAN INFO (separate) ────────────────────────── -->
    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">👪 Guardian Information</legend>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="text" name="gname" placeholder="Guardian's Full Name" required
               class="px-4 py-2 border rounded w-full" />
        <input type="text" name="grelation" placeholder="Relationship (e.g., Father, Aunt)" required
               class="px-4 py-2 border rounded w-full" />
        <input type="text" name="gcontact" placeholder="Guardian's Contact Phone" required
               class="px-4 py-2 border rounded w-full" />
      </div>
    </fieldset>

    <!-- ── MARITAL INFO ────────────────────────────────────── -->
    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">💍 Marital Info</legend>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <select name="marital" id="marital" required class="px-4 py-2 border rounded w-full">
          <option value="">Marital Status</option>
          <option>Single</option>
          <option>Married</option>
        </select>

        <!-- Spouse fields: hidden unless “Married” -->
        <div id="spouseGroup" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:col-span-2 hidden">
          <input type="text" name="sname" id="sname" placeholder="Spouse Name"
                 class="px-4 py-2 border rounded w-full" />
          <input type="text" name="scont" id="scont" placeholder="Spouse Contact"
                 class="px-4 py-2 border rounded w-full" />
        </div>
      </div>
    </fieldset>

    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">🎓 Studentship Info</legend>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <select name="program" required class="px-4 py-2 border rounded w-full">
          <option value="">-- Select Program --</option>
          <option>Certificate</option><option>Diploma</option>
          <option>B.Div</option><option>PGDT</option>
        </select>

        <select name="year" required class="px-4 py-2 border rounded w-full">
          <option value="">-- Select Academic Year --</option>
          <option>1st Year</option><option>2nd Year</option>
          <option>3rd Year</option><option>4th Year</option>
        </select>

        <select name="semester" id="semester" required
                class="px-4 py-2 border rounded w-full">
          <option value="">-- Select Semester --</option>
          <option>First Semester</option><option>Second Semester</option>
        </select>
      </div>
    </fieldset>

   <!-- ── UPLOADS ────────────────────────────────────────── -->
    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">🧾 Uploads</legend>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block mb-1">Passport Photo (Min 1 MB)</label>
          <input type="file" name="passport" accept=".jpg,.jpeg,.png,.pdf" required
                 class="w-full px-4 py-2 border rounded" />
        </div>

        <div>
          <label class="block mb-1">Proof of Hostel Fee Payment</label>
          <input type="file" name="fees" accept=".jpg,.jpeg,.png,.pdf" required
                 class="w-full px-4 py-2 border rounded" />
        </div>

        <input type="text" name="fee" placeholder="Amount Paid" required
               class="px-4 py-2 border rounded w-full" />

        <label class="block w-full">
          <span class="text-sm text-gray-600">Date of Payment</span>
          <input type="date" name="payment_date" required
                 class="px-4 py-2 border rounded w-full mt-1" />
        </label>
      </div>
    </fieldset>

    <!-- ── DECLARATION ────────────────────────────────────── -->
    <fieldset class="border border-gray-300 p-4 rounded">
      <legend class="font-semibold text-purple-700">📝 Declaration</legend>

      <div class="space-y-2">
        <label class="inline-flex items-center">
          <input type="checkbox" name="has_mattress" value="yes" class="mr-2" required />
          I can present a student mattress upon resumption.
        </label>

        <p class="text-sm text-gray-700">
          <strong>Note:</strong> Students occupying Adullam hostel facilities are
          required to provide their own student-sized mattress.
        </p>

        <label class="inline-flex items-center">
          <input type="checkbox" name="confirm_born_again" value="yes" class="mr-2" required />
          I am born again and agree to all hostel rules. Fees paid are non-refundable.
        </label>
      </div>
    </fieldset>


    <input type="hidden" name="student_type" value="returning" />

    <button type="submit" id="submitBtn"
            class="w-full bg-purple-700 text-white py-3 rounded shadow hover:bg-purple-800">
      📨 Submit Hostel Reservation
    </button>
  </form>
<?php endif; /* fully-full vs form */ ?>
</main>

<!-- ───────────────────── JS helpers ────────────────────── -->
<script>
/* pass PHP array to JS */
const remaining = <?=json_encode($remaining)?>;

/* disable / enable submit depending on user choice */
function evaluateAvailability() {
  const sem   = document.getElementById('semester').value;
  const gender= document.getElementById('gender').value;
  const btn   = document.getElementById('submitBtn');

  if (!sem || !gender) { btn.disabled = false; return; }

  const left = remaining[sem][gender];
  if (left === 0) {
    btn.disabled = true;
    Swal.fire({icon:'info', title:'No Beds Left',
               text:`All ${gender} beds for ${sem} are filled. Please choose a different semester or contact the hostel.`,
               confirmButtonColor:'#6B21A8'});
  } else {
    btn.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  /* age auto-calc */
  const dob  = document.getElementById('dob');
  const age  = document.getElementById('age');
  dob.addEventListener('change', () => {
    const d = new Date(dob.value), t = new Date();
    let a = t.getFullYear() - d.getFullYear();
    if (t.getMonth() < d.getMonth() ||
        (t.getMonth() === d.getMonth() && t.getDate() < d.getDate())) a--;
    age.value = (a >= 0 && a <= 120) ? a : '';
  });

  /* origin placeholder swap */
  const nat  = document.getElementById('nationality');
  const orig = document.getElementById('stateOrigin');
  const swap = () =>
    orig.placeholder = nat.value === 'International' ? 'Country of Origin' : 'State of Origin';
  nat.addEventListener('change', swap); swap();

  /* spouse toggle */
  const marital = document.getElementById('marital');
  const spouseG = document.getElementById('spouseGroup');
  const toggleS = () => {
    const married = marital.value === 'Married';
    spouseG.classList.toggle('hidden', !married);
    document.getElementById('sname').required = married;
    document.getElementById('scont').required = married;
  };
  marital.addEventListener('change', toggleS); toggleS();

  /* monitor semester / gender to enforce capacity */
  document.getElementById('semester').addEventListener('change', evaluateAvailability);
  document.getElementById('gender').addEventListener('change',   evaluateAvailability);
});
</script>
</body>
</html>
