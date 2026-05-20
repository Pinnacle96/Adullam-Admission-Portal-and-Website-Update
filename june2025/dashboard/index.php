<?php
require 'db.php';
session_start();

// // Redirect if already logged in
// if (isset($_SESSION['applicant_id'])) {
//     header('Location: dashboard.php');
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Adullam Seminary Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        
        .form-input {
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(126, 58, 242, 0.15);
        }
        
        .btn-primary {
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(107, 33, 168, 0.2);
        }
        
        .notice-banner {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center px-4 py-8">
    <div class="bg-white rounded-xl shadow-sm w-full max-w-md overflow-hidden border border-gray-100">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-purple-700 to-purple-600 p-6 text-center">
            <div class="flex justify-center mb-3">
                <img src="../assets/img/favicon.png" alt="Adullam Logo" class="h-14 w-14" />
            </div>
            <h1 class="text-2xl font-bold text-white">RCN Theological Seminary</h1>
            <p class="text-purple-100 mt-1">Adullam Portal</p>
        </div>

        <!-- Notice Banner -->
        <?php
        $banner = $pdo->query("SELECT value FROM settings WHERE `key` = 'notice_banner'")->fetchColumn();
        if ($banner):
        ?>
            <div class="notice-banner bg-blue-50 text-blue-800 text-sm p-3 border-b border-blue-100 flex items-start">
                <svg class="flex-shrink-0 h-4 w-4 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd"></path>
                </svg>
                <span><?= htmlspecialchars($banner) ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Title -->
        <div class="border-b border-gray-200">
            <div class="flex-1 py-4 font-medium text-purple-700 text-center text-lg">
                Create Account
            </div>
        </div>

        <!-- Register Form -->
        <form id="form-register" class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" name="first_name" required autocomplete="given-name"
                        class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="John" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="last_name" required autocomplete="family-name"
                        class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Doe" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name (Optional)</label>
                <input type="text" name="middle_name" autocomplete="additional-name"
                    class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Middle" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" required autocomplete="email"
                    class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="your@email.com" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" name="phone" required autocomplete="tel"
                    class="form-input w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="+234 812 345 6789" />
                <p class="mt-1 text-xs text-gray-500">Include country code</p>
            </div>
            <button type="submit" class="btn-primary w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-4 rounded-lg shadow-sm">
                Create Account
            </button>
            <p class="text-sm text-center text-gray-600">
                Already have an account? 
                <a href="applicant_login.php" class="font-medium text-purple-600 hover:text-purple-500">Sign in here</a>
            </p>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission handlers with loading states
            function handleFormSubmit(form, endpoint, successMessage, redirectUrl = null) {
                return async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    
                    // Set loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    `;

                    try {
                        const formData = new FormData(form);
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: successMessage,
                                text: result.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                if (redirectUrl) {
                                    window.location.href = redirectUrl;
                                } else if (result.redirect) {
                                    window.location.href = result.redirect;
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message || 'Something went wrong',
                                confirmButtonColor: '#7e3af2'
                            });
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Network error occurred. Please try again.',
                            confirmButtonColor: '#7e3af2'
                        });
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                };
            }

            // Attach handler for registration form
            const formRegister = document.getElementById('form-register');
            formRegister.addEventListener('submit', handleFormSubmit(
                formRegister, 
                'register.php', 
                'Account created!',
                'verify.php'
            ));

            // Show success message if coming from password set
            if (localStorage.getItem('pwSet')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Set!',
                    text: 'You can now log in with your email and password.',
                    confirmButtonColor: '#7e3af2'
                });
                localStorage.removeItem('pwSet');
            }
        });
    </script>
</body>

</html>