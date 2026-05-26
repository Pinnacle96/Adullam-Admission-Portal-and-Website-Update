<?php
// Define variables if not already set by parent page
if (!isset($status)) $status = 'incomplete';
if (!isset($mode)) $mode = 'online';
if (!isset($isSubmitted)) $isSubmitted = false;

// Get hostel registration status (for conditional link display)
if (!isset($hostelRegistrationOpen)) {
    // If not passed from parent, check database
    if (!isset($pdo)) {
        require_once 'db.php';
    }
    if (!function_exists('isHostelRegistrationOpen')) {
        require_once 'functions.php';
    }

    // Determine student type to check correct registration status
    // For onsite students, assume returning student registration
    $checkType = (isset($mode) && strtolower($mode) === 'onsite') ? 'returning' : 'new';
    $hostelRegistrationOpen = isHostelRegistrationOpen($pdo, $checkType);
}
?>
<style>
    /* Smooth transition for sidebar */
    #sidebar {
        transition: transform 0.3s ease-in-out;
    }

    /* Hide sidebar on mobile by default */
    @media (max-width: 1023px) {
        #sidebar {
            transform: translateX(-100%);
        }

        #sidebar.open {
            transform: translateX(0);
        }
    }
</style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Mobile Toggle Button -->
    <div class="bg-purple-900 text-white p-4 lg:hidden flex justify-between items-center">
        <span class="text-lg font-semibold">📚 Adullam</span>
        <button id="toggleSidebar" class="text-white text-2xl focus:outline-none">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>
    </div>

    <!-- Main Container for Sidebar and Content -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar"
            class="fixed lg:sticky top-0 w-64 flex flex-col bg-purple-900 text-white h-screen overflow-y-auto p-6 space-y-4 z-50">
            <h2 class="text-xl font-bold lg:block hidden flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Dashboard
            </h2>
            <nav class="space-y-2">
                <a href="dashboard" class="flex items-center px-4 py-2 rounded hover:bg-purple-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </a>
                <?php if (isset($status) && $status === 'admitted'): ?>
                    <a href="payment_proof" class="flex items-center px-4 py-2 rounded hover:bg-purple-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Upload proof of Payment
                    </a>
                <?php endif; ?>
                <?php if (isset($status) && $status === 'admitted' && $hostelRegistrationOpen): ?>
                    <a href="register_hostel_unified" class="flex items-center px-4 py-2 rounded hover:bg-purple-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Hostel Registration
                    </a>
                <?php endif; ?>

                <a href="application_form" class="flex items-center hover:bg-purple-700 px-3 py-2 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= (isset($isSubmitted) && $isSubmitted) ? 'View Application' : 'Continue Application' ?>
                </a>
                <a href="profile" class="flex items-center px-4 py-2 rounded hover:bg-purple-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile
                </a>
                <a href="logout" class="flex items-center px-4 py-2 text-red-300 hover:text-white hover:bg-purple-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </nav>
        </aside>