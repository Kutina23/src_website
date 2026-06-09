<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'models/Services.php';

$db = Database::getInstance();
$servicesModel = new Services($db);
$allServices = $servicesModel->getAllActive(['order_by' => 'display_order ASC, created_at ASC']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services - DHLTU SRC | Services & Support</title>
  <meta name="description" content="Explore all SRC services including Academic Affairs, Student Welfare, Financial Management, Elections, Clubs, and Communications.">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include 'include/header.php'; ?>

<!-- ══════════════════════════════════
     SERVICES HERO
══════════════════════════════════ -->
<section class="section" id="services-hero" style="background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);">
  <div class="container" style="max-width:720px; text-align:center;">
    <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:400;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);margin-bottom:48px;transition:all 0.3s ease;" onmouseover="this.style.gap='14px'" onmouseout="this.style.gap='8px'">
      
    </a>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:300;line-height:1.1;color:var(--cream);margin-bottom:24px;">
      Our Core <em style="color:var(--gold-light);">Services</em>
    </h1>
    <p style="font-size:15px;font-weight:300;line-height:1.9;color:rgba(245,240,232,0.65);max-width:560px;margin:0 auto;">
      Comprehensive support and advocacy for every aspect of student life at DHLTU. Browse each service below to learn more about how we can help you.
    </p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     SERVICES GRID — dynamic from DB
══════════════════════════════════ -->
<section class="section" id="all-services" style="background:var(--navy);">
  <div class="services-header" style="text-align:center;margin-bottom:80px;">
    <div class="services-eyebrow">What We Offer</div>
    <h2 class="section-title reveal">Core <em>Services</em><br>&amp; Functions</h2>
  </div>
  <div class="container">
    <div class="services-grid">
      <?php if (empty($allServices)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--text-muted);">
          <i class="bi bi-collection-play" style="font-size:40px;color:rgba(201,168,76,0.2);display:block;margin-bottom:16px;"></i>
          <p style="font-size:15px;">Services are being updated. Please check back soon.</p>
        </div>
      <?php else: ?>
        <?php foreach ($allServices as $index => $svc): ?>
        <div class="service-card reveal delay-<?php echo ($index % 3) + 1; ?>">
          <div class="service-num"><?php echo sprintf('%02d', $index + 1); ?></div>
          <div class="service-icon-wrap"><i class="bi <?php echo htmlspecialchars($svc['icon'] ?: 'bi-star'); ?>"></i></div>
          <div class="service-title"><?php echo htmlspecialchars($svc['title']); ?></div>
          <div class="service-desc"><?php echo htmlspecialchars($svc['description'] ?: ''); ?></div>
          <a href="service-detail.php?id=<?php echo $svc['id']; ?>" class="service-link">Explore Service</a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     WHY USE OUR SERVICES
══════════════════════════════════ -->
<section class="section" id="service-benefits" style="background:var(--navy-mid);">
  <div style="max-width:800px;margin:0 auto;text-align:center;">
    <div class="services-eyebrow" style="justify-content:center;">Our Commitment</div>
    <h2 class="section-title reveal">Why Use Our <em>Services</em>?</h2>
    <p style="font-size:15px;font-weight:300;color:rgba(245,240,232,0.55);max-width:560px;margin:0 auto 64px;">
      The SRC is built to serve you — here's what sets every service apart.
    </p>
  </div>
  <div class="container">
    <div class="info-section" style="max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;">

      <div class="info-card reveal delay-1" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;font-size:24px;color:var(--gold);">
          <i class="bi bi-shield-check"></i>
        </div>
        <h4 style="font-size:17px;font-weight:600;color:var(--cream);margin-bottom:10px;">100% Student-Focused</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.8;margin-bottom:0;">Every decision we make prioritizes your interests, wellbeing, and academic success.</p>
      </div>

      <div class="info-card reveal delay-2" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;font-size:24px;color:var(--gold);">
          <i class="bi bi-chat-dots"></i>
        </div>
        <h4 style="font-size:17px;font-weight:600;color:var(--cream);margin-bottom:10px;">Open Communication</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.8;margin-bottom:0;">Direct access to SRC leadership through multiple channels and open office hours.</p>
      </div>

      <div class="info-card reveal delay-3" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;font-size:24px;color:var(--gold);">
          <i class="bi bi-lightning-fill"></i>
        </div>
        <h4 style="font-size:17px;font-weight:600;color:var(--cream);margin-bottom:10px;">Quick Resolution</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.8;margin-bottom:0;">Efficient processes designed to address your concerns and implement solutions rapidly.</p>
      </div>

      <div class="info-card reveal delay-4" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;font-size:24px;color:var(--gold);">
          <i class="bi bi-handshake"></i>
        </div>
        <h4 style="font-size:17px;font-weight:600;color:var(--cream);margin-bottom:10px;">Transparent Operations</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.8;margin-bottom:0;">Full transparency in budgets, decisions, and operations with regular public reports.</p>
      </div>

    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     CTA — HAVE A QUESTION?
══════════════════════════════════ -->
<section class="section" id="service-cta" style="background:var(--navy);">
  <div style="max-width:700px;margin:0 auto;text-align:center;">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,4vw,52px);font-weight:300;line-height:1.1;color:var(--cream);margin-bottom:20px;">
      Have a Question?<br><em style="color:var(--gold-light);">Contact Us</em>
    </h2>
    <p style="font-size:15px;font-weight:300;line-height:1.9;color:rgba(245,240,232,0.65);max-width:480px;margin:0 auto 48px;">
      Can't find what you're looking for? Reach out to our support team and we'll get back to you as quickly as possible.
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="mailto:src@hltu.edu.gh" class="btn-primary">Email Us</a>
      <a href="index.php#contact" class="btn-outline">Contact Form</a>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>

<!-- Scroll Reveal -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.reveal').forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(40px)';
      el.style.transition = 'opacity 0.9s cubic-bezier(0.16,1,0.3,1), transform 0.9s cubic-bezier(0.16,1,0.3,1)';
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var delay = 0;
          if (el.classList.contains('delay-1')) delay = 150;
          else if (el.classList.contains('delay-2')) delay = 300;
          else if (el.classList.contains('delay-3')) delay = 450;
          else if (el.classList.contains('delay-4')) delay = 600;
          else if (el.classList.contains('delay-5')) delay = 750;
          else if (el.classList.contains('delay-6')) delay = 900;
          setTimeout(function () {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
          }, delay);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });
  });
 </script>
 <script>
  // Mobile menu toggle (outside DOMContentLoaded for immediate execution)
  var mobileToggle = document.querySelector('.mobile-toggle');
  var navList = document.querySelector('.nav-list');
  
  if (mobileToggle && window.innerWidth <= 900) {
    mobileToggle.style.display = 'flex';
  }
  
  if (mobileToggle && navList) {
    mobileToggle.addEventListener('click', function () {
      mobileToggle.classList.toggle('active');
      navList.classList.toggle('active');
    });
  }
  
  // Close mobile menu when clicking outside
  document.addEventListener('click', function (e) {
    if (mobileToggle && navList && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
      mobileToggle.classList.remove('active');
      navList.classList.remove('active');
    }
  });
  
  // Nav link dropdown toggle on mobile
  document.querySelectorAll('.nav-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var navItem = link.parentElement;
      var dropdown = navItem ? navItem.querySelector('.dropdown') : null;
      
      if (dropdown && window.innerWidth <= 768) {
        e.preventDefault();
        document.querySelectorAll('.dropdown.open').forEach(function (d) {
          if (d !== dropdown) {
            d.classList.remove('open');
            d.closest('.nav-item')?.classList.remove('open');
          }
        });
        dropdown.classList.toggle('open');
        navItem?.classList.toggle('open');
      } else {
        if (mobileToggle && navList) {
          mobileToggle.classList.remove('active');
          navList.classList.remove('active');
          document.querySelectorAll('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
          document.querySelectorAll('.nav-item.open').forEach(function (n) { n.classList.remove('open'); });
        }
      }
    });
  });
 </script>

</body>
</html>