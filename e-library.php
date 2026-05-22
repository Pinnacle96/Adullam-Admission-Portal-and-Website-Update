<?php
include('includes/header.php');

$bookResources = [
    [
        'title' => 'Global Christians E-Books',
        'description' => 'Access a wide collection of free Christian e-books, Bible teaching materials, and ministry resources from Cybermissions International.',
        'url' => 'https://www.globalchristians.org/ebooks/'
    ],
    [
        'title' => 'Desiring God Books',
        'description' => 'Explore free Christ-centered books, devotionals, theology resources, and teachings by John Piper and other Christian authors.',
        'url' => 'https://www.desiringgod.org/books/all'
    ],
    [
        'title' => 'Geeky Christian Free E-Books',
        'description' => 'Discover a curated collection of free Christian e-books covering theology, discipleship, apologetics, and spiritual growth.',
        'url' => 'https://geekychristian.com/free-christian-ebooks/'
    ],
    [
        'title' => 'Bibles Net E-Books Library',
        'description' => 'Access downloadable Christian books, biblical teachings, devotionals, and spiritual study resources.',
        'url' => 'https://www.biblesnet.com/ebooks.html'
    ],
    [
        'title' => 'Stellenbosch Theology Repository',
        'description' => 'Download Old Testament and New Testament dissertations, theological research works, and academic publications.',
        'url' => 'https://scholar.sun.ac.za/collections/77c01cdb-bdae-49f1-a2fa-a1780b96c09e'
    ],
    [
        'title' => 'Christian Classics Ethereal Library',
        'description' => 'Read classical Christian literature, theological writings, and historical works from early Christian scholars and authors.',
        'url' => 'https://www.ccel.org/?page=2'
    ],
    [
        'title' => 'NTS Library Theology PDF Books',
        'description' => 'Access a large collection of free theology PDF books, Bible study materials, and ministry resources.',
        'url' => 'https://www.ntslibrary.com/theology-PDF-books.htm'
    ],
    [
        'title' => 'Tyndale Reading Room',
        'description' => 'Read theological books and scholarly resources online through Tyndale digital reading rooms.',
        'url' => 'https://reading-rooms.tyndale.ca'
    ],
    [
        'title' => 'Credo Magazine Resources',
        'description' => 'Explore theological articles, books, videos, and Christian teachings focused on biblical doctrine and spiritual growth.',
        'url' => 'https://credomag.com/2011/09/be-faithful-unto-death-revelation-28-11/'
    ],
    [
        'title' => 'Z-Library',
        'description' => 'A rich online digital library for accessing books, academic articles, and research materials across multiple disciplines.',
        'url' => 'https://z-library.sk/'
    ]
];

$articleResources = [
    [
        'title' => 'Journal of Theological Studies',
        'description' => 'A long-standing journal covering theological research, scholarship, and interpretation across ancient and modern texts.',
        'url' => 'https://academic.oup.com/jts'
    ],
    [
        'title' => 'Australian Journal of Biblical Archaeology',
        'description' => 'Focuses on biblical archaeology and related studies with material reproduced from academic sources.',
        'url' => 'https://biblicalarchaeology.org.uk/journal_ajba.php'
    ],
    [
        'title' => 'Africa Journal of Evangelical Theology',
        'description' => 'A scholarly evangelical journal featuring articles and book reviews on theology and ministry within Africa.',
        'url' => 'https://biblicalstudies.org.uk/articles_ajet-02.php'
    ],
    [
        'title' => 'Avondale Theological Papers',
        'description' => 'A repository of theological research papers and scholarly articles published through Avondale University Research.',
        'url' => 'https://research.avondale.edu.au/theo_papers/index.html#year_2021'
    ],
    [
        'title' => 'Baptist Review of Theology',
        'description' => 'Contains scholarly discussions and reviews from a Baptist theological perspective.',
        'url' => 'https://biblicalstudies.org.uk/articles_brt.php'
    ],
    [
        'title' => 'Bulletin for Biblical Research',
        'description' => 'The official journal of the Institute for Biblical Research, focusing on biblical studies and theology.',
        'url' => 'https://biblicalstudies.org.uk/articles_bbr_01.php'
    ],
    [
        'title' => 'Calvary Baptist Theological Journal',
        'description' => 'Features theology and ministry-focused scholarship originally published by Calvary Baptist Seminary.',
        'url' => 'https://biblicalstudies.org.uk/articles_cbtj.php'
    ],
    [
        'title' => 'The Expositor Series',
        'description' => 'A collection of biblical exposition and theological writings for deeper scriptural understanding.',
        'url' => 'https://biblicalstudies.org.uk/articles_expositor-series-1.php'
    ],
    [
        'title' => 'Grace Theological Journal',
        'description' => 'Contains evangelical theological research and biblical studies first published by Grace Theological Seminary.',
        'url' => 'https://biblicalstudies.org.uk/articles_grace-theological-journal.php'
    ],
    [
        'title' => 'Journal of the Evangelical Theological Society',
        'description' => 'A respected evangelical scholarly journal featuring theological, biblical, and ministry research articles.',
        'url' => 'https://biblicalstudies.org.uk/articles_jets-01.php'
    ],
    [
        'title' => 'Journal of Theological Studies - Original Series',
        'description' => 'The original series of the Journal of Theological Studies published between 1899 and 1949.',
        'url' => 'https://biblicalstudies.org.uk/articles_jts-os_01.php'
    ],
    [
        'title' => 'McMaster Journal of Theology and Ministry',
        'description' => 'Provides pastors, educators, and laypersons with accessible theological and professional studies.',
        'url' => 'https://biblicalstudies.org.uk/articles_mcmaster-journal_01.php'
    ],
    [
        'title' => 'MATS Journal',
        'description' => 'Features essays and theological research from Malaysian scholars and postgraduate students.',
        'url' => 'https://biblicalstudies.org.uk/articles_mats_01.php'
    ],
    [
        'title' => 'Midwestern Journal of Theology',
        'description' => 'Aims to assist Christians and churches through theological articles, sermons, and book reviews.',
        'url' => 'https://biblicalstudies.org.uk/articles_midwestern-journal-of-theology_01.php'
    ],
    [
        'title' => 'Neotestamentica',
        'description' => 'An academic journal under the New Testament Society of South Africa focusing on New Testament scholarship.',
        'url' => 'https://biblicalstudies.org.uk/articles_neotestamentica-01.php'
    ],
    [
        'title' => 'Themelios',
        'description' => 'Focuses on evangelical theology and student ministry scholarship from an IFES background.',
        'url' => 'https://biblicalstudies.org.uk/articles_themelios-ifes.php'
    ],
    [
        'title' => 'Tyndale Bulletin',
        'description' => 'The official journal of the Tyndale Fellowship, available as an open-source theological research publication.',
        'url' => 'https://biblicalstudies.org.uk/articles_tyndale-bulletin_01.php'
    ],
    [
        'title' => 'Tyndale Press Monographs',
        'description' => 'A collection of theological lectures and monographs covering biblical and theological studies.',
        'url' => 'https://biblicalstudies.org.uk/articles_tyndale.php'
    ],
    [
        'title' => 'Vox Evangelica',
        'description' => 'Evangelical theological scholarship originally published by the London Bible College.',
        'url' => 'https://biblicalstudies.org.uk/articles_vox_evangelica.php'
    ]
];

$highlights = [
    [
        'title' => 'Books and Repositories',
        'description' => 'Browse trusted libraries, theology repositories, devotionals, and downloadable study materials.'
    ],
    [
        'title' => 'Journals and Articles',
        'description' => 'Read biblical studies journals, theological papers, and evangelical research collections.'
    ],
    [
        'title' => 'Flexible Access',
        'description' => 'Use the library for personal study, sermon preparation, classroom support, and academic research.'
    ]
];
?>

<?php if (false): ?>
<main>
    <!-- Previous layout kept for future reference -->
    <section class="relative bg-gradient-to-r from-purple-800 to-purple-600 py-20 md:py-32 overflow-hidden">
        <div class="absolute inset-0">
            <img src="assets/img/e-library.jpg" alt="Digital E-Library" class="w-full h-full object-cover opacity-30">
        </div>
        <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6 animate-fadeInUp">Digital E-Library</h1>
            <p class="text-lg md:text-xl font-light max-w-4xl mx-auto mb-8 animate-fadeInUp animation-delay-200">
                Explore a rich collection of books, research materials, audio teachings, videos, journals, and downloadable resources designed to support learning, growth, and knowledge development.
            </p>
            <a href="#resources" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-lg shadow-lg hover:bg-purple-100 transition transform hover:scale-105 animate-fadeInUp animation-delay-400">Explore Resources</a>
        </div>
    </section>
</main>
<?php endif; ?>

<main class="bg-gradient-to-b from-white via-purple-50/40 to-white">
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0">
            <img src="assets/img/e-library.jpg" alt="Digital E-Library" class="h-full w-full object-cover opacity-20">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-purple-950/90 to-purple-700/85"></div>
        </div>
        <div class="absolute -top-16 right-0 h-72 w-72 rounded-full bg-purple-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 md:py-28 lg:py-32">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
                <div>
                    <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold tracking-wide">
                        Digital Learning Hub
                    </span>
                    <h1 class="mt-6 text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        Digital E-Library
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg md:text-xl text-purple-100 leading-relaxed">
                        Explore a rich collection of books, research materials, journals, and downloadable resources designed to support learning, ministry preparation, and theological growth.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="#books" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-800 shadow-lg transition hover:bg-purple-100">
                            Explore Books
                        </a>
                        <a href="#articles" class="inline-flex items-center justify-center rounded-full border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10">
                            Browse Journals
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur">
                        <p class="text-sm uppercase tracking-[0.2em] text-purple-200">Collections</p>
                        <p class="mt-3 text-4xl font-extrabold"><?php echo count($bookResources) + count($articleResources); ?>+</p>
                        <p class="mt-2 text-sm text-purple-100">Curated links for theological reading, study, and academic research.</p>
                    </div>
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur">
                        <p class="text-sm uppercase tracking-[0.2em] text-purple-200">Books</p>
                        <p class="mt-3 text-4xl font-extrabold"><?php echo count($bookResources); ?></p>
                        <p class="mt-2 text-sm text-purple-100">Trusted repositories, devotionals, and open access theology libraries.</p>
                    </div>
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur sm:col-span-2">
                        <p class="text-sm uppercase tracking-[0.2em] text-purple-200">Articles and Journals</p>
                        <p class="mt-3 text-4xl font-extrabold"><?php echo count($articleResources); ?></p>
                        <p class="mt-2 text-sm text-purple-100">Peer-reviewed articles, biblical studies journals, and evangelical research resources.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 md:-mt-14 z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($highlights as $item): ?>
                    <div class="rounded-3xl bg-white p-7 shadow-xl shadow-purple-100/70 ring-1 ring-purple-100">
                        <div class="mb-4 h-12 w-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900"><?php echo htmlspecialchars($item['title']); ?></h2>
                        <p class="mt-3 text-gray-600 leading-relaxed"><?php echo htmlspecialchars($item['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] bg-white ring-1 ring-purple-100 shadow-xl shadow-purple-100/40 p-8 md:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    <div class="lg:col-span-1">
                        <span class="inline-flex rounded-full bg-purple-100 px-4 py-2 text-sm font-semibold text-purple-700">Why This Library Matters</span>
                        <h2 class="mt-4 text-3xl md:text-4xl font-extrabold text-slate-900">A cleaner way to discover theology resources</h2>
                    </div>
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-700">
                        <div class="rounded-2xl bg-slate-50 p-6">
                            <h3 class="text-lg font-bold text-slate-900">For study and research</h3>
                            <p class="mt-2 leading-relaxed">Use these collections for personal study, theological writing, sermon preparation, and deeper classroom engagement.</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-6">
                            <h3 class="text-lg font-bold text-slate-900">For quick access</h3>
                            <p class="mt-2 leading-relaxed">Each resource is presented as a clear card so visitors can scan faster instead of reading through long bullet lists.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4 md:py-8" id="books">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
                <div>
                    <span class="inline-flex rounded-full bg-purple-100 px-4 py-2 text-sm font-semibold text-purple-700">Resource Collection</span>
                    <h2 class="mt-4 text-3xl md:text-4xl font-extrabold text-purple-900">Theological Books</h2>
                    <p class="mt-3 max-w-3xl text-lg text-gray-600">Access recommended theological e-books, repositories, devotionals, and study materials for spiritual and academic growth.</p>
                </div>
                <a href="#articles" class="inline-flex items-center text-purple-700 font-semibold hover:text-purple-900">
                    Go to Journals
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($bookResources as $resource): ?>
                    <article class="group rounded-3xl bg-white p-7 shadow-lg shadow-purple-100/50 ring-1 ring-purple-100 transition duration-300 hover:-translate-y-1 hover:shadow-2xl">
                        <div class="flex items-center justify-between gap-4">
                            <span class="inline-flex rounded-full bg-purple-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-purple-700">Books</span>
                            <span class="text-purple-300 transition group-hover:text-purple-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5h5m0 0v5m0-5L10 14"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7v12h12"></path>
                                </svg>
                            </span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900"><?php echo htmlspecialchars($resource['title']); ?></h3>
                        <p class="mt-3 text-gray-600 leading-relaxed"><?php echo htmlspecialchars($resource['description']); ?></p>
                        <a href="<?php echo htmlspecialchars($resource['url']); ?>" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center font-semibold text-purple-700 hover:text-purple-900">
                            Access Resource
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5h5m0 0v5m0-5L10 14"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7v12h12"></path>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24" id="articles">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] bg-slate-950 px-8 py-10 md:px-10 md:py-12 text-white overflow-hidden relative">
                <div class="absolute top-0 right-0 h-48 w-48 rounded-full bg-purple-500/20 blur-3xl"></div>
                <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div>
                        <span class="inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-purple-200">Scholarly Reading</span>
                        <h2 class="mt-4 text-3xl md:text-4xl font-extrabold">Biblical-Theological Articles</h2>
                        <p class="mt-3 max-w-3xl text-lg text-purple-100">Browse journals, theological papers, biblical archaeology publications, and scholarly evangelical research resources.</p>
                    </div>
                    <a href="#books" class="inline-flex items-center text-white font-semibold hover:text-purple-200">
                        Back to Books
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($articleResources as $resource): ?>
                    <article class="rounded-3xl bg-white p-7 shadow-lg shadow-slate-200/70 ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-2xl">
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-700">Journal</span>
                        <h3 class="mt-5 text-xl font-bold text-slate-900"><?php echo htmlspecialchars($resource['title']); ?></h3>
                        <p class="mt-3 text-gray-600 leading-relaxed"><?php echo htmlspecialchars($resource['description']); ?></p>
                        <a href="<?php echo htmlspecialchars($resource['url']); ?>" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center font-semibold text-purple-700 hover:text-purple-900">
                            Read More
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5h5m0 0v5m0-5L10 14"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7v12h12"></path>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="pb-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="rounded-[2rem] bg-gradient-to-r from-purple-800 to-purple-600 p-8 md:p-10 text-white shadow-2xl">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <h2 class="text-3xl md:text-4xl font-extrabold">Continue your theological journey</h2>
                        <p class="mt-4 text-lg text-purple-100">Use the library for learning today, and explore our academic programs if you want guided theological formation.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 lg:justify-end">
                        <a href="admissions" class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 font-semibold text-purple-700 hover:bg-purple-100 transition">
                            View Admissions
                        </a>
                        <a href="requirements" class="inline-flex items-center justify-center rounded-full border border-white/30 px-7 py-3.5 font-semibold text-white hover:bg-white/10 transition">
                            Apply Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="fixed bottom-6 right-6 z-50">
        <a href="#books" class="inline-flex items-center rounded-full bg-purple-700 px-6 py-3 text-white font-semibold shadow-lg shadow-purple-300/40 transition hover:bg-purple-900 hover:scale-105" aria-label="Browse E-Library Resources">
            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Browse Library
        </a>
    </div>
</main>

<?php include('includes/footer.php'); ?>

<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (!target) {
                return;
            }

            e.preventDefault();
            target.scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
</script>
</body>
</html>
