<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'models/Services.php';

$db = Database::getInstance();
$servicesModel = new Services($db);

$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$service = $serviceId > 0 ? $servicesModel->getById($serviceId) : null;

if (!$service) {
    http_response_code(404);
    $service = null;
}

$allServices = $servicesModel->getAllActive(['order_by' => 'display_order ASC, created_at ASC']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $service ? htmlspecialchars($service['title']) . ' - DHLTU SRC' : 'Service Not Found - DHLTU SRC'; ?></title>
  <meta name="description" content="<?php echo $service ? htmlspecialchars($service['description']) : 'SRC service details.'; ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include 'include/header.php'; ?>

<?php if ($service): ?>
<!-- ══════════════════════════════════
     HERO
══════════════════════════════════ -->
<section class="section" id="service-hero" style="background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%); padding-top: 140px; padding-bottom: 80px;">
  <div class="container" style="max-width:720px; text-align:center;">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:88px;height:88px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.25);border-radius:20px;margin-bottom:28px;font-size:40px;color:var(--gold);">
      <i class="bi <?php echo htmlspecialchars($service['icon'] ?: 'bi-star'); ?>"></i>
    </div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:300;line-height:1.1;color:var(--cream);margin-bottom:24px;">
      <?php echo htmlspecialchars($service['title']); ?>
    </h1>
    <p style="font-size:15px;font-weight:300;line-height:1.9;color:rgba(245,240,232,0.65);max-width:560px;margin:0 auto;">
      <?php echo nl2br(htmlspecialchars($service['description'] ?: 'Service managed by the SRC to support student life.')); ?>
    </p>
    <div style="margin-top:40px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="services.php" class="service-link">All Services</a>
      <a href="index.php#contact" class="btn-outline">Contact Support</a>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     OVERVIEW
══════════════════════════════════ -->
<section class="section" id="service-overview" style="background:var(--navy);">
  <div class="container">
    <div style="max-width:800px;margin:0 auto;">
      <div style="text-align:center;margin-bottom:56px;">
        <div class="services-eyebrow" style="justify-content:center;">Overview</div>
        <h2 class="section-title reveal">About This <em>Service</em></h2>
      </div>
      <div class="info-card reveal delay-1" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:40px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);max-width:760px;margin:0 auto;">
        <div style="font-size:15px;font-weight:300;color:rgba(245,240,232,0.65);line-height:1.9;">
          <?php echo nl2br(htmlspecialchars($service['description'] ?: 'The SRC is committed to delivering this service with transparency and efficiency for the benefit of every student at DHLTU.')); ?>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     HOW TO ACCESS
══════════════════════════════════ -->
<section class="section" id="how-to-access" style="background:var(--navy-mid);">
  <div class="container" style="max-width:800px;">
    <div style="text-align:center;margin-bottom:56px;">
      <div class="services-eyebrow" style="justify-content:center;">Getting Started</div>
      <h2 class="section-title reveal">How to <em>Access</em></h2>
    </div>
    <div class="services-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));max-width:900px;margin:0 auto;">
      <div class="info-card reveal delay-1" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);text-align:center;">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:24px;color:var(--gold);">
          <i class="bi bi-building"></i>
        </div>
        <h4 style="font-size:16px;font-weight:600;color:var(--cream);margin-bottom:8px;">Visit SRC Office</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.7;margin:0;">Come to the SRC Secretariat on campus during working hours.</p>
      </div>
      <div class="info-card reveal delay-2" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);text-align:center;">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:24px;color:var(--gold);">
          <i class="bi bi-laptop"></i>
        </div>
        <h4 style="font-size:16px;font-weight:600;color:var(--cream);margin-bottom:8px;">Online Request</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.7;margin:0;">Submit your request through the student portal from anywhere.</p>
      </div>
      <div class="info-card reveal delay-3" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);text-align:center;">
        <div class="info-card-icon" style="width:56px;height:56px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:24px;color:var(--gold);">
          <i class="bi bi-telephone"></i>
        </div>
        <h4 style="font-size:16px;font-weight:600;color:var(--cream);margin-bottom:8px;">Direct Contact</h4>
        <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.7;margin:0;">Call or email the relevant SRC officer directly for help.</p>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     WHAT YOU GET
══════════════════════════════════ -->
<section class="section" id="what-you-get" style="background:var(--navy);">
  <div class="container">
    <div style="max-width:800px;margin:0 auto;">
      <div style="text-align:center;margin-bottom:56px;">
        <div class="services-eyebrow" style="justify-content:center;">Student Benefits</div>
        <h2 class="section-title reveal">What You <em>Get</em></h2>
      </div>
      <div style="display:grid;gap:24px;max-width:760px;margin:0 auto;">
        <div class="info-card reveal delay-1" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);display:flex;gap:20px;align-items:flex-start;">
          <div class="info-card-icon" style="width:44px;height:44px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:var(--gold);">
            <i class="bi bi-check-circle"></i>
          </div>
          <div>
            <h4 style="font-size:15px;font-weight:600;color:var(--cream);margin:0 0 8px;">Professional & Confidential</h4>
            <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.7;margin:0;">All requests are handled with strict confidentiality and professionalism.</p>
          </div>
        </div>
        <div class="info-card reveal delay-2" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);display:flex;gap:20px;align-items:flex-start;">
          <div class="info-card-icon" style="width:44px;height:44px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:var(--gold);">
            <i class="bi bi-check-circle"></i>
          </div>
          <div>
            <h4 style="font-size:15px;font-weight:600;color:var(--cream);margin:0 0 8px;">Timely Response</h4>
            <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.7;margin:0;">You will receive acknowledgement and follow-up as soon as possible.</p>
          </div>
        </div>
        <div class="info-card reveal delay-3" style="background:var(--navy-mid);border:1px solid rgba(201,168,76,0.1);padding:32px;border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);display:flex;gap:20px;align-items:flex-start;">
          <div class="info-card-icon" style="width:44px;height:44px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:var(--gold);">
            <i class="bi bi-check-circle"></i>
          </div>
          <div>
            <h4 style="font-size:15px;font-weight:600;color:var(--cream);margin:0 0 8px;">Full Follow-through</h4>
            <p style="font-size:13px;font-weight:300;color:var(--text-muted);line-height:1.7;margin:0;">We track progress until your concern is fully resolved.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     CTA
══════════════════════════════════ -->
<section class="section" id="service-cta" style="background:var(--navy-mid);">
  <div style="max-width:700px;margin:0 auto;text-align:center;">
    <h2 class="section-title reveal" style="font-size:clamp(32px,4vw,52px);font-weight:300;line-height:1.1;color:var(--cream);margin-bottom:20px;">
      Ready to Get <em style="color:var(--gold-light);">Started</em>?
    </h2>
    <p style="font-size:15px;font-weight:300;line-height:1.9;color:rgba(245,240,232,0.65);max-width:480px;margin:0 auto 48px;">
      Reach out today and let us help you make the most of this service.
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="index.php#contact" class="btn-primary">Contact Support</a>
      <a href="services.php" class="btn-outline">Back to Services</a>
    </div>
  </div>
</section>

<!-- OTHER SERVICES -->
<section class="section" id="other-services" style="background:var(--navy);">
  <div class="container">
    <div style="text-align:center;margin-bottom:56px;">
      <div class="services-eyebrow" style="justify-content:center;">Explore More</div>
      <h2 class="section-title reveal">Other <em>Services</em></h2>
    </div>
    <div class="services-grid">
      <?php foreach (array_slice($allServices, 0, 6) as $index => $svc): ?>
        <div class="service-card reveal delay-<?php echo ($index % 3) + 1; ?>">
          <div class="service-num"><?php echo sprintf('%02d', $index + 1); ?></div>
          <div class="service-icon-wrap"><i class="bi <?php echo htmlspecialchars($svc['icon'] ?: 'bi-star'); ?>"></i></div>
          <div class="service-title"><?php echo htmlspecialchars($svc['title']); ?></div>
          <div class="service-desc"><?php echo htmlspecialchars($svc['description'] ?: ''); ?></div>
          <a href="service-detail.php?id=<?php echo $svc['id']; ?>" class="service-link">Learn More</a>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:48px;">
      <a href="services.php" class="btn-primary" style="display:inline-block;">View All Services</a>
    </div>
  </div>
</section>

<?php else: ?>
<!-- SERVICE NOT FOUND -->
<section class="section" id="not-found" style="background:var(--navy);text-align:center;">
  <div class="container" style="max-width:600px;padding-top:140px;padding-bottom:140px;">
    <div style="font-size:64px;color:var(--gold);margin-bottom:24px;"><i class="bi bi-emoji-frown"></i></div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,4vw,48px);font-weight:300;line-height:1.2;color:var(--cream);margin-bottom:20px;">
      Service Not Found
    </h1>
    <p style="font-size:15px;font-weight:300;line-height:1.8;color:var(--text-muted);margin-bottom:40px;">
      The service you're looking for doesn't exist or may have been removed.
    </p>
    <a href="services.php" class="btn-primary">Back to Services</a>
  </div>
</section>
<?php endif; ?>

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

  document.addEventListener('click', function (e) {
    if (mobileToggle && navList && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
      mobileToggle.classList.remove('active');
      navList.classList.remove('active');
    }
  });

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
