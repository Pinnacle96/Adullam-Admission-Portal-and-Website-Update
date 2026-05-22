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


partner_main = """<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="assets/img/partner.jpg" alt="Donation and Partnership at Adullam Seminary" class="w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] gap-10 items-center">
                <div>
                    <h1 class="max-w-3xl text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        Donation & Partnership
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        Partner with us as we raise leaders and scholars who will impact the church and society.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Your Support Makes a Difference</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Donation Accounts</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Donate Now</span>
                    </div>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="#donate" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-800 shadow-lg transition hover:bg-purple-100">
                            Support Our Mission
                        </a>
                        <a href="#overview" class="inline-flex items-center justify-center rounded-full border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10">
                            Your Support Makes a Difference
                        </a>
                    </div>
                </div>

                <div class="lg:justify-self-end">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="rounded-[1.5rem] bg-white/95 p-6 text-slate-900">
                            <a href="#overview" class="flex items-center justify-between rounded-2xl bg-purple-50 px-4 py-4 transition hover:bg-purple-100">
                                <span class="font-semibold text-slate-900">Your Support Makes a Difference</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#donate" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Donation Accounts</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#donate" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Donate Now</span>
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
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">Your Support Makes a Difference</h2>
                        <p class="mt-6 text-lg text-gray-700 leading-relaxed">
                            <strong class="text-purple-700">RCN Theological Seminary—Adullam</strong> has been equipping students through spiritual formation, academic research, and practical ministry for over 15 years. Your partnership allows us to continue this vital work and produce alumni who are transforming the Church and society at large.
                        </p>
                        <p class="mt-4 text-lg text-gray-700 leading-relaxed">
                            Whether you are sponsoring a student's education or contributing to our projects, your generosity directly empowers a new generation of leaders. We are deeply grateful for your support.
                        </p>
                        <a href="#donate" class="inline-flex items-center justify-center mt-8 rounded-full bg-purple-700 px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-purple-900">
                            Donate Now
                        </a>
                    </div>

                    <div class="rounded-[1.75rem] bg-gradient-to-br from-purple-700 to-slate-900 p-[1px]">
                        <div class="rounded-[1.7rem] bg-white p-6">
                            <img src="assets/img/partner.jpg" alt="Partner with Adullam Seminary" class="h-80 w-full rounded-[1.25rem] object-cover">
                            <p class="sr-only">A community of supporters and students at Adullam Seminary, collaborating to advance theological education.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24" id="donate">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">Donation Accounts</h2>
                <p class="mt-4 text-lg text-gray-600">Choose your preferred currency to make a donation to Adullam Seminary.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-5">Donation in Pounds (GBP) 🇬🇧</h3>
                    <div class="space-y-3 text-gray-700 leading-relaxed">
                        <p><strong class="text-purple-700">Bank:</strong> Access Bank Plc</p>
                        <p><strong class="text-purple-700">Account Name:</strong> Remnant Christian Network Theological Seminary - Adullam</p>
                        <p><strong class="text-purple-700">Account No:</strong> 1667594370</p>
                        <p><strong class="text-purple-700">Swift:</strong> ABNGNGLA</p>
                        <p><strong class="text-purple-700">IBAN:</strong> GB27CITI18500811071211</p>
                        <p><strong class="text-purple-700">Intermediary Bank Swift:</strong> CITIGB2L</p>
                        <p><strong class="text-purple-700">Sort Code:</strong> 185008</p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-5">Donation in US Dollars (USD) 🇺🇸</h3>
                    <div class="space-y-3 text-gray-700 leading-relaxed">
                        <p><strong class="text-purple-700">Bank:</strong> Access Bank Plc</p>
                        <p><strong class="text-purple-700">Account Name:</strong> Remnant Christian Network Theological Seminary - Adullam</p>
                        <p><strong class="text-purple-700">Account No:</strong> 1665250883</p>
                        <p><strong class="text-purple-700">Swift:</strong> ABNGNGLA</p>
                        <p><strong class="text-purple-700">Intermediary Bank:</strong> Citibank (CITIUS33)</p>
                        <p><strong class="text-purple-700">SWIFT CODE:</strong> UNAFNGLA</p>
                        <p><strong class="text-purple-700">Routing Number:</strong> 021000089</p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-5">Donation in Euros (EUR) 🇪🇺</h3>
                    <div class="space-y-3 text-gray-700 leading-relaxed">
                        <p><strong class="text-purple-700">Bank:</strong> Access Bank Plc</p>
                        <p><strong class="text-purple-700">Account Name:</strong> Remnant Christian Network Theological Seminary - Adullam</p>
                        <p><strong class="text-purple-700">Account No:</strong> 1664879355</p>
                        <p><strong class="text-purple-700">Swift:</strong> ABNGNGLA</p>
                        <p><strong class="text-purple-700">IBAN:</strong> GB74CITI18500811071238</p>
                        <p><strong class="text-purple-700">Intermediary Bank Swift:</strong> CITIGB2L</p>
                        <p><strong class="text-purple-700">SWIFT CODE:</strong> UNAFNGLA</p>
                        <p><strong class="text-purple-700">Sort Code:</strong> 185008</p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                    <h3 class="text-2xl font-bold text-gray-800 mb-5">Donation in Naira (NGN) 🇳🇬</h3>
                    <div class="space-y-3 text-gray-700 leading-relaxed">
                        <p><strong class="text-purple-700">Bank:</strong> Access Bank Plc</p>
                        <p><strong class="text-purple-700">Account Name:</strong> Remnant Christian Network Theological Seminary - Adullam</p>
                        <p><strong class="text-purple-700">Account No:</strong> 1652191540</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="fixed bottom-6 right-6 z-50">
        <a href="#donate" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="Donate to Adullam Seminary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Donate Now
        </a>
    </div>
</main>"""

online_main = """<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
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
</main>"""

admissions_main = """<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
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
</main>"""

replace_main(ROOT / "partner.php", partner_main)
replace_main(ROOT / "online_school.php", online_main)
replace_main(ROOT / "admissions.php", admissions_main)

print("group1 redesigned")
