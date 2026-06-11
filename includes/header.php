<?php
include('includes/dbconnection.php');
session_start();
error_reporting(0);

// Securely fetch the base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return "{$protocol}://{$host}{$path}";
}
$base_url = getBaseUrl();
$current_url = $base_url . $_SERVER['REQUEST_URI'];

// Securely fetch contact info
$contactData = [];
if (isset($con)) {
    $stmt = $con->prepare("SELECT Email, MobileNumber, PageDescription FROM tblpage WHERE PageType = ?");
    $pageType = 'contactus';
    $stmt->bind_param('s', $pageType);
    $stmt->execute();
    $result = $stmt->get_result();
    $contactData = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$hostelRegistrationOpenNew = true;
$hostelRegistrationOpenReturning = true;
if (isset($con)) {
    $stmt = $con->prepare("SELECT `key`, value FROM settings WHERE `key` IN (?, ?, ?)");
    if ($stmt) {
        $legacyKey = 'hostel_registration_open';
        $newKey = 'hostel_registration_open_new';
        $returningKey = 'hostel_registration_open_returning';
        $stmt->bind_param('sss', $legacyKey, $newKey, $returningKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[(string)$row['key']] = (string)$row['value'];
        }
        $stmt->close();

        $legacyValue = $settings['hostel_registration_open'] ?? '1';
        $hostelRegistrationOpenNew = (($settings['hostel_registration_open_new'] ?? $legacyValue) === '1');
        $hostelRegistrationOpenReturning = (($settings['hostel_registration_open_returning'] ?? $legacyValue) === '1');
    }
}

// Dynamic SEO metadata based on page
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'index.php' => 'RCN Theological Seminary - Adullam | Home',
    'about.php' => 'About Us - RCN Theological Seminary - Adullam',
    'welcome.php' => 'President\'s Welcome - RCN Theological Seminary - Adullam',
    'online_school.php' => 'Online School - RCN Theological Seminary - Adullam',
    'admissions.php' => 'Admissions - RCN Theological Seminary - Adullam',
    'partner.php' => 'Donation & Partnership - RCN Theological Seminary - Adullam',
    'contact.php' => 'Contact Us - RCN Theological Seminary - Adullam',
    'cert.php' => 'Certificate in Theology - RCN Theological Seminary - Adullam',
    'dip.php' => 'Diploma in Theology - RCN Theological Seminary - Adullam',
    'bdiv.php' => 'Bachelor of Divinity - RCN Theological Seminary - Adullam',
    'pgdt.php' => 'Postgraduate Diploma - RCN Theological Seminary - Adullam',
    'masters.php' => 'Master\'s Programs Christian Apologetics - RCN Theological Seminary - Adullam',
    'master.php' => 'Master\'s Programs Biblical Studies - RCN Theological Seminary - Adullam',
    'e-library.php' => 'Digital E-Library - RCN Theological Seminary - Adullam'
];
$page_descriptions = [
    'index.php' => 'RCN Theological Seminary - Adullam equips Christ-like leaders with deep biblical roots. Explore our Theology, Divinity, and Biblical Studies programs.',
    'about.php' => 'Learn about RCN Theological Seminary - Adullam, our mission, and our commitment to biblical orthodoxy and spiritual formation.',
    'welcome.php' => 'A warm welcome from the Provost of RCN Theological Seminary - Adullam, inviting you to join our vibrant community.',
    'online_school.php' => 'Discover flexible online theological education at RCN Theological Seminary - Adullam, designed for global learners.',
    'admissions.php' => 'Apply to RCN Theological Seminary - Adullam. Learn about our admission process and start your journey today.',
    'partner.php' => 'Support RCN Theological Seminary - Adullam through donations and partnerships to shape the next generation of Christian leaders.',
    'contact.php' => 'Get in touch with RCN Theological Seminary - Adullam for inquiries about programs, admissions, or partnerships.',
    'cert.php' => 'Earn a Certificate in Theology at RCN Theological Seminary - Adullam, designed for foundational biblical training.',
    'dip.php' => 'Pursue a Diploma in Theology at RCN Theological Seminary - Adullam to deepen your biblical knowledge.',
    'bdiv.php' => 'Join the Bachelor of Divinity program at RCN Theological Seminary - Adullam for comprehensive theological education.',
    'pgdt.php' => 'Advance your studies with the Postgraduate Diploma in Theology at RCN Theological Seminary - Adullam.',
    'masters.php' => 'Explore Master’s programs in Christian Apologetics at RCN Theological Seminary - Adullam.',
    'master.php' => 'Master’s Biblical Studies, Adullam Seminary, theological education',
    'e-library.php' => 'Explore theological books, research materials, journals, audio teachings, videos, and downloadable resources in the Adullam Seminary digital e-library.'
];
$page_keywords = [
    'index.php' => 'RCN Theological Seminary, Adullam, Bible school, Christian education, Theology programs, Nigeria seminary',
    'about.php' => 'Adullam Seminary, about us, biblical orthodoxy, Christian leadership, theological education',
    'welcome.php' => 'Adullam Seminary, President’s welcome, Provost message, Christian community, theological education',
    'online_school.php' => 'Adullam online school, theological education online, Christian studies, flexible learning',
    'admissions.php' => 'Adullam admissions, apply to seminary, Christian education, theology application',
    'partner.php' => 'Adullam donations, partnership, support Christian education, theological seminary',
    'contact.php' => 'Adullam contact, theological seminary Nigeria, Christian education inquiries',
    'cert.php' => 'Certificate in Theology, Adullam Seminary, biblical training, Christian education',
    'dip.php' => 'Diploma in Theology, Adullam Seminary, biblical studies, Christian education',
    'bdiv.php' => 'Bachelor of Divinity, Adullam Seminary, theological education, Christian leadership',
    'pgdt.php' => 'Postgraduate Diploma Theology, Adullam Seminary, advanced biblical studies',
    'masters.php' => 'Master’s Christian Apologetics, Adullam Seminary, theological education',
    'master.php' => 'Master’s Biblical Studies, Adullam Seminary, theological education',
    'e-library.php' => 'Digital E-Library, Adullam Seminary, theological books, Christian journals, research materials, downloadable resources'
];

$page_title = $page_titles[$current_page] ?? 'RCN Theological Seminary - Adullam';
$page_description = $page_descriptions[$current_page] ?? 'RCN Theological Seminary - Adullam exists to train and equip Christ-like leaders with deep biblical roots. Explore our comprehensive programs in Theology, Divinity, and Biblical Studies.';
$page_keywords = $page_keywords[$current_page] ?? 'RCN, Adullam, Theological Seminary, Seminary in Nigeria, Bible school, Christian education, Theology programs';
$logo_url = $base_url . "/assets/img/favicon.png"; // Use logo1.png for OG image
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="RCN Theological Seminary - Adullam">
    <link rel="canonical" href="<?= htmlspecialchars($current_url) ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($current_url) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= htmlspecialchars($logo_url) ?>">
    <meta property="og:site_name" content="RCN Theological Seminary - Adullam">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($logo_url) ?>">

    <link rel="icon" href="assets/img/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="assets/img/favicon.png">

    <!-- Fonts & CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Smooth Fade In for Dropdowns */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .group:hover .group-hover\:block {
            animation: fadeIn 0.2s ease-out forwards;
        }
    </style>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "RCN Theological Seminary - Adullam",
        "url": "<?= htmlspecialchars($base_url) ?>",
        "logo": "<?= htmlspecialchars($logo_url) ?>",
        "description": "<?= htmlspecialchars($page_description) ?>",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "NG",
            "addressLocality": "<?= htmlspecialchars($contactData[0]['PageDescription'] ?? 'Nigeria') ?>"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+234<?= htmlspecialchars($contactData[0]['MobileNumber'] ?? '') ?>",
            "contactType": "general",
            "email": "<?= htmlspecialchars($contactData[0]['Email'] ?? '') ?>"
        }
    }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <!-- TOP BAR (Contact & Student Portal) -->
    <div class="bg-slate-900 text-slate-300 text-sm py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-2">
            <!-- Contact Info -->
            <div class="flex items-center space-x-4 sm:space-x-6">
                <?php if (!empty($contactData)): ?>
                    <?php foreach ($contactData as $row): ?>
                        <a href="mailto:<?= htmlspecialchars($row['Email']) ?>" class="flex items-center hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span class="hidden sm:inline"><?= htmlspecialchars($row['Email']) ?></span>
                        </a>
                        <a href="tel:+234<?= htmlspecialchars($row['MobileNumber']) ?>" class="flex items-center hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>+234<?= htmlspecialchars($row['MobileNumber']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Student Portal Dropdown (Desktop) -->
            <div class="hidden sm:block relative group z-50">
                <button class="flex items-center hover:text-white font-medium focus:outline-none py-2">
                    Student Portal
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div class="absolute right-0 top-full pt-2 w-56 hidden group-hover:block">
                    <div class="bg-white rounded-md shadow-lg border border-gray-100 py-1">
                        <a href="dashboard/applicant_login" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Login / Apply</a>
                        <a href="admission_status" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Check Admission Status</a>
                        <?php if ($hostelRegistrationOpenReturning): ?>
                        <a href="dashboard/register_hostel_returning" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Hostel Registration</a>
                        <?php endif; ?>
                        <a href="june2025/dashboard/applicant_login" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Returning Student Portal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <header class="relative sticky top-0 bg-white shadow-sm z-40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- LOGO -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="index" aria-label="RCN Theological Seminary - Adullam">
                        <img class="h-12 w-auto md:h-14" src="assets/img/logo1.png" alt="Adullam Logo">
                    </a>
                </div>

                <!--
                Previous desktop navigation kept for future reference.
                <nav class="hidden lg:flex space-x-8 items-center">
                    <a href="index" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">Home</a>
                    <a href="about" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">About Us</a>
                    <div class="relative group">
                        <button class="flex items-center text-sm font-semibold text-gray-700 hover:text-purple-700 focus:outline-none py-4">
                            Academic Programs
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute left-0 top-full pt-2 w-64 hidden group-hover:block">
                            <div class="bg-white border border-gray-100 rounded-lg shadow-xl py-2">
                                <a href="cert" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Certificate in Theology</a>
                                <a href="dip" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Diploma in Theology</a>
                                <a href="bdiv" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Bachelor of Divinity</a>
                                <a href="pgdt" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Postgraduate Diploma</a>
                                <a href="masters" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">M.A. Christian Apologetics</a>
                                <a href="master" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">M.A. Biblical Studies</a>
                            </div>
                        </div>
                    </div>
                    <a href="online_school" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">Online School</a>
                    <a href="e-library" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">E-Library</a>
                    <a href="admissions" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">Admissions</a>
                    <a href="partner" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">Partnership</a>
                    <a href="contact" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">Contact</a>
                </nav>
                -->

                <!-- DESKTOP NAVIGATION -->
                <nav class="hidden lg:flex space-x-7 items-center">
                    <a href="index" class="text-sm font-semibold text-gray-700 hover:text-purple-700 transition-colors">Home</a>

                    <div class="relative group">
                        <button class="flex items-center text-sm font-semibold text-gray-700 hover:text-purple-700 focus:outline-none py-4">
                            About
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute left-0 top-full pt-2 w-56 hidden group-hover:block">
                            <div class="bg-white border border-gray-100 rounded-lg shadow-xl py-2">
                                <a href="about" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">About Us</a>
                                <a href="contact" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Contact Us</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academics Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center text-sm font-semibold text-gray-700 hover:text-purple-700 focus:outline-none py-4">
                            Academic Programs
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute left-0 top-full pt-2 w-64 hidden group-hover:block">
                            <div class="bg-white border border-gray-100 rounded-lg shadow-xl py-2">
                                <a href="cert" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Certificate in Theology</a>
                                <a href="dip" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Diploma in Theology</a>
                                <a href="bdiv" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Bachelor of Divinity</a>
                                <a href="pgdt" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Postgraduate Diploma</a>
                                <a href="masters" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">M.A. Christian Apologetics</a>
                                <a href="master" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">M.A. Biblical Studies</a>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="flex items-center text-sm font-semibold text-gray-700 hover:text-purple-700 focus:outline-none py-4">
                            Resources
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute left-0 top-full pt-2 w-56 hidden group-hover:block">
                            <div class="bg-white border border-gray-100 rounded-lg shadow-xl py-2">
                                <a href="online_school" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Online School</a>
                                <a href="e-library" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">E-Library</a>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="flex items-center text-sm font-semibold text-gray-700 hover:text-purple-700 focus:outline-none py-4">
                            Explore
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute left-0 top-full pt-2 w-60 hidden group-hover:block">
                            <div class="bg-white border border-gray-100 rounded-lg shadow-xl py-2">
                                <a href="admissions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Admissions</a>
                                <a href="partner" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">Donation & Partnership</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- APPLY BUTTON (Desktop) -->
                <div class="hidden lg:flex items-center">
                    <a href="requirements" class="bg-purple-700 hover:bg-purple-800 text-white px-6 py-2.5 rounded-full text-sm font-semibold transition shadow-md">
                        Apply Now
                    </a>
                </div>

                <!-- MOBILE MENU BUTTON -->
                <div class="lg:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-700 hover:text-purple-700 focus:outline-none p-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- MOBILE MENU -->
        <div id="mobile-menu" class="absolute left-0 right-0 top-full z-50 hidden border-t border-gray-100 bg-white/95 backdrop-blur lg:hidden shadow-2xl overflow-y-auto max-h-[calc(100vh-5rem)]">
            <div class="max-w-7xl mx-auto px-4 pt-4 pb-8 space-y-2">
                
                <!--
                Previous mobile navigation kept for future reference.
                <a href="index" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Home</a>
                <a href="about" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">About Us</a>
                <a href="online_school" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Online School</a>
                <a href="e-library" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">E-Library</a>
                <a href="admissions" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Admissions</a>
                <a href="partner" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Donation & Partnership</a>
                <a href="contact" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Contact Us</a>
                -->

                <a href="index" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">Home</a>

                <!-- About Accordion -->
                <div>
                    <button id="mobile-about-btn" class="w-full text-left flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                        About
                        <svg id="mobile-about-icon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-about-menu" class="hidden pl-6 space-y-1 mt-1 border-l-2 border-purple-100 ml-3">
                        <a href="about" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">About Us</a>
                        <a href="contact" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Contact Us</a>
                    </div>
                </div>
                
                <!-- Academics Accordion -->
                <div>
                    <button id="mobile-academics-btn" class="w-full text-left flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                        Academic Programs
                        <svg id="mobile-academics-icon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-academics-menu" class="hidden pl-6 space-y-1 mt-1 border-l-2 border-purple-100 ml-3">
                        <a href="cert" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Certificate in Theology</a>
                        <a href="dip" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Diploma in Theology</a>
                        <a href="bdiv" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Bachelor of Divinity</a>
                        <a href="pgdt" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Postgraduate Diploma</a>
                        <a href="masters" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">M.A. Christian Apologetics</a>
                        <a href="master" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">M.A. Biblical Studies</a>
                    </div>
                </div>

                <!-- Resources Accordion -->
                <div>
                    <button id="mobile-resources-btn" class="w-full text-left flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                        Resources
                        <svg id="mobile-resources-icon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-resources-menu" class="hidden pl-6 space-y-1 mt-1 border-l-2 border-purple-100 ml-3">
                        <a href="online_school" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Online School</a>
                        <a href="e-library" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">E-Library</a>
                    </div>
                </div>

                <!-- Explore Accordion -->
                <div>
                    <button id="mobile-explore-btn" class="w-full text-left flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                        Explore
                        <svg id="mobile-explore-icon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-explore-menu" class="hidden pl-6 space-y-1 mt-1 border-l-2 border-purple-100 ml-3">
                        <a href="admissions" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Admissions</a>
                        <a href="partner" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Donation & Partnership</a>
                    </div>
                </div>

                <!-- Student Portal Accordion -->
                <div>
                    <button id="mobile-portal-btn" class="w-full text-left flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                        Student Portal
                        <svg id="mobile-portal-icon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-portal-menu" class="hidden pl-6 space-y-1 mt-1 border-l-2 border-purple-100 ml-3">
                        <a href="dashboard/applicant_login" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Login / Apply</a>
                        <a href="admission_status" target="_blank" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Check Admission Status</a>
                        <?php if ($hostelRegistrationOpenReturning): ?>
                        <a href="dashboard/register_hostel_returning" target="_blank" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Hostel Registration</a>
                        <?php endif; ?>
                        <a href="june2025/dashboard/applicant_login" target="_blank" class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-purple-700">Returning Student Portal</a>
                    </div>
                </div>

                <!-- Mobile CTA -->
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <a href="requirements" class="block w-full text-center bg-purple-700 text-white px-4 py-3 rounded-lg font-bold hover:bg-purple-800 transition shadow-md">
                        Apply Now
                    </a>
                </div>
            </div>
        </div>
    </header>

    <script>
        // Toggle Mobile Menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        if(mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Toggle Mobile Accordions
        function setupAccordion(btnId, menuId, iconId) {
            const btn = document.getElementById(btnId);
            const menu = document.getElementById(menuId);
            const icon = document.getElementById(iconId);
            
            if(btn) {
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                    icon.classList.toggle('rotate-180');
                });
            }
        }

        setupAccordion('mobile-about-btn', 'mobile-about-menu', 'mobile-about-icon');
        setupAccordion('mobile-academics-btn', 'mobile-academics-menu', 'mobile-academics-icon');
        setupAccordion('mobile-resources-btn', 'mobile-resources-menu', 'mobile-resources-icon');
        setupAccordion('mobile-explore-btn', 'mobile-explore-menu', 'mobile-explore-icon');
        setupAccordion('mobile-portal-btn', 'mobile-portal-menu', 'mobile-portal-icon');
    </script>
