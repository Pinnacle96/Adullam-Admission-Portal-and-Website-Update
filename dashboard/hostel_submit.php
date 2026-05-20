<?php
/* ──────────────────────────────────────────────────────────────
   hostel_submit.php   –   handles BOTH new & returning students
   • checks duplicates
   • validates hostel capacity (gender + semester)
   • saves record + uploads
   • sends confirmation e-mail
   ────────────────────────────────────────────────────────────── */
require 'db.php';
require 'mailer.php';
require 'functions.php';          // ➜ contains hostelIsFull() and hostelRemainingBeds()
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index'); exit;
}

$studentType = strtolower(trim((string)($_POST['student_type'] ?? 'new')));
$dest = ($studentType === 'returning') ? 'register_hostel_returning' : 'register_hostel';

if (!isHostelRegistrationOpen($pdo, $studentType)) {
    header("Location: {$dest}?closed=1");
    exit;
}

/* ---------- reCAPTCHA verification ---------- */
$recaptchaSecret = "6LckELErAAAAAEX6sZUeY6MybRwhq-XweFFMHiNh"; // Replace with your reCAPTCHA v2 secret
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptchaResponse)) {
    header("Location: {$dest}?captcha=0"); exit;
}

$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret="
          . urlencode($recaptchaSecret) . "&response=" . urlencode($recaptchaResponse));
$captchaSuccess = json_decode($verify, true);

if (empty($captchaSuccess['success']) || $captchaSuccess['success'] !== true) {
    header("Location: {$dest}?captcha=0"); exit;
}

/* ---------- tiny helper ---------- */
function sanitize(string $key): string { return htmlspecialchars(trim($_POST[$key] ?? '')); }

/* ---------- collect POST ---------- */
$user_id      = $_SESSION['user_id'] ?? null;             // null for returning
$student_type = sanitize('student_type');                 // new | returning

$full_name  = sanitize('full_name');
$email      = strtolower(sanitize('email'));
$phone      = sanitize('contact');
$econtact   = sanitize('econtact');

$gender     = sanitize('gender');
$dob        = sanitize('dob');
$age        = intval(sanitize('age'));
$blood      = sanitize('blood');
$geno       = sanitize('genotype');
$allergy    = sanitize('allergy');
$nation     = sanitize('nationality');
$origin     = sanitize('state_origin');

$marital    = sanitize('marital');
$sname      = sanitize('sname');
$scont      = sanitize('scont');

$program    = sanitize('program');
$year       = sanitize('year');           // empty for NEW students
$semester   = sanitize('semester');

$res_address = sanitize('res_address');
$res_city    = sanitize('res_city');
$res_state   = sanitize('res_state');
$res_country = sanitize('res_country');

$perm_address = sanitize('perm_address');
$perm_city    = sanitize('perm_city');
$perm_state   = sanitize('perm_state');
$perm_country = sanitize('perm_country');

$gname = sanitize('gname');
$grel  = sanitize('grelation');
$gcont = sanitize('gcontact');

$amount_paid   = floatval(str_replace(',','',sanitize('fee')));
$pay_date      = sanitize('payment_date') ?: date('Y-m-d');
$mattress      = isset($_POST['has_mattress'])      ? 'Yes' : 'No';
$declaration   = isset($_POST['confirm_born_again'])? 1     : 0;

/* ---------- duplicate check ---------- */
if ($student_type==='new'){
    $dup = $pdo->prepare("SELECT COUNT(*) FROM hostel_registrations
                          WHERE user_id=? AND semester=? AND student_type='new'");
    $dup->execute([$user_id,$semester]);
    if ($dup->fetchColumn()>0){
        header("Location: register_hostel?error=1"); exit;
    }
} else {
    $dup = $pdo->prepare("SELECT COUNT(*) FROM hostel_registrations
                          WHERE email=? AND semester=? AND program_year=? AND student_type='returning'");
    $dup->execute([$email,$semester,$year]);
    if ($dup->fetchColumn()>0){
        header("Location: register_hostel_returning?error=1"); exit;
    }
}

/* ---------- CAPACITY VERIFICATION (🚦 NEW) ---------- */
if (hostelIsFull($pdo,$semester,$gender)){
    // optional: determine remaining so we can show nice msg
    header("Location: " .
       ($student_type==='new' ? 'register_hostel' : 'register_hostel_returning')
       . "?full=1");
    exit;
}

/* ---------- handle uploads ---------- */
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
$passport_file = '';
$payment_file  = '';

if (!empty($_FILES['passport']['name'])){
    $passport_file = $uploadDir.time().'_passport_'.basename($_FILES['passport']['name']);
    move_uploaded_file($_FILES['passport']['tmp_name'],$passport_file);
}
if (!empty($_FILES['fees']['name'])){
    $payment_file  = $uploadDir.time().'_payment_'.basename($_FILES['fees']['name']);
    move_uploaded_file($_FILES['fees']['tmp_name'],$payment_file);
}

/* ---------- insert record ---------- */
$sql="INSERT INTO hostel_registrations
 (user_id,full_name,email,phone,emergency_contact,gender,dob,age,
  blood_group,genotype,allergies,nationality,state_of_origin,
  marital_status,spouse_name,spouse_contact,
  program,program_year,semester,student_type,
  res_address,res_city,res_state,res_country,
  perm_address,perm_city,perm_state,perm_country,
  guardian_name,guardian_relation,guardian_contact,
  passport_file,payment_proof_file,amount_paid,payment_date,
  mattress_present,declaration_agreed)
 VALUES
 (:uid,:fn,:em,:ph,:ec,:gd,:dob,:age,
  :bl,:ge,:al,:na,:or,
  :ms,:sn,:sc,
  :pr,:yr,:sem,:st,
  :ra,:rc,:rs,:rco,
  :pa,:pc,:ps,:pco,
  :gn,:gr,:gc,
  :pf,:payf,:amt,:pdt,
  :mat,:dec)";
$pdo->prepare($sql)->execute([
 'uid'=>$user_id,'fn'=>$full_name,'em'=>$email,'ph'=>$phone,'ec'=>$econtact,'gd'=>$gender,
 'dob'=>$dob,'age'=>$age,'bl'=>$blood,'ge'=>$geno,'al'=>$allergy,'na'=>$nation,'or'=>$origin,
 'ms'=>$marital,'sn'=>$sname,'sc'=>$scont,'pr'=>$program,'yr'=>$year,'sem'=>$semester,'st'=>$student_type,
 'ra'=>$res_address,'rc'=>$res_city,'rs'=>$res_state,'rco'=>$res_country,
 'pa'=>$perm_address,'pc'=>$perm_city,'ps'=>$perm_state,'pco'=>$perm_country,
 'gn'=>$gname,'gr'=>$grel,'gc'=>$gcont,
 'pf'=>$passport_file,'payf'=>$payment_file,'amt'=>$amount_paid,'pdt'=>$pay_date,
 'mat'=>$mattress,'dec'=>$declaration
]);

/* ---------- Confirmation Email ---------- */
$subject = "✅ Family House Registration Confirmation";
$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$body = "
<div style='font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:600px;margin:auto;padding:20px;background-color:#f9fafb;border-radius:8px;border:1px solid #eee;'>
  <div style='text-align:center;margin-bottom:30px;'>
    <img src='https://adullam.ng/assets/img/logo.png' alt='Adullam Seminary' style='height:60px;' />
    <h2 style='color:#6B21A8;margin-top:10px;'>Adullam Seminary Family House Registration</h2>
  </div>
  <p style='font-size:16px;color:#111;'>Dear <strong>{$full_name}</strong>,</p>
  <p style='font-size:15px;color:#333;line-height:1.6;'>We have received your Family House registration for the <strong>{$program}</strong> program, <strong>{$semester}</strong>.</p>

  <p style='font-size:15px;color:#333;line-height:1.6;'>Your application will be reviewed and you will be notified upon approval. Please ensure your student mattress is ready for inspection on resumption.</p>

  <p style='font-size:15px;color:#333;line-height:1.6; margin-top:20px;'>
    <strong>Family House Room Reservation was successful</strong> – Please check back for management decision and to print out <strong>2 copies</strong> of the slip if approved. These must be submitted at the accommodation verification desk upon resumption on <strong>5th January, 2026.</strong>.
  </p>

  <div style='text-align:center;margin:30px 0;'>
    <a href='https://adullam.ng/dashboard/index' style='background-color:#6B21A8;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;'>Go to Dashboard</a>
  </div>

  <p style='font-size:14px;color:#555;border-top:1px solid #ddd;padding-top:20px;'>Blessings,<br><strong>Adullam Seminary Team</strong><br><a href='mailto:admissions@adullam.ng' style='color:#6B21A8;'>admissions@adullam.ng</a><br><a href='https://adullam.ng' style='color:#6B21A8;'>www.adullam.ng</a></p>
</div>";

sendMail($email, "Adullam Seminary", $subject, $body);

/* ---------- redirect with success ---------- */
$dest = ($student_type==='new') ? 'register_hostel' : 'register_hostel_returning';
header("Location: {$dest}?success=1"); exit;
?>
