import re
from pathlib import Path

ROOT = Path(r"c:\wamp64\www\adullam")

program_pages = {
    "cert.php": {
        "hero_image": "assets/img/cert.jpg",
        "hero_alt": "Certificate in Theology Program",
        "title": "Certificate in Theology",
        "subtitle": "A solid foundation in biblical studies and Christian doctrine for new believers and aspiring leaders.",
        "hero_cta_href": "#apply",
        "hero_cta_text": "Explore Program",
        "overview_title": "What You Will Learn",
        "overview_body": [
            "Our Certificate in Theology program provides a comprehensive introduction to the core tenets of Christian faith. It's designed to ground you in biblical principles and equip you with the spiritual and intellectual tools for ministry and Christian living."
        ],
        "overview_list": [
            "Establish believers in the foundation of the Christian Faith",
            "Develop spiritual stamina for Christian living in a failing world",
            "Develop skills in critical thinking",
        ],
        "overview_button": "Apply Now",
        "overview_image": "assets/img/cert.jpg",
        "overview_image_alt": "Certificate in Theology Program",
        "overview_image_desc": "Students engaging in theological studies and ministry activities.",
        "admissions": [
            '<strong class="text-purple-700">Application Fee:</strong> A non-refundable fee of N15,000 for local students or $30 for international students.',
            '<strong class="text-purple-700">Completed Application Form:</strong> Your application must be submitted online.',
            '<strong class="text-purple-700">Academic Credentials:</strong> Ability to read and write. No specific certification is required. Whether you have a formal education or informal learning, you can apply.',
            '<strong class="text-purple-700">English Proficiency:</strong> The ability to demonstrate proficiency in English (reading and writing).',
            '<strong class="text-purple-700">References:</strong> Provide the phone numbers and email addresses of two referees.',
            '<strong class="text-purple-700">Recommendation Letter:</strong> One recommendation letter from a clergy in your local church. <a href="sample.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Sample Recommendation Letter">Download Sample</a>',
            '<strong class="text-purple-700">International Students:</strong> International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact +2348022164432 for more details).',
        ],
        "detail_cards": [
            (
                "Program Options",
                '<p><strong class="text-purple-700">On-Campus:</strong> A full residency program with class attendance, ministry practicum, and a field trip for a holistic learning experience.</p><p class="mt-4"><strong class="text-purple-700">Online:</strong> A flexible and engaging program for those with work or family commitments, allowing you to study from anywhere in the world.</p>',
            ),
            (
                "Program Length",
                '<p>The Certificate in Theology is a focused, 10-month program, divided into two semesters, perfect for those seeking to quickly gain a solid biblical foundation.</p>',
            ),
            (
                "Important Downloads",
                '<p><strong class="text-purple-700">Fees:</strong> <a href="fees/CERTIFICATE_FEES.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Certificate Fees PDF">Download PDF</a></p><p class="mt-4"><strong class="text-purple-700">Course List:</strong> <a href="course list/CERTIFICATE OF THEOLOGY COURSE LISTING.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Course List PDF">Download PDF</a></p>',
            ),
        ],
        "sticky_href": "requirements",
        "sticky_text": "Apply Now",
        "sticky_aria": "Apply Now for Certificate in Theology",
    },
    "dip.php": {
        "hero_image": "assets/img/dip.jpg",
        "hero_alt": "Diploma in Theology Program",
        "title": "Diploma in Theology",
        "subtitle": "A foundational program designed to equip you with the knowledge and skills for effective ministry.",
        "hero_cta_href": "#apply",
        "hero_cta_text": "Explore Program",
        "overview_title": "What You Will Learn",
        "overview_body": [
            "Our Diploma in Theology program provides a thorough grounding in Christian doctrine and spiritual formation. You will develop the intellectual and spiritual stamina required for a life of purpose, ministry, and effective service to the body of Christ."
        ],
        "overview_list": [
            "Establish believers in the foundation of the Christian Faith",
            "Develop spiritual stamina for Christian living in a failing world",
            "Lay a foundation for critical theological research writing",
        ],
        "overview_button": "Apply Now",
        "overview_image": "assets/img/dip.jpg",
        "overview_image_alt": "Diploma in Theology Program",
        "overview_image_desc": "Students engaging in theological studies and ministry activities.",
        "admissions": [
            '<strong class="text-purple-700">Application Fee:</strong> A non-refundable fee of N15,000 for local students or $30 for international students.',
            '<strong class="text-purple-700">Completed Application Form:</strong> Your application must be submitted online.',
            '<strong class="text-purple-700">Academic Credentials:</strong> A minimum of five credits, including English Language, in SSCE or its equivalent.',
            '<strong class="text-purple-700">English Proficiency:</strong> The ability to demonstrate proficiency in English (reading and writing).',
            '<strong class="text-purple-700">References:</strong> Provide the phone numbers and email addresses of two referees.',
            '<strong class="text-purple-700">Recommendation Letter:</strong> One recommendation letter from a clergy in your local church. <a href="sample.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Sample Recommendation Letter">Download Sample</a>',
            '<strong class="text-purple-700">International Students:</strong> International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact +2348022164432 for more details).',
        ],
        "detail_cards": [
            (
                "Program Options",
                '<p><strong class="text-purple-700">On-Campus:</strong> A full residency program with class attendance, ministry practicum, and a field trip for a holistic learning experience.</p><p class="mt-4"><strong class="text-purple-700">Online:</strong> A flexible and engaging program for those with work or family commitments, allowing you to study from anywhere in the world.</p>',
            ),
            (
                "Program Length",
                '<p>The Diploma in Theology is a 3-year program, divided into 6 semesters, offering a comprehensive and in-depth study of the faith.</p>',
            ),
            (
                "Important Downloads",
                '<p><strong class="text-purple-700">Fees:</strong> <a href="fees/DIPLOMA_FEES.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Diploma Fees PDF">Download PDF</a></p><p class="mt-4"><strong class="text-purple-700">Course List:</strong> <a href="course list/DIPLOMA OF THEOLOGY COURSE LISTING.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Course List PDF">Download PDF</a></p>',
            ),
        ],
        "sticky_href": "requirements",
        "sticky_text": "Apply Now",
        "sticky_aria": "Apply Now for Diploma in Theology",
    },
    "bdiv.php": {
        "hero_image": "assets/img/bdiv.jpg",
        "hero_alt": "Bachelor of Divinity Program",
        "title": "Bachelor of Divinity",
        "subtitle": "Equipping you with foundational knowledge and spiritual discipline for a lifetime of ministry.",
        "hero_cta_href": "#apply",
        "hero_cta_text": "Explore Program",
        "overview_title": "What You Will Learn",
        "overview_body": [
            "Our Bachelor of Divinity program is designed to provide a comprehensive foundation in biblical and theological studies, preparing you to serve as a well-equipped and Christ-like leader. You will gain a deep understanding of scripture, develop a disciplined spiritual life, and acquire the skills necessary for effective ministry."
        ],
        "overview_list": [
            "Establish believers in the foundation of the Christian Faith",
            "Develop spiritual stamina for Christian living in a failing world",
            "Develop skills in critical theological research writing",
        ],
        "overview_button": "Apply Now",
        "overview_image": "assets/img/bdiv.jpg",
        "overview_image_alt": "Bachelor of Divinity Program",
        "overview_image_desc": "Students engaging in theological studies and ministry activities.",
        "admissions": [
            '<strong class="text-purple-700">Application Fee:</strong> A non-refundable fee of N15,000 for local students or $30 for international students.',
            '<strong class="text-purple-700">Completed Application Form:</strong> Your application must be submitted online.',
            '<strong class="text-purple-700">Academic Credentials:</strong> A minimum of five credits, including English Language, in SSCE or its equivalent.',
            '<strong class="text-purple-700">English Proficiency:</strong> The ability to demonstrate proficiency in English (reading and writing).',
            '<strong class="text-purple-700">References:</strong> Provide the phone numbers and email addresses of two referees.',
            '<strong class="text-purple-700">Recommendation Letter:</strong> One recommendation letter from a clergy in your local church. <a href="sample.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Sample Recommendation Letter">Download Sample</a>',
            '<strong class="text-purple-700">International Students:</strong> International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact +2348022164432 for more details).',
        ],
        "detail_cards": [
            (
                "Program Options",
                '<p><strong class="text-purple-700">On-Campus:</strong> A full residency program with class attendance, ministry practicum, and a field trip for a holistic learning experience.</p><p class="mt-4"><strong class="text-purple-700">Online:</strong> A flexible and engaging program designed for those with work and family commitments, allowing you to study from anywhere in the world.</p>',
            ),
            (
                "Program Length",
                '<p>The Bachelor of Divinity program is a 4-year curriculum, divided into 8 semesters, providing ample time for deep study and spiritual formation.</p>',
            ),
            (
                "Important Downloads",
                '<p><strong class="text-purple-700">Fees:</strong> <a href="fees/BACHELOR_FEES.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Bachelor Fees PDF">Download PDF</a></p><p class="mt-4"><strong class="text-purple-700">Course List:</strong> <a href="course list/BACHELOR OF DIVINITY COURSE LISTING.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Course List PDF">Download PDF</a></p>',
            ),
        ],
        "sticky_href": "requirements",
        "sticky_text": "Apply Now",
        "sticky_aria": "Apply Now for Bachelor of Divinity",
    },
    "pgdt.php": {
        "hero_image": "assets/img/pgdt.jpg",
        "hero_alt": "Post Graduate Diploma Program",
        "title": "Post Graduate Diploma in Theology (PGDT)",
        "subtitle": "Advance your theological knowledge and prepare for further academic or ministerial pursuits.",
        "hero_cta_href": "#apply",
        "hero_cta_text": "Explore Program",
        "overview_title": "What You Will Learn",
        "overview_body": [
            "Our Post Graduate Diploma in Theology (PGDT) is a robust program designed for individuals with a bachelor's degree seeking to deepen their theological understanding. This program will equip you with advanced skills in biblical research and critical thinking, preparing you for higher studies or enhanced ministerial service."
        ],
        "overview_list": [
            "Establish believers in the foundation of the Christian Faith",
            "Develop spiritual stamina for Christian living in a failing world",
            "Develop skills in critical theological research writing",
        ],
        "overview_button": "Apply Now",
        "overview_image": "assets/img/pgdt.png",
        "overview_image_alt": "Post Graduate Diploma Program",
        "overview_image_desc": "Scholars engaging in advanced theological studies and ministry activities.",
        "admissions": [
            '<strong class="text-purple-700">Application Fee:</strong> A non-refundable fee of N25,000 for local students or $40 for international students.',
            '<strong class="text-purple-700">Completed Application Form:</strong> Your application must be submitted online.',
            '<strong class="text-purple-700">Academic Credentials:</strong> A minimum of a Bachelor’s degree or equivalent is required.',
            '<strong class="text-purple-700">References:</strong> Provide the phone numbers and email addresses of two referees.',
            '<strong class="text-purple-700">Recommendation Letter:</strong> One recommendation letter from a clergy in your local church. <a href="sample.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Sample Recommendation Letter">Download Sample</a>',
            '<strong class="text-purple-700">International Students:</strong> International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact +2348022164432 for more details).',
        ],
        "detail_cards": [
            (
                "Program Options",
                '<p><strong class="text-purple-700">On-Campus:</strong> A full residency program with class attendance, ministry practicum, and a field trip for a holistic learning experience.</p><p class="mt-4"><strong class="text-purple-700">Online:</strong> A flexible and engaging program designed for those with work and family commitments, allowing you to study from anywhere in the world.</p>',
            ),
            (
                "Program Length",
                '<p>The PGDT is an intensive 10-month program, divided into two semesters, perfect for advanced theological study.</p>',
            ),
            (
                "Important Downloads",
                '<p><strong class="text-purple-700">Fees:</strong> <a href="fees/POSTGRADTE_DIPLOMA_FEES.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download PGDT Fees PDF">Download PDF</a></p><p class="mt-4"><strong class="text-purple-700">Course List:</strong> <a href="course list/POSTGRADUATE DIPLOMA IN THEOLOGY COURSE LISTING.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download PGDT Course List PDF">Download PDF</a></p>',
            ),
        ],
        "sticky_href": "requirements",
        "sticky_text": "Apply Now",
        "sticky_aria": "Apply Now for Post Graduate Diploma in Theology",
    },
    "master.php": {
        "hero_image": "assets/img/masters.jpg",
        "hero_alt": "Master of Arts Program",
        "title": "Master of Arts Biblical Studies (OT/NT)",
        "subtitle": "Elevate your theological understanding and engage the world with advanced biblical scholarship.",
        "hero_cta_href": "#apply",
        "hero_cta_text": "Explore Program",
        "overview_title": "What You Will Learn",
        "overview_body": [
            "Our Master of Arts program is designed for scholars and leaders who wish to advance their knowledge in theology and ministry. The curriculum provides an advanced understanding of the Bible, critical thinking skills, and the principles of Christian apologetics, preparing you to defend and share your faith in today's world."
        ],
        "overview_list": [
            "Advanced knowledge of the Bible in historical and theological context",
            "Proficiency in biblical interpretation",
            "Understanding of Christian apologetics principles and practices",
            "Critical thinking and defense of faith",
        ],
        "overview_button": "Apply Now",
        "overview_image": "assets/img/masters.png",
        "overview_image_alt": "Master of Arts Program",
        "overview_image_desc": "Scholars engaging in advanced theological studies and ministry activities.",
        "admissions": [
            '<strong class="text-purple-700">Application Fee:</strong> A non-refundable fee of N25,000 for local students or $40 for international students.',
            '<strong class="text-purple-700">Completed Application Form:</strong> Your application must be submitted online.',
            '<strong class="text-purple-700">Academic Credentials:</strong> A Bachelor of Theology or Postgraduate Diploma in Theology is required.',
            '<strong class="text-purple-700">References:</strong> Provide the phone numbers and email addresses of two referees.',
            '<strong class="text-purple-700">Recommendation Letter:</strong> One recommendation letter from a clergy in your local church. <a href="sample.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Sample Recommendation Letter">Download Sample</a>',
            '<strong class="text-purple-700">International Students:</strong> International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact +2348022164432 for more details).',
        ],
        "detail_cards": [
            (
                "Program Options",
                '<p><strong class="text-purple-700">Master of Theology:</strong> Biblical Studies (OT/NT)</p>',
            ),
            (
                "Learning Options",
                '<p><strong class="text-purple-700">On-Campus:</strong> A full-time residency program with classes, ministry practicum, and field trips for a comprehensive experience.</p><p class="mt-4"><strong class="text-purple-700">Online:</strong> A flexible and engaging program for working professionals, allowing you to balance studies with your commitments.</p>',
            ),
            (
                "Important Downloads",
                '<p><strong class="text-purple-700">Fees:</strong> <a href="fees/MASTERS.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Master\'s Fees PDF">Download PDF</a></p><p class="mt-4"><strong class="text-purple-700">Course Lists:</strong></p><a href="course list/M.A. THEOLOGY - BIBLICAL STUDIES COURSE LISTING.pdf" class="block text-purple-700 hover:underline font-semibold mt-2" aria-label="Download M.A. Biblical Studies Course List PDF">M.A. Biblical Studies (OT/NT)</a>',
            ),
        ],
        "sticky_href": "requirements",
        "sticky_text": "Apply Now",
        "sticky_aria": "Apply Now for Master of Arts",
    },
    "masters.php": {
        "hero_image": "assets/img/masters.jpg",
        "hero_alt": "Master of Arts Program",
        "title": "Master of Arts Christian Apologetics",
        "subtitle": "Elevate your theological understanding and engage the world with advanced biblical scholarship.",
        "hero_cta_href": "#apply",
        "hero_cta_text": "Explore Program",
        "overview_title": "What You Will Learn",
        "overview_body": [
            "Our Master of Arts program is designed for scholars and leaders who wish to advance their knowledge in theology and ministry. The curriculum provides an advanced understanding of the Bible, critical thinking skills, and the principles of Christian apologetics, preparing you to defend and share your faith in today's world."
        ],
        "overview_list": [
            "Advanced knowledge of the Bible in historical and theological context",
            "Proficiency in biblical interpretation",
            "Understanding of Christian apologetics principles and practices",
            "Critical thinking and defense of faith",
        ],
        "overview_button": "Apply Now",
        "overview_image": "assets/img/masters.png",
        "overview_image_alt": "Master of Arts Program",
        "overview_image_desc": "Scholars engaging in advanced theological studies and ministry activities.",
        "admissions": [
            '<strong class="text-purple-700">Application Fee:</strong> A non-refundable fee of N25,000 for local students or $40 for international students.',
            '<strong class="text-purple-700">Completed Application Form:</strong> Your application must be submitted online.',
            '<strong class="text-purple-700">Academic Credentials:</strong> A Bachelor of Theology or Postgraduate Diploma in Theology is required.',
            '<strong class="text-purple-700">References:</strong> Provide the phone numbers and email addresses of two referees.',
            '<strong class="text-purple-700">Recommendation Letter:</strong> One recommendation letter from a clergy in your local church. <a href="sample.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Sample Recommendation Letter">Download Sample</a>',
            '<strong class="text-purple-700">International Students:</strong> International students applying for the on-campus program option must secure an STR Visa from the Nigerian Embassy and prepare to pay for a resident card upon arrival (Contact +2348022164432 for more details).',
        ],
        "detail_cards": [
            (
                "Program Options",
                '<p><strong class="text-purple-700">Master of Theology:</strong> Christian Apologetics</p>',
            ),
            (
                "Learning Options",
                '<p><strong class="text-purple-700">On-Campus:</strong> A full-time residency program with classes, ministry practicum, and field trips for a comprehensive experience.</p><p class="mt-4"><strong class="text-purple-700">Online:</strong> A flexible and engaging program for working professionals, allowing you to balance studies with your commitments.</p>',
            ),
            (
                "Important Downloads",
                '<p><strong class="text-purple-700">Fees:</strong> <a href="fees/MASTERS.pdf" class="text-purple-700 hover:underline font-semibold" aria-label="Download Master\'s Fees PDF">Download PDF</a></p><p class="mt-4"><strong class="text-purple-700">Course Lists:</strong></p><a href="course list/M.A. THEOLOGY - CHRISTIAN APOLOGETICS COURSE LISTING.pdf" class="block text-purple-700 hover:underline font-semibold mt-2" aria-label="Download M.A. Christian Apologetics Course List PDF">M.A. Christian Apologetics</a>',
            ),
        ],
        "sticky_href": "requirements",
        "sticky_text": "Apply Now",
        "sticky_aria": "Apply Now for Master of Arts",
    },
}


def replace_main(file_path: Path, new_main: str) -> None:
    text = file_path.read_text(encoding="utf-8")
    pattern = re.compile(r"<main[\s\S]*?</main>", re.S)
    if "<?php if (false): ?>" in text:
        text = pattern.sub(new_main, text, count=1)
    else:
        text = pattern.sub(lambda m: f"<?php if (false): ?>\n{m.group(0)}\n<?php endif; ?>\n\n{new_main}", text, count=1)
    file_path.write_text(text, encoding="utf-8")


def render_list(items):
    blocks = []
    for item in items:
        blocks.append(
            f'''                            <li class="flex items-start rounded-2xl bg-slate-50 px-4 py-4 ring-1 ring-slate-100">
                                <svg class="w-5 h-5 text-purple-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>{item}</span>
                            </li>'''
        )
    return "\n".join(blocks)


def render_admissions(items):
    return "\n".join(f"                        <li>{item}</li>" for item in items)


def render_cards(cards):
    blocks = []
    for title, body in cards:
        blocks.append(
            f'''                    <div class="rounded-[1.75rem] bg-white p-8 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100 transition duration-300 hover:-translate-y-1">
                        <h3 class="text-2xl font-bold text-gray-800 mb-5">{title}</h3>
                        <div class="space-y-3 text-gray-700 leading-relaxed">{body}</div>
                    </div>'''
        )
    return "\n".join(blocks)


def render_program_page(data):
    overview_paragraphs = "\n".join(
        f'                        <p class="mt-6 text-lg text-gray-700 leading-relaxed">{paragraph}</p>'
        for paragraph in data["overview_body"]
    )
    overview_list = render_list(data["overview_list"])
    admissions = render_admissions(data["admissions"])
    cards = render_cards(data["detail_cards"])

    return f'''<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="{data["hero_image"]}" alt="{data["hero_alt"]}" class="w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.12),_transparent_35%),linear-gradient(120deg,rgba(2,6,23,0.96),rgba(76,29,149,0.92),rgba(126,34,206,0.82))]"></div>
        </div>
        <div class="absolute -top-12 right-0 h-64 w-64 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] gap-10 items-center">
                <div>
                    <h1 class="max-w-3xl text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        {data["title"]}
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        {data["subtitle"]}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">{data["overview_title"]}</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Admission Requirements</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-purple-100">Program Details & Requirements</span>
                    </div>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="{data["hero_cta_href"]}" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-800 shadow-lg transition hover:bg-purple-100">
                            {data["hero_cta_text"]}
                        </a>
                        <a href="#admissions" class="inline-flex items-center justify-center rounded-full border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10">
                            Admission Requirements
                        </a>
                    </div>
                </div>

                <div class="lg:justify-self-end">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="rounded-[1.5rem] bg-white/95 p-6 text-slate-900">
                            <a href="#overview" class="flex items-center justify-between rounded-2xl bg-purple-50 px-4 py-4 transition hover:bg-purple-100">
                                <span class="font-semibold text-slate-900">{data["overview_title"]}</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#admissions" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Admission Requirements</span>
                                <svg class="h-5 w-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="#apply" class="mt-3 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4 transition hover:bg-slate-100">
                                <span class="font-semibold text-slate-900">Program Details & Requirements</span>
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
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">{data["overview_title"]}</h2>
{overview_paragraphs}
                        <ul class="mt-8 space-y-3 text-gray-700">
{overview_list}
                        </ul>
                        <a href="requirements" class="inline-flex items-center justify-center mt-8 rounded-full bg-purple-700 px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-purple-900">
                            {data["overview_button"]}
                        </a>
                    </div>

                    <div class="rounded-[1.75rem] bg-gradient-to-br from-purple-700 to-slate-900 p-[1px]">
                        <div class="rounded-[1.7rem] bg-white p-6">
                            <img src="{data["overview_image"]}" alt="{data["overview_image_alt"]}" class="h-80 w-full rounded-[1.25rem] object-cover">
                            <p class="sr-only">{data["overview_image_desc"]}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24" id="admissions">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">Admission Requirements</h2>
            </div>
            <div class="rounded-[2rem] bg-white p-8 md:p-10 shadow-xl shadow-purple-100/50 ring-1 ring-purple-100">
                <ul class="list-disc list-inside space-y-4 text-gray-700 leading-relaxed">
{admissions}
                </ul>
            </div>
        </div>
    </section>

    <section class="pb-16 md:pb-24" id="apply">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-extrabold text-purple-900">Program Details & Requirements</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
{cards}
            </div>
        </div>
    </section>

    <div class="fixed bottom-6 right-6 z-50">
        <a href="{data["sticky_href"]}" class="bg-purple-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg hover:bg-purple-900 transition transform hover:scale-105 flex items-center" aria-label="{data["sticky_aria"]}">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            {data["sticky_text"]}
        </a>
    </div>
</main>'''


for file_name, page_data in program_pages.items():
    replace_main(ROOT / file_name, render_program_page(page_data))

print("program pages redesigned")
