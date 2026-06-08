<?php
// DHLTU SRC — Emergency General Assembly Page
// File: emergency-ga.php
// Purpose: Displays all Emergency General Assembly sessions (dynamic — ga_sessions.session_type='EMERGENCY')
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaSessions.php';

$gaModel = new GaSessions(db());
$egaSessions = $gaModel->getByType('EMERGENCY', 20);
$eraSessions = $gaModel->getByType('EMERGENCY', 20);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emergency General Assembly — DHLTU SRC</title>

  <meta name="description" content="Emergency General Assembly (EGA) of DHLTU SRC. Convened for urgent matters requiring immediate student deliberation. View upcoming and past sessions.">
  <meta name="keywords" content="DHLTU SRC EGA, Emergency General Assembly, SRC Urgent Meeting, Student Body Emergency">
  <meta name="author" content="DHLTU SRC">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600,0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include 'include/header.php'; ?>
<!-- ══════════════════════════════════════════════════════
     HERO — EGA Overview
     ══════════════════════════════════════════════════════ -->
<section class="constitution-header">
  <div class="constitution-content">
    <div class="section-eyebrow" style="--content: '01';">General Assembly &middot; Emergency Session</div>
    <h1 class="constitution-title">Emergency General Assembly</h1>
    <p class="constitution-subtitle">Urgent student deliberation when the matter cannot wait for the next AGM</p>

    <div class="constitution-meta">
      <div class="meta-item">
        <i class="bi bi-broadcast"></i>
        <span>Scope: Urgent issues requiring immediate student action</span>
      </div>
      <div class="meta-item">
        <i class="bi bi-people"></i>
        <span>Quorum: 25 % of total student population</span>
      </div>
      <div class="meta-item">
        <i class="bi bi-shield-check"></i>
        <span>Authority: Equivalent to AGM for agenda items considered</span>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- PURPOSE — grounds for convening -->
<section class="section" id="purpose">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '02';">Grounds for Convening</div>
      <h2 class="section-title">When is an EGA Called?</h2>
    </div>

    <div class="info-section">
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-triangle-exclamation"></i></div>
        <h4>Crisis & Safety</h4>
        <p>Critical situations affecting student welfare or campus safety that require an immediate collective response.</p>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-currency-dollar"></i></div>
        <h4>Extraordinary Finance</h4>
        <p>Unexpected financial events demanding urgent student approval outside the ordinary budget cycle.</p>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-lock"></i></div>
        <h4>Constitutional Urgency</h4>
        <p>Amendments or clarifications that cannot wait until the next AGM without causing operational harm.</p>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-person-x"></i></div>
        <h4>Vacancy in Office</h4>
        <p>Unexpected vacancies in the Presidency or key executive positions requiring immediate student ratification.</p>
      </div>
    </div>

    <div class="card" style="margin-top: 40px;">
      <h3 style="color: var(--gold); margin-bottom: 16px;">Notice & Quorum</h3>
      <p style="margin-bottom: 12px;">
        The SRC President, with Executive Council approval, may call an EGA with a minimum of <strong>48 hours' notice</strong> communicated through all official student channels. A quorum of <strong>at least 25 % of the total student population</strong> must be present for any decision of the EGA to be binding.
      </p>
      <p style="margin-bottom: 0;">
        All decisions taken at an EGA have the full force of the Annual General Meeting and are ratified and recorded in the official minutes archive.
      </p>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- EGA SESSIONS GRID -->
<section class="section" id="ega-sessions" style="background: var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '03';">Sessions</div>
      <h2 class="section-title">Emergency General Assemblies</h2>
    </div>

    <div class="info-section">
      <?php if (empty($eraSessions)): ?>
      <div class="empty-state" style="grid-column: 1/-1;">
        <i class="bi bi-broadcast"></i>
        <p>No Emergency General Assembly sessions have been recorded yet.</p>
      </div>
      <?php else: foreach ($eraSessions as $row):
        $dtRow     = formatDate($row['scheduled_datetime'], 'M j, Y');
        $timeRow   = formatDate($row['scheduled_datetime'], 'g:i A');
        $statusSlug= strtolower(str_replace('_', '_', $row['status'] ?? 'COMPLETED'));
        $abstract  = htmlspecialchars(
            $row['description']
            ?: sprintf(
                'Emergency General Assembly convened for urgent deliberation and collective student action.'
            )
        );
        $hasMinutes= !empty($row['minutes_file_path']);
        $minutesUrl= $hasMinutes ? htmlspecialchars($row['minutes_file_path']) : '#';
        $sessionLabel = htmlspecialchars($row['title']);
      ?>
      <div class="ga-session-card">
        <div class="session-card-topbar">
          <span class="session-type-badge session-type-emergency">EMERGENCY</span>
          <span class="session-status-badge status-<?php echo $statusSlug; ?>">
            <?php echo htmlspecialchars($row['status']); ?>
          </span>
        </div>
        <span class="session-card-year"><?php echo $sessionLabel; ?></span>
        <div class="session-card-meta">
          <span class="session-meta-item"><i class="bi bi-calendar-event"></i> <?php echo $dtRow; ?></span>
          <span class="session-meta-item"><i class="bi bi-clock"></i> <?php echo $timeRow; ?></span>
          <span class="session-meta-item"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($row['location'] ?? 'TBA'); ?></span>
        </div>
        <p class="session-card-abstract"><?php echo $abstract; ?></p>
        <div class="session-card-actions">
          <?php if ($hasMinutes): ?>
          <a href="<?php echo $minutesUrl; ?>" class="session-btn session-btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> Minutes
          </a>
          <?php endif; ?>
          <a href="resolutions.php" class="session-btn session-btn-outline">
            <i class="bi bi-file-earmark-text"></i> Resolutions
          </a>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div><!-- /info-section -->
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- QUICK RESOURCES -->
<section class="section" id="ega-resources">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '04';">Resources</div>
      <h2 class="section-title">EGA Records & Information</h2>
    </div>

    <div class="info-section">
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-pdf"></i></div>
        <h4>Minutes Archive</h4>
        <p>Ratified minutes from every EGA session, ensuring full accountability and traceability of emergency decisions.</p>
        <a href="meeting-minutes.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Archive &rarr;</a>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-text"></i></div>
        <h4>Resolutions & Motions</h4>
        <p>Official record of all motions carried and resolutions passed at each EGA session.</p>
        <a href="resolutions.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Resolutions &rarr;</a>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-clock-history"></i></div>
        <h4>AGM & Special Sessions</h4>
        <p>All other General Assembly records — the scheduled Annual General Meetings and topic-focused Special Sessions.</p>
        <a href="annual-general-meeting.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View AGM &rarr;</a>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-cone-striped"></i></div>
        <h4>How to Call an EGA</h4>
        <p>A guide for student representatives, council members, and student groups on when and how to request an emergency assembly.</p>
        <a href="#" style="font-size: 13px; margin-top: 12px; display: inline-block;">Read Guide &rarr;</a>
      </div>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>
<script>
(function() {
  var mobileToggle = document.querySelector('.mobile-toggle');
  var navList      = document.querySelector('.nav-list');

  if (!mobileToggle || !navList) return;

  mobileToggle.addEventListener('click', function() {
    mobileToggle.classList.toggle('active');
    navList.classList.toggle('active');
  });

  document.addEventListener('click', function(e) {
    if (!mobileToggle.contains(e.target) && !navList.contains(e.target)) {
      mobileToggle.classList.remove('active');
      navList.classList.remove('active');
    }
  });

  document.querySelectorAll('.nav-link').forEach(function(link) {
    link.addEventListener('click', function() {
      mobileToggle.classList.remove('active');
      navList.classList.remove('active');
    });
  });
})();
</script>

</body>
</html>