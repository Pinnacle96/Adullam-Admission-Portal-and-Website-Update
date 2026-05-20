<?php
include('includes/dbconnection.php');
session_start();
error_reporting(0);

if (isset($_POST['sub'])) {
  $email = $_POST['email'];
  $query = mysqli_query($con, "INSERT INTO tblsubscriber(Email) VALUES('$email')");
  if ($query) {
    echo "<script>alert('Your subscription was successful!');</script>";
    echo "<script>window.location.href ='index.php'</script>";
  } else {
    echo '<script>alert("Something went wrong. Please try again.")</script>';
  }
}
?>

<!-- Footer Section -->
<footer class="bg-gray-900 text-gray-300 pt-12 pb-6">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

      <!-- Contact Info -->
      <div>
        <h2 class="text-xl font-bold text-white mb-4">ADULLAM</h2>
        <?php
        $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='contactus'");
        while ($row = mysqli_fetch_array($ret)) {
        ?>
          <p class="flex items-start text-sm mb-2">
            📍 <?php echo htmlspecialchars($row['PageDescription']); ?>
          </p>
          <p class="flex items-center text-sm mb-2">
            📞 <strong>Phone:</strong> +234<?php echo htmlspecialchars($row['MobileNumber']); ?>
          </p>
          <p class="flex items-center text-sm">
            📧 <strong>Email:</strong>
            <a href="mailto:<?php echo htmlspecialchars($row['Email']); ?>" class="text-purple-500 hover:text-purple-300 ml-1">
              <?php echo htmlspecialchars($row['Email']); ?>
            </a>
          </p>
        <?php } ?>
      </div>

      <!-- Useful Links -->
      <div>
        <h4 class="text-xl font-semibold text-white mb-4">Useful Links</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="index.php" class="hover:text-purple-400">Home</a></li>
          <li><a href="about.php" class="hover:text-purple-400">About</a></li>
          <li><a href="contact.php" class="hover:text-purple-400">Contact</a></li>
          <li><a href="index.php#services" class="hover:text-purple-400">Program</a></li>
          <li><a href="dashboard/administrator.php" class="hover:text-purple-400">Admin Login</a></li>
        </ul>
      </div>

      <!-- Programs -->
      <div>
        <h4 class="text-xl font-semibold text-white mb-4">Our Programs</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="cert.php" class="hover:text-purple-400">Certificate in Theology</a></li>
          <li><a href="dip.php" class="hover:text-purple-400">Diploma in Theology</a></li>
          <li><a href="biv.php" class="hover:text-purple-400">Bachelor of Divinity</a></li>
          <li><a href="pgdt.php" class="hover:text-purple-400">Postgraduate Degree</a></li>
          <li><a href="masters.php" class="hover:text-purple-400">M.A Christian Apologetics</a></li>
          <li><a href="masters.php" class="hover:text-purple-400">M.A Biblical Studies (OT/NT)</a></li>
          <li><a href="short.php" class="hover:text-purple-400">Short Course</a></li>
        </ul>
      </div>

      <!-- Newsletter -->
      <div>
        <h4 class="text-xl font-semibold text-white mb-4">Our Newsletter</h4>
        <p class="text-sm mb-4">Subscribe to receive updates on our programs and events.</p>
        <form action="" method="post" class="flex flex-col gap-4">
          <input type="email" name="email" placeholder="Your email address" required
            class="w-full px-4 py-2 rounded-md bg-gray-800 text-white focus:outline-none">
          <button type="submit" name="sub"
            class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-md">Subscribe</button>
        </form>
      </div>
    </div>

    <div class="mt-10 border-t border-gray-700 pt-6 text-center text-sm">
      <p>&copy; <?php echo date('Y'); ?> - 2027 <strong class="text-white px-1">ADULLAM</strong> All rights reserved.</p>
      <p class="mt-2">Developed by <a href="https://wa.me/+2348150829549/"
          class="text-purple-500 hover:text-purple-300">Pinnacle Tech Hub</a></p>
    </div>
  </div>
</footer>

<a href="#" id="scroll-top"
  class="fixed bottom-5 right-5 bg-purple-700 text-white p-3 rounded-full shadow-lg hover:bg-purple-900 transition hidden">
  ↑
</a>

<!-- Optional: Temporarily disable main.js until it’s verified -->
 <script src="js/main.js"></script> 

<script>
  const scrollTop = document.getElementById('scroll-top');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) scrollTop.classList.remove('hidden');
    else scrollTop.classList.add('hidden');
  });
  scrollTop.addEventListener('click', e => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
</script>
