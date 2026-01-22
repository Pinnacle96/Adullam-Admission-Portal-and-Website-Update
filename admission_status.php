<?php
require_once 'dashboard/db.php';
session_start();
header('Content-Type: text/html');

$admission_no = '';
$status = '';
$message = '';
$fullName = '';
$program = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $admission_no_post = trim($_POST['admission_no'] ?? '');

    if (empty($admission_no_post)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter your application number.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT 
            a.status, u.first_name, u.last_name, u.email, ad.program, ad.ma_focus
            FROM applications a
            JOIN users u ON a.user_id = u.id
            JOIN application_details ad ON a.user_id = ad.user_id
            WHERE a.admission_no = ?");
        $stmt->execute([$admission_no_post]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $programName = '';
            // Determine program name from code
            switch (strtoupper($result['program'])) {
                case 'MA':
                    $programName = 'Master of Arts';
                    if (!empty($result['ma_focus'])) {
                        $programName .= ' (' . $result['ma_focus'] . ')';
                    }
                    break;
                case 'PGDT':
                    $programName = 'Postgraduate Diploma';
                    break;
                case 'B.DIV':
                    $programName = 'Bachelor of Divinity';
                    break;
                case 'DIPLOMA':
                    $programName = 'Diploma in Theology';
                    break;
                case 'CERTIFICATE':
                    $programName = 'Certificate in Theology';
                    break;
                default:
                    $programName = $result['program'];
            }

            $response = [
                'status' => 'success',
                'applicant_status' => $result['status'],
                'fullName' => $result['first_name'] . ' ' . $result['last_name'],
                'email' => $result['email'],
                'program' => $programName,
                'admission_no' => $admission_no_post
            ];
            echo json_encode($response);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Application number not found.']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again later.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <style>
        .result-box {
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex flex-col items-center justify-center text-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Check Your Admission Status</h1>
            <p class="text-gray-600 mb-6">Enter your admission number below to view your status.</p>
        </div>
        <form id="statusForm" class="space-y-4">
            <div>
                <label for="admission_no" class="sr-only">Admission Number</label>
                <input type="text" id="admission_no" name="admission_no" placeholder="Enter Application Number" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all duration-300">
            </div>
            <button type="submit"
                    class="w-full bg-purple-700 text-white font-semibold py-3 rounded-lg hover:bg-purple-800 transition-colors duration-300">
                Check Status
            </button>
        </form>

        <div id="statusResult" class="mt-8 hidden">
            </div>

        <div class="mt-8 text-center text-sm">
            <a href="https://adullam.ng" class="text-purple-700 hover:text-purple-900 font-semibold underline">&larr; Back to Adullam.ng</a>
        </div>
    </div>

    <script>
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const statusResultDiv = document.getElementById('statusResult');

            statusResultDiv.innerHTML = '<div class="text-center text-gray-500">Checking...</div>';
            statusResultDiv.classList.remove('hidden');

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let message = '';
                    let color = '';
                    let icon = '';
                    let action = '';

                    switch (data.applicant_status) {
                        case 'pending':
                            message = 'Your application is currently under review.';
                            color = 'text-yellow-600';
                            icon = '⏳';
                            break;
                        case 'admitted':
                            message = 'Congratulations! You have been provisionally admitted.';
                            color = 'text-green-600';
                            icon = '🎉';
                            action = `<a href="dashboard/applicant_login" class="inline-block mt-4 text-purple-700 hover:text-purple-900 font-semibold underline">Go to your dashboard</a>
                                      <button id="printBtn" class="mt-4 ml-4 bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg hover:bg-purple-800 transition-colors duration-300">Print Admission Letter</button>`;
                            break;
                        case 'denied':
                            message = 'We regret to inform you that your application was unsuccessful at this time.';
                            color = 'text-red-600';
                            icon = '❌';
                            break;
                        default:
                            message = 'Your application status is not yet available.';
                            color = 'text-gray-600';
                            icon = 'ℹ️';
                            break;
                    }

                    statusResultDiv.innerHTML = `
                        <div class="result-box p-6 rounded-lg border-2 ${
                            data.applicant_status === 'admitted' ? 'border-green-400 bg-green-50' :
                            data.applicant_status === 'denied' ? 'border-red-400 bg-red-50' :
                            'border-yellow-400 bg-yellow-50'
                        }">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">${icon}</span>
                                <h3 class="text-lg font-bold ${color}">${message}</h3>
                            </div>
                            <ul class="text-gray-700 leading-relaxed space-y-2 text-sm">
                                <li><strong>Name:</strong> ${data.fullName}</li>
                                <li><strong>Application Number:</strong> ${data.admission_no}</li>
                                <li><strong>Program:</strong> ${data.program}</li>
                            </ul>
                            ${action}
                        </div>
                    `;

                    // Add print functionality if admitted
                    if (data.applicant_status === 'admitted') {
                        document.getElementById('printBtn').addEventListener('click', () => {
                            const printWindow = window.open('', '_blank');
                            printWindow.document.write(`
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Application Status</title>
                                    <style>
                                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 2rem; }
                                        .container { max-width: 800px; margin: auto; padding: 2rem; border: 1px solid #ccc; border-radius: 8px; }
                                        .header { text-align: center; margin-bottom: 2rem; }
                                        .header h1 { color: #6B21A8; font-size: 2rem; font-weight: bold; }
                                        .details p { margin: 0.5rem 0; }
                                        .details strong { color: #333; }
                                        .congratulations { margin-top: 2rem; font-size: 1.25rem; font-weight: bold; color: #15803d; }
                                    </style>
                                </head>
                                <body>
                                    <div class="container">
                                        <div class="header">
                                            <h1>Adullam Seminary</h1>
                                            <p>Application Status</p>
                                        </div>
                                        <div class="details">
                                            <p><strong>To:</strong> ${data.fullName}</p>
                                            <p><strong>Email:</strong> ${data.email}</p>
                                            <p><strong>Application Number:</strong> ${data.admission_no}</p>
                                            <p><strong>Program:</strong> ${data.program}</p>
                                        </div>
                                        <p class="congratulations">
                                            Congratulations! You have been provisionally admitted into the ${data.program} program at Adullam Seminary.
                                        </p>
                                        <p>
                                            This is a provisional admission status pending the submission of all required documents and payment of the acceptance fee.
                                            We look forward to welcoming you to the Adullam family.
                                        </p>
                                        <p style="margin-top: 2rem;">
                                            Sincerely,<br>
                                            Adullam Admissions Team
                                        </p>
                                    </div>
                                </body>
                                </html>
                            `);
                            printWindow.document.close();
                            printWindow.print();
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                        confirmButtonColor: '#6B21A8'
                    });
                    statusResultDiv.innerHTML = '';
                    statusResultDiv.classList.add('hidden');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Unable to connect to the server. Please try again.',
                    confirmButtonColor: '#6B21A8'
                });
                statusResultDiv.innerHTML = '';
                statusResultDiv.classList.add('hidden');
            });
        });
    </script>
</body>
</html>