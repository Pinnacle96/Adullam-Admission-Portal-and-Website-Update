
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
                <img src="assets/img/admission.jpg" alt="Admissions at Adullam" class="w-full h-full object-cover opacity-30">
            </div>
            <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6 animate-fadeInUp">Admissions at Adullam</h1>
                <p class="text-lg md:text-xl font-light max-w-3xl mx-auto mb-8 animate-fadeInUp animation-delay-200">Discover your path to theological education. We offer programs for various academic backgrounds with flexible admission windows.</p>
                <a href="requirements" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105 animate-fadeInUp animation-delay-400">Start Your Journey</a>
            </div>
        </section>

        <!-- Overview Section -->
        <section class="py-16 md:py-24" id="overview">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
                    <div class="order-2 lg:order-1">
                        <h2 class="text-3xl md:text-4xl font-bold text-purple-700 mb-6 animate-fadeInUp">Your Future Starts Here</h2>
                        <p class="text-lg text-gray-700 leading-relaxed mb-4">
                            At Adullam Seminary, we have two enrollment periods each year for both our On-site and Online Schools: <strong class="text-purple-700">January intake</strong> and <strong class="text-purple-700">June intake</strong>. Our programs attract a diverse and global community of students from different nations and backgrounds.
                        </p>
                        <p class="text-lg text-gray-700 leading-relaxed">
                            We invite you to explore the perfect program to match your calling and prepare for a life of purpose, ministry, and spiritual growth. Our flexible options are designed to fit your unique needs.
                        </p>
                         <p class="text-lg text-gray-700 leading-relaxed">
                        We are currently accepting applications for January, 2026 Session!
                         </p>
                        <a href="requirements" class="inline-block bg-purple-700 text-white font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105 mt-6">Apply Now</a>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="image-container shadow-lg transform hover:scale-105 transition-transform duration-300">
                            <img src="assets/img/adm.jpg" alt="Students in a classroom discussing" aria-describedby="admission-image-desc">
                        </div>
                        <p id="admission-image-desc" class="sr-only">Students engaging in classroom discussions at Adullam Seminary.</p>
                    </div>
                </div>

                <!-- Academic Programs Section -->
                <div class="max-w-4xl mx-auto mt-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-center text-purple-800 mb-4 animate-fadeInUp">Our Academic Programs</h2>
                    <p class="text-center text-lg text-gray-600 mb-12 animate-fadeInUp animation-delay-200">We offer a range of programs tailored to different academic backgrounds and spiritual goals.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white p-8 rounded-xl shadow-lg border-t-4 border-purple-600 transform hover:scale-105 transition">
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">Undergraduate Programs</h3>
                            <ul class="space-y-4">
                                <li>
                                    <a href="cert" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Certificate in Theology">
                                        <p class="text-lg font-semibold">Certificate in Theology <span class="text-gray-500 font-normal">(1 year)</span></p>
                                        <p class="text-sm text-gray-600">For applicants with an O'Level/Secondary School Certificate or equivalent.</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="dip" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Diploma in Theology">
                                        <p class="text-lg font-semibold">Diploma in Theology <span class="text-gray-500 font-normal">(3 years)</span></p>
                                        <p class="text-sm text-gray-600">For applicants with an O'Level/Secondary School Certificate or equivalent.</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="bdiv" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Bachelor of Divinity">
                                        <p class="text-lg font-semibold">Bachelor of Divinity <span class="text-gray-500 font-normal">(4 years)</span></p>
                                        <p class="text-sm text-gray-600">For applicants with an O'Level/Secondary School Certificate or equivalent.</p>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="bg-white p-8 rounded-xl shadow-lg border-t-4 border-purple-600 transform hover:scale-105 transition">
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">Postgraduate Programs</h3>
                            <ul class="space-y-4">
                                <li>
                                    <a href="pgdt" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Postgraduate Diploma in Theology">
                                        <p class="text-lg font-semibold">Postgraduate Diploma in Theology <span class="text-gray-500 font-normal">(1 year)</span></p>
                                        <p class="text-sm text-gray-600">For applicants with a Higher National Diploma (HND) or a university first degree.</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="masters" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Master of Arts">
                                        <p class="text-lg font-semibold">Master of Arts <span class="text-gray-500 font-normal">(2 years)</span></p>
                                        <p class="text-sm text-gray-600">Specializations in Christian Apologetics, Old Testament, and New Testament.</p>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="mt-20 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold text-purple-700 mb-6 animate-fadeInUp">Ready to Take the Next Step?</h2>
                    <a href="requirements" class="inline-block bg-purple-700 text-white font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-900 transition transform hover:scale-105" aria-label="Apply Now for Adullam Seminary Programs">Apply Now</a>
                </div>
            </div>
        </section>

        <!-- Sticky Apply Now Button -->
        <div class="fixed bottom-6 right-6 z-50">
            <a href="dashboard" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Apply Now for Adullam Seminary Programs">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Apply Now
            </a>
        </div>
    </main>
<?php endif; ?>

<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="assets/img/admission.jpg" alt="Admissions at Adullam" class="w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] gap-10 items-center">
                <div>
                    <h1 class="max-w-3xl text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        Admissions at Adullam
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        Discover your path to theological education. We offer programs for various academic backgrounds with flexible admission windows.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Your Future Starts Here</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Our Academic Programs</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Ready to Take the Next Step?</span>
                    </div>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="requirements" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-800 shadow-lg transition hover:bg-purple-100">
                            Start Your Journey
                        </a>
                        <a href="#programs" class="inline-flex items-center justify-center rounded-full border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10">
                            Our Academic Programs
                        </a>
                    </div>
                </div>

                <div class="lg:justify-self-end">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="rounded-[1.5rem] bg-white/95 p-6 text-slate-900">
                            <a href="#overview" class="flex items-center justify-between rounded-2xl bg-purple-50 px-4 py-4 transition hover:bg-purple-100">
                                <span class="font-semibold text-slate-900">Your Future Starts Here</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#programs" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Our Academic Programs</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#next-step" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Ready to Take the Next Step?</span>
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
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">Your Future Starts Here</h2>
                        <p class="mt-6 text-lg text-gray-700 leading-relaxed">
                            At Adullam Seminary, we have two enrollment periods each year for both our On-site and Online Schools: <strong class="text-purple-700">January intake</strong> and <strong class="text-purple-700">June intake</strong>. Our programs attract a diverse and global community of students from different nations and backgrounds.
                        </p>
                        <p class="mt-4 text-lg text-gray-700 leading-relaxed">
                            We invite you to explore the perfect program to match your calling and prepare for a life of purpose, ministry, and spiritual growth. Our flexible options are designed to fit your unique needs.
                        </p>
                        <p class="mt-4 text-lg text-gray-700 leading-relaxed">
                            We are currently accepting applications for January, 2026 Session!
                        </p>
                        <a href="requirements" class="inline-flex items-center justify-center mt-8 rounded-full bg-purple-700 px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-purple-900">
                            Apply Now
                        </a>
                    </div>

                    <div class="rounded-[1.75rem] bg-gradient-to-br from-purple-700 to-slate-900 p-[1px]">
                        <div class="rounded-[1.7rem] bg-white p-6">
                            <img src="assets/img/adm.jpg" alt="Students in a classroom discussing" class="h-80 w-full rounded-[1.25rem] object-cover">
                            <p class="sr-only">Students engaging in classroom discussions at Adullam Seminary.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24" id="programs">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">Our Academic Programs</h2>
                <p class="mt-4 text-lg text-gray-600">We offer a range of programs tailored to different academic backgrounds and spiritual goals.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-5">Undergraduate Programs</h3>
                    <ul class="space-y-4">
                        <li>
                            <a href="cert" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Certificate in Theology">
                                <p class="text-lg font-semibold">Certificate in Theology <span class="text-gray-500 font-normal">(1 year)</span></p>
                                <p class="text-sm text-gray-600">For applicants with an O'Level/Secondary School Certificate or equivalent.</p>
                            </a>
                        </li>
                        <li>
                            <a href="dip" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Diploma in Theology">
                                <p class="text-lg font-semibold">Diploma in Theology <span class="text-gray-500 font-normal">(3 years)</span></p>
                                <p class="text-sm text-gray-600">For applicants with an O'Level/Secondary School Certificate or equivalent.</p>
                            </a>
                        </li>
                        <li>
                            <a href="bdiv" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Bachelor of Divinity">
                                <p class="text-lg font-semibold">Bachelor of Divinity <span class="text-gray-500 font-normal">(4 years)</span></p>
                                <p class="text-sm text-gray-600">For applicants with an O'Level/Secondary School Certificate or equivalent.</p>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-5">Postgraduate Programs</h3>
                    <ul class="space-y-4">
                        <li>
                            <a href="pgdt" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Postgraduate Diploma in Theology">
                                <p class="text-lg font-semibold">Postgraduate Diploma in Theology <span class="text-gray-500 font-normal">(1 year)</span></p>
                                <p class="text-sm text-gray-600">For applicants with a Higher National Diploma (HND) or a university first degree.</p>
                            </a>
                        </li>
                        <li>
                            <a href="masters" class="block text-purple-700 hover:text-purple-900 transition duration-300" aria-label="Learn more about Master of Arts">
                                <p class="text-lg font-semibold">Master of Arts <span class="text-gray-500 font-normal">(2 years)</span></p>
                                <p class="text-sm text-gray-600">Specializations in Christian Apologetics, Old Testament, and New Testament.</p>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 rounded-[2rem] bg-white p-8 md:p-10 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100 text-center" id="next-step">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">Ready to Take the Next Step?</h2>
                <a href="requirements" class="inline-flex items-center justify-center mt-8 rounded-full bg-purple-700 px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-purple-900" aria-label="Apply Now for Adullam Seminary Programs">Apply Now</a>
            </div>
        </div>
    </section>

    <div class="fixed bottom-6 right-6 z-50">
        <a href="dashboard" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Apply Now for Adullam Seminary Programs">
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