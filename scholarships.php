<?php
// DHLTU SRC — Scholarships Page
// File: scholarships.php
// Purpose: Displays scholarship opportunities and application guide
?>
<?php
require_once 'config/database.php';
require_once 'models/Scholarship.php';
$db = Database::getInstance();
$scholarshipModel = new Scholarship($db);
$allScholarships = $scholarshipModel->getAllActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Scholarship opportunities and application guide for DHLTU students. Discover available scholarships, eligibility requirements, and how to apply.">
  <meta name="author" content="DHLTU SRC">
  <title>Scholarships — DHLTU Student Representative Council</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

<?php include 'include/header.php'; ?>

<!-- ============================================================
      SCHOLARSHIPS PAGE HERO
      ============================================================ -->
<section class="scholarships-page-hero" id="scholarships">
  <div class="scholarships-hero-bg"></div>
  <div class="scholarships-hero-inner">
    <div class="scholarships-hero-eyebrow">
      <i class="bi bi-mortarboard"></i> Educational Support
    </div>
    <h1 class="scholarships-hero-title">Scholarships<br><span>Opportunities</span></h1>
    <p class="scholarships-hero-lead">
      Explore available scholarship opportunities, eligibility requirements, and application procedures
      for DHLTU students. Stay informed about deadlines and application requirements.
    </p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ============================================================
      SCHOLARSHIPS LIST SECTION
      ============================================================ -->
<section class="scholarships-list-section">
  <div class="container">
    <!-- Heading -->
    <div class="section-header">
      <div class="scholarships-eyebrow" style="justify-content:center;">
        <i class="bi bi-card-list"></i> Available Scholarships
      </div>
      <h2 class="scholarships-section-title">Current Opportunities</h2>
    </div>

    <!-- ── Scholarship cards grid ── -->
    <div class="scholarships-grid" id="scholarshipsGrid">
      <?php foreach ($allScholarships as $i => $s): ?>
      <article class="scholarship-card reveal delay-<?php echo ($i % 3) + 1; ?>" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($s['type'] ?? 'Other'); ?>
          </span>
          <h3 class="scholarship-title"><?php echo htmlspecialchars($s['title']); ?></h3>
          <span class="scholarship-deadline">Deadline: <?php echo $s['deadline'] ? date('M j, Y', strtotime($s['deadline'])) : '—'; ?></span>
          <p class="scholarship-desc">
            <?php echo htmlspecialchars($s['description'] ?? ''); ?>
          </p>
          <?php if (!empty($s['external_link'])): ?>
            <a href="<?php echo htmlspecialchars($s['external_link']); ?>" class="scholarship-btn" target="_blank" rel="noopener">Apply Now <i class="bi bi-box-arrow-up-right"></i></a>
          <?php else: ?>
            <a href="scholarship-detail.php?id=<?php echo $s['id']; ?>" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (empty($allScholarships)): ?>
      <article class="scholarship-card reveal delay-1" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> Government
          </span>
          <h3 class="scholarship-title">Government Scholarship</h3>
          <span class="scholarship-deadline">Deadline: 15 September 2026</span>
          <p class="scholarship-desc">
            Financial support for brilliant but needy students pursuing diploma and degree programs.
            Covers tuition fees and provides a stipend for academic materials.
          </p>
          <a href="scholarship-detail.php?id=government" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>

      <article class="scholarship-card reveal delay-2" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-award"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> Academic
          </span>
          <h3 class="scholarship-title">Academic Excellence Scholarship</h3>
          <span class="scholarship-deadline">Deadline: 30 October 2026</span>
          <p class="scholarship-desc">
            Scholarship opportunities awarded to students with outstanding academic performance.
            Recognizes and rewards consistent high achievers across all departments.
          </p>
          <a href="scholarship-detail.php?id=academic" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>

      <article class="scholarship-card reveal delay-3" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-globe2"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> International
          </span>
          <h3 class="scholarship-title">International Study Grant</h3>
          <span class="scholarship-deadline">Deadline: 20 November 2026</span>
          <p class="scholarship-desc">
            Support program for students interested in international academic opportunities and exchange programs.
            Provides funding for study abroad and collaborative degree programs.
          </p>
          <a href="scholarship-detail.php?id=international" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ============================================================
     SCHOLARSHIPS LIST SECTION
     ============================================================ -->
<section class="scholarships-list-section">
  <div class="container">
    <!-- Heading -->
    <div class="section-header">
      <div class="scholarships-eyebrow" style="justify-content:center;">
        <i class="bi bi-card-list"></i> Available Scholarships
      </div>
      <h2 class="scholarships-section-title">Current Opportunities</h2>
    </div>

    <!-- ── Scholarship cards grid ── -->
    <div class="scholarships-grid" id="scholarshipsGrid">
      <?php 
      $db = Database::getInstance();
      $scholarshipModel = new Scholarship($db);
      $allScholarships = $scholarshipModel->getAllActive();
      foreach ($allScholarships as $i => $s): ?>
      <article class="scholarship-card reveal delay-<?php echo ($i % 3) + 1; ?>" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($s['type'] ?? 'Other'); ?>
          </span>
          <h3 class="scholarship-title"><?php echo htmlspecialchars($s['title']); ?></h3>
          <span class="scholarship-deadline">Deadline: <?php echo $s['deadline'] ? date('M j, Y', strtotime($s['deadline'])) : '—'; ?></span>
          <p class="scholarship-desc">
            <?php echo htmlspecialchars($s['description'] ?? ''); ?>
          </p>
          <?php if (!empty($s['external_link'])): ?>
            <a href="<?php echo htmlspecialchars($s['external_link']); ?>" class="scholarship-btn" target="_blank" rel="noopener">Apply Now <i class="bi bi-box-arrow-up-right"></i></a>
          <?php else: ?>
            <a href="scholarship-detail.php?id=<?php echo $s['id']; ?>" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (empty($allScholarships)): ?>
      <article class="scholarship-card reveal delay-1" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> Government
          </span>
          <h3 class="scholarship-title">Government Scholarship</h3>
          <span class="scholarship-deadline">Deadline: 15 September 2026</span>
          <p class="scholarship-desc">
            Financial support for brilliant but needy students pursuing diploma and degree programs.
            Covers tuition fees and provides a stipend for academic materials.
          </p>
          <a href="scholarship-detail.php?id=government" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>

      <article class="scholarship-card reveal delay-2" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-award"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> Academic
          </span>
          <h3 class="scholarship-title">Academic Excellence Scholarship</h3>
          <span class="scholarship-deadline">Deadline: 30 October 2026</span>
          <p class="scholarship-desc">
            Scholarship opportunities awarded to students with outstanding academic performance.
            Recognizes and rewards consistent high achievers across all departments.
          </p>
          <a href="scholarship-detail.php?id=academic" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>

      <article class="scholarship-card reveal delay-3" tabindex="0">
        <div class="scholarship-icon-wrap">
          <i class="bi bi-globe2"></i>
        </div>
        <div class="scholarship-card-body">
          <span class="scholarship-category">
            <i class="bi bi-tag-fill"></i> International
          </span>
          <h3 class="scholarship-title">International Study Grant</h3>
          <span class="scholarship-deadline">Deadline: 20 November 2026</span>
          <p class="scholarship-desc">
            Support program for students interested in international academic opportunities and exchange programs.
            Provides funding for study abroad and collaborative degree programs.
          </p>
          <a href="scholarship-detail.php?id=international" class="scholarship-btn">Read More <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ============================================================
     REQUIREMENTS SECTION
     ============================================================ -->
<section class="scholarships-section" id="requirements">
  <div class="container">
    <div class="scholarships-eyebrow" style="justify-content:center; margin-bottom: 40px;">
      <i class="bi bi-check-circle"></i> Eligibility
    </div>
    <h2 class="scholarships-section-title" style="text-align:center;">Scholarship Requirements</h2>
    <div class="scholarships-info-box reveal">
      <h3>General Requirements</h3>
      <p>
        Applicants are expected to meet academic, financial, and behavioral requirements before
        applying for scholarship opportunities. Supporting documents may include transcripts,
        recommendation letters, and proof of admission. All applications must be submitted
        through the SRC office before the stated deadline.
      </p>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ============================================================
     APPLICATION GUIDE SECTION
     ============================================================ -->
<section class="scholarships-section" id="application" style="background: var(--navy-mid);">
  <div class="container">
    <div class="scholarships-eyebrow" style="justify-content:center; margin-bottom: 40px;">
      <i class="bi bi-info-circle"></i> Application Process
    </div>
    <h2 class="scholarships-section-title" style="text-align:center;">How To Apply</h2>
    <div class="scholarships-steps-grid">
      <div class="scholarship-step-card reveal delay-1">
        <div class="step-number">01</div>
        <h3 class="step-title">Read &amp; Confirm</h3>
        <p class="step-desc">
          Read scholarship details carefully and confirm your eligibility.
          Ensure you meet all academic and documentation requirements.
        </p>
      </div>
      <div class="scholarship-step-card reveal delay-2">
        <div class="step-number">02</div>
        <h3 class="step-title">Prepare Documents</h3>
        <p class="step-desc">
          Prepare all required academic and personal documents before submission.
          Gather transcripts, recommendation letters, and any supporting materials.
        </p>
      </div>
      <div class="scholarship-step-card reveal delay-3">
        <div class="step-number">03</div>
        <h3 class="step-title">Submit Application</h3>
        <p class="step-desc">
          Submit applications before the stated scholarship deadline.
          All forms must be complete and signed before final submission.
        </p>
      </div>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>

<style>
  /* ── CSS Custom Properties (align with :root in main.css) ── */
  :root {
    --gold:         #C9A84C;
    --gold-light:   #E8C97A;
    --gold-dark:    #8B6914;
    --navy:         #0A1628;
    --navy-mid:     #0F2040;
    --navy-light:   #1A3060;
    --cream:        #F5F0E8;
    --text-muted:   #8A9BB8;
    --transition-fast: 0.3s ease;
  }

  /* ══════════════════════════════════════
     SCHOLARSHIPS PAGE HERO
  ══════════════════════════════════════ */
  .scholarships-page-hero {
    position: relative;
    min-height: 480px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);
    padding: 160px 40px 100px;
    overflow: hidden;
    text-align: center;
  }
  .scholarships-page-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 30%, rgba(201,168,76,0.10) 0%, transparent 70%);
  }
  .scholarships-page-hero::after {
    content: '✦';
    position: absolute;
    bottom: -10px; left: 50%;
    transform: translateX(-50%);
    font-size: 12px; color: var(--gold);
    background: var(--navy); padding: 0 14px;
  }
  .scholarships-hero-inner { position: relative; z-index: 1; max-width: 720px; }
  .scholarships-hero-eyebrow {
    font-family: 'Space Mono', monospace;
    font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase;
    color: var(--gold);
    display: inline-flex; align-items: center; gap: 10px;
    margin-bottom: 28px;
  }
  .scholarships-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(48px, 6vw, 84px);
    font-weight: 300; line-height: 0.92; color: var(--cream);
    margin-bottom: 28px;
  }
  .scholarships-hero-title span {
    font-weight: 700; font-style: italic; color: var(--gold-light);
    display: block;
  }
  .scholarships-hero-lead {
    font-size: 15px; font-weight: 300; line-height: 1.9;
    color: var(--text-muted);
    max-width: 560px;
    margin: 0 auto 48px;
  }

  /* ── section eyebrow used by the list header ── */
  .scholarships-eyebrow {
    font-family: 'Space Mono', monospace;
    font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase;
    color: var(--gold);
    display: inline-flex; align-items: center; gap: 12px;
    margin-bottom: 16px;
  }
  .scholarships-eyebrow::before { content: '02'; color: rgba(201,168,76,0.3); font-size: 9px; }

  /* ══════════════════════════════════════
     SCHOLARSHIPS LIST SECTION
  ══════════════════════════════════════ */
  .scholarships-list-section { padding: 80px 40px 120px; background: var(--navy); }

  .scholarships-section { padding: 80px 40px; background: var(--navy); }

  #requirements, #application { scroll-margin-top: 55px; }

  /* ── Section title ── */
  .scholarships-section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 4vw, 52px);
    font-weight: 300; color: var(--cream);
    margin: 0;
    text-align: center;
  }

  /* ── Scholarship grid ── */
  .scholarships-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
    margin-top: 60px;
  }

  /* ── Individual card ── */
  .scholarship-card {
    background: var(--navy-mid);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 2px;
    overflow: hidden;
    display: flex; flex-direction: column;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    cursor: default;
  }
  .scholarship-card:hover {
    border-color: rgba(201,168,76,0.30);
    transform: translateY(-6px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  }

  /* ── Icon area ── */
  .scholarship-icon-wrap {
    width: 100%;
    height: 140px;
    background: linear-gradient(135deg, rgba(201,168,76,0.12), rgba(201,168,76,0.06));
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(201,168,76,0.1);
  }
  .scholarship-icon-wrap i {
    font-size: 48px;
    color: var(--gold);
  }

  /* ── Card body ── */
  .scholarship-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }

  .scholarship-category {
    font-family: 'Space Mono', monospace;
    font-size: 9px; letter-spacing: 0.15em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 12px;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .scholarship-category i { font-size: 10px; }

  .scholarship-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 21px; font-weight: 600; color: var(--cream);
    line-height: 1.2; margin-bottom: 12px;
  }

  .scholarship-deadline {
    font-family: 'Space Mono', monospace;
    font-size: 10px; letter-spacing: 0.15em; text-transform: uppercase;
    color: var(--text-muted);
    display: block;
    margin-bottom: 12px;
  }

  .scholarship-desc {
    font-size: 13px; font-weight: 300; color: var(--text-muted);
    line-height: 1.75; margin-bottom: 24px; flex: 1;
  }

  /* ── Action button ── */
  .scholarship-btn {
    font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--gold);
    background: transparent;
    border: 1px solid rgba(201,168,76,0.30);
    padding: 10px 22px;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all var(--transition-fast);
    font-family: 'Outfit', sans-serif;
    border-radius: 2px;
    text-decoration: none;
  }
  .scholarship-btn:hover {
    color: var(--navy);
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-color: transparent;
  }

  /* ── Info box ── */
  .scholarships-info-box {
    background: rgba(201,168,76,0.04);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 4px;
    padding: 36px;
    max-width: 800px;
    margin: 0 auto;
  }
  .scholarships-info-box h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px; font-weight: 600; color: var(--cream);
    margin-bottom: 16px;
  }
  .scholarships-info-box p {
    font-size: 14px; font-weight: 300; color: var(--text-muted);
    line-height: 1.85; margin: 0;
  }

  /* ── Steps grid ── */
  .scholarships-steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
    margin-top: 40px;
  }

  .scholarship-step-card {
    background: var(--navy-mid);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 2px;
    padding: 32px;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
  }
  .scholarship-step-card:hover {
    border-color: rgba(201,168,76,0.30);
    transform: translateY(-6px);
  }

  .step-number {
    font-family: 'Space Mono', monospace;
    font-size: 36px; font-weight: 700;
    color: var(--gold);
    margin-bottom: 16px;
    opacity: 0.5;
  }

  .step-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 21px; font-weight: 600; color: var(--cream);
    margin-bottom: 12px;
  }

  .step-desc {
    font-size: 13px; font-weight: 300; color: var(--text-muted);
    line-height: 1.75; margin: 0;
  }

  /* ══════════════════════════════════════
     RESPONSIVE
  ══════════════════════════════════════ */
  @media (max-width: 1100px) {
    .scholarships-grid,
    .scholarships-steps-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 768px) {
    .scholarships-page-hero { min-height: 360px; padding: 130px 20px 80px; }
    .scholarships-list-section,
    .scholarships-section { padding: 60px 20px 100px; }
    .scholarships-grid,
    .scholarships-steps-grid { grid-template-columns: 1fr; }
    .scholarships-hero-title { font-size: clamp(40px, 10vw, 64px); }
  }
</style>

<script>
  // ── Mobile Menu Toggle ────────────────────────────────────────
  (function() {
    var mobileToggle = document.querySelector('.mobile-toggle');
    var navList      = document.querySelector('.nav-list');

    if (!mobileToggle || !navList) return;

    function checkBreakpoint() {
      if (window.innerWidth <= 900) {
        mobileToggle.style.display = 'flex';
      } else {
        mobileToggle.style.display = 'none';
        navList.classList.remove('active');
      }
    }
    checkBreakpoint();
    window.addEventListener('resize', checkBreakpoint);

    mobileToggle.addEventListener('click', function () {
      mobileToggle.classList.toggle('active');
      navList.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
      if (!mobileToggle.contains(e.target) && !navList.contains(e.target)) {
        mobileToggle.classList.remove('active');
        navList.classList.remove('active');
      }
    });

    document.querySelectorAll('.nav-link').forEach(function (link) {
      link.addEventListener('click', function (e) {
        var navItem  = link.parentElement;
        var dropdown = navItem.querySelector('.dropdown');

        if (dropdown && window.innerWidth <= 768) {
          e.preventDefault();

          document.querySelectorAll('.dropdown.open').forEach(function (d) {
            if (d !== dropdown) {
              d.classList.remove('open');
              d.closest('.nav-item')?.classList.remove('open');
            }
          });

          dropdown.classList.toggle('open');
          navItem.classList.toggle('open');
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
  })();

  // ── Scroll Reveal ─────────────────────────────────────────────
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

</body>
</html>