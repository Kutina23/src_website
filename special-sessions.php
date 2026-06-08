<?php
// DHLTU SRC — Special Sessions Page
// File: special-sessions.php
// Purpose: Lists all Special Sessions convened by SRC (dynamic — ga_sessions.session_type='SPECIAL')
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaSessions.php';

$gaModel = new GaSessions(db());
$allSessions = $gaModel->getByType('SPECIAL', 20);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Special Sessions — DHLTU SRC</title>

  <meta name="description" content="Special Sessions of DHLTU SRC. Focused, topic-driven assemblies recorded in ga_sessions (session_type='SPECIAL'). View schedules, minutes, and resolutions.">
  <meta name="keywords" content="DHLTU SRC Special Sessions, Committee Meetings, Working Groups, SRC Special Assembly">
  <meta name="author" content="DHLTU SRC">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600,0,700;1,300,1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include 'include/header.php'; ?>
<!-- ══════════════════════════════════════════════════════
     HERO — Special Sessions Overview
     ══════════════════════════════════════════════════════ -->
<section class="constitution-header">
  <div class="constitution-content">
    <div class="section-eyebrow" style="--content: '01';">General Assembly &middot; Special Session</div>
    <h1 class="constitution-title">Special Sessions</h1>
    <p class="constitution-subtitle">Focused, topic-driven assemblies for dedicated deliberation on specific issues</p>

    <div class="constitution-meta">
      <div class="meta-item">
        <i class="bi bi-broadcast"></i>
        <span>Topic-focused assemblies outside the annual calendar</span>
      </div>
      <div class="meta-item">
        <i class="bi bi-geo-alt"></i>
        <span>Venue published per session</span>
      </div>
      <div class="meta-item">
        <i class="bi bi-people"></i>
        <span>Open to all students and council members</span>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════════════════════════
     PURPOSE — what a Special Session is
   Schema driver  : ga_sessions.session_type = 'SPECIAL'
                  ga_sessions.title / description / scheduled_datetime / location / status
     ══════════════════════════════════════════════════════ -->
<section class="section" id="purpose">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '02';">About Special Sessions</div>
      <h2 class="section-title">What is a Special Session?</h2>
    </div>

    <div class="card">
      <p style="margin-bottom: 16px;">
        Special Sessions are convened by the SRC Executive Council to address specific, time-sensitive issues that warrant dedicated, focused deliberation outside the routine AGM agenda. Each session has a single primary topic, a defined outcome objective, and a published agenda circulated to all students in advance.
      </p>
      <p style="margin-bottom: 16px;">
        Unlike Emergency General Assemblies, Special Sessions do not necessarily carry the full authority of the AGM unless so declared by the presiding officer. They are recorded in the <code>ga_sessions</code> table with <code>session_type = 'SPECIAL'</code> and their outcomes are ratified at the next ordinary meeting.
      </p>

      <h3 style="color: var(--gold); margin: 24px 0 12px;">Why Convene a Special Session?</h3>
      <ul style="margin-left: 20px; line-height: 2;">
        <li>Policy changes too complex for an AGM time slot</li>
        <li>Working-group reports that need plenary discussion and approval</li>
        <li>Feedback loops on proposals raised at a previous AGM</li>
        <li>Joint sittings with committee chairs on cross-cutting issues</li>
        <li>Introduction of new student services or initiatives</li>
      </ul>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════════════════════════
     SPECIAL SESSIONS GRID — all sessions as grid cards
     Schema drivers  : ga_sessions.session_type='SPECIAL',
                     ga_sessions.status, scheduled_datetime,
                     location, description, minutes_url
     ══════════════════════════════════════════════════════ -->
<section class="section" id="sessions-grid" style="background: var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '03';">All Sessions</div>
      <h2 class="section-title">Special Sessions</h2>
    </div>

    <div class="info-section">
      <?php if (empty($allSessions)): ?>
      <div class="empty-state" style="grid-column: 1/-1;">
        <i class="bi bi-lightning"></i>
        <p>No Special Sessions have been recorded yet.</p>
        <p style="font-size:13px;margin-top:8px;">Check back soon — upcoming sessions will appear here once added.</p>
      </div>
      <?php else: foreach ($allSessions as $row):
        $dtRow      = formatDate($row['scheduled_datetime'], 'M j, Y');
        $timeRow    = formatDate($row['scheduled_datetime'], 'g:i A');
        $statusSlug = strtolower(str_replace('_', '_', $row['status'] ?? 'COMPLETED'));
        $abstract   = htmlspecialchars(
            $row['description']
            ?: sprintf(
                'Special Session on %s.',
                strtolower(str_replace('-', ' ', $row['title']))
            )
        );
        $hasMinutes = !empty($row['minutes_file_path']);
        $minutesUrl = $hasMinutes ? htmlspecialchars($row['minutes_file_path']) : '#';
        $sessionLabel = htmlspecialchars($row['title']);
        $btnLabel= $hasMinutes ? htmlspecialchars($row['minutes_meeting_title'] ?: 'Minutes') : 'Minutes';
      ?>
      <div class="ga-session-card">
        <div class="session-card-topbar">
          <span class="session-type-badge session-type-special">SPECIAL</span>
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
            <i class="bi bi-file-earmark-pdf"></i> <?php echo $btnLabel; ?>
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

<!-- ══════════════════════════════════════════════════════
     QUICK RESOURCES
     ══════════════════════════════════════════════════════ -->
<section class="section" id="ss-resources">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '04';">Quick Access</div>
      <h2 class="section-title">Special Session Records</h2>
    </div>

    <div class="info-section">
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-pdf"></i></div>
        <h4>Minutes Archive</h4>
        <p>Download ratified minutes from every Special Session. Session records are updated after ratification at the next ordinary meeting.</p>
        <a href="meeting-minutes.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Archive &rarr;</a>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-text"></i></div>
        <h4>Resolutions & Motions</h4>
        <p>Track which motions were carried and how each session's resolutions were implemented across the SRC calendar.</p>
        <a href="resolutions.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Resolutions &rarr;</a>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-calendar-event"></i></div>
        <h4>Annual General Meeting</h4>
        <p>The flagship yearly assembly where all Special Session decisions are formally ratified and adopted into SRC policy.</p>
        <a href="annual-general-meeting.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View AGM &rarr;</a>
      </div>

      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-lightning"></i></div>
        <h4>Emergency General Assembly</h4>
        <p>For matters too urgent to wait for a Special Session. EGAs have the binding authority of the AGM for the agenda considered.</p>
        <a href="emergency-ga.php" style="font-size: 13px; margin-top: 12px; display: inline-block;">View EGA &rarr;</a>
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