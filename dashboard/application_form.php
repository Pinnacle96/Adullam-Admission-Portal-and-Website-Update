<?php
// Enable logging first
require_once __DIR__ . '/logging.php';

ini_set('display_errors', 1); // Temporarily set to 1 to see errors
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';
require_once 'mailer.php';

// --- Helper Functions ---
function log_upload_trace($message, $type = 'INFO') {
    $log_file = __DIR__ . '/upload_trace.log';
    $timestamp = date('[Y-m-d H:i:s T]');
    file_put_contents($log_file, "$timestamp [$type] $message\n", FILE_APPEND);
}

function saveFile($file, $field) {
    log_message("saveFile called for field: $field, file name: " . ($file['name'] ?? 'no name'), "INFO");
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $php_errors = [
            UPLOAD_ERR_INI_SIZE   => "File exceeds the max size defined in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "File exceeds the max size defined in the HTML form.",
            UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
        ];
        $errorMessage = $php_errors[$file['error']] ?? "Unknown PHP upload error.";
        log_message("Upload error for $field: $errorMessage", "ERROR");
        return ['success' => false, 'message' => "An upload error occurred for $field: " . $errorMessage];
    }
    
    $maxFileSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        $readableLimit = "5MB";
        $actualSize = round($file['size'] / (1024 * 1024), 2) . "MB";
        log_message("File too big for $field: $actualSize > $readableLimit", "ERROR");
        return ['success' => false, 'message' => "The uploaded document for $field ($actualSize) is larger than the allowed size of $readableLimit."];
    }
    
    $targetDir = __DIR__ . "/uploads/documents/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
        log_message("Created upload directory: $targetDir", "INFO");
    }
    
    $fileName = basename($file['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    if (!in_array($ext, $allowed)) {
        log_message("Invalid file type for $field: $ext", "ERROR");
        return ['success' => false, 'message' => "Invalid file type for $field. Allowed: " . implode(', ', $allowed)];
    }
    
    $newName = uniqid($field . "_") . "." . $ext;
    $targetFile = $targetDir . $newName;
    log_message("Attempting to save file to: $targetFile", "INFO");
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        log_message("File saved successfully: $targetFile", "INFO");
        // Return path relative to public_html for database
        $relativePath = "dashboard/uploads/documents/" . $newName;
        return ['success' => true, 'path' => $relativePath];
    }
    log_message("Failed to move uploaded file to: $targetFile", "ERROR");
    return ['success' => false, 'message' => "Failed to save $field."];
}

function generateToken($length = 40) {
    return bin2hex(random_bytes($length / 2));
}

// --- Auth & Permission Checks ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

$regOpen = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();
if (!$regOpen) {
    header("Location: student_dashboard");
    exit;
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? max(1, min(7, (int)$_GET['step'])) : 1;
log_message("Application form loaded for step $step, user ID: $user_id", "INFO");

// --- Check Submission Status ---
$subStmt = $pdo->prepare("SELECT submitted FROM applications WHERE user_id = ?");
$subStmt->execute([$user_id]);
$isSubmitted = $subStmt->fetchColumn();

// --- Step 1 Init: Create Application if needed ---
if ($step === 1) {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        $cohort = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'")->fetchColumn() ?: 'January 2026';
        $pdo->prepare("INSERT INTO applications (user_id, current_level, cohort) VALUES (?, 1, ?)")->execute([$user_id, $cohort]);
    }
}

// --- Form Processing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_message("POST request received for step $step", "INFO");
    
    // Block updates if submitted (Read-Only Mode)
    if ($isSubmitted) {
        log_message("Application already submitted, redirecting to dashboard", "WARNING");
        header("Location: dashboard");
        exit;
    }

    if ($step === 1) {
        // Step 1: Program Details
        $gender = trim($_POST['gender']);
        $dob = trim($_POST['dob']);
        $program = trim($_POST['program']);
        $ma_focus = $_POST['ma_focus'] ?? null;
        $mode = trim($_POST['mode']);
        $res_address = trim($_POST['res_address']);
        $res_city = trim($_POST['res_city']);
        $res_state = trim($_POST['res_state']);
        $res_country = trim($_POST['res_country']);
        $perm_address = trim($_POST['perm_address']);
        $perm_city = trim($_POST['perm_city']);
        $perm_state = trim($_POST['perm_state']);
        $perm_country = trim($_POST['perm_country']);

        $stmt = $pdo->prepare("INSERT INTO application_details 
            (user_id, gender, dob, program, ma_focus, mode_of_study, res_address, res_city, res_state, res_country,
             perm_address, perm_city, perm_state, perm_country)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            gender = VALUES(gender), dob = VALUES(dob), program = VALUES(program), ma_focus = VALUES(ma_focus),
            mode_of_study = VALUES(mode_of_study), res_address = VALUES(res_address), res_city = VALUES(res_city),
            res_state = VALUES(res_state), res_country = VALUES(res_country),
            perm_address = VALUES(perm_address), perm_city = VALUES(perm_city),
            perm_state = VALUES(perm_state), perm_country = VALUES(perm_country)");
        $stmt->execute([$user_id, $gender, $dob, $program, $ma_focus, $mode, $res_address, $res_city, $res_state, $res_country, $perm_address, $perm_city, $perm_state, $perm_country]);
        
        $next_level = 2;

    } elseif ($step === 2) {
        // Step 2: Personal Info
        $fields = ['maritalstatus', 'children', 'dhealth', 'disciplinary', 'mental_health', 'fbank', 'drug', 'employment', 'felony', 'smisconduct', 'soffence', 'divource', 'spouse'];
        $data = [];
        foreach ($fields as $field) {
            if ($field === 'children') {
                $data[$field] = ($_POST['maritalstatus'] ?? '') === 'Single' ? '0' : trim($_POST['children'] ?? '');
            } else {
                $data[$field] = trim($_POST[$field] ?? '');
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO application_personal (
            user_id, maritalstatus, children, dhealth, disciplinary, mental_health,
            fbank, drug, employment, felony, smisconduct, soffence, divource, spouse
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            maritalstatus = VALUES(maritalstatus), children = VALUES(children), dhealth = VALUES(dhealth),
            disciplinary = VALUES(disciplinary), mental_health = VALUES(mental_health), fbank = VALUES(fbank),
            drug = VALUES(drug), employment = VALUES(employment), felony = VALUES(felony),
            smisconduct = VALUES(smisconduct), soffence = VALUES(soffence), divource = VALUES(divource),
            spouse = VALUES(spouse)");
        $stmt->execute(array_merge([$user_id], array_values($data)));
        
        $next_level = 3;

    } elseif ($step === 3) {
        // Step 3: Church Info
        $church_name = trim($_POST['church_name'] ?? '');
        $caddress = trim($_POST['caddress'] ?? '');
        if ($church_name && $caddress) {
            $stmt = $pdo->prepare("INSERT INTO application_church (user_id, church_name, caddress) 
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE church_name = VALUES(church_name), caddress = VALUES(caddress)");
            $stmt->execute([$user_id, $church_name, $caddress]);
        }
        $next_level = 4;

    } elseif ($step === 4) {
        // Step 4: Autobiography
        $qgospel = trim($_POST['qgospel'] ?? '');
        $sgrowth = trim($_POST['sgrowth'] ?? '');
        $callto  = trim($_POST['callto'] ?? '');
        if ($qgospel || $sgrowth || $callto) {
            $stmt = $pdo->prepare("INSERT INTO application_autobiography (user_id, qgospel, sgrowth, callto)
                VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE qgospel = VALUES(qgospel), sgrowth = VALUES(sgrowth), callto = VALUES(callto)");
            $stmt->execute([$user_id, $qgospel, $sgrowth, $callto]);
        }
        $next_level = 5;

    } elseif ($step === 5) {
        // Step 5: References
        $ref1Name  = htmlspecialchars(trim($_POST['ref1Name']));
        $ref1Phone = htmlspecialchars(trim($_POST['ref1Phone']));
        $ref1Email = filter_var(trim($_POST['ref1Email']), FILTER_SANITIZE_EMAIL);
        $ref2Name  = htmlspecialchars(trim($_POST['ref2Name']));
        $ref2Phone = htmlspecialchars(trim($_POST['ref2Phone']));
        $ref2Email = filter_var(trim($_POST['ref2Email']), FILTER_SANITIZE_EMAIL);

        // Validation
        if (!filter_var($ref1Email, FILTER_VALIDATE_EMAIL) || !filter_var($ref2Email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email addresses.";
            header("Location: application_form?step=5");
            exit;
        }
        
        $phonePattern = '/^\+\d{6,15}$/';
        if (!preg_match($phonePattern, $ref1Phone) || !preg_match($phonePattern, $ref2Phone)) {
             $_SESSION['error'] = "Invalid phone numbers. Please include country code (e.g., +2348012345678).";
             header("Location: application_form?step=5");
             exit;
        }

        $stmt = $pdo->prepare("INSERT INTO application_references (user_id, ref1Name, ref1Phone, ref1Email, ref2Name, ref2Phone, ref2Email)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE ref1Name=VALUES(ref1Name), ref1Phone=VALUES(ref1Phone), ref1Email=VALUES(ref1Email),
            ref2Name=VALUES(ref2Name), ref2Phone=VALUES(ref2Phone), ref2Email=VALUES(ref2Email)");
        $stmt->execute([$user_id, $ref1Name, $ref1Phone, $ref1Email, $ref2Name, $ref2Phone, $ref2Email]);

        // Email Sending Logic (Only on Continue)
        if (isset($_POST['continue'])) {
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $applicant = $stmt->fetch();
            $fullName = $applicant ? trim($applicant['first_name'] . ' ' . $applicant['last_name']) : "Applicant";

            $referees = [
                ['email' => $ref1Email, 'name' => $ref1Name],
                ['email' => $ref2Email, 'name' => $ref2Name],
            ];
            $emailErrors = [];

            foreach ($referees as $ref) {
                try {
                    $token = generateToken();
                    $stmt = $pdo->prepare("INSERT INTO application_recommendations
                        (user_id, referee_email, referee_name, token, submitted)
                        VALUES (?, ?, ?, ?, 0)
                        ON DUPLICATE KEY UPDATE
                            referee_name = VALUES(referee_name),
                            referee_email = VALUES(referee_email),
                            token = IF(submitted = 0, VALUES(token), token)");
                    $stmt->execute([$user_id, $ref['email'], $ref['name'], $token]);

                    if (function_exists('sendMail')) {
                        $recommendationLink = "https://adullam.ng/dashboard/recommend?token=" . urlencode($token);
                        $rawSubject = "📩 Recommendation Request from " . $fullName;
                        $subject = '=?UTF-8?B?' . base64_encode($rawSubject) . '?=';
                        
                        $body = "
                        <div style='font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;
                                    max-width:600px;margin:auto;padding:20px;
                                    background-color:#f9fafb;border-radius:8px;border:1px solid #eee;'>
                          <div style='text-align:center;margin-bottom:30px;'>
                            <img src='https://adullam.ng/assets/img/logo.png' alt='Adullam Seminary' style='height:60px;' />
                            <h2 style='color:#6B21A8;margin:10px 0 0;'>Adullam Seminary Admissions</h2>
                          </div>
                          <p style='font-size:16px;color:#111;'>Dear <strong>" . htmlspecialchars($ref['name']) . "</strong>,</p>
                          <p style='font-size:15px;color:#333;line-height:1.6;'>
                            <strong>$fullName</strong> has applied to <strong>RCN Theological Seminary - Adullam</strong> and listed you as a referee.
                            We kindly request your support in completing a recommendation form on their behalf.
                          </p>
                          <p style='font-size:15px;color:#333;line-height:1.6;'>Please click the button below to securely submit your recommendation:</p>
                          <div style='text-align:center;margin:30px 0;'>
                            <a href='$recommendationLink'
                               style='background-color:#6B21A8;color:white;padding:12px 24px;
                                      text-decoration:none;border-radius:6px;font-weight:bold;
                                      display:inline-block;'>Submit Recommendation</a>
                          </div>
                          <p style='font-size:14px;color:#555;line-height:1.5;'>
                            Or copy and paste this link into your browser:<br>
                            <a href='$recommendationLink' style='color:#6B21A8;word-break:break-all;'>$recommendationLink</a>
                          </p>
                          <hr style='margin:30px 0;border:none;border-top:1px solid #ddd;' />
                          <p style='font-size:13px;color:#666;text-align:center;line-height:1.5;'>
                            Thank you for taking the time to support the academic journey of our applicants.<br>
                            <strong>Adullam Admissions Team</strong><br>
                            <a href='mailto:rcnts.adullam@gmail.com' style='color:#6B21A8;'>rcnts.adullam@gmail.com</a> | 
                            <a href='https://adullam.ng' style='color:#6B21A8;'>www.adullam.ng</a>
                          </p>
                        </div>
                        ";
                        $result = sendMail($ref['email'], $ref['name'], $subject, $body);
                        if ($result === false || (is_array($result) && isset($result['error']))) {
                            $emailErrors[] = "Failed to send email to {$ref['name']}";
                        }
                    }
                } catch (Exception $e) {
                    error_log("Failed to setup recommendation for {$ref['email']}: " . $e->getMessage());
                    $emailErrors[] = "Error processing {$ref['name']}";
                }
            }
            
            if (!empty($emailErrors)) {
                $_SESSION['warning'] = "Some emails could not be sent. Please check the addresses.";
            } else {
                $_SESSION['success_ref'] = "Recommendation requests sent successfully.";
            }
        }
        $next_level = 6;

    } elseif ($step === 6) {
        // Step 6: Documents
        $progStmt = $pdo->prepare("SELECT program FROM application_details WHERE user_id = ?");
        $progStmt->execute([$user_id]);
        $prog = $progStmt->fetchColumn();
        $isPGDTorMA = in_array(strtoupper($prog ?? ''), ['MA', 'PGDT']);

        $fields = ['passport', 'ssce_cert', 'ssce_cert2', 'birth_cert', 'origin_cert', 'recommendation', 'payment_proof'];
        if ($isPGDTorMA) {
            $fields[] = 'degree_cert';
            $fields[] = 'transcript';
        }

        $uploadedPaths = [];
        $uploadErrors = [];

        foreach ($fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $res = saveFile($_FILES[$field], $field);
                if ($res['success']) {
                    $uploadedPaths[$field] = $res['path'];
                } else {
                    $uploadErrors[] = $res['message'];
                }
            }
        }

        if (!empty($uploadedPaths)) {
            $check = $pdo->prepare("SELECT user_id FROM application_documents WHERE user_id = ?");
            $check->execute([$user_id]);
            if ($check->fetch()) {
                $setClause = [];
                $params = [];
                foreach ($uploadedPaths as $col => $val) {
                    $setClause[] = "$col = ?";
                    $params[] = $val;
                }
                $sql = "UPDATE application_documents SET " . implode(', ', $setClause) . " WHERE user_id = ?";
                $params[] = $user_id;
                $pdo->prepare($sql)->execute($params);
            } else {
                $cols = implode(', ', array_keys($uploadedPaths));
                $placeholders = implode(', ', array_fill(0, count($uploadedPaths), '?'));
                $sql = "INSERT INTO application_documents (user_id, $cols) VALUES (?, $placeholders)";
                $pdo->prepare($sql)->execute(array_merge([$user_id], array_values($uploadedPaths)));
            }
        }

        if (isset($_POST['continue'])) {
            $requiredFields = ['passport', 'ssce_cert', 'birth_cert', 'origin_cert', 'recommendation', 'payment_proof'];
            if ($isPGDTorMA) $requiredFields[] = 'degree_cert';
            
            // Check if files exist in DB if not uploaded now
            $existingDocs = $pdo->prepare("SELECT * FROM application_documents WHERE user_id = ?");
            $existingDocs->execute([$user_id]);
            $existing = $existingDocs->fetch(PDO::FETCH_ASSOC) ?: [];

            foreach ($requiredFields as $req) {
                if (empty($uploadedPaths[$req]) && empty($existing[$req])) {
                    $uploadErrors[] = "The " . str_replace('_', ' ', $req) . " file is required.";
                }
            }
        }

        if (!empty($uploadErrors)) {
            $_SESSION['upload_errors'] = $uploadErrors;
            header("Location: application_form?step=6");
            exit;
        }
        
        $next_level = 7;

    } elseif ($step === 7 && isset($_POST['submit_application'])) {
        // Step 7: Final Submission
        
        // Prevent duplicate submission
        $check = $pdo->prepare("SELECT admission_no FROM applications WHERE user_id = ? AND submitted = 1");
        $check->execute([$user_id]);
        if ($check->fetchColumn()) {
            header("Location: dashboard");
            exit;
        }

        // Generate Admission No
        $admissionNo = '';
        do {
            $last = $pdo->query("SELECT COUNT(*) FROM applications WHERE admission_no IS NOT NULL")->fetchColumn();
            $nextSerial = str_pad((int)$last + rand(1, 999), 4, '0', STR_PAD_LEFT);
            $admissionNo = "ADM/JAN/2026/" . $nextSerial;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE admission_no = ?");
            $stmt->execute([$admissionNo]);
            $exists = $stmt->fetchColumn();
        } while ($exists);

        $cohortStmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'");
        $activeCohort = $cohortStmt->fetchColumn() ?: 'January 2026';

        $pdo->prepare("UPDATE applications SET submitted = 1, status = 'submitted', admission_no = ?, submitted_at = NOW(), cohort = ? WHERE user_id = ?")->execute([$admissionNo, $activeCohort, $user_id]);

        // Email Notification
        $stmt = $pdo->prepare("SELECT first_name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $progStmt = $pdo->prepare("SELECT program, ma_focus, mode_of_study, res_country FROM application_details WHERE user_id = ?");
        $progStmt->execute([$user_id]);
        $progData = $progStmt->fetch();

        if ($user && $progData) {
            $program = $progData['program'];
            $ma_focus = $progData['ma_focus'] ?? '';
            $study_mode = $progData['mode_of_study'];
            $country = $progData['res_country'];
            $normalizedProgram = strtolower(trim($program));
            $normalizedMode = strtolower(trim($study_mode));
            $isNigerian = (strtolower(trim($country)) === 'nigeria');

            $feeDocUrl = '';
            switch ($normalizedProgram) {
                case 'ma':
                    $feeDocUrl = $isNigerian
                        ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_MA_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_MA_Local_Fees.pdf')
                        : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_MA_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_MA_International_Fees.pdf');
                    break;
                case 'pgdt':
                    $feeDocUrl = $isNigerian
                        ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_PGDT_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_PGDT_Local_Fees.pdf')
                        : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_PGDT_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_PGDT_International_Fees.pdf');
                    break;
                case 'b.div':
                    $feeDocUrl = $isNigerian
                        ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_BACHELOR_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_BACHELOR_Local_Fees.pdf')
                        : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_BACHELOR_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_BACHELOR_International_Fees.pdf');
                    break;
                case 'diploma':
                    $feeDocUrl = $isNigerian
                        ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_DIPLOMA_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_DIPLOMA_Local_Fees.pdf')
                        : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_DIPLOMA_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_DIPLOMA_International_Fees.pdf');
                    break;
                case 'certificate':
                    $feeDocUrl = $isNigerian
                        ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_CERTIFICATE_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_CERTIFICATE_Local_Fees.pdf')
                        : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_CERTIFICATE_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_CERTIFICATE_International_Fees.pdf');
                    break;
                default:
                    $feeDocUrl = 'https://adullam.ng/fees/fees_structure.pdf';
                    break;
            }

            $feeDocLink = '<a href="' . htmlspecialchars($feeDocUrl) . '" style="color:#6B21A8;text-decoration:underline;">fee document</a>';
            $contactEmail = '<a href="mailto:rcnts.adullam@gmail.com" style="color:#6B21A8;text-decoration:underline;">rcnts.adullam@gmail.com</a>';
            $portalLink = '<a href="https://adullam.ng/dashboard/applicant_login" style="color:#6B21A8;text-decoration:underline;">Login Here</a>';
            $first = htmlspecialchars($user['first_name']);
            $program_display = htmlspecialchars($program) . (($normalizedProgram === 'ma') ? " ($ma_focus)" : "");
            $study_mode_display = htmlspecialchars($study_mode);
            $subject = '=?UTF-8?B?' . base64_encode("✅ Application Submission Confirmation - RCN Theological Seminary") . '?=';
            
            $body = "<div style='max-width:600px;margin:auto;padding:20px;background-color:#f9fafb;font-family:sans-serif;border-radius:8px;border:1px solid #eee;'>
              <div style='text-align:center;margin-bottom:20px;'>
                <img src='https://adullam.ng/assets/logo.png' alt='Adullam Seminary' style='height:80px;margin-bottom:10px;' />
                <h2 style='color:#6B21A8;margin:0;'>RCN Theological Seminary - Adullam</h2>
              </div>
              <hr style='margin:20px 0;border:none;border-top:1px solid #ddd;' />
              <p>Dear <strong>$first</strong>,</p>
              <p>We are pleased to confirm that your application has been successfully submitted. <strong>Application Number:</strong> $admissionNo</p>
              <p><strong>Program:</strong> $program_display <br> <strong>Study Option:</strong> $study_mode_display</p>
              <p>The admissions committee will review your application within 21 days.</p>
              <p>Resumption: <strong>Monday, 5th January, 2026</strong>.</p>
              <ol style='padding-left: 20px;'>
                <li>Pay your fees (60% min) per the $feeDocLink.</li>
                <li>Upload payment receipt on your portal.</li>
                " . ($normalizedMode !== 'online' ? "<li>Prepare to travel to Makurdi.</li>" : "") . "
              </ol>
              <p>Contact: $contactEmail</p>
              <p>Warm regards,<br /><strong>Adullam Admissions Committee</strong></p>
            </div>";

            if (function_exists('sendMail')) {
                sendMail($user['email'], $user['first_name'], $subject, $body);
            }
        }

        header("Location: dashboard?submitted=1");
        exit;
    }

    if (isset($_POST['continue'])) {
        if (isset($next_level)) {
            $pdo->prepare("UPDATE applications SET current_level = ? WHERE user_id = ?")->execute([$next_level, $user_id]);
        }
        header("Location: application_form?step=" . ($next_level ?? $step));
        exit;
    } elseif (isset($_POST['save'])) {
        $_SESSION['success'] = "Progress saved successfully!";
        header("Location: application_form?step=$step");
        exit;
    } elseif (isset($_POST['previous'])) {
        $prev = max(1, $step - 1);
        header("Location: application_form?step=$prev");
        exit;
    }
}

// --- Data Fetching ---
$details = $pdo->prepare("SELECT * FROM application_details WHERE user_id = ?");
$details->execute([$user_id]);
$details = $details->fetch(PDO::FETCH_ASSOC) ?: [];
$personal = $pdo->prepare("SELECT * FROM application_personal WHERE user_id = ?");
$personal->execute([$user_id]);
$personal = $personal->fetch(PDO::FETCH_ASSOC) ?: [];
$church = $pdo->prepare("SELECT * FROM application_church WHERE user_id = ?");
$church->execute([$user_id]);
$church = $church->fetch(PDO::FETCH_ASSOC) ?: [];
$autobio = $pdo->prepare("SELECT * FROM application_autobiography WHERE user_id = ?");
$autobio->execute([$user_id]);
$autobio = $autobio->fetch(PDO::FETCH_ASSOC) ?: [];
$refs = $pdo->prepare("SELECT * FROM application_references WHERE user_id = ?");
$refs->execute([$user_id]);
$refs = $refs->fetch(PDO::FETCH_ASSOC) ?: [];
$docs = $pdo->prepare("SELECT * FROM application_documents WHERE user_id = ?");
$docs->execute([$user_id]);
$docs = $docs->fetch(PDO::FETCH_ASSOC) ?: [];
$userQuery = $pdo->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ?");
$userQuery->execute([$user_id]);
$userData = $userQuery->fetch();
$full_name = trim(($userData['first_name']??'') . ' ' . ($userData['middle_name'] ?? '') . ' ' . ($userData['last_name']??''));
$progress = ($step / 7) * 100;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - Step <?= $step ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="mb-8">
            <div class="flex justify-between text-xs font-medium text-gray-500 mb-2">
                <span>Start</span><span>Step <?= $step ?> of 7</span><span>Finish</span>
            </div>
            <div class="w-full bg-gray-300 rounded-full h-2.5">
                <div class="bg-purple-600 h-2.5 rounded-full transition-all duration-500"
                    style="width: <?= $progress ?>%"></div>
            </div>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-xl">
            <?php if ($isSubmitted): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
                <p class="font-bold">Application Submitted</p>
                <p>Your application has been submitted and is currently in <strong>Read-Only Mode</strong>. You cannot
                    make further edits.</p>
                <a href="dashboard"
                    class="mt-2 inline-block bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Back to
                    Dashboard</a>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <?php if ($step === 1): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6">Step 1: Personal Information</h2>
                <fieldset class="border border-gray-200 p-4 rounded-md mb-6">
                    <legend class="text-sm font-semibold text-purple-700 px-2">👤 Personal Info</legend>
                    <div class="space-y-4">
                        <input type="text" value="<?= htmlspecialchars($full_name) ?>" readonly
                            class="w-full px-4 py-2 bg-gray-100 border rounded-md cursor-not-allowed" />
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-700">Gender<span class="text-red-500">*</span></label>
                                <select name="gender" required
                                    class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
                                    <option value="">-- Select --</option>
                                    <option value="male" <?= ($details['gender'] ?? '') === 'male' ? 'selected' : '' ?>>
                                        Male</option>
                                    <option value="female"
                                        <?= ($details['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-gray-700">Date of Birth<span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="dob" required
                                    value="<?= htmlspecialchars($details['dob'] ?? '') ?>"
                                    class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700">Program Applying For<span
                                    class="text-red-500">*</span></label>
                            <select name="program" id="program" required
                                class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
                                <option value="">-- Select Program --</option>
                                <option value="Certificate"
                                    <?= ($details['program'] ?? '') === 'Certificate' ? 'selected' : '' ?>>Certificate
                                </option>
                                <option value="Diploma"
                                    <?= ($details['program'] ?? '') === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                                <option value="B.Div" <?= ($details['program'] ?? '') === 'B.Div' ? 'selected' : '' ?>>
                                    Bachelor of Divinity (B.Div)</option>
                                <option value="PGDT" <?= ($details['program'] ?? '') === 'PGDT' ? 'selected' : '' ?>>
                                    Postgraduate Diploma (PGDT)</option>
                                <option value="MA" <?= ($details['program'] ?? '') === 'MA' ? 'selected' : '' ?>>Master
                                    of Arts (MA)</option>
                            </select>
                        </div>
                        <div id="maFocusWrapper" class="<?= ($details['program'] ?? '') === 'MA' ? '' : 'hidden' ?>">
                            <label class="text-sm text-gray-700">MA Focus</label>
                            <select name="ma_focus"
                                class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
                                <option value="">-- Select MA Focus --</option>
                                <option value="MA Christian Apologetics"
                                    <?= ($details['ma_focus'] ?? '') === 'MA Christian Apologetics' ? 'selected' : '' ?>>
                                    MA Christian Apologetics</option>
                                <option value="MA Biblical Studies (OT/NT)"
                                    <?= ($details['ma_focus'] ?? '') === 'MA Biblical Studies (OT/NT)' ? 'selected' : '' ?>>
                                    MA Biblical Studies (OT/NT)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700">Mode of Study<span
                                    class="text-red-500">*</span></label>
                            <select name="mode" required
                                class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
                                <option value="">-- Select --</option>
                                <option value="online"
                                    <?= ($details['mode_of_study'] ?? '') === 'online' ? 'selected' : '' ?>>Online
                                </option>
                                <option value="onsite"
                                    <?= ($details['mode_of_study'] ?? '') === 'onsite' ? 'selected' : '' ?>>Onsite
                                </option>
                            </select>
                        </div>
                    </div>
                </fieldset>
                <fieldset class="border border-gray-200 p-4 rounded-md mb-6">
                    <legend class="text-sm font-semibold text-purple-700 px-2">📍 Residential Address</legend>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <input type="text" name="res_address" required placeholder="Address Line"
                            value="<?= htmlspecialchars($details['res_address'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        <input type="text" name="res_city" required placeholder="City"
                            value="<?= htmlspecialchars($details['res_city'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        <input type="text" name="res_state" required placeholder="State"
                            value="<?= htmlspecialchars($details['res_state'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        <input type="text" name="res_country" required placeholder="Country"
                            value="<?= htmlspecialchars($details['res_country'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                    </div>
                </fieldset>
                <fieldset class="border border-gray-200 p-4 rounded-md">
                    <legend class="text-sm font-semibold text-purple-700 px-2">🏡 Permanent Address</legend>
                    <label class="block text-sm mb-2"><input type="checkbox" id="copyAddress" class="mr-2"> Same as
                        Residential Address</label>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <input type="text" name="perm_address" placeholder="Address Line"
                            value="<?= htmlspecialchars($details['perm_address'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        <input type="text" name="perm_city" placeholder="City"
                            value="<?= htmlspecialchars($details['perm_city'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        <input type="text" name="perm_state" placeholder="State"
                            value="<?= htmlspecialchars($details['perm_state'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        <input type="text" name="perm_country" placeholder="Country"
                            value="<?= htmlspecialchars($details['perm_country'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                    </div>
                </fieldset>

                <?php elseif ($step === 2): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6">Step 2: Personal Evaluation</h2>
                <p class="text-gray-600 mb-4">The following questions are for counselling purposes.</p>
                <div class="space-y-6">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Marital Status<span
                                    class='text-red-500'>*</span></label>
                            <select name='maritalstatus' id="maritalSelect" required
                                class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
                                <option value=''>Marital Status</option>
                                <?php foreach (['Married', 'Divorced', 'Remarried', 'Separated', 'Single', 'Widowed'] as $opt) {
                                        $sel = ($personal['maritalstatus'] ?? '') === $opt ? "selected" : "";
                                        echo "<option value='{$opt}' {$sel}>{$opt}</option>";
                                    } ?>
                            </select>
                        </div>
                        <div id="children-wrapper">
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Number of Children<span
                                    class='text-red-500'>*</span></label>
                            <input name='children' id="childrenField" type='number' min='0'
                                value="<?= htmlspecialchars($personal['children'] ?? '') ?>"
                                class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
                        </div>
                    </div>
                    <?php 
                        $questions = [
                            'dhealth' => 'Do you have any physical, mental or emotional disabilities?',
                            'disciplinary' => 'Have you ever been on academic or disciplinary probation?',
                            'mental_health' => 'Have you ever been under mental health care?',
                            'fbank' => 'Have you ever declared bankruptcy or legal action against your finances?',
                            'drug' => 'Have you ever used illegal drugs or abused alcohol?',
                            'employment' => 'Have you ever been dismissed/fired from a job?',
                            'felony' => 'Have you ever been convicted of a felony or dishonorably discharged?',
                            'smisconduct' => 'Have you ever been involved in sexual activity outside of marriage?',
                            'soffence' => 'Are you a registered sex offender or convicted of a sex offence?',
                            'divource' => 'Have you ever been divorced?'
                        ];
                        foreach ($questions as $key => $label) {
                            $val = $personal[$key] ?? '';
                            echo "<div><label class='block text-sm font-medium text-gray-700 mb-1'>{$label}<span class='text-red-500'>*</span></label>
                                  <select name='{$key}' required class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
                                    <option value=''>Choose an Option</option>
                                    <option value='Yes' " . ($val === 'Yes' ? 'selected' : '') . ">Yes</option>
                                    <option value='No' " . ($val === 'No' ? 'selected' : '') . ">No</option>
                                  </select></div>";
                        }
                        ?>
                    <div id="spouse-wrapper"
                        class="<?= ($personal['maritalstatus'] ?? '') === 'Married' ? '' : 'hidden' ?>">
                        <label class='block text-sm font-medium text-gray-700 mb-1'>If married, is your spouse in
                            agreement with this program?</label>
                        <select name='spouse' id="spouseField"
                            class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
                            <option value=''>Choose an Option</option>
                            <option value='Yes' <?= ($personal['spouse'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes
                            </option>
                            <option value='No' <?= ($personal['spouse'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <?php elseif ($step === 3): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6">Step 3: Church Information</h2>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Church Name<span
                                class="text-red-500">*</span></label>
                        <input type="text" name="church_name"
                            value="<?= htmlspecialchars($church['church_name'] ?? '') ?>" required
                            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Church Address<span
                                class="text-red-500">*</span></label>
                        <textarea name="caddress" rows="3" required
                            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600"><?= htmlspecialchars($church['caddress'] ?? '') ?></textarea>
                    </div>
                </div>

                <?php elseif ($step === 4): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6">Step 4: Autobiography</h2>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">How would you explain the gospel of Jesus
                            Christ?<span class="text-red-500">*</span></label>
                        <textarea name="qgospel" rows="5" required
                            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600"><?= htmlspecialchars($autobio['qgospel'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Your conversion and spiritual
                            growth:<span class="text-red-500">*</span></label>
                        <textarea name="sgrowth" rows="5" required
                            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600"><?= htmlspecialchars($autobio['sgrowth'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Call to ministry and/or reason for
                            applying:<span class="text-red-500">*</span></label>
                        <textarea name="callto" rows="5" required
                            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600"><?= htmlspecialchars($autobio['callto'] ?? '') ?></textarea>
                    </div>
                </div>

                <?php elseif ($step === 5): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6">Step 5: Reference Information</h2>
                <div class="space-y-6">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference 1 Name</label>
                            <input type="text" name="ref1Name" value="<?= htmlspecialchars($refs['ref1Name'] ?? '') ?>"
                                required
                                class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference 1 Phone</label>
                            <input type="tel" name="ref1Phone" value="<?= htmlspecialchars($refs['ref1Phone'] ?? '') ?>"
                                required placeholder="+2348012345678" pattern="^\+\d{6,15}$"
                                oninvalid="this.setCustomValidity('Enter a valid phone number with country code, e.g. +2348012345678')"
                                oninput="this.setCustomValidity('')"
                                class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                            <small class="text-gray-500">Include country code</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference 1 Email</label>
                            <input type="email" name="ref1Email"
                                value="<?= htmlspecialchars($refs['ref1Email'] ?? '') ?>" required
                                class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4 border-t pt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference 2 Name</label>
                            <input type="text" name="ref2Name" value="<?= htmlspecialchars($refs['ref2Name'] ?? '') ?>"
                                required
                                class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference 2 Phone</label>
                            <input type="tel" name="ref2Phone" value="<?= htmlspecialchars($refs['ref2Phone'] ?? '') ?>"
                                required placeholder="+2348012345678" pattern="^\+\d{6,15}$"
                                oninvalid="this.setCustomValidity('Enter a valid phone number with country code, e.g. +2348012345678')"
                                oninput="this.setCustomValidity('')"
                                class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                            <small class="text-gray-500">Include country code</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference 2 Email</label>
                            <input type="email" name="ref2Email"
                                value="<?= htmlspecialchars($refs['ref2Email'] ?? '') ?>" required
                                class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                        </div>
                    </div>
                </div>

                <?php elseif ($step === 6): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6">Step 6: Upload Documents</h2>
                <div class="space-y-6">
                    <?php
                        $isPGDTorMA = in_array(strtoupper($details['program'] ?? ''), ['MA', 'PGDT']);
                        
                        // Calculate Fees Document URL
                        $normalizedProgram = strtolower(trim($details['program'] ?? ''));
                        $normalizedMode = strtolower(trim($details['mode_of_study'] ?? ''));
                        $isNigerian = (strtolower(trim($details['res_country'] ?? '')) === 'nigeria');

                        $feeDocUrl = 'https://adullam.ng/fees/fees_structure.pdf';
                        switch ($normalizedProgram) {
                            case 'ma':
                                $feeDocUrl = $isNigerian
                                    ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_MA_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_MA_Local_Fees.pdf')
                                    : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_MA_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_MA_International_Fees.pdf');
                                break;
                            case 'pgdt':
                                $feeDocUrl = $isNigerian
                                    ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_PGDT_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_PGDT_Local_Fees.pdf')
                                    : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_PGDT_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_PGDT_International_Fees.pdf');
                                break;
                            case 'b.div':
                                $feeDocUrl = $isNigerian
                                    ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_BACHELOR_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_BACHELOR_Local_Fees.pdf')
                                    : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_BACHELOR_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_BACHELOR_International_Fees.pdf');
                                break;
                            case 'diploma':
                                $feeDocUrl = $isNigerian
                                    ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_DIPLOMA_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_DIPLOMA_Local_Fees.pdf')
                                    : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_DIPLOMA_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_DIPLOMA_International_Fees.pdf');
                                break;
                            case 'certificate':
                                $feeDocUrl = $isNigerian
                                    ? ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_CERTIFICATE_Local_Fees.pdf' : 'https://adullam.ng/fees/Onsite_CERTIFICATE_Local_Fees.pdf')
                                    : ($normalizedMode === 'online' ? 'https://adullam.ng/fees/Online_CERTIFICATE_International_Fees.pdf' : 'https://adullam.ng/fees/Onsite_CERTIFICATE_International_Fees.pdf');
                                break;
                        }

                        $inputs = [
                            'passport' => ['label' => 'Passport Photograph (JPG, PNG, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png'],
                            'ssce_cert' => ['label' => 'SSCE Certificate/Equivalent - First Sitting (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
                            'ssce_cert2' => ['label' => 'SSCE Certificate/Equivalent - Second Sitting (Optional, JPG, PNG, PDF, < 5mb)', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.pdf'],
                            'birth_cert' => ['label' => 'Birth Certificate (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
                            'origin_cert' => ['label' => 'Proof of Nationality ( E.g. Intl Passport, Local Gov. of Origin Cert., or National ID.) (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'],
                            'recommendation' => [
                                'label' => 'Recommendation Letter from Clergy (PDF, DOCX, DOC, < 5mb) <a href="https://adullam.ng/assets/documents/sample_recommendation.pdf" target="_blank" class="text-purple-600 underline text-xs ml-2 hover:text-purple-800">⬇️ Download Sample</a>', 
                                'required' => true, 
                                'accept' => '.pdf,.doc,.docx'
                            ],
                            'payment_proof' => [
                                'label' => 'Application Fees Proof (JPG, PNG, PDF, < 5mb) <a href="' . $feeDocUrl . '" target="_blank" class="text-purple-600 underline text-xs ml-2 hover:text-purple-800">⬇️ Download Fees Structure</a>', 
                                'required' => true, 
                                'accept' => '.jpg,.jpeg,.png,.pdf'
                            ],
                        ];
                        if ($isPGDTorMA) {
                            $inputs['degree_cert'] = ['label' => 'Degree Certificate (JPG, PNG, PDF, < 5mb)', 'required' => true, 'accept' => '.jpg,.jpeg,.png,.pdf'];
                            $inputs['transcript'] = ['label' => 'Transcript (Optional, JPG, PNG, PDF, < 5mb)', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.pdf'];
                        }
                        foreach ($inputs as $key => $conf):
                        ?>
                    <div
                        class="border p-4 rounded-lg bg-gray-50 flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-2 md:mb-0">
                            <label class="block font-medium text-gray-700"><?= $conf['label'] ?></label>
                            <?php if (!empty($docs[$key])): ?>
                            <p class="text-xs text-green-600 mt-1">✅ Already uploaded: <a href="<?= $docs[$key] ?>"
                                    target="_blank" class="text-purple-600 underline"><?= basename($docs[$key]) ?></a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="<?= $key ?>" accept="<?= $conf['accept'] ?>"
                            class="text-sm border p-2 rounded"
                            <?= $conf['required'] && empty($docs[$key]) ? 'required' : '' ?>>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php elseif ($step === 7): ?>
                <h2 class="text-2xl font-bold text-purple-800 mb-6 text-center">📋 Preview Your Application</h2>

                <div class="bg-white shadow p-6 rounded-xl mb-8">
                    <div class="flex flex-col sm:flex-row gap-6 items-center mb-8">
                        <div class="w-40 h-40 overflow-hidden rounded-full border border-gray-300 bg-gray-100">
                            <?php if (!empty($docs['passport'])): ?>
                            <img src="<?= htmlspecialchars($docs['passport']) ?>" alt="Passport Photo"
                                class="object-cover w-full h-full">
                            <?php else: ?>
                            <div class="text-sm text-gray-400 flex items-center justify-center h-full">No Photo</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold">Welcome, <?= htmlspecialchars($full_name) ?></h2>
                            <p class="text-sm text-gray-600 mt-2">Please carefully review all provided information and
                                uploaded files/documents. Verify that all details are accurate and complete or make any
                                necessary edits at this stage.</p>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <?php
                            $sections = [
                                'Program Details' => ['data' => $details, 'step' => 1],
                                'Personal Information' => ['data' => $personal, 'step' => 2],
                                'Church Information' => ['data' => $church, 'step' => 3],
                                'Autobiography' => ['data' => $autobio, 'step' => 4],
                                'References' => ['data' => $refs, 'step' => 5],
                            ];

                            foreach ($sections as $title => $info):
                                if (empty($info['data'])) continue;
                            ?>
                        <div class="mt-8">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-bold text-purple-700"><?= $title ?></h3>
                                <?php if (!$isSubmitted): ?>
                                <a href="?step=<?= $info['step'] ?>"
                                    class="text-sm text-purple-600 underline hover:text-purple-800">✏️ Edit</a>
                                <?php endif; ?>
                            </div>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 bg-gray-50 p-4 rounded-lg shadow">
                                <?php foreach ($info['data'] as $key => $value): 
                                            if (in_array($key, ['id', 'user_id'])) continue;
                                        ?>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1"><?= ucwords(str_replace('_', ' ', $key)) ?>
                                    </p>
                                    <p class="text-sm font-medium text-gray-800 bg-white border px-3 py-1 rounded">
                                        <?= nl2br(htmlspecialchars($value ?? '')) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="mt-8">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-bold text-purple-700">📎 Uploaded Documents</h3>
                                <?php if (!$isSubmitted): ?>
                                <a href="?step=6" class="text-sm text-purple-600 underline hover:text-purple-800">✏️
                                    Edit</a>
                                <?php endif; ?>
                            </div>
                            <ul class="space-y-2 bg-gray-50 p-4 rounded-lg shadow">
                                <?php 
                                    $docLabels = [
                                        'passport' => 'Passport Photograph',
                                        'ssce_cert' => 'SSCE Certificate - 1st Sitting',
                                        'ssce_cert2' => 'SSCE Certificate - 2nd Sitting',
                                        'birth_cert' => 'Birth Certificate',
                                        'origin_cert' => 'Proof of Nationality',
                                        'recommendation' => 'Recommendation Letter',
                                        'payment_proof' => 'Application Fees Proof',
                                        'degree_cert' => 'Degree Certificate',
                                        'transcript' => 'Transcript'
                                    ];
                                    foreach ($docLabels as $key => $label): 
                                        if (!empty($docs[$key])):
                                    ?>
                                <li class="flex justify-between items-center border-b py-2 last:border-0">
                                    <span class="text-sm font-medium text-gray-700"><?= $label ?></span>
                                    <a href="<?= htmlspecialchars($docs[$key]) ?>" target="_blank"
                                        class="bg-purple-700 text-white px-3 py-1 text-sm rounded hover:bg-purple-800">View</a>
                                </li>
                                <?php endif; endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <?php if (!$isSubmitted): ?>
                    <div class="mt-8 bg-yellow-50 p-4 border border-yellow-200 rounded">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" name="agree" required class="mt-1 h-4 w-4 text-purple-600">
                            <span class="text-sm text-gray-800">I confirm that all information provided is accurate and
                                truthful. I understand that falsifying information may lead to disqualification.</span>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="flex justify-between items-center mt-10 pt-6 border-t">
                    <?php if ($isSubmitted): ?>
                    <!-- Read-Only Navigation -->
                    <?php if ($step > 1): ?>
                    <a href="?step=<?= $step - 1 ?>"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Previous</a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <?php if ($step < 7): ?>
                    <a href="?step=<?= $step + 1 ?>"
                        class="px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 font-medium">Next</a>
                    <?php else: ?>
                    <a href="dashboard"
                        class="px-8 py-3 bg-blue-600 text-white rounded shadow-lg hover:bg-blue-700 font-bold text-lg">Back
                        to Dashboard</a>
                    <?php endif; ?>

                    <?php else: ?>
                    <!-- Editable Navigation -->
                    <?php if ($step > 1): ?>
                    <button type="submit" name="previous"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Previous</button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <?php if ($step < 7): ?>
                        <button type="submit" name="save"
                            class="px-6 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Save
                            Progress</button>
                        <button type="submit" name="continue"
                            class="px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 font-medium">Continue</button>
                        <?php else: ?>
                        <button type="submit" name="submit_application"
                            class="px-8 py-3 bg-green-600 text-white rounded shadow-lg hover:bg-green-700 font-bold text-lg">Submit
                            Application</button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
    // JS for Steps 1 & 2
    const programSelect = document.getElementById('program');
    if (programSelect) {
        programSelect.addEventListener('change', function() {
            const maDiv = document.getElementById('maFocusWrapper');
            if (this.value === 'MA') maDiv.classList.remove('hidden');
            else maDiv.classList.add('hidden');
        });
    }
    const sameAddress = document.getElementById('copyAddress');
    if (sameAddress) {
        sameAddress.addEventListener('change', function() {
            if (this.checked) {
                document.querySelector('[name="perm_address"]').value = document.querySelector(
                    '[name="res_address"]').value;
                document.querySelector('[name="perm_city"]').value = document.querySelector('[name="res_city"]')
                    .value;
                document.querySelector('[name="perm_state"]').value = document.querySelector(
                    '[name="res_state"]').value;
                document.querySelector('[name="perm_country"]').value = document.querySelector(
                    '[name="res_country"]').value;
            } else {
                document.querySelector('[name="perm_address"]').value = '';
                document.querySelector('[name="perm_city"]').value = '';
                document.querySelector('[name="perm_state"]').value = '';
                document.querySelector('[name="perm_country"]').value = '';
            }
        });
    }
    const maritalSelect = document.getElementById('maritalSelect');
    if (maritalSelect) {
        function toggleMarital() {
            const val = maritalSelect.value;
            document.getElementById('spouse-wrapper').classList.toggle('hidden', val !== 'Married');
            const childrenWrapper = document.getElementById('children-wrapper');
            const childrenField = document.getElementById('childrenField');
            if (val === 'Single') {
                childrenWrapper.classList.add('hidden');
                childrenField.value = '0';
                childrenField.required = false;
            } else {
                childrenWrapper.classList.remove('hidden');
                childrenField.required = true;
            }
        }
        maritalSelect.addEventListener('change', toggleMarital);
        toggleMarital();
    }

    <?php if (!empty($_SESSION['upload_errors'])): ?>
    Swal.fire({
        icon: 'warning',
        title: 'Upload Issues',
        html: '<?= implode("<br>", array_map("htmlspecialchars", $_SESSION['upload_errors'])) ?>',
        confirmButtonColor: '#6B21A8'
    });
    <?php unset($_SESSION['upload_errors']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: '<?= htmlspecialchars($_SESSION['success']) ?>',
        confirmButtonColor: '#6B21A8'
    });
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success_ref'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?= $_SESSION['success_ref'] ?>',
        confirmButtonColor: '#6B21A8'
    });
    <?php unset($_SESSION['success_ref']); ?>
    <?php endif; ?>

    <?php if ($isSubmitted): ?>
    // Read-Only Mode: Disable all inputs
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(el => {
            // Don't disable navigation buttons if I used button tags (I used <a> for nav, but just in case)
            if (!el.classList.contains('bg-gray-200') && !el.classList.contains('bg-purple-700')) {
                el.disabled = true;
            }
            // Actually, I replaced the buttons with <a> tags for Next/Prev, so strict disabling is fine for inputs.
            if (el.tagName !== 'A') el.disabled = true;
        });
    });
    <?php endif; ?>
    </script>
</body>

</html>