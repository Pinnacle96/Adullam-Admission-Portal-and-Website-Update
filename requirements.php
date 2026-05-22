<?php 
include('includes/header.php'); 
require 'dashboard/db.php';

// Fetch dynamic modal settings from DB if needed
$modal_title = "";
$modal_content = "";
if (isset($con)) {
    $stmt = $con->prepare("SELECT PageTitle, PageDescription FROM tblpage WHERE PageType = 'home_modal'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $modal_title = $row['PageTitle'];
        $modal_content = $row['PageDescription'];
    }
    $stmt->close();
}

// Check if registration is open from settings
$regStatus = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();
$isRegistrationOpen = ($regStatus == 1);

// Fetch deadline for countdown
$deadlineFile = 'dashboard/modal_settings.json';
$countdownHtml = '';
$isApplicationClosed = false;

if (file_exists($deadlineFile)) {
    $settings = json_decode(file_get_contents($deadlineFile), true);
    $deadline = $settings['deadline'] ?? '';
    if (!empty($deadline)) {
        if (time() > strtotime($deadline . ' 23:59:59')) {
            $isApplicationClosed = true;
        }
        $formattedDate = date('F j, Y', strtotime($deadline));
        $countdownHtml .= '<div class="p-4 bg-purple-900/10 rounded-2xl text-center border border-purple-200 shadow-sm backdrop-blur-sm">';
        $countdownHtml .= '<p class="text-xs text-purple-800 font-bold mb-1 uppercase tracking-widest">';
        $countdownHtml .= 'Application Deadline: <span class="text-purple-950">' . $formattedDate . '</span>';
        $countdownHtml .= '</p>';
        $countdownHtml .= '<div id="countdown" class="text-2xl font-black text-purple-700 font-mono tracking-tighter" data-deadline="' . $deadline . '">Loading...</div>';
        $countdownHtml .= '</div>';
    }
}
?>

<?php if (false): ?>
<main class="bg-gray-50 min-h-screen pt-24 pb-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header & Countdown Flex -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-8 mb-12">
            <div class="text-center md:text-left">
                <h1 class="text-4xl md:text-5xl font-black text-purple-950 mb-3 tracking-tight leading-none">Admission Requirements</h1>
                <p class="text-lg text-gray-600 font-medium italic max-w-2xl">
                    "Carefully read through this page to familiarize yourself with the qualifications required for each program before you begin your application."
                </p>
            </div>
            <div class="w-full md:w-auto">
                <?php if (!$isApplicationClosed && !empty($countdownHtml)): ?>
                    <?= $countdownHtml ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2-Column Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
            
            <!-- Column 1: Undergraduate -->
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

            <!-- Column 2: Postgraduate -->
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

            <!-- International Students Notice (Full Width) -->
            <div class="lg:col-span-2 bg-purple-900 text-white p-6 rounded-[2rem] shadow-lg flex flex-col md:flex-row items-center gap-6 border-4 border-white">
                <span class="text-4xl p-4 bg-white/10 rounded-2xl">🌍</span>
                <div class="text-center md:text-left">
                    <p class="text-sm md:text-base leading-relaxed font-medium">
                        <span class="font-black text-purple-200 uppercase tracking-widest mr-2">International Students:</span> International students applying for the on-campus program option must secure an <span class="text-purple-200 underline font-bold">STR Visa</span> from the Nigerian Embassy and prepare to pay for a resident card upon arrival.
                    </p>
                </div>
                <a href="tel:+2348022164432" class="flex-shrink-0 bg-white text-purple-900 px-6 py-3 rounded-full font-black text-sm uppercase tracking-tighter hover:bg-purple-100 transition shadow-lg">
                    Contact: ‪+234 802 216 4432‬
                </a>
            </div>

            <!-- Final Action & Save Notice (Full Width) -->
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
</main>
<?php endif; ?>

<main class="bg-gradient-to-b from-white via-purple-50/40 to-white pb-20">
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
</main>

<script>
    const countdownElement = document.getElementById("countdown");
    if (countdownElement) {
        const deadline = countdownElement.getAttribute('data-deadline');
        if (deadline) {
            const closingDate = new Date(deadline + " 23:59:59").getTime();
            const timer = setInterval(function () {
                const now = new Date().getTime();
                const distance = closingDate - now;
                if (distance < 0) {
                    clearInterval(timer);
                    countdownElement.innerHTML = "APPLICATION CLOSED";
                    return;
                }
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                countdownElement.innerHTML = days + "d : " + hours + "h : " + minutes + "m : " + seconds + "s";
            }, 1000);
        }
    }
</script>

<?php include('includes/footer.php'); ?>
