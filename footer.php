<!-- Add this to your CSS file or style tag -->
<style>
  .farmbridge-footer {
    background: #000 !important;
    color: #fff;
    padding: 40px 0;
    font-family: 'Inter', sans-serif;
    border-top: 4px solid #2e7d32;
    width: 100%;
  }
  .farmbridge-footer * {
    background: transparent !important;
    color: inherit !important;
  }
  .farmbridge-footer a {
    color: #aaa !important;
    text-decoration: none;
    transition: color 0.3s;
  }
  .farmbridge-footer a:hover {
    color: #fff !important;
  }
  .footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
  }
  .footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
  }
  .footer-col h4 {
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 600;
  }
  .footer-col ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .footer-col li {
    margin-bottom: 10px;
  }
  .social-links {
    margin-top: 20px;
  }
  .social-links a {
    display: inline-block;
    margin-right: 15px;
    font-size: 18px;
  }
  .copyright {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #333;
    color: #aaa;
  }
  #back-to-top {
    position: fixed;
    bottom: 60px;
    right: 120px;
    background: #2e7d32;
    color: #fff;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 20px;
    z-index: 999;
    opacity: 0;
    transition: all 0.3s;
  }
</style>

<!-- Footer HTML -->
<footer class="farmbridge-footer">
  <div class="footer-container">
    <div class="footer-grid">
      <!-- Column 1 - Contact Info -->
      <div class="footer-col">
        <h3>FarmBridge AI Rwanda</h3>
        <p><i class="bi bi-geo-alt"></i> Kigali, Rwanda</p>
        <p><i class="bi bi-telephone"></i> +250 781 065 112</p>
        <p><i class="bi bi-envelope"></i> info@farmbridge.rw</p>
        
        <div class="social-links">
          <a href="#"><i class="bi bi-facebook"></i></a>
          <a href="#"><i class="bi bi-twitter-x"></i></a>
          <a href="https://www.instagram.com/bertin4real/" target="new"><i class="bi bi-instagram"></i></a>
          <a href="https://www.linkedin.com/in/bertin-hakizayezu-217a79355" target="new"><i class="bi bi-linkedin"></i></a>
          <a href="https://www.youtube.com/@bertinoficial8541" target="new"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      
      <!-- Column 2 - Links -->
      <div class="footer-col">
        <h4>Useful Links</h4>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php">About us</a></li>
          <li><a href="#">Services</a></li>
          <li><a href="#">Terms of service</a></li>
          <li><a href="#">Privacy policy</a></li>
        </ul>
      </div>
      
      <!-- Column 3 - Services -->
      <div class="footer-col">
        <h4>Our Services</h4>
        <ul>
          <li><a href="#">Market Access</a></li>
          <li><a href="#">Price Analysis</a></li>
          <li><a href="#">Farm Management</a></li>
          <li><a href="#">AI Assistance</a></li>
          <li><a href="#">Payment Solutions</a></li>
        </ul>
      </div>
      
      <!-- Column 4 - Resources -->
      <div class="footer-col">
        <h4>Resources</h4>
        <ul>
          <li><a href="#">Blog</a></li>
          <li><a href="#">FAQs</a></li>
          <li><a href="#">Guides</a></li>
          <li><a href="#">Videos</a></li>
          <li><a href="#">Webinars</a></li>
        </ul>
      </div>
    </div>
    
    <!-- Copyright -->
    <div class="copyright">
      <p>&copy; <?php echo date('Y'); ?> FarmBridge AI Rwanda. All Rights Reserved</p>
      <p>Designed by FarmBridge Team</p>
    </div>
  </div>
</footer>

<!-- Back to Top Button -->
<a href="#" id="back-to-top"><i class="bi bi-arrow-up"></i></a>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<script>
  // Back to Top Button
  window.addEventListener('scroll', function() {
    var backToTop = document.getElementById('back-to-top');
    if (window.pageYOffset > 300) {
      backToTop.style.opacity = '1';
    } else {
      backToTop.style.opacity = '0';
    }
  });
</script>