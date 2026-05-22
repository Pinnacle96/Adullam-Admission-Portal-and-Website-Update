<?php
include('includes/dbconnection.php');
session_start();
error_reporting(0);

if (isset($_POST['submit'])) {
    // Sanitize and validate inputs
    $name = trim(mysqli_real_escape_string($con, $_POST['name']));
    $emailid = trim(mysqli_real_escape_string($con, $_POST['emailid']));
    $phoneno = trim(mysqli_real_escape_string($con, $_POST['phoneno']));
    $message = strip_tags($_POST['message']);

    // Use prepared statements to prevent SQL Injection
    $stmt = mysqli_prepare($con, "INSERT INTO tblcontact(Name, Email, PhoneNumber, Message) VALUES(?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $emailid, $phoneno, $message);

    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Query sent successfully.');</script>";
            echo "<script>window.location.href='contact'</script>";
        } else {
        echo "<script>alert('An error occurred. Please try again.');</script>";
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}
?>

    <?php include('includes/header.php'); ?>

    <?php if (false): ?>
<main>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-r from-purple-800 to-purple-600 py-20 md:py-32 overflow-hidden">
            <div class="absolute inset-0">
                <img src="assets/img/contact.jpg" alt="Contact Adullam Seminary" class="w-full h-full object-cover opacity-30">
            </div>
            <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6 animate-fadeInUp">Contact Us</h1>
                <p class="text-lg md:text-xl font-light max-w-3xl mx-auto mb-8 animate-fadeInUp animation-delay-200">We'd love to hear from you. Please reach out with any questions, comments, or inquiries.</p>
                <a href="#contact" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105 animate-fadeInUp animation-delay-400">Get in Touch</a>
            </div>
        </section>

        <!-- Google Map Section -->
        <section class="w-full">
            <iframe
                src="https://maps.google.com/maps?q=Wurukum%20Makurdi&t=&z=13&ie=UTF8&iwloc=&output=embed"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                class="w-full h-96"
                title="Adullam Seminary Location in Wurukum, Makurdi">
            </iframe>
        </section>

        <!-- Contact Information and Form Section -->
        <section class="py-16 md:py-24" id="contact">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Contact Information -->
                <div class="bg-white rounded-xl shadow-lg p-8 transform hover:scale-105 transition">
                    <h2 class="text-3xl font-bold text-purple-700 mb-6 animate-fadeInUp">Our Information</h2>
                    <div class="space-y-6 text-lg text-gray-700">
                        <?php
                        $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='contactus'");
                        while ($row = mysqli_fetch_array($ret)) {
                        ?>
                            <div class="flex items-start space-x-4">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <div>
                                    <h4 class="font-semibold">Location</h4>
                                    <p><?php echo $row['PageDescription']; ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <div>
                                    <h4 class="font-semibold">Email Address</h4>
                                    <a href="mailto:<?php echo $row['Email']; ?>" class="text-purple-700 hover:underline" aria-label="Email <?php echo $row['Email']; ?>"><?php echo $row['Email']; ?></a>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <div>
                                    <h4 class="font-semibold">Call Us</h4>
                                    <p>+234<?php echo $row['MobileNumber']; ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    <h4 class="font-semibold">Opening Hours</h4>
                                    <p><?php echo $row['Timing']; ?> WAT</p>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Contact Image -->
                <div class="image-container shadow-lg transform hover:scale-105 transition-transform duration-300">
                    <img src="assets/img/contact.jpg" alt="Contact Adullam Seminary" aria-describedby="contact-image-desc">
                    <p id="contact-image-desc" class="sr-only">A welcoming scene at Adullam Seminary, showcasing our community ready to assist with your inquiries.</p>
                </div>

                <!-- Contact Form -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-3xl font-bold text-purple-700 mb-6 animate-fadeInUp">Send Us a Message</h2>
                    <form method="post" class="space-y-6" aria-label="Contact Form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="sr-only">Your Name</label>
                                <input type="text" name="name" id="name" class="w-full p-4 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Your Name" required>
                            </div>
                            <div>
                                <label for="emailid" class="sr-only">Your Email</label>
                                <input type="email" name="emailid" id="emailid" class="w-full p-4 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Your Email" required>
                            </div>
                        </div>
                        <div>
                            <label for="phoneno" class="sr-only">Phone Number</label>
                            <input type="tel" name="phoneno" id="phoneno" class="w-full p-4 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Phone Number" required pattern="[0-9]{10,15}">
                        </div>
                        <div>
                            <label for="message" class="sr-only">Your Message</label>
                            <textarea name="message" id="message" rows="5" class="w-full p-4 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" name="submit" class="w-full bg-purple-700 text-white font-semibold py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105">Send Message</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Sticky CTA Button -->
        <div class="fixed bottom-6 right-6 z-50">
            <a href="#contact" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Contact Adullam Seminary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Get in Touch
            </a>
        </div>
    </main>
<?php endif; ?>

<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="assets/img/contact.jpg" alt="Contact Adullam Seminary" class="w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] gap-10 items-center">
                <div>
                    <h1 class="max-w-3xl text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        Contact Us
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        We'd love to hear from you. Please reach out with any questions, comments, or inquiries.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Our Information</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Send Us a Message</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Get in Touch</span>
                    </div>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="#contact" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-800 shadow-lg transition hover:bg-purple-100">
                            Get in Touch
                        </a>
                    </div>
                </div>

                <div class="lg:justify-self-end">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="rounded-[1.5rem] bg-white/95 p-6 text-slate-900">
                            <a href="#contact" class="flex items-center justify-between rounded-2xl bg-purple-50 px-4 py-4 transition hover:bg-purple-100">
                                <span class="font-semibold text-slate-900">Our Information</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#contact" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Send Us a Message</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 md:-mt-14 z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] overflow-hidden bg-white shadow-2xl shadow-purple-100/60 ring-1 ring-purple-100">
                <iframe
                    src="https://maps.google.com/maps?q=Wurukum%20Makurdi&t=&z=13&ie=UTF8&iwloc=&output=embed"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="w-full h-96"
                    title="Adullam Seminary Location in Wurukum, Makurdi">
                </iframe>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24" id="contact">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                <h2 class="text-3xl font-bold text-purple-700 mb-6">Our Information</h2>
                <div class="space-y-6 text-lg text-gray-700">
                    <?php
                    $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='contactus'");
                    while ($row = mysqli_fetch_array($ret)) {
                    ?>
                        <div class="flex items-start space-x-4">
                            <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <div>
                                <h4 class="font-semibold">Location</h4>
                                <p><?php echo $row['PageDescription']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <div>
                                <h4 class="font-semibold">Email Address</h4>
                                <a href="mailto:<?php echo $row['Email']; ?>" class="text-purple-700 hover:underline" aria-label="Email <?php echo $row['Email']; ?>"><?php echo $row['Email']; ?></a>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <div>
                                <h4 class="font-semibold">Call Us</h4>
                                <p>+234<?php echo $row['MobileNumber']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <h4 class="font-semibold">Opening Hours</h4>
                                <p><?php echo $row['Timing']; ?> WAT</p>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-gradient-to-br from-purple-700 to-slate-900 p-[1px]">
                <div class="rounded-[1.7rem] bg-white p-6 h-full">
                    <img src="assets/img/contact.jpg" alt="Contact Adullam Seminary" class="h-full min-h-[24rem] w-full rounded-[1.25rem] object-cover">
                    <p class="sr-only">A welcoming scene at Adullam Seminary, showcasing our community ready to assist with your inquiries.</p>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                <h2 class="text-3xl font-bold text-purple-700 mb-6">Send Us a Message</h2>
                <form method="post" class="space-y-6" aria-label="Contact Form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="sr-only">Your Name</label>
                            <input type="text" name="name" id="name" class="w-full p-4 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Your Name" required>
                        </div>
                        <div>
                            <label for="emailid" class="sr-only">Your Email</label>
                            <input type="email" name="emailid" id="emailid" class="w-full p-4 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Your Email" required>
                        </div>
                    </div>
                    <div>
                        <label for="phoneno" class="sr-only">Phone Number</label>
                        <input type="tel" name="phoneno" id="phoneno" class="w-full p-4 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Phone Number" required pattern="[0-9]{10,15}">
                    </div>
                    <div>
                        <label for="message" class="sr-only">Your Message</label>
                        <textarea name="message" id="message" rows="5" class="w-full p-4 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600 transition" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" name="submit" class="w-full bg-purple-700 text-white font-semibold py-4 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-[1.01]">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <div class="fixed bottom-6 right-6 z-50">
        <a href="#contact" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Contact Adullam Seminary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            Get in Touch
        </a>
    </div>
</main>

    <?php include('includes/footer.php'); ?>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Animation delay utility
        document.querySelectorAll('.animation-delay-200').forEach(el => {
            el.style.animationDelay = '200ms';
        });
        document.querySelectorAll('.animation-delay-400').forEach(el => {
            el.style.animationDelay = '400ms';
        });
    </script>
</body>
</html>