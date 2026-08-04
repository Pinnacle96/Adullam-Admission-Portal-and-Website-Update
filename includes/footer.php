<?php
include('includes/dbconnection.php');
session_start();
error_reporting(0);

// Check if the form is submitted
if (isset($_POST['sub'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $con->prepare("INSERT INTO tblsubscriber(Email) VALUES(?)");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                echo "<script>alert('Your subscription was successful!'); window.location.href='index';</script>";
            } else {
                echo "<script>alert('Something went wrong. Please try again.');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Database error. Please try again later.');</script>";
        }
    } else {
        echo "<script>alert('Please enter a valid email address.');</script>";
    }
}
?>

<footer class="bg-gray-900 text-gray-300 pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <!-- About Adullam -->
            <div class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4 animate-fadeInUp">ADULLAM</h2>
                <?php
                $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='contactus'");
                while ($row = mysqli_fetch_array($ret)) {
                ?>
                    <p class="flex items-start text-sm animate-fadeInUp animation-delay-200">
                        <svg class="w-5 h-5 mr-3 text-purple-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span><?php echo htmlspecialchars($row['PageDescription']); ?></span>
                    </p>
                    <p class="flex items-start text-sm animate-fadeInUp animation-delay-400">
                        <svg class="w-5 h-5 mr-3 text-purple-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span>+234<?php echo htmlspecialchars($row['MobileNumber']); ?></span>
                    </p>
                    <p class="flex items-start text-sm animate-fadeInUp animation-delay-600">
                        <svg class="w-5 h-5 mr-3 text-purple-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <a href="mailto:<?php echo htmlspecialchars($row['Email']); ?>" class="hover:text-purple-400 transition-colors" aria-label="Email <?php echo htmlspecialchars($row['Email']); ?>"><?php echo htmlspecialchars($row['Email']); ?></a>
                    </p>
                <?php } ?>
            </div>

            <!-- Useful Links -->
            <div>
                <h4 class="text-xl font-semibold text-white mb-4 animate-fadeInUp">Useful Links</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="index" class="hover:text-purple-400 transition-colors" aria-label="Home page">Home</a></li>
                    <li><a href="about" class="hover:text-purple-400 transition-colors" aria-label="About page">About</a></li>
                    <li><a href="contact" class="hover:text-purple-400 transition-colors" aria-label="Contact page">Contact</a></li>
                    <li><a href="index#programs" class="hover:text-purple-400 transition-colors" aria-label="Programs section">Programs</a></li>
                    <li><a href="privacy-policy" class="hover:text-purple-400 transition-colors" aria-label="Privacy Policy">Privacy Policy</a></li>
                    <li><a href="delete-account" class="hover:text-purple-400 transition-colors" aria-label="Delete Account">Delete Account</a></li>
                    <li><a href="dashboard/administrator" class="hover:text-purple-400 transition-colors" aria-label="Admin login page">Admin Login</a></li>
                </ul>
            </div>

            <!-- Our Programs -->
            <div>
                <h4 class="text-xl font-semibold text-white mb-4 animate-fadeInUp">Our Programs</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="cert" class="hover:text-purple-400 transition-colors" aria-label="Certificate in Theology">Certificate in Theology</a></li>
                    <li><a href="dip" class="hover:text-purple-400 transition-colors" aria-label="Diploma in Theology">Diploma in Theology</a></li>
                    <li><a href="bdiv" class="hover:text-purple-400 transition-colors" aria-label="Bachelor of Divinity">Bachelor of Divinity</a></li>
                    <li><a href="pgdt" class="hover:text-purple-400 transition-colors" aria-label="Postgraduate Diploma in Theology">Postgraduate Diploma</a></li>
                    <li><a href="masters" class="hover:text-purple-400 transition-colors" aria-label="Master of Arts in Christian Apologetics">M.A. Christian Apologetics</a></li>
                    <li><a href="master" class="hover:text-purple-400 transition-colors" aria-label="Master of Arts in Biblical Studies">M.A. Biblical Studies (OT/NT)</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <h4 class="text-xl font-semibold text-white mb-4 animate-fadeInUp">Newsletter</h4>
                <p class="text-sm mb-4 animate-fadeInUp animation-delay-200">Stay updated with our latest news and events. Subscribe to our newsletter.</p>
                <form action="" method="post" class="flex flex-col gap-4" id="newsletterForm" aria-label="Newsletter subscription form">
                    <input type="email" name="email" placeholder="Your email address" required class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-purple-500 focus:outline-none placeholder-gray-500 transition" aria-label="Email address for newsletter subscription">
                    <button type="submit" name="sub" class="bg-purple-700 hover:bg-purple-900 text-white px-6 py-3 rounded-lg font-medium transition transform hover:scale-105">Subscribe</button>
                </form>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="mt-12 border-t border-gray-700 pt-8 text-center text-sm space-y-4">
            <p>&copy; <?php echo date('Y'); ?> <strong class="text-white px-1">ADULLAM.</strong> All Rights Reserved.</p>
            <p>Developed by <a href="https://wa.me/+2348150829549/" target="_blank" class="text-purple-500 hover:text-purple-300 transition-colors" aria-label="Contact Pinnacle Tech Hub via WhatsApp">Pinnacle Tech Hub</a></p>
            <div class="flex justify-center items-center mt-4 space-x-6">
                <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Visit our Facebook page">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Visit our X profile">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Visit our Instagram page">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Visit our LinkedIn page">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Visit our YouTube channel">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<a href="#" id="scroll-top" class="fixed bottom-6 right-6 bg-purple-700 text-white p-3 rounded-full shadow-lg hover:bg-purple-900 transition-all duration-300 hidden z-50" aria-label="Scroll to top">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
</a>

<script>
    // Scroll-to-top functionality
    const scrollTop = document.getElementById('scroll-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollTop.classList.remove('hidden');
        } else {
            scrollTop.classList.add('hidden');
        }
    });
    scrollTop.addEventListener('click', e => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Animation delay utility
    document.querySelectorAll('.animation-delay-200').forEach(el => el.style.animationDelay = '200ms');
    document.querySelectorAll('.animation-delay-400').forEach(el => el.style.animationDelay = '400ms');
    document.querySelectorAll('.animation-delay-600').forEach(el => el.style.animationDelay = '600ms');
</script>