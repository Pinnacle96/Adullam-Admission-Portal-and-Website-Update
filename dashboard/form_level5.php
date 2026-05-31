<?php
// Redirect legacy form_level5 to the unified multi-step application form
header('Location: application_form?step=5', true, 301);
exit;

// ================= ERROR LOGGING =================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
error_reporting(E_ALL);
// =================================================

session_start();
require 'db.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

// Check registration status
$regOpen = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();
if (!$regOpen) {
    header("Location: student_dashboard");
    exit;
}

$user_id = $_SESSION['user_id'];

// 🔍 Fetch applicant's full name safely
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    $fullName = $applicant ? trim($applicant['first_name'] . ' ' . $applicant['last_name']) : "Applicant";
} catch (Exception $e) {
    error_log("Database error fetching user $user_id: " . $e->getMessage());
    $fullName = "Applicant";
}

// 🔄 Load saved references (for prefilling form)
$refData = [
    'ref1Name' => '', 'ref1Phone' => '', 'ref1Email' => '',
    'ref2Name' => '', 'ref2Phone' => '', 'ref2Email' => ''
];

try {
    $stmt = $pdo->prepare("SELECT * FROM application_references WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $refData = array_merge($refData, $row);
    }
} catch (Exception $e) {
    error_log("Failed to load reference info for $user_id: " . $e->getMessage());
}

// 🔑 Generate secure token
function generateToken($length = 40) {
    return bin2hex(random_bytes($length / 2));
}

// ========== FORM PROCESSING ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $ref1Name  = htmlspecialchars(trim($_POST['ref1Name']));
    $ref1Phone = htmlspecialchars(trim($_POST['ref1Phone']));
    $ref1Email = filter_var(trim($_POST['ref1Email']), FILTER_SANITIZE_EMAIL);
    $ref2Name  = htmlspecialchars(trim($_POST['ref2Name']));
    $ref2Phone = htmlspecialchars(trim($_POST['ref2Phone']));
    $ref2Email = filter_var(trim($_POST['ref2Email']), FILTER_SANITIZE_EMAIL);

    // Validate emails
    if (!filter_var($ref1Email, FILTER_VALIDATE_EMAIL) || !filter_var($ref2Email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Email',
                text: 'Please enter valid email addresses for both referees.',
                confirmButtonColor: '#6B21A8'
            });
        </script>";
        exit;
    }

    // Validate phone numbers (server-side)
    $phonePattern = '/^\+\d{6,15}$/';
    if (!preg_match($phonePattern, $ref1Phone) || !preg_match($phonePattern, $ref2Phone)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Phone Number',
                text: 'Please enter valid phone numbers with country code (e.g., +2348012345678).',
                confirmButtonColor: '#6B21A8'
            });
        </script>";
        exit;
    }

    // Save reference info
    try {
        $stmt = $pdo->prepare("INSERT INTO application_references 
            (user_id, ref1Name, ref1Phone, ref1Email, ref2Name, ref2Phone, ref2Email)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ref1Name = VALUES(ref1Name),
                ref1Phone = VALUES(ref1Phone),
                ref1Email = VALUES(ref1Email),
                ref2Name = VALUES(ref2Name),
                ref2Phone = VALUES(ref2Phone),
                ref2Email = VALUES(ref2Email)");
        $stmt->execute([$user_id, $ref1Name, $ref1Phone, $ref1Email, $ref2Name, $ref2Phone, $ref2Email]);
    } catch (Exception $e) {
        error_log("Failed to save reference info for user $user_id: " . $e->getMessage());
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not save reference info. Please try again.',
                confirmButtonColor: '#6B21A8'
            });
        </script>";
        exit;
    }

    // Process recommendation requests only on Continue
    if (isset($_POST['continue'])) {
        $referees = [
            ['email' => $ref1Email, 'name' => $ref1Name],
            ['email' => $ref2Email, 'name' => $ref2Name],
        ];
        $emailErrors = [];

        foreach ($referees as $ref) {
            try {
                // ✅ Generate token ONCE per referee
                $token = generateToken();

                $stmt = $pdo->prepare("INSERT INTO application_recommendations
                    (user_id, referee_email, referee_name, token, submitted)
                    VALUES (?, ?, ?, ?, 0)
                    ON DUPLICATE KEY UPDATE
                        referee_name = VALUES(referee_name),
                        referee_email = VALUES(referee_email),
                        token = IF(submitted = 0, VALUES(token), token)");
                $stmt->execute([$user_id, $ref['email'], $ref['name'], $token]);

                // ✅ Use the SAME token stored in DB
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
                } else {
                    $emailErrors[] = "Email system not configured.";
                }

            } catch (Exception $e) {
                error_log("Failed to setup recommendation for {$ref['email']}: " . $e->getMessage());
                $emailErrors[] = "Error processing {$ref['name']}";
            }
        }

        // Show feedback
        if (!empty($emailErrors)) {
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Partial Success',
                    text: 'Some emails could not be sent. Please verify addresses.',
                    confirmButtonColor: '#6B21A8'
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Recommendation requests sent successfully.',
                    confirmButtonColor: '#6B21A8'
                });
            </script>";
        }

        // Move to next form
        $pdo->prepare("UPDATE applications SET current_level = 6 WHERE user_id = ?")->execute([$user_id]);
        echo "<script>setTimeout(() => { window.location.href = 'form_level6'; }, 2000);</script>";
        exit;
    }

    // Save for Later
    if (isset($_POST['save'])) {
        echo "<script>
            localStorage.setItem('form5_saved', '1');
            window.location.href = 'form_level5';
        </script>";
        exit;
    }

    // Previous
    if (isset($_POST['previous'])) {
        echo "<script>window.location.href = 'form_level4';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application - Step 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-4xl">
        <h2 class="text-xl font-bold text-purple-800 mb-2 text-center">Step 5 of 6: Reference Information</h2>
        <form method="POST" class="space-y-6">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference 1 Name</label>
                    <input type="text" name="ref1Name" value="<?= htmlspecialchars($refData['ref1Name']) ?>" required
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference 1 Phone</label>
                    <input type="tel" name="ref1Phone" value="<?= htmlspecialchars($refData['ref1Phone']) ?>" required
                        placeholder="+2348012345678"
                        pattern="^\+\d{6,15}$"
                        oninvalid="this.setCustomValidity('Enter a valid phone number with country code, e.g. +2348012345678')"
                        oninput="this.setCustomValidity('')"
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                    <small class="text-gray-500">Include country code (e.g., +234 for Nigeria)</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference 1 Email</label>
                    <input type="email" name="ref1Email" value="<?= htmlspecialchars($refData['ref1Email']) ?>" required
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 pt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference 2 Name</label>
                    <input type="text" name="ref2Name" value="<?= htmlspecialchars($refData['ref2Name']) ?>" required
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference 2 Phone</label>
                    <input type="tel" name="ref2Phone" value="<?= htmlspecialchars($refData['ref2Phone']) ?>" required
                        placeholder="+2348012345678"
                        pattern="^\+\d{6,15}$"
                        oninvalid="this.setCustomValidity('Enter a valid phone number with country code, e.g. +2348012345678')"
                        oninput="this.setCustomValidity('')"
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                    <small class="text-gray-500">Include country code (e.g., +234 for Nigeria)</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference 2 Email</label>
                    <input type="email" name="ref2Email" value="<?= htmlspecialchars($refData['ref2Email']) ?>" required
                        class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between gap-4 pt-6">
                <button type="submit" name="previous"
                    class="w-full sm:w-auto bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow">⬅ Previous</button>
                <button type="submit" name="save"
                    class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-white px-6 py-2 rounded-lg shadow">💾 Save for Later</button>
                <button type="submit" name="continue"
                    class="w-full sm:w-auto bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg shadow">Next ➡</button>
            </div>
        </form>
    </div>

    <script>
    if (localStorage.getItem('form5_saved')) {
        Swal.fire({
            icon: 'success',
            title: 'Saved!',
            text: 'Your references have been saved.',
            confirmButtonColor: '#6B21A8'
        });
        localStorage.removeItem('form5_saved');
    }
    </script>
</body>
</html>
