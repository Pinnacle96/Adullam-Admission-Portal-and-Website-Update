<?php
session_start();
require 'db.php';
require_once 'mailer.php';

$token = $_GET['token'] ?? '';
$valid = false;
$referee = null;
$message = '';

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM application_recommendations WHERE token = ?");
    $stmt->execute([$token]);
    $referee = $stmt->fetch();

    // Check if the token exists
    if (!$referee) {
        $message = "This recommendation link is invalid. Please contact the applicant to get a new link.";
    } elseif ($referee['submitted'] == 1) {
        $message = "This recommendation has already been submitted.";
    } else {
        $valid = true; // Link is valid and unsubmitted
    }
} else {
    $message = "No token provided. Please use the complete link from the email.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $postToken = $_POST['token'];
    $recommendation = trim($_POST['recommendation']);

    // Re-verify the token to prevent race conditions
    $stmt = $pdo->prepare("SELECT * FROM application_recommendations WHERE token = ? AND submitted = 0");
    $stmt->execute([$postToken]);
    $referee = $stmt->fetch();

    if (!$referee) {
        $message = "Submission failed. The link is invalid or already submitted.";
        $valid = false;
    } else {
        // Validate recommendation length
        if (strlen($recommendation) < 50) {
            $message = "Recommendation is too short. Please provide at least 50 characters.";
            $valid = true; // keep form visible
        } else {
            $updateStmt = $pdo->prepare("UPDATE application_recommendations SET recommendation = ?, submitted = 1 WHERE token = ?");
            $updateStmt->execute([$recommendation, $postToken]);

            // Fetch applicant details
            $appStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name, email FROM users WHERE id = ?");
            $appStmt->execute([$referee['user_id']]);
            $applicant = $appStmt->fetch();

            if ($applicant) {
                $applicantName = $applicant['full_name'];
                $applicantEmail = $applicant['email'];
                $refName = $referee['referee_name'];

                // Send alert to applicant
                $subject = "📬 Recommendation Received from $refName";
                $body = "
                    <div style='font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:600px;margin:auto;padding:20px;background-color:#f9fafb;border-radius:8px;border:1px solid #eee;'>
                        <div style='text-align:center;margin-bottom:30px;'>
                            <h2 style='color:#6B21A8;margin-top:10px;'>Adullam Seminary Admissions</h2>
                        </div>
                        <p style='font-size:16px;color:#111;'>Dear <strong>" . htmlspecialchars($applicantName) . "</strong>,</p>
                        <p style='font-size:15px;color:#333;line-height:1.6;'>
                            We are pleased to inform you that your referee, <strong>" . htmlspecialchars($refName) . "</strong>, 
                            has successfully submitted a letter of recommendation for your application to Adullam Seminary.
                        </p>
                        <p style='font-size:15px;color:#333;line-height:1.6;'>
                            No further action is required from their end. You may proceed with the next stage of your application at your convenience.
                        </p>
                        <div style='text-align:center;margin:30px 0;'>
                            <a href='https://adullam.ng/dashboard/index' style='background-color:#6B21A8;color:white;
                                padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;'>
                                Continue Application
                            </a>
                        </div>
                        <p style='font-size:14px;color:#555;border-top:1px solid #ddd;padding-top:20px;'>
                            Blessings,<br>
                            <strong>Adullam Admissions Team</strong><br>
                            <a href='mailto:admissions@adullam.ng' style='color:#6B21A8;'>admissions@adullam.ng</a><br>
                            <a href='https://adullam.ng' style='color:#6B21A8;'>www.adullam.ng</a>
                        </p>
                    </div>";
                sendMail($applicantEmail, "Adullam Admissions", $subject, $body);
            }

            // Set a session variable to show success message on redirect
            $_SESSION['rec_done'] = true;
            header("Location: recommend?token={$postToken}");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit Recommendation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
  <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-2xl">
    <?php if ($valid): ?>
      <h2 class="text-xl font-bold text-purple-800 text-center mb-2">Recommendation for Adullam Seminary</h2>
      <p class="text-sm text-gray-600 text-center mb-4">
        Hi <strong><?= htmlspecialchars($referee['referee_name']) ?></strong>, 
        please write your recommendation for the applicant below.
      </p>
      <?php if (!empty($message)): ?>
        <p class="text-sm text-red-600 text-center mb-4"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>
      <form method="POST" class="space-y-6">
        <textarea name="recommendation" required rows="8" 
          class="w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600"
          placeholder="Write your honest recommendation..."></textarea>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <button type="submit" class="w-full bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg shadow">
          Submit Recommendation
        </button>
      </form>
    <?php else: ?>
      <h2 class="text-xl font-bold text-red-700 text-center">Invalid or Already Used Link</h2>
      <p class="text-sm text-gray-600 text-center"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
  </div>

  <script>
    <?php if (isset($_SESSION['rec_done']) && $_SESSION['rec_done']): ?>
      Swal.fire({
        icon: 'success',
        title: 'Thank You!',
        text: 'Your recommendation was submitted successfully.',
        confirmButtonColor: '#6B21A8'
      });
      <?php unset($_SESSION['rec_done']); ?>
    <?php endif; ?>
  </script>
</body>
</html>
