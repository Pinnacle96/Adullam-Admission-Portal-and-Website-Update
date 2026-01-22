<?php include('includes/header.php'); ?>

<main class="bg-gray-50 text-gray-800 font-sans">
     
<?php
// Fetch Modal Settings
$modal_active = false;
$modal_content = "";
$modal_title = "";

if (isset($con)) {
    $stmt = $con->prepare("SELECT PageTitle, PageDescription, Email FROM tblpage WHERE PageType = 'home_modal'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['Email'] === 'active') {
            $modal_active = true;
            $modal_content = $row['PageDescription'];
            $modal_title = $row['PageTitle'];
        }
    }
    $stmt->close();
}


if ($modal_active) {
?>
<div id="messageModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity opacity-0" id="modalBackdrop"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal Panel -->
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="modalPanel">
                
                <!-- Close Button (Top Right) -->
                <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block z-10">
                    <button type="button" id="closeModalX" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <?php if(!empty($modal_title)): ?>
                            <h3 class="text-xl font-semibold leading-6 text-gray-900 mb-4" id="modal-title"><?= htmlspecialchars($modal_title) ?></h3>
                            <?php endif; ?>
                            
                            <?php
                            $deadlineFile = 'dashboard/modal_settings.json';
                            $countdownHtml = '';
                            $isApplicationClosed = false;
                            
                            if (file_exists($deadlineFile)) {
                                $settings = json_decode(file_get_contents($deadlineFile), true);
                                $deadline = $settings['deadline'] ?? '';
                                if (!empty($deadline)) {
                                    // Check if application is closed (End of the deadline day)
                                    if (time() > strtotime($deadline . ' 23:59:59')) {
                                        $isApplicationClosed = true;
                                    }

                                    $formattedDate = date('F j, Y', strtotime($deadline));
                                    // Buffer the HTML
                                    $countdownHtml .= '<div class="mb-4 p-3 bg-purple-50 rounded-lg text-center border border-purple-100">';
                                    $countdownHtml .= '<p class="text-sm text-purple-800 font-medium mb-1">';
                                    $countdownHtml .= 'Closing Date: <span class="font-bold">' . $formattedDate . '</span>';
                                    $countdownHtml .= '</p>';
                                    $countdownHtml .= '<div id="countdown" class="text-2xl font-bold text-purple-700 font-mono tracking-wider" data-deadline="' . $deadline . '">Loading...</div>';
                                    $countdownHtml .= '</div>';
                                }
                            }

                            // Smart Position Logic:
                            // 1. If {{countdown}} is found, replace it.
                            // 2. If "Begin your journey" is found, insert before it (User request).
                            // 3. Otherwise, prepend to top.
                            
                            if (!empty($countdownHtml)) {
                                if (strpos($modal_content, '{{countdown}}') !== false) {
                                    $modal_content = str_replace('{{countdown}}', $countdownHtml, $modal_content);
                                } elseif (preg_match('/(<p[^>]*>\s*Begin your journey)/i', $modal_content)) {
                                    // Insert BEFORE the paragraph containing "Begin your journey" to ensure valid HTML
                                    $modal_content = preg_replace('/(<p[^>]*>\s*Begin your journey)/i', $countdownHtml . '$1', $modal_content, 1);
                                } elseif (stripos($modal_content, 'Begin your journey') !== false) {
                                    // Fallback if not inside a p tag (rare but possible)
                                    $modal_content = preg_replace('/(Begin your journey)/i', $countdownHtml . '$1', $modal_content, 1);
                                } else {
                                    // Default: Top
                                    echo $countdownHtml;
                                }
                            }
                            ?>
                            
                            <div class="mt-2 prose max-w-none text-gray-600">
                                <?= $modal_content ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer (Mobile Close & Action) -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <?php if (!$isApplicationClosed): ?>
                    <a href="dashboard" class="inline-flex w-full justify-center rounded-md bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 sm:w-auto">Apply Now</a>
                    <?php endif; ?>
                    <button type="button" id="closeModalBtn" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('messageModal');
        const backdrop = document.getElementById('modalBackdrop');
        const panel = document.getElementById('modalPanel');
        const closeBtn = document.getElementById('closeModalBtn');
        const closeX = document.getElementById('closeModalX');
        
        // Show modal with delay
        setTimeout(() => {
            modal.classList.remove('hidden');
            // Trigger reflow
            void modal.offsetWidth;
            
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
        }, 1000);

        // Close modal function
        function hideModal() {
            backdrop.classList.add('opacity-0');
            panel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
            panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        if(closeBtn) closeBtn.addEventListener('click', hideModal);
        if(closeX) closeX.addEventListener('click', hideModal);

        // Close on outside click (Scroll Container)
        const scrollContainer = modal.querySelector('.fixed.inset-0.z-10');
        if(scrollContainer) {
            scrollContainer.addEventListener('click', function(e) {
                // If clicking the container itself or the flex wrapper (not the panel)
                if (e.target === scrollContainer || e.target.classList.contains('flex')) {
                    hideModal();
                }
            });
        }
    });
</script>
<?php } ?>


    
    <!-- Hero Section -->
    <section id="hero-slider" class="relative min-h-screen flex items-center justify-center text-center text-white overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000 ease-in-out hero-slide active opacity-100" style="background-image: url('assets/img/hero-carousel/1.jpg');"></div>
        <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000 ease-in-out hero-slide opacity-0" style="background-image: url('assets/img/hero-carousel/2.jpg');"></div>
        <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000 ease-in-out hero-slide opacity-0" style="background-image: url('assets/img/hero-carousel/4.jpg');"></div>
        
        <div class="absolute inset-0 bg-black opacity-60 z-10"></div>
        
        <div class="relative z-20 max-w-4xl px-6">
            <div class="hero-text-content opacity-100 transition-all duration-700 ease-out">
                <h2 class="text-4xl md:text-6xl font-extrabold leading-tight tracking-tight mb-4">RCN Theological Seminary - Adullam</h2>
                <p class="text-lg md:text-xl font-light max-w-2xl mx-auto mb-8">
                    An institution committed to raising Christ-like leaders with deep biblical roots and spiritual fire.
                </p>
                <a href="#programs" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-semibold px-8 py-4 rounded-full shadow-lg transition transform hover:scale-105">
                    Explore Our Programs
                </a>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="bg-purple-700 text-white py-16">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-2">Contending for the Truth</h2>
            <p class="text-lg md:text-xl font-light mb-8">In a world of falsehood, it begins with a proper education.</p>
            <a href="dashboard" class="inline-block bg-white text-purple-700 font-semibold px-8 py-4 rounded-full shadow-xl hover:bg-gray-200 transition transform hover:scale-105">
                Apply Now
            </a>
        </div>
    </section>
    
    <!-- About Section -->
    <section id="about" class="bg-gray-100 py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="flex flex-col items-center text-center lg:text-left lg:items-start">
                <img src="assets/img/team/team 1.jpg" alt="Provost" class="w-64 h-64 rounded-full object-cover shadow-xl mb-6">
                <p class="text-xl italic font-serif text-gray-600 max-w-md">"Our Seminary stands on faithfulness to the Scriptures and deep devotion to Christ."</p>
                <p class="text-gray-500 mt-2">- The President</p>
            </div>
            
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-purple-800 mb-6">A Message from the President</h2>
                <div class="space-y-6 text-gray-700 leading-relaxed text-lg">
                    <p>
                        I am excited to welcome you to <strong>RCN Theological Seminary - Adullam</strong>. Established in 2015, our seminary was founded to equip Christian leaders who will bring an accurate witness of Christ to all spheres of life.
                    </p>
                    <p>
                        Adullam is a vibrant community of students, faculty, and scholars, living and learning together in the belief that moral and spiritual values in education are transformative. We foster a strong culture of collaboration, inclusion, and biblical orthodoxy.
                    </p>
                    <a href="welcome.php" class="inline-flex items-center text-purple-700 hover:text-purple-900 font-semibold transition group">
                        Read More
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Programs Section -->
     <section id="programs" class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-6">

        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-purple-700">Explore Our Programs</h2>
            <p class="mt-2 text-gray-600 text-base md:text-lg">Training Christ-like leaders with deep biblical roots.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8">

            <a href="cert" class="group bg-white rounded-xl p-6 shadow hover:shadow-lg transition border hover:border-purple-200 transform hover:-translate-y-1">
                <div class="flex justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-600 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-gray-800 group-hover:text-purple-700">Certificate in
                    Theology</h3>
            </a>

            <a href="dip" class="group bg-white rounded-xl p-6 shadow hover:shadow-lg transition border hover:border-purple-200 transform hover:-translate-y-1">
                <div class="flex justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-600 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                        <path d="M9.5 11.5l2.5 1.5 2.5-1.5" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-gray-800 group-hover:text-purple-700">Diploma in
                    Theology</h3>
            </a>

            <a href="bdiv" class="group bg-white rounded-xl p-6 shadow hover:shadow-lg transition border hover:border-purple-200 transform hover:-translate-y-1">
                <div class="flex justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-600 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 19V6.2C4 5.0799 4 4.51984 4.21799 4.09202C4.40973 3.71569 4.71569 3.40973 5.09202 3.21799C5.51984 3 6.0799 3 7.2 3H16.8C17.9201 3 18.4802 3 18.908 3.21799C19.2843 3.40973 19.5903 3.71569 19.782 4.09202C20 4.51984 20 5.0799 20 6.2V17H6C4.89543 17 4 17.8954 4 19ZM4 19C4 20.1046 4.89543 21 6 21H20M9 7H15M9 11H15M9 15H12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-gray-800 group-hover:text-purple-700">Bachelor of
                    Divinity</h3>
            </a>

            <a href="pgdt" class="group bg-white rounded-xl p-6 shadow hover:shadow-lg transition border hover:border-purple-200 transform hover:-translate-y-1">
                <div class="flex justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-600 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                        <path d="M14 12.5V18" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-gray-800 group-hover:text-purple-700">Postgraduate
                    Diploma</h3>
            </a>

            <a href="masters" class="group bg-white rounded-xl p-6 shadow hover:shadow-lg transition border hover:border-purple-200 transform hover:-translate-y-1">
                <div class="flex justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-600 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-gray-800 group-hover:text-purple-700">M.A Christian
                    Apologetics</h3>
            </a>

            <a href="master" class="group bg-white rounded-xl p-6 shadow hover:shadow-lg transition border hover:border-purple-200 transform hover:-translate-y-1">
                <div class="flex justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-600 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-gray-800 group-hover:text-purple-700">M.A Biblical
                    Studies (OT/NT)</h3>
            </a>
        </div>
    </div>
</section>
    
    <!-- Events & Chapel Section -->
    <section id="events-chapel" class="bg-gray-100 py-24 md:py-32">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
      
      <!-- Seminary Events -->
      <div>
        <h2 class="text-3xl font-bold text-purple-800 mb-6">Seminary Events</h2>
        <div class="swiper events-swiper rounded-xl overflow-hidden shadow-lg">
          <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="assets/images/Events/landscale.png" alt="Alumni" class="w-full h-[28rem] object-cover"></div>
             <div class="swiper-slide"><img src="assets/images/Events/cls1.jpg" alt="Alumni" class="w-full h-[28rem] object-cover"></div>
              <div class="swiper-slide"><img src="assets/images/Events/cls2.jpg" alt="Alumni" class="w-full h-[28rem] object-cover"></div>
               <div class="swiper-slide"><img src="assets/images/Events/cls3.jpg" alt="Alumni" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/alumni1.jpg" alt="Alumni" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/app1.jpg" alt="Event 1" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/app2.jpg" alt="Event 2" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/app3.jpg" alt="Event 3" class="w-full h-[28rem] object-cover"></div>
            <!--<div class="swiper-slide"><img src="assets/images/Events/app4.jpg" alt="Event 4" class="w-full h-[28rem] object-cover"></div>-->
            <!--<div class="swiper-slide"><img src="assets/images/Events/mission1.png" alt="Event 5" class="w-full h-[28rem] object-cover"></div>-->
            <div class="swiper-slide"><img src="assets/images/Events/mission2.png" alt="Event 6" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/mission3.png" alt="Event 7" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/sport1.jpg" alt="Event 8" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/sport2.jpg" alt="Event 9" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Events/sport3.jpg" alt="Event 10" class="w-full h-[28rem] object-cover"></div>
          </div>
          <div class="swiper-pagination events-pagination mt-4"></div>
        </div>
      </div>

      <!-- Chapel Moments -->
      <div>
        <h2 class="text-3xl font-bold text-purple-800 mb-6">Chapel Moments</h2>
        <div class="swiper chapel-swiper rounded-xl overflow-hidden shadow-lg">
          <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="assets/images/Chapel/chap3.jpg" alt="Service 1" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Chapel/chap1.jpg" alt="Service 2" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Chapel/chap4.jpg" alt="Service 3" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Chapel/chap2.jpg" alt="Service 4" class="w-full h-[28rem] object-cover"></div>
            <div class="swiper-slide"><img src="assets/images/Chapel/chap5.jpg" alt="Service 5" class="w-full h-[28rem] object-cover"></div>
          </div>
          <div class="swiper-pagination chapel-pagination mt-4"></div>
        </div>
      </div>
    </div>
  </div>
</section>

    
   <!-- Testimonials Section -->
<section id="testimonials" class="bg-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-purple-800">💬 What Our Students Say</h2>
            <p class="mt-2 text-gray-600 text-lg">Real voices, real journeys, transformed lives.</p>
        </div>
        
        <div class="swiper testimonials-swiper"> <!-- Changed class name -->
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="bg-gray-100 rounded-xl shadow-sm p-8 text-center transition-all duration-300 transform hover:-translate-y-2">
                        <img src="assets/img/testimonials/nicole.jpeg" alt="Nicole" class="w-24 h-24 mx-auto rounded-full object-cover mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Nicole</h3>
                        <p class="text-sm text-gray-500 mb-4">Texas, USA</p>
                        <div class="text-yellow-400 text-sm mb-4">★★★★★</div>
                        <p class="text-base text-gray-700 leading-relaxed">
                            "At Adullam, I learned so much about God that I knew I was being lied to. Now I walk in peace and truth."
                        </p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="bg-gray-100 rounded-xl shadow-sm p-8 text-center transition-all duration-300 transform hover:-translate-y-2">
                        <img src="assets/img/testimonials/le.jpeg" alt="Lesley Uzohuo" class="w-24 h-24 mx-auto rounded-full object-cover mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Lesley Uzohuo</h3>
                        <p class="text-sm text-gray-500 mb-4">California, USA</p>
                        <div class="text-yellow-400 text-sm mb-4">★★★★★</div>
                        <p class="text-base text-gray-700 leading-relaxed">
                            "I realized I wasn't prepared for ministry. Adullam gave me the framework and spiritual foundation I lacked."
                        </p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="bg-gray-100 rounded-xl shadow-sm p-8 text-center transition-all duration-300 transform hover:-translate-y-2">
                        <img src="assets/img/testimonials/li.jpeg" alt="Andy A. Erick" class="w-24 h-24 mx-auto rounded-full object-cover mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Andy A. Erick</h3>
                        <p class="text-sm text-gray-500 mb-4">Ivory Coast</p>
                        <div class="text-yellow-400 text-sm mb-4">★★★★★</div>
                        <p class="text-base text-gray-700 leading-relaxed">
                            "After counting the cost, I discovered nothing is more valuable than discovering God's will through Adullam."
                        </p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="bg-gray-100 rounded-xl shadow-sm p-8 text-center transition-all duration-300 transform hover:-translate-y-2">
                        <img src="assets/img/testimonials/1.jpg" alt="Mr/Mrs Mbuyane" class="w-24 h-24 mx-auto rounded-full object-cover mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Mr/Mrs Mbuyane</h3>
                        <p class="text-sm text-gray-500 mb-4">Johannesburg, South Africa</p>
                        <div class="text-yellow-400 text-sm mb-4">★★★★★</div>
                        <p class="text-base text-gray-700 leading-relaxed">
                            "Three months into Adullam, we saw fruit in our spiritual growth through structure and mentoring."
                        </p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="bg-gray-100 rounded-xl shadow-sm p-8 text-center transition-all duration-300 transform hover:-translate-y-2">
                        <img src="assets/img/testimonials/2.jpeg" alt="Francis" class="w-24 h-24 mx-auto rounded-full object-cover mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Francis</h3>
                        <p class="text-sm text-gray-500 mb-4">Nigeria</p>
                        <div class="text-yellow-400 text-sm mb-4">★★★★★</div>
                        <p class="text-base text-gray-700 leading-relaxed">
                            "Christ-centered, internationally diverse, and intellectually stretching—Adullam is second to none."
                        </p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="bg-gray-100 rounded-xl shadow-sm p-8 text-center transition-all duration-300 transform hover:-translate-y-2">
                        <img src="assets/img/testimonials/4.jpeg" alt="Collinpowell Ebai" class="w-24 h-24 mx-auto rounded-full object-cover mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Collinpowell Ebai</h3>
                        <p class="text-sm text-gray-500 mb-4">USA</p>
                        <div class="text-yellow-400 text-sm mb-4">★★★★★</div>
                        <p class="text-base text-gray-700 leading-relaxed">
                            "Adullam is a spiritual breeding ground for anyone seeking to follow God's will with clarity."
                        </p>
                    </div>
                </div>
            </div>
            <div class="swiper-button-next testimonials-next !text-purple-700"></div>
            <div class="swiper-button-prev testimonials-prev !text-purple-700"></div>
            <div class="swiper-pagination testimonials-pagination mt-6"></div>
        </div>
    </div>
</section>
</main>

<?php include('includes/footer.php'); ?>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
    
    // Add this code block inside your existing document.addEventListener('DOMContentLoaded', ...) function

    // Modal functionality
    const messageModal = document.getElementById('messageModal');
    const closeModalButton = document.getElementById('closeModal');

    // Function to show the modal
    const showModal = () => {
        if (!messageModal) return;
        messageModal.classList.remove('hidden', 'opacity-0');
        messageModal.classList.add('flex', 'opacity-100');
        // Prevent background scrolling
        document.body.style.overflow = 'hidden'; 
    };

    // Function to hide the modal
    const hideModal = () => {
        if (!messageModal) return;
        messageModal.classList.remove('flex', 'opacity-100');
        messageModal.classList.add('hidden', 'opacity-0');
        // Re-enable background scrolling
        document.body.style.overflow = 'auto'; 
    };

    // Show the modal after a short delay to ensure the page is fully rendered
    setTimeout(showModal, 1000);

    // Event listener to close the modal
    if (closeModalButton) {
        closeModalButton.addEventListener('click', hideModal);
    }

    // Event listener to close the modal if the user clicks outside the modal content
    if (messageModal) {
        messageModal.addEventListener('click', (event) => {
            // Check if the click occurred on the modal backdrop itself, not its children
            if (event.target === messageModal) {
                hideModal();
            }
        });
    }
    // Initialize Swipers after DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Testimonials Swiper
        const testimonialsSwiper = new Swiper('.testimonials-swiper', {
            loop: true,
            autoplay: { 
                delay: 5000, 
                disableOnInteraction: false 
            },
            pagination: { 
                el: '.testimonials-pagination', 
                clickable: true 
            },
            navigation: { 
                nextEl: '.testimonials-next', 
                prevEl: '.testimonials-prev' 
            },
            breakpoints: {
                640: { 
                    slidesPerView: 1, 
                    spaceBetween: 20 
                },
                768: { 
                    slidesPerView: 2, 
                    spaceBetween: 30 
                },
                1024: { 
                    slidesPerView: 3, 
                    spaceBetween: 30 
                }
            }
        });

        // Events Swiper
        const eventsSwiper = new Swiper('.events-swiper', {
            loop: true,
            autoplay: { 
                delay: 4000, 
                disableOnInteraction: false 
            },
            pagination: { 
                el: '.events-pagination', 
                clickable: true 
            }
        });

        // Chapel Swiper
        const chapelSwiper = new Swiper('.chapel-swiper', {
            loop: true,
            autoplay: { 
                delay: 3000, 
                disableOnInteraction: false 
            },
            pagination: { 
                el: '.chapel-pagination', 
                clickable: true 
            }
        });
        
        // Hero slider functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.hero-slide');
        
        function showSlide(index) {
            slides.forEach((slide) => {
                slide.classList.remove('active', 'opacity-100');
                slide.classList.add('opacity-0');
            });
            slides[index].classList.add('active', 'opacity-100');
        }
        
        // Auto-advance hero slides
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }, 6000);
    });
    
   
    // Countdown Timer Logic
    const countdownElement = document.getElementById("countdown");
    if (countdownElement) {
        const deadline = countdownElement.getAttribute('data-deadline');
        if (deadline) {
            // Set the target date based on the data attribute
            // Append end of day time if not present to ensure full day coverage
            const closingDate = new Date(deadline + " 23:59:59").getTime();

            const timer = setInterval(function () {
                const now = new Date().getTime();
                const distance = closingDate - now;

                if (distance < 0) {
                    clearInterval(timer);
                    countdownElement.innerHTML = "Application Closed";
                    countdownElement.classList.remove("pulse-soft");
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