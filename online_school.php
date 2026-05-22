
    <?php 
    // Include configuration and header
    include('includes/dbconnection.php');
    include('includes/header.php');
    ?>

    <?php if (false): ?>
<main>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-r from-purple-800 to-purple-600 py-20 md:py-32 overflow-hidden">
            <div class="absolute inset-0">
                <img src="assets/img/online.jpg" alt="Adullam Online School" class="w-full h-full object-cover opacity-30">
            </div>
            <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6 animate-fadeInUp">Adullam Online School</h1>
                <p class="text-lg md:text-xl font-light max-w-3xl mx-auto mb-8 animate-fadeInUp animation-delay-200">Discover a new way to learn theology with our flexible and engaging online programs designed for your busy life.</p>
                <a href="requirements" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105 animate-fadeInUp animation-delay-400">Explore Programs</a>
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
                        <a href="requirements" class="inline-block bg-purple-700 text-white font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105">Apply Now</a>
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
                        <a href="requirements" class="inline-block bg-purple-700 text-white font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105" aria-label="Apply Now for Adullam Online School">Apply Now</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sticky Apply Now Button -->
        <div class="fixed bottom-6 right-6 z-50">
            <a href="requirements" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Apply Now for Adullam Online School">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Apply Now
            </a>
        </div>
    </main>
<?php endif; ?>

<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="assets/img/online.jpg" alt="Adullam Online School" class="w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] gap-10 items-center">
                <div>
                    <h1 class="max-w-3xl text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        Adullam Online School
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        Discover a new way to learn theology with our flexible and engaging online programs designed for your busy life.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">A Blend of Academic Flexibility and Spiritual Engagement</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">How It Works</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Beyond the Classroom: Spiritual Engagement</span>
                    </div>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="requirements" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-800 shadow-lg transition hover:bg-purple-100">
                            Explore Programs
                        </a>
                        <a href="#how-it-works" class="inline-flex items-center justify-center rounded-full border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10">
                            How It Works
                        </a>
                    </div>
                </div>

                <div class="lg:justify-self-end">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="rounded-[1.5rem] bg-white/95 p-6 text-slate-900">
                            <a href="#overview" class="flex items-center justify-between rounded-2xl bg-purple-50 px-4 py-4 transition hover:bg-purple-100">
                                <span class="font-semibold text-slate-900">A Blend of Academic Flexibility and Spiritual Engagement</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#how-it-works" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">How It Works</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#engagement" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Beyond the Classroom: Spiritual Engagement</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 md:-mt-14 z-10" id="overview">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] bg-white p-8 md:p-10 shadow-2xl shadow-purple-100/60 ring-1 ring-purple-100">
                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.1fr)_360px] gap-10 items-center">
                    <div>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">A Blend of Academic Flexibility and Spiritual Engagement</h2>
                        <p class="mt-6 text-lg text-gray-700 leading-relaxed">
                            At Adullam Online School, we understand the importance of balancing academic pursuits with other responsibilities. Our online programs offer a unique blend of <strong class="text-purple-700">flexibility and engaging content</strong>, making it possible for individuals with family or work commitments to further their education without sacrificing their existing obligations.
                        </p>
                        <a href="requirements" class="inline-flex items-center justify-center mt-8 rounded-full bg-purple-700 px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-purple-900">
                            Apply Now
                        </a>
                    </div>

                    <div class="rounded-[1.75rem] bg-gradient-to-br from-purple-700 to-slate-900 p-[1px]">
                        <div class="rounded-[1.7rem] bg-white p-6">
                            <img src="assets/img/online.jpg" alt="Students learning online on their laptops" class="h-80 w-full rounded-[1.25rem] object-cover">
                            <p class="sr-only">Students engaging in online theological studies using laptops.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24" id="how-it-works">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">How It Works</h2>
                <p class="mt-4 text-lg text-gray-600">We make online learning accessible and effective with a structured approach to classes and community.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Flexible Schedules</h3>
                    <p class="text-gray-700">Our classes are held on <strong class="text-purple-700">Fridays, Saturdays, and Sundays</strong>, designed to fit around your work week.</p>
                    <ul class="mt-4 list-disc pl-5 text-gray-600 space-y-2">
                        <li><strong>8:00 PM - 9:00 PM WAT:</strong> Prayer Session</li>
                        <li><strong>9:00 PM - 11:30 PM WAT:</strong> Live Lecture</li>
                    </ul>
                    <p class="mt-4 text-sm text-gray-500">Lectures may occasionally exceed the scheduled time, and additional classes can be arranged as needed.</p>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Access to a Robust LMS</h3>
                    <p class="text-gray-700">Our students use a programmed <strong class="text-purple-700">Learning Management System (LMS)</strong> to access lecture materials, recordings, and submit assignments. It's your all-in-one academic hub.</p>
                    <p class="mt-4 text-sm text-gray-500">This platform provides a seamless and organized learning experience, putting everything you need at your fingertips.</p>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Interactive Live Classes</h3>
                    <p class="text-gray-700">Live sessions are conducted via <strong class="text-purple-700">Microsoft Teams</strong>, enabling real-time engagement with instructors and peers. Ask questions, participate in discussions, and connect with your classmates.</p>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Never Miss a Moment</h3>
                    <p class="text-gray-700">All live lectures are <strong class="text-purple-700">recorded and uploaded</strong> to our LMS. This means you can catch up on any missed material or review sessions at your convenience, on your own schedule.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-16 md:pb-24" id="engagement">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] bg-slate-950 px-8 py-10 md:px-10 md:py-12 text-white overflow-hidden relative">
                <div class="absolute top-0 right-0 h-48 w-48 rounded-full bg-purple-500/20 blur-3xl"></div>
                <div class="relative">
                    <h2 class="text-3xl md:text-4xl font-extrabold">Beyond the Classroom: Spiritual Engagement</h2>
                    <p class="mt-4 text-lg text-purple-100">Our online fellowship, the Global Week of Intercession, is a key part of our community. It strengthens faith and fosters connections.</p>
                    <div class="mt-8 rounded-[1.75rem] bg-white/10 p-8 ring-1 ring-white/10">
                        <h3 class="text-xl font-bold mb-4">Contact Week Schedule</h3>
                        <ul class="list-disc pl-6 space-y-4 text-lg">
                            <li><strong class="text-white">Monday:</strong> Bible Study with our Chaplain, 9:00 PM WAT</li>
                            <li><strong class="text-white">Tuesday:</strong> Q&A session with our President, 8:00 PM WAT</li>
                            <li><strong class="text-white">Thursday to Friday:</strong> 24-hour Prayer Session (from Thursday midnight to Friday midnight)</li>
                            <li><strong class="text-white">Saturday:</strong> 7-hour Prayer Session, 8:00 PM to 3:00 AM WAT</li>
                        </ul>
                        <p class="mt-6 text-sm md:text-base text-purple-100">These activities provide opportunities to deepen your faith, connect with fellow students, and receive guidance from our leadership.</p>
                    </div>
                </div>
            </div>

            <div class="mt-10 rounded-[2rem] bg-white p-8 md:p-10 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100 text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">Ready to Start Your Journey?</h2>
                <div class="mt-8 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="contact" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-8 py-4 font-semibold text-purple-700 shadow-sm transition hover:bg-purple-50" aria-label="Contact Us for More Information">Contact Us</a>
                    <a href="requirements" class="inline-flex items-center justify-center rounded-full bg-purple-700 px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-purple-900" aria-label="Apply Now for Adullam Online School">Apply Now</a>
                </div>
            </div>
        </div>
    </section>

    <div class="fixed bottom-6 right-6 z-50">
        <a href="requirements" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Apply Now for Adullam Online School">
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