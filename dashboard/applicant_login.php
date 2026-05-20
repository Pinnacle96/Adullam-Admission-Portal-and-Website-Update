<?php
session_start();
require 'db.php';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admission Login – Adullam Seminary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png" />
    <style>
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(107, 33, 168, 0.2);
        }
        .btn-primary {
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(107, 33, 168, 0.2);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-purple-50 to-gray-100 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="bg-white shadow-xl rounded-2xl w-full max-w-md overflow-hidden">
        <!-- Header Section -->
        <div class="bg-purple-700 p-6 text-center">
            <div class="flex justify-center mb-4">
                <img src="../assets/img/favicon.png" alt="Adullam Logo" class="h-14 w-14" />
            </div>
            <h1 class="text-2xl font-bold text-white">Adullam Seminary</h1>
            <p class="text-purple-100 mt-1">Admission Portal Login</p>
        </div>

        <!-- Main Content -->
        <div class="p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
                Login to your account
            </h2>
<?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'error'): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                </p>
                <p class="mt-2 text-sm text-purple-700">
                    Didn’t get the email? 
                </p>
                <!-- Resend Verification Form -->
                <form action="resend_verification" method="POST" class="mt-2 flex space-x-2">
                    <input type="email" name="email" required placeholder="Enter your email"
                        class="form-input px-3 py-2 border border-gray-300 rounded-md w-full focus:ring-purple-500 focus:border-purple-500 text-sm" />
                    <button type="submit"
                        class="btn-primary bg-purple-700 hover:bg-purple-800 text-white px-3 py-2 rounded-md text-sm">
                        Resend
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['status'], $_SESSION['message']); ?>
<?php elseif (isset($_SESSION['status']) && $_SESSION['status'] === 'success'): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1.707-7.707a1 1 0 011.414 0L10 11.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                </p>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['status'], $_SESSION['message']); ?>
<?php endif; ?>

            <!-- Error Message Display -->
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo htmlspecialchars($_GET['error']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="formLogin" method="POST" action="login" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="login_email" required autocomplete="email"
                        class="form-input w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500" 
                        placeholder="your@email.com" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                        class="form-input w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500" 
                        placeholder="••••••••" />
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" 
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded" />
                        <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>
                    <div class="text-sm">
                        <a href="forgot_password" class="font-medium text-purple-600 hover:text-purple-500">Forgot password?</a>
                    </div>
                </div>
                <button type="submit"
                    class="btn-primary w-full bg-purple-700 hover:bg-purple-800 text-white py-3 px-4 rounded-md shadow-md transition">
                    <span class="flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        Sign In
                    </span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <a href="index" class="font-medium text-purple-600 hover:text-purple-500">Apply now</a>
                </p>
            </div>
        </div>
    </div>

   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formLogin = document.getElementById('formLogin');
        
        formLogin.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Signing in...
            `;

            try {
                const formData = new FormData(formLogin);
                const response = await fetch('login', {
                    method: 'POST',
                    body: formData
                });

                let result = null;
                try {
                    result = await response.json();
                } catch (e) {
                    const text = await response.text();
                    throw new Error(text || 'Invalid server response');
                }

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        text: 'Redirecting to your dashboard...',
                        footer: result.request_id ? `Trace ID: ${result.request_id}` : undefined,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = result.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: result.message || 'Invalid email or password',
                        footer: result.request_id ? `Trace ID: ${result.request_id}` : undefined,
                        confirmButtonColor: '#6B21A8'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonColor: '#6B21A8'
                });
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });

        // ✅ Show success alert if password was just set
       // ✅ Show success alert only once, right after coming from set_password.php
if (localStorage.getItem('pwSet')) {
    Swal.fire({
        icon: 'success',
        title: 'Password Set!',
        text: 'You can now log in with your new password.',
        confirmButtonColor: '#6B21A8'
    });
    localStorage.removeItem('pwSet'); // clear so it never repeats
}

    });
</script>

</body>
</html>
