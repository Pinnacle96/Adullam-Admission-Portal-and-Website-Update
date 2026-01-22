
    <?php 
    // Include configuration and header
    include('includes/dbconnection.php');
    include('includes/header.php');
    ?>

    <main>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-r from-purple-800 to-purple-600 py-20 md:py-32 overflow-hidden">
            <div class="absolute inset-0">
                <img src="assets/img/online.jpg" alt="Adullam Online School" class="w-full h-full object-cover opacity-30">
            </div>
            <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6 animate-fadeInUp">Adullam Online School</h1>
                <p class="text-lg md:text-xl font-light max-w-3xl mx-auto mb-8 animate-fadeInUp animation-delay-200">Discover a new way to learn theology with our flexible and engaging online programs designed for your busy life.</p>
                <a href="#apply" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105 animate-fadeInUp animation-delay-400">Explore Programs</a>
            </div>
        </section>

        <!-- Overview Section -->
        <section class="py-16 md:py-24" id="overview">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
                    <div class="order-2 lg:order-1">
                        <h2 class="text-3xl md:text-4xl font-bold text-purple-700 mb-6 animate-fadeInUp">A Blend of Academic Flexibility and Spiritual Engagement</h2>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            At Adullam Online School, we understand the importance of balancing academic pursuits with other responsibilities. Our online programs offer a unique blend of <strong class="text-purple-700">flexibility and engaging content</strong>, making it possible for individuals with family or work commitments to further their education without sacrificing their existing obligations.
                        </p>
                        <a href="dashboard" class="inline-block bg-purple-700 text-white font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105">Apply Now</a>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="image-container shadow-lg transform hover:scale-105 transition-transform duration-300">
                            <img src="assets/img/online.jpg" alt="Students learning online on their laptops" aria-describedby="online-image-desc">
                        </div>
                        <p id="online-image-desc" class="sr-only">Students engaging in online theological studies using laptops.</p>
                    </div>
                </div>

                <!-- How It Works Section -->
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-center text-purple-800 mb-4 animate-fadeInUp">How It Works</h2>
                    <p class="text-center text-lg text-gray-600 mb-12 animate-fadeInUp animation-delay-200">We make online learning accessible and effective with a structured approach to classes and community.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white p-8 rounded-xl shadow-lg border-t-4 border-purple-600 transform hover:scale-105 transition">
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Flexible Schedules</h3>
                            <p class="text-gray-700 mb-2">Our classes are held on <strong class="text-purple-700">Fridays, Saturdays, and Sundays</strong>, designed to fit around your work week.</p>
                            <ul class="list-disc pl-5 text-gray-600 text-sm">
                                <li><strong>8:00 PM - 9:00 PM WAT:</strong> Prayer Session</li>
                                <li><strong>9:00 PM - 11:30 PM WAT:</strong> Live Lecture</li>
                            </ul>
                            <p class="text-sm text-gray-500 mt-2">Lectures may occasionally exceed the scheduled time, and additional classes can be arranged as needed.</p>
                        </div>
                        <div class="bg-white p-8 rounded-xl shadow-lg border-t-4 border-purple-600 transform hover:scale-105 transition">
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Access to a Robust LMS</h3>
                            <p class="text-gray-700">Our students use a programmed <strong class="text-purple-700">Learning Management System (LMS)</strong> to access lecture materials, recordings, and submit assignments. It's your all-in-one academic hub.</p>
                            <p class="text-sm text-gray-500 mt-2">This platform provides a seamless and organized learning experience, putting everything you need at your fingertips.</p>
                        </div>
                        <div class="bg-white p-8 rounded-xl shadow-lg border-t-4 border-purple-600 transform hover:scale-105 transition">
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Interactive Live Classes</h3>
                            <p class="text-gray-700">Live sessions are conducted via <strong class="text-purple-700">Microsoft Teams</strong>, enabling real-time engagement with instructors and peers. Ask questions, participate in discussions, and connect with your classmates.</p>
                        </div>
                        <div class="bg-white p-8 rounded-xl shadow-lg border-t-4 border-purple-600 transform hover:scale-105 transition">
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Never Miss a Moment</h3>
                            <p class="text-gray-700">All live lectures are <strong class="text-purple-700">recorded and uploaded</strong> to our LMS. This means you can catch up on any missed material or review sessions at your convenience, on your own schedule.</p>
                        </div>
                    </div>
                </div>

                <!-- Spiritual Engagement Section -->
                <div class="max-w-4xl mx-auto mt-20">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-center text-purple-800 mb-4 animate-fadeInUp">Beyond the Classroom: Spiritual Engagement</h2>
                    <p class="text-center text-lg text-gray-600 mb-12 animate-fadeInUp animation-delay-200">Our online fellowship, the Global Week of Intercession, is a key part of our community. It strengthens faith and fosters connections.</p>
                    <div class="bg-purple-800 text-white p-8 md:p-12 rounded-xl shadow-lg">
                        <h3 class="text-xl font-bold mb-4">Contact Week Schedule</h3>
                        <ul class="list-disc pl-6 space-y-4 text-lg">
                            <li><strong class="text-white">Monday:</strong> Bible Study with our Chaplain, 9:00 PM WAT</li>
                            <li><strong class="text-white">Tuesday:</strong> Q&A session with our President, 8:00 PM WAT</li>
                            <li><strong class="text-white">Thursday to Friday:</strong> 24-hour Prayer Session (from Thursday midnight to Friday midnight)</li>
                            <li><strong class="text-white">Saturday:</strong> 7-hour Prayer Session, 8:00 PM to 3:00 AM WAT</li>
                        </ul>
                        <p class="mt-6 text-sm md:text-base opacity-80">These activities provide opportunities to deepen your faith, connect with fellow students, and receive guidance from our leadership.</p>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="mt-20 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold text-purple-700 mb-6 animate-fadeInUp">Ready to Start Your Journey?</h2>
                    <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                        <a href="contact" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105" aria-label="Contact Us for More Information">Contact Us</a>
                        <a href="dashboard" class="inline-block bg-purple-700 text-white font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105" aria-label="Apply Now for Adullam Online School">Apply Now</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sticky Apply Now Button -->
        <div class="fixed bottom-6 right-6 z-50">
            <a href="dashboard" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Apply Now for Adullam Online School">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Apply Now
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