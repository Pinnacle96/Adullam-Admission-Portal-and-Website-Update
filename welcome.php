<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>President's Welcome - RCN Theological Seminary - Adullam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        /* Smooth scroll for anchor links */
        html {
            scroll-behavior: smooth;
        }
        /* Constrain image height */
        .image-container {
            max-height: 400px; /* Matches previous redesigns */
            overflow: hidden;
            border-radius: 0.75rem; /* Matches rounded-xl */
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures image fills container without distortion */
            object-position: center; /* Centers the image */
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <?php if (false): ?>
<main>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-r from-purple-800 to-purple-600 py-20 md:py-32 overflow-hidden">
            <div class="absolute inset-0">
                <img src="assets/img/team/team 1.jpg" alt="Welcome to Adullam Seminary" class="w-full h-full object-cover opacity-30">
            </div>
            <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
                <nav class="text-sm text-purple-200 mb-4 animate-fadeInUp">
                    <ol class="flex justify-center space-x-2">
                        <li><a href="index" class="hover:underline hover:text-white transition-colors" aria-label="Back to Home">Home</a></li>
                        <li class="font-bold">/ President's Welcome</li>
                    </ol>
                </nav>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6 animate-fadeInUp animation-delay-200">President's Welcome</h1>
                <p class="text-lg md:text-xl font-light max-w-3xl mx-auto mb-8 animate-fadeInUp animation-delay-400">Join our vibrant community at RCN Theological Seminary - Adullam, where faith and scholarship transform lives.</p>
                <!--<a href="dashboard" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105 animate-fadeInUp animation-delay-600" aria-label="Apply Now for Adullam Seminary">Apply Now</a>-->
            </div>
        </section>

        <!-- Welcome Message Section -->
        <section class="py-16 md:py-24 bg-gray-100">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-3 gap-12 items-center">
                <div class="order-1 lg:order-1">
                    <div class="image-container shadow-lg transform hover:scale-105 transition-transform duration-300">
                        <img src="assets/img/team/team 1.jpg" alt="Reverend Daniel Ogidi, Provost of Adullam Seminary" aria-describedby="provost-image-desc">
                    </div>
                    <p id="provost-image-desc" class="sr-only">Reverend Daniel Ogidi, Provost of Adullam Seminary, welcoming prospective students.</p>
                    <p class="text-xl italic font-serif text-gray-600 mt-4 text-center lg:text-left">Reverend Daniel Ogidi, President</p>
                </div>
                <div class="lg:col-span-2 order-2 lg:order-2">
                    <h2 class="text-3xl md:text-4xl font-bold text-purple-700 mb-8 animate-fadeInUp">A Message from the President</h2>
                    <div class="space-y-6 text-lg text-gray-700 leading-relaxed">
                        <p class="mb-6">
                            I am excited to welcome you to <strong class="text-purple-700">RCN Theological Seminary - Adullam</strong>. Established in 2015, our seminary was founded to equip Christian leaders who bring an accurate witness of Christ to all spheres of life. Adullam is a vibrant community of students, faculty, scholars, counselors, and friends, united in the belief that moral and religious values in education are transformative for all.
                        </p>
                        <p class="mb-6">
                            Our community is bound by a strong commitment to collaboration, respect, diversity, inclusion, and learning from one another, all within the framework of our absolute devotion to Christ and faithfulness to the Scriptures. As a Bible-believing community, we uphold biblical orthodoxy, prioritize spiritual formation, and embrace the supernatural operations of the Spirit of God.
                        </p>
                        <p class="mb-6">
                            Adullam is renowned for its commitment to excellence in theological scholarship and spiritual formation, producing alumni who make a profound impact in both the church and society. We invite you to partner with us in shaping the next generation and bringing transformation to our world for the glory of God.
                        </p>
                        <p class="mb-6">
                            We foster personal and social responsibility by creating opportunities for students to share their learning with the community and contribute meaningfully to their surroundings. Our graduates are equipped to engage the complexities of a changing world with curiosity, empathy, joy, and resilience.
                        </p>
                        <p class="mb-6">
                            At Adullam, we are invested in your success and offer a robust support system to guide you. Do not hesitate to seek help or advice as you become part of our community. Your academic journey will be enriched by our commitment to your growth and development.
                        </p>
                        <p class="mb-6">
                            Beyond academics, we encourage you to explore entrepreneurship and skill acquisition during your studies. These opportunities prepare you for the challenges of today’s society, particularly in Nigeria. Join the Adullam community today for a life-changing experience you will never forget.
                        </p>
                    </div>
                    <div class="pt-6">
                        <p class="text-2xl font-bold text-purple-700">Reverend Daniel Ogidi</p>
                        <p class="text-lg text-gray-600">President</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sticky CTA Button -->
        <!--<div class="fixed bottom-6 right-6 z-50">-->
        <!--    <a href="dashboard" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Apply Now for Adullam Seminary">-->
        <!--        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>-->
        <!--        Apply Now-->
        <!--    </a>-->
        <!--</div>-->
    </main>
<?php endif; ?>

<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="assets/img/team/team 1.jpg" alt="Welcome to Adullam Seminary" class="w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <nav class="text-sm text-purple-200 mb-6">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="index" class="hover:underline hover:text-white transition-colors" aria-label="Back to Home">Home</a></li>
                    <li>/ President's Welcome</li>
                </ol>
            </nav>
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] gap-10 items-center">
                <div>
                    <h1 class="max-w-3xl text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        President's Welcome
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        Join our vibrant community at RCN Theological Seminary - Adullam, where faith and scholarship transform lives.
                    </p>
                </div>

                <div class="lg:justify-self-end">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="rounded-[1.5rem] bg-white/95 p-6 text-slate-900">
                            <a href="#message" class="flex items-center justify-between rounded-2xl bg-purple-50 px-4 py-4 transition hover:bg-purple-100">
                                <span class="font-semibold text-slate-900">A Message from the President</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <div class="mt-3 rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="font-semibold text-slate-900">Reverend Daniel Ogidi</p>
                                <p class="text-sm text-gray-600">President</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 md:-mt-14 z-10 py-2" id="message">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] bg-white p-8 md:p-10 shadow-2xl shadow-purple-100/60 ring-1 ring-purple-100">
                <div class="grid grid-cols-1 lg:grid-cols-[340px_minmax(0,1fr)] gap-10 items-start">
                    <div>
                        <div class="rounded-[1.75rem] bg-gradient-to-br from-purple-700 to-slate-900 p-[1px]">
                            <div class="rounded-[1.7rem] bg-white p-6">
                                <img src="assets/img/team/team 1.jpg" alt="Reverend Daniel Ogidi, Provost of Adullam Seminary" class="h-96 w-full rounded-[1.25rem] object-cover">
                                <p class="sr-only">Reverend Daniel Ogidi, Provost of Adullam Seminary, welcoming prospective students.</p>
                                <p class="text-xl italic font-serif text-gray-600 mt-4 text-center">Reverend Daniel Ogidi, President</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">A Message from the President</h2>
                        <div class="mt-8 space-y-6 text-lg text-gray-700 leading-relaxed">
                            <p>I am excited to welcome you to <strong class="text-purple-700">RCN Theological Seminary - Adullam</strong>. Established in 2015, our seminary was founded to equip Christian leaders who bring an accurate witness of Christ to all spheres of life. Adullam is a vibrant community of students, faculty, scholars, counselors, and friends, united in the belief that moral and religious values in education are transformative for all.</p>
                            <p>Our community is bound by a strong commitment to collaboration, respect, diversity, inclusion, and learning from one another, all within the framework of our absolute devotion to Christ and faithfulness to the Scriptures. As a Bible-believing community, we uphold biblical orthodoxy, prioritize spiritual formation, and embrace the supernatural operations of the Spirit of God.</p>
                            <p>Adullam is renowned for its commitment to excellence in theological scholarship and spiritual formation, producing alumni who make a profound impact in both the church and society. We invite you to partner with us in shaping the next generation and bringing transformation to our world for the glory of God.</p>
                            <p>We foster personal and social responsibility by creating opportunities for students to share their learning with the community and contribute meaningfully to their surroundings. Our graduates are equipped to engage the complexities of a changing world with curiosity, empathy, joy, and resilience.</p>
                            <p>At Adullam, we are invested in your success and offer a robust support system to guide you. Do not hesitate to seek help or advice as you become part of our community. Your academic journey will be enriched by our commitment to your growth and development.</p>
                            <p>Beyond academics, we encourage you to explore entrepreneurship and skill acquisition during your studies. These opportunities prepare you for the challenges of today’s society, particularly in Nigeria. Join the Adullam community today for a life-changing experience you will never forget.</p>
                        </div>
                        <div class="pt-8">
                            <p class="text-2xl font-bold text-purple-700">Reverend Daniel Ogidi</p>
                            <p class="text-lg text-gray-600">President</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
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
        document.querySelectorAll('.animation-delay-200').forEach(el => el.style.animationDelay = '200ms');
        document.querySelectorAll('.animation-delay-400').forEach(el => el.style.animationDelay = '400ms');
        document.querySelectorAll('.animation-delay-600').forEach(el => el.style.animationDelay = '600ms');
    </script>
</body>
</html>