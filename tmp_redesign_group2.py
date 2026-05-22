import re
from pathlib import Path

ROOT = Path(r"c:\wamp64\www\adullam")


def replace_main(file_path: Path, new_main: str) -> None:
    text = file_path.read_text(encoding="utf-8")
    pattern = re.compile(r"<main[\s\S]*?</main>", re.S)
    if "<?php if (false): ?>" in text:
        text = pattern.sub(new_main, text, count=1)
    else:
        text = pattern.sub(lambda m: f"<?php if (false): ?>\n{m.group(0)}\n<?php endif; ?>\n\n{new_main}", text, count=1)
    file_path.write_text(text, encoding="utf-8")


contact_main = """<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
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
</main>"""

welcome_main = """<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
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
</main>"""

requirements_main = """<main class="bg-gradient-to-b from-white via-purple-50/40 to-white pb-20">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-24 lg:py-28">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_360px] gap-10 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black leading-tight">Admission Requirements</h1>
                    <p class="mt-6 max-w-3xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        "Carefully read through this page to familiarize yourself with the qualifications required for each program before you begin your application."
                    </p>
                </div>
                <div>
                    <?php if (!$isApplicationClosed && !empty($countdownHtml)): ?>
                        <?= $countdownHtml ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 md:-mt-14 z-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
                <div class="bg-white rounded-[2.5rem] shadow-xl border border-purple-100 overflow-hidden flex flex-col">
                    <div class="bg-purple-700 p-8 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10 text-white">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0z"></path></svg>
                        </div>
                        <div class="flex items-center gap-4 relative z-10">
                            <span class="p-3 bg-white/20 rounded-2xl text-2xl">🎓</span>
                            <h2 class="text-2xl font-black uppercase tracking-wider">Undergraduate Programmes</h2>
                        </div>
                    </div>

                    <div class="p-8 space-y-6 flex-grow">
                        <p class="text-gray-900 font-bold text-sm uppercase tracking-wider mb-2">Requirements for Undergraduate Admissions:</p>

                        <div class="flex items-start gap-4">
                            <span class="text-xl">📸</span>
                            <p class="text-gray-800">A recent passport photograph</p>
                        </div>

                        <div class="flex items-start gap-4 p-5 bg-purple-50 rounded-2xl border border-purple-100">
                            <span class="text-xl">💵</span>
                            <div class="space-y-2">
                                <p class="text-gray-800 leading-snug font-medium">
                                    <span class="font-black text-purple-900">₦15,000</span> (Local Students) or <span class="font-black text-purple-900">$30</span> (International Students) non-refundable application fee proof of payment
                                </p>
                                <a href="dashboard/Account Details New.pdf" target="_blank" class="inline-flex items-center text-sm text-purple-600 font-bold underline underline-offset-4 decoration-2 hover:text-purple-800 transition-colors">
                                    Download account details
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <p class="text-xs font-black text-purple-900 uppercase tracking-widest border-b border-purple-100 pb-2 flex items-center gap-2">
                                <span class="text-lg">📘</span> Academic Credentials:
                            </p>
                            <div class="grid grid-cols-1 gap-3">
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-gray-800 text-sm"><span class="font-black text-purple-700">Certificate:</span> Ability to read and write. No specific certification required. Whether you have a formal education or informal learning, you can apply.</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-gray-800 text-sm"><span class="font-black text-purple-700">Diploma:</span> SSCE (or its equivalent) with 5 credits, including English</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-gray-800 text-sm"><span class="font-black text-purple-700">B.Div:</span> SSCE (or its equivalent) with 5 credits including English</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="text-xl">📱</span>
                            <p class="text-gray-800 font-medium">Phone numbers and email of two referees</p>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="text-xl">📜</span>
                            <div class="space-y-1">
                                <p class="text-gray-800 font-medium">One recommendation letter from a clergy</p>
                                <a href="dashboard/clergy_template.pdf" target="_blank" class="inline-flex items-center text-xs text-purple-600 font-bold underline underline-offset-2 hover:text-purple-800 transition-colors">
                                    Download Template
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-xl border border-purple-100 overflow-hidden flex flex-col">
                    <div class="bg-purple-950 p-8 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10 text-white">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0z"></path></svg>
                        </div>
                        <div class="flex items-center gap-4 relative z-10">
                            <span class="p-3 bg-white/10 rounded-2xl text-2xl">🎓</span>
                            <h2 class="text-2xl font-black uppercase tracking-wider">Postgraduate Programmes</h2>
                        </div>
                    </div>

                    <div class="p-8 space-y-6 flex-grow">
                        <p class="text-gray-900 font-bold text-sm uppercase tracking-wider mb-2">Requirements for Post-graduate Admissions:</p>

                        <div class="flex items-start gap-4">
                            <span class="text-xl">📸</span>
                            <p class="text-gray-800">A recent passport photograph</p>
                        </div>

                        <div class="flex items-start gap-4 p-5 bg-purple-50 rounded-2xl border border-purple-100">
                            <span class="text-xl">💵</span>
                            <div class="space-y-2">
                                <p class="text-gray-800 leading-snug font-medium">
                                    <span class="font-black text-purple-900">₦25,000</span> (Local Students) or <span class="font-black text-purple-900">$40</span> (International Students) non-refundable application fee proof of payment
                                </p>
                                <a href="dashboard/Account Details New.pdf" target="_blank" class="inline-flex items-center text-sm text-purple-600 font-bold underline underline-offset-4 decoration-2 hover:text-purple-800 transition-colors">
                                    Download account details
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <p class="text-xs font-black text-purple-900 uppercase tracking-widest border-b border-purple-100 pb-2 flex items-center gap-2">
                                <span class="text-lg">📘</span> Academic Credentials:
                            </p>
                            <div class="grid grid-cols-1 gap-3">
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-gray-800 text-sm"><span class="font-black text-purple-700">PGDT:</span> Bachelor’s degree or HND in any field</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-gray-800 text-sm"><span class="font-black text-purple-700">MA:</span> BA or PGD official transcript from a recognized Theological Seminary.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="text-xl">📱</span>
                            <p class="text-gray-800 font-medium">Phone numbers and email of two referees</p>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="text-xl">📜</span>
                            <div class="space-y-1">
                                <p class="text-gray-800 font-medium">One recommendation letter from a clergy</p>
                                <a href="dashboard/clergy_template.pdf" target="_blank" class="inline-flex items-center text-xs text-purple-600 font-bold underline underline-offset-2 hover:text-purple-800 transition-colors">
                                    Download Template
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-purple-900 text-white p-6 rounded-[2rem] shadow-lg flex flex-col md:flex-row items-center gap-6 border-4 border-white">
                    <span class="text-4xl p-4 bg-white/10 rounded-2xl">🌍</span>
                    <div class="text-center md:text-left">
                        <p class="text-sm md:text-base leading-relaxed font-medium">
                            <span class="font-black text-purple-200 uppercase tracking-widest mr-2">International Students:</span> International students applying for the on-campus program option must secure an <span class="text-purple-200 underline font-bold">STR Visa</span> from the Nigerian Embassy and prepare to pay for a resident card upon arrival.
                        </p>
                    </div>
                    <a href="tel:+2348022164432" class="flex-shrink-0 bg-white text-purple-900 px-6 py-3 rounded-full font-black text-sm uppercase tracking-tighter hover:bg-purple-100 transition shadow-lg">
                        Contact: +234 802 216 4432
                    </a>
                </div>

                <div class="lg:col-span-2 bg-amber-50 border-2 border-amber-200 p-8 rounded-[2.5rem] text-center space-y-8">
                    <div class="max-w-3xl mx-auto">
                        <p class="text-lg text-amber-950 font-bold leading-relaxed mb-2">
                            💡 Your application is automatically saved and can be revisited at any time during the enrollment phase.
                        </p>
                        <p class="text-sm text-amber-900 leading-relaxed italic">
                            "Please don't click submit until all required documents are uploaded, as admission decisions will be based on the documents available for review after your final submission."
                        </p>
                    </div>

                    <div class="flex flex-col items-center gap-4">
                        <?php if ($isRegistrationOpen && !$isApplicationClosed): ?>
                            <a href="dashboard/index" class="inline-flex items-center justify-center px-16 py-6 text-2xl font-black text-white bg-purple-700 rounded-full shadow-[0_20px_60px_rgba(107,33,168,0.4)] hover:bg-purple-900 hover:shadow-[0_20px_60px_rgba(107,33,168,0.6)] transition-all transform hover:-translate-y-2 active:translate-y-0 active:scale-95 group">
                                PROCEED TO APPLY
                                <svg class="w-8 h-8 ml-3 transform group-hover:translate-x-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                        <?php else: ?>
                            <div class="p-6 bg-red-50 border-2 border-red-200 rounded-3xl">
                                <p class="text-red-700 font-black text-xl uppercase tracking-tighter">Admissions are currently closed</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>"""

replace_main(ROOT / "contact.php", contact_main)
replace_main(ROOT / "welcome.php", welcome_main)
replace_main(ROOT / "requirements.php", requirements_main)

print("group2 redesigned")
