<?php
// Footer component for public files - uses CSS from main.css
?>
<footer id="footer">
  <div class="footer-container">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="footer-logo-row">
          <img src="assets/images/logo.png" alt="SRC Logo" class="footer-logo-img">
          <div>
            <div class="footer-school-name">Dr. Hilla Limann Technical University</div>
            <div class="footer-school-sub">Student Representative Council</div>
          </div>
        </div>
        <p class="footer-tagline">Championing student rights, fostering excellence, and building a vibrant campus community since 1992. Knowledge. Service. Excellence.</p>
        <div class="footer-socials">
          <a href="https://www.facebook.com/share/g/1DubEJRJmS/?mibextid=wwXIfr" class="footer-social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://twitter.com" class="footer-social-link" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
          <a href="https://www.tiktok.com/@dhltusrcmedia?_r=1&_t=ZS-974G0FrbN5w" class="footer-social-link" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
          <a href="https://instagram.com" class="footer-social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="https://linkedin.com" class="footer-social-link" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>

      <div class="footer-links-section">
        <div class="footer-col-title">Quick Links</div>
        <ul class="footer-links">
          <li><a href="index.php#about">About SRC</a></li>
          <li><a href="index.php#services">Our Services</a></li>
          <li><a href="index.php#council">Executive Council</a></li>
          <li><a href="index.php#elections">Elections</a></li>
          
        </ul>
      </div>

      <div class="footer-links-section">
        <div class="footer-col-title">Student Resources</div>
        <ul class="footer-links">
          <li><a href="constitution.php">SRC Constitution</a></li>
          <li><a href="events-calendar.php">Events Calendar</a></li>
          <li><a href="scholarships.php">Scholarship Info</a></li>
          <li><a href="campus-map.php">Campus Map</a></li>
        </ul>
      </div>

      <div class="footer-links-section">
        <div class="footer-col-title">Stay Connected</div>
        <p class="footer-newsletter-text">Subscribe to receive announcements, news, and events</p>
        <form id="subscribe-form" class="footer-newsletter-form">
          <input type="email" id="subscribe-email" class="footer-input" placeholder="Your email address" required>
          <input type="text" id="subscribe-name" class="footer-input" placeholder="Your name (optional)">
          <button type="submit" class="footer-subscribe-btn">Subscribe</button>
        </form>
        <div id="subscribe-result" class="footer-result" style="display: none;"></div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="footer-copy">© 2025 SRC, Dr. Hilla Limann Technical University. All rights reserved.</div>
      <div class="footer-badge">DHLTU · SRC · 2024/2025</div>
    </div>
  </div>
</footer>

<script src="assets/js/email-subscription.js"></script>