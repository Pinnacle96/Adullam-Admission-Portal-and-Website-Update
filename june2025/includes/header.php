<?php
include('includes/dbconnection.php');
session_start();
error_reporting(0);

// Securely fetch contact info
$contactData = [];
if (isset($con)) {
    $stmt = mysqli_prepare($con, "SELECT Email, MobileNumber FROM tblpage WHERE PageType = ?");
    $pageType = 'contactus';
    mysqli_stmt_bind_param($stmt, 's', $pageType);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $contactData = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RCN Theological Seminary - Adullam</title>
  <meta name="description" content="RCN Theological Seminary - Adullam exists to train and equip Christ-like leaders.">

  <link rel="icon" href="assets/img/favicon.png" />
  <link rel="apple-touch-icon" href="assets/img/favicon.png" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#633F83',
            deepPurple: '#5E397F',
            lightGray: '#BABBBD',
            coolGray: '#7A7C7E',
            softGray: '#CFD0D1',
          }
        }
      }
    }
  </script>
  <style>
    .dropdown-menu {
      display: none;
    }

    .dropdown-menu.open {
      display: block !important;
    }

    @media (min-width: 1280px) {
      .group:hover .dropdown-menu {
        display: block;
      }
    }
  </style>
</head>

<body class="bg-white text-gray-800 font-sans">
  <header class="sticky top-0 bg-white shadow z-50">
    <div class="bg-gray-100 text-xs sm:text-sm py-2">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 sm:gap-2 text-gray-600">
          <?php if (!empty($contactData)): ?>
            <?php foreach ($contactData as $row): ?>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span><?= htmlspecialchars($row['Email']) ?></span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>+234<?= htmlspecialchars($row['MobileNumber']) ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Logo + Navigation -->
    <div class="py-4">
      <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
        <a href="index.php"><img src="assets/img/logo1.png" alt="Adullam Logo" class="h-12 w-auto" /></a>
        <button id="mobile-toggle" class="xl:hidden text-primary focus:outline-none" aria-label="Toggle menu">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16m-7 6h7" />
          </svg>
        </button>

        <nav id="navmenu"
          class="mobile-nav hidden xl:block w-full xl:w-auto absolute xl:static top-full left-0 bg-white xl:bg-transparent shadow-xl xl:shadow-none z-50">
          <ul class="flex flex-col xl:flex-row gap-2 xl:gap-6 text-primary font-medium p-4 xl:p-0">
            <li><a href="index.php" class="block py-2 hover:text-deepPurple">Home</a></li>
            <li><a href="about.php" class="block py-2 hover:text-deepPurple">About Us</a></li>

            <!-- Dropdown -->
            <li class="relative group">
              <button class="dropdown-toggle block py-2 w-full text-left xl:hover:text-deepPurple" aria-expanded="false">
                Academics Program
                <svg class="w-4 h-4 inline-block ml-1 xl:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <ul class="dropdown-menu hidden xl:absolute group-hover:block bg-white xl:shadow-lg mt-1 xl:mt-2 rounded-md w-full xl:w-56 text-gray-700 xl:border">
                <li><a href="cert.php" class="block px-4 py-2 hover:bg-purple-100">Certificate in Theology</a></li>
                <li><a href="dip.php" class="block px-4 py-2 hover:bg-purple-100">Diploma in Theology</a></li>
                <li><a href="biv.php" class="block px-4 py-2 hover:bg-purple-100">Bachelor of Divinity</a></li>
                <li><a href="pgdt.php" class="block px-4 py-2 hover:bg-purple-100">Postgraduate Diploma</a></li>
                <li><a href="masters.php" class="block px-4 py-2 hover:bg-purple-100">M.A Christian Apologetics</a></li>
                <li><a href="masters.php" class="block px-4 py-2 hover:bg-purple-100">M.A Biblical Studies (OT/NT)</a></li>
                <!--<li><a href="short.php" class="block px-4 py-2 hover:bg-purple-100">Short Course</a></li>-->
              </ul>
            </li>
            <li><a href="online_school.php" class="block py-2 hover:text-deepPurple">Online School</a></li>
             <li><a href="admissions.php" class="block py-2 hover:text-deepPurple">Admission</a></li>
            <!--<li><a href="notice-details.php" class="block py-2 hover:text-deepPurple">Events</a></li>-->
             <li><a href="partner.php" class="block py-2 hover:text-deepPurple">Partnership</a></li>
            <li><a href="contact.php" class="block py-2 hover:text-deepPurple">Contact Us</a></li>
           
           <li class="relative group">
  <button class="dropdown-toggle block py-2 w-full text-left xl:hover:text-deepPurple" aria-expanded="false">
   Student Portal
    <svg class="w-4 h-4 inline-block ml-1 xl:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>
  <ul class="dropdown-menu hidden xl:absolute group-hover:block bg-white xl:shadow-lg mt-1 xl:mt-2 rounded-md w-full xl:w-56 text-gray-700 xl:border transition-all duration-300 ease-in-out">
      <li>
      <a href="dashboard/applicant_login.php" target="_blank" class="block px-4 py-2 hover:bg-purple-100 transition-colors duration-200">
        Login/Apply
      </a>
    </li>
    
    <li>
      <a href="admission_status.php" target="_blank" class="block px-4 py-2 hover:bg-purple-100 transition-colors duration-200">
        Admission Status
      </a>
    </li>
   <li>
      <a href="dashboard/register_hostel_returning.php" target="_blank" class="block px-4 py-2 hover:bg-purple-100 transition-colors duration-200">
        Hostel Registration (Returning student)
      </a>
    </li>
  </ul>
</li>
  </ul>
</li>
          </ul>
        </nav>
      </div>
    </div>
  </header>

  <!-- Nav behavior -->
  <script>
    const toggleButton = document.getElementById('mobile-toggle');
    const navMenu = document.getElementById('navmenu');
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    toggleButton.addEventListener('click', () => {
      navMenu.classList.toggle('hidden');
      navMenu.classList.toggle('open');
    });

    dropdownToggles.forEach(toggle => {
      toggle.addEventListener('click', (e) => {
        if (window.innerWidth < 1280) {
          e.preventDefault();
          const dropdown = toggle.nextElementSibling;
          dropdown.classList.toggle('open');
          toggle.setAttribute('aria-expanded', toggle.getAttribute('aria-expanded') === 'false' ? 'true' : 'false');
        }
      });
    });

    document.addEventListener('click', (e) => {
      if (!navMenu.contains(e.target) && !toggleButton.contains(e.target)) {
        navMenu.classList.add('hidden');
        navMenu.classList.remove('open');
        document.querySelectorAll('.dropdown-menu.open').forEach(dropdown => dropdown.classList.remove('open'));
        dropdownToggles.forEach(toggle => toggle.setAttribute('aria-expanded', 'false'));
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 1280) {
        document.querySelectorAll('.dropdown-menu.open').forEach(dropdown => dropdown.classList.remove('open'));
        dropdownToggles.forEach(toggle => toggle.setAttribute('aria-expanded', 'false'));
      }
    });
     let dropdownTimeout;
  const dropdownHoverDelay = 300; // 300ms delay before closing

  document.querySelectorAll('.group').forEach(group => {
    const dropdown = group.querySelector('.dropdown-menu');
    
    // Desktop hover behavior
    group.addEventListener('mouseenter', () => {
      clearTimeout(dropdownTimeout);
      if (window.innerWidth >= 1280) {
        dropdown.classList.add('open');
      }
    });
    
    group.addEventListener('mouseleave', () => {
      if (window.innerWidth >= 1280) {
        dropdownTimeout = setTimeout(() => {
          dropdown.classList.remove('open');
        }, dropdownHoverDelay);
      }
    });
    
    // Keep dropdown open when hovering over it
    dropdown.addEventListener('mouseenter', () => {
      clearTimeout(dropdownTimeout);
    });
    
    dropdown.addEventListener('mouseleave', () => {
      if (window.innerWidth >= 1280) {
        dropdownTimeout = setTimeout(() => {
          dropdown.classList.remove('open');
        }, dropdownHoverDelay);
      }
    });
  });

  // Mobile touch behavior remains the same
  dropdownToggles.forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      if (window.innerWidth < 1280) {
        e.preventDefault();
        const dropdown = toggle.nextElementSibling;
        dropdown.classList.toggle('open');
        toggle.setAttribute('aria-expanded', dropdown.classList.contains('open'));
      }
    });
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.group')) {
      document.querySelectorAll('.dropdown-menu.open').forEach(dropdown => {
        dropdown.classList.remove('open');
      });
      dropdownToggles.forEach(toggle => {
        toggle.setAttribute('aria-expanded', 'false');
      });
    }
  });

  </script>
</body>
</html>
