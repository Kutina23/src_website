<?php
require_once 'config/database.php';
require_once 'models/News.php';

$db = Database::getInstance();
$newsModel = new News($db);

$page_title = "Press Releases";
$current_page = "press";

// Fetch press release data: use 'PRESS_RELEASE' category, fallback to empty array
$releasesByYear = [];
$allReleases = [];
try {
    $releases = $newsModel->getByCategory('PRESS_RELEASE', 100);
    $allReleases = $releases;
    // Group by year
    foreach ($releases as $r) {
        $year = date('Y', strtotime($r['published_at']));
        if (!isset($releasesByYear[$year])) $releasesByYear[$year] = [];
        $releasesByYear[$year][] = $r;
    }
    krsort($releasesByYear);
} catch (Exception $e) {
    // Table may not exist or category not yet seeded — use empty data
    $allReleases = [];
}

// Archive stats
$totalReleases = count($allReleases);
$thisYearReleases = 0;
$thisMonthReleases = 0;
$now = new DateTime();
$thisMonthNum = (int)$now->format('n');
$thisYearNum  = (int)$now->format('Y');
foreach ($allReleases as $r) {
    $pub = new DateTime($r['published_at']);
    if ((int)$pub->format('Y') === $thisYearNum) $thisYearReleases++;
    if ((int)$pub->format('n') === $thisMonthNum) $thisMonthReleases++;
}

// Archive year counts
$archiveByYear = [];
foreach ($allReleases as $r) {
    $year = date('Y', strtotime($r['published_at']));
    if (!isset($archiveByYear[$year])) $archiveByYear[$year] = 0;
    $archiveByYear[$year]++;
}
if (!$allReleases) {
    $archiveByYear = [$thisYearNum => 0, $thisYearNum - 1 => 0];
}

// Helper: format display date
function fmtDate($dateStr) {
    return date('d M Y', strtotime($dateStr));
}
function fmtDay($dateStr) {
    return date('d', strtotime($dateStr));
}
function fmtMonth($dateStr) {
    return date('M Y', strtotime($dateStr));
}
function buildExcerpt($text, $len = 220) {
    if (empty($text)) return '';
    $text = strip_tags($text);
    return strlen($text) > $len ? substr($text, 0, $len) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?> — HLTU SRC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
  <style>
    :root {
      --navy: #0a1628;
      --navy-mid: #0f2040;
      --navy-light: #152a50;
      --gold: #c9a84c;
      --gold-light: #e2c170;
      --gold-dark: #a07830;
      --cream: #f5f0e8;
      --white: #ffffff;
      --text-muted: rgba(245,240,232,0.45);
      --transition-fast: 0.2s ease;
      --transition-med: 0.35s ease;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--navy); color: var(--cream); font-family: 'Outfit', sans-serif; min-height: 100vh; }

    /* PAGE HEADER */
    .page-header {
      padding: 160px 80px 80px;
      background: linear-gradient(160deg, var(--navy-mid), var(--navy));
      position: relative; overflow: hidden;
      border-bottom: 1px solid rgba(201,168,76,0.12);
    }
    .page-header::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(201,168,76,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,0.03) 1px, transparent 1px);
      background-size: 60px 60px;
    }
    .page-header-orb {
      position: absolute; top: -80px; right: -80px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(201,168,76,0.08), transparent 70%);
      border-radius: 50%; pointer-events: none;
    }
    .page-eyebrow {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.25em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 20px; display: flex; align-items: center; gap: 12px;
      position: relative; z-index: 1;
    }
    .page-eyebrow::before { content: ''; width: 30px; height: 1px; background: var(--gold); }
    .page-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(42px, 5vw, 72px);
      font-weight: 300; line-height: 1; color: var(--cream);
      margin-bottom: 16px; position: relative; z-index: 1;
    }
    .page-title em { font-style: italic; color: var(--gold-light); }
    .page-subtitle {
      font-size: 15px; font-weight: 300; line-height: 1.8;
      color: rgba(245,240,232,0.55); max-width: 560px;
      position: relative; z-index: 1;
    }
    .header-stats {
      display: flex; gap: 48px; margin-top: 48px;
      position: relative; z-index: 1;
    }
    .header-stat-num {
      font-family: 'Cormorant Garamond', serif;
      font-size: 36px; font-weight: 700; color: var(--gold-light); line-height: 1;
    }
    .header-stat-label { font-size: 10px; letter-spacing: 0.15em; text-transform: uppercase; color: var(--text-muted); margin-top: 4px; }

    /* MAIN LAYOUT */
    .press-layout {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 0;
      align-items: start;
    }

    /* RELEASES LIST */
    .releases-main { padding: 60px 60px 80px 80px; }
    .section-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 32px;
      display: flex; align-items: center; gap: 10px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(201,168,76,0.15); }

    .year-group { margin-bottom: 48px; }
    .year-header {
      font-family: 'Cormorant Garamond', serif;
      font-size: 48px; font-weight: 700;
      color: rgba(201,168,76,0.1);
      line-height: 1; margin-bottom: 24px;
      letter-spacing: -2px;
    }

    .release-item {
      display: grid;
      grid-template-columns: 100px 1fr auto;
      gap: 32px; align-items: start;
      padding: 32px 0;
      border-bottom: 1px solid rgba(201,168,76,0.08);
      transition: all var(--transition-fast);
      text-decoration: none;
      color: inherit;
    }
    .release-item:hover { padding-left: 8px; }
    .release-date-block { text-align: left; }
    .release-date-day {
      font-family: 'Cormorant Garamond', serif;
      font-size: 40px; font-weight: 700; color: var(--gold-light);
      line-height: 1; display: block;
    }
    .release-date-month {
      font-family: 'Space Mono', monospace;
      font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--text-muted); display: block;
    }
    .release-content { }
    .release-ref {
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--text-muted); margin-bottom: 8px;
    }
    .release-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 22px; font-weight: 400; color: var(--cream);
      line-height: 1.3; margin-bottom: 10px;
      text-decoration: none; display: block;
      transition: color var(--transition-fast);
    }
    .release-title:hover { color: var(--gold-light); }
    .release-summary { font-size: 13px; color: var(--text-muted); line-height: 1.7; margin-bottom: 16px; }
    .release-tags { display: flex; gap: 8px; flex-wrap: wrap; }
    .release-tag {
      font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--gold); border: 1px solid rgba(201,168,76,0.25);
      padding: 3px 10px;
    }
    .release-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; flex-shrink: 0; }
    .release-dl {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--gold); text-decoration: none;
      border: 1px solid rgba(201,168,76,0.25); padding: 8px 16px;
      transition: all var(--transition-fast);
      white-space: nowrap;
    }
    .release-dl:hover { border-color: var(--gold); background: rgba(201,168,76,0.06); }
    .release-dl::before { content: '↓'; }
    .release-view {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--text-muted); text-decoration: none;
      padding: 8px 16px; transition: color var(--transition-fast);
      white-space: nowrap;
    }
    .release-view:hover { color: var(--gold); }
    .release-view::after { content: '→'; }

    /* SIDEBAR */
    .press-sidebar {
      padding: 60px 40px 60px 0;
      position: sticky; top: 123px;
    }
    .sidebar-section { margin-bottom: 40px; }
    .sidebar-title {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 20px; padding-bottom: 12px;
      border-bottom: 1px solid rgba(201,168,76,0.12);
    }
    .media-contact-card {
      border: 1px solid rgba(201,168,76,0.15);
      padding: 24px;
      background: var(--navy-mid);
    }
    .media-name { font-size: 14px; font-weight: 600; color: var(--cream); margin-bottom: 4px; }
    .media-role { font-size: 11px; color: var(--text-muted); margin-bottom: 16px; }
    .media-link {
      display: flex; align-items: center; gap: 10px;
      font-size: 12px; color: var(--gold); text-decoration: none;
      margin-bottom: 8px; transition: gap var(--transition-fast);
    }
    .media-link:hover { gap: 14px; }
    .media-link-icon { width: 18px; text-align: center; }

    .archive-list { flex-direction: column; gap: 0; }
    .archive-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid rgba(201,168,76,0.06);
      text-decoration: none;
      transition: padding var(--transition-fast);
      cursor: pointer;
    }
    .archive-item:hover { padding-left: 6px; }
    .archive-year { font-size: 13px; color: var(--cream); }
    .archive-count {
      font-family: 'Space Mono', monospace; font-size: 10px;
      color: var(--gold); letter-spacing: 0.1em;
    }

    .download-kit {
      border: 1px solid rgba(201,168,76,0.2);
      padding: 24px;
      text-align: center;
      background: linear-gradient(135deg, rgba(201,168,76,0.04), transparent);
    }
    .download-kit-title { font-size: 14px; font-weight: 600; color: var(--cream); margin-bottom: 8px; }
    .download-kit-desc { font-size: 12px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }
    .download-kit-btn {
      display: block; padding: 12px;
      background: linear-gradient(135deg, var(--gold-light), var(--gold));
      color: var(--navy); font-size: 11px; font-weight: 600;
      letter-spacing: 0.1em; text-transform: uppercase;
      text-decoration: none; text-align: center;
      clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
      transition: all var(--transition-fast);
    }
    .download-kit-btn:hover { background: linear-gradient(135deg, #fff, var(--gold-light)); }

    /* No data state */
    .no-data {
      padding: 60px 0;
      text-align: center;
      color: var(--text-muted);
    }
    .no-data-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 24px;
      color: var(--cream);
      margin-bottom: 8px;
    }

    @media (max-width: 1024px) {
      .press-layout { grid-template-columns: 1fr; }
      .releases-main { padding: 40px; }
      .press-sidebar { padding: 0 40px 60px; position: static; }
      .release-item { grid-template-columns: 80px 1fr; }
      .release-actions { flex-direction: row; grid-column: 1 / -1; }
    }
    @media (max-width: 640px) {
      .page-header, footer { padding-left: 20px; padding-right: 20px; }
      .releases-main, .press-sidebar { padding: 20px; }
      .release-item { grid-template-columns: 1fr; gap: 16px; }
      .header-stats { gap: 24px; flex-wrap: wrap; }
      .release-actions { flex-direction: column; }
    }
  </style>
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

  <div class="cursor" id="cursor"></div>
  <div class="cursor-ring" id="cursorRing"></div>

  <?php include 'include/header.php'; ?>

  <div class="page-header">
    <div class="page-header-orb"></div>
    <div class="page-eyebrow">SRC Communications Office</div>
    <h1 class="page-title"><em>Official</em><br>Press Releases</h1>
    <p class="page-subtitle">Formal statements, position papers, and official communications issued by the DHLTU Student Representative Council.</p>
    <div class="header-stats">
      <div><div class="header-stat-num"><?= $totalReleases ?></div><div class="header-stat-label">Total Releases</div></div>
      <div><div class="header-stat-num"><?= $thisYearReleases ?></div><div class="header-stat-label">This Year</div></div>
      <div><div class="header-stat-num"><?= $thisMonthReleases ?></div><div class="header-stat-label">This Month</div></div>
    </div>
  </div>

  <div class="press-layout">
    <div class="releases-main">
      <div class="section-label"><?= date('Y') ?> Press Releases</div>

      <?php if (empty($releasesByYear)): ?>
      <div class="no-data">
        <div class="no-data-title">No Press Releases Yet</div>
        <p>Press releases will appear here once published by the SRC Communications team.</p>
      </div>
      <?php endif; ?>

      <?php foreach ($releasesByYear as $year => $releases): ?>
      <a name="year-<?= htmlspecialchars($year) ?>"></a>
      <div class="year-group">
        <div class="year-header"><?= htmlspecialchars($year) ?></div>
        <?php foreach ($releases as $r):
          $tags = json_decode($r['tags'] ?? '', true);
          $tags = is_array($tags) ? $tags : [];
        ?>
        <a href="#" class="release-item" onclick="return false;">
          <div class="release-date-block">
            <span class="release-date-day"><?= fmtDay($r['published_at']) ?></span>
            <span class="release-date-month"><?= fmtMonth($r['published_at']) ?></span>
          </div>
          <div class="release-content">
            <div class="release-ref">REF: <?= htmlspecialchars($r['id'] ? 'HLTU/SRC/PR/' . $year . '/' . str_pad($r['id'], 3, '0', STR_PAD_LEFT) : 'HLTU/SRC/PR/NEW') ?></div>
            <span class="release-title"><?= htmlspecialchars($r['title']) ?></span>
            <p class="release-summary"><?= htmlspecialchars(buildExcerpt($r['excerpt'] ?: $r['content'], 220)) ?></p>
            <div class="release-tags">
              <span class="release-tag"><?= htmlspecialchars($r['category']) ?></span>
              <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                <span class="release-tag"><?= htmlspecialchars($tag) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="release-actions">
            <a href="#" class="release-dl" onclick="return false;">Download PDF</a>
            <a href="#" class="release-view" onclick="return false;">Full Statement</a>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Sidebar -->
    <div class="press-sidebar">
      <div class="sidebar-section">
        <div class="sidebar-title">Media Contact</div>
        <div class="media-contact-card">
          <div class="media-name">Communications Office</div>
          <div class="media-role">SRC Directorate, DHLTU</div>
          <a href="mailto:comms@dhltusrc.edu.gh" class="media-link"><span class="media-link-icon">&#9993;</span> comms@dhltusrc.edu.gh</a>
          <a href="tel:+233000000000" class="media-link"><span class="media-link-icon">&#9742;</span> Contact via Portal</a>
        </div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Archive by Year</div>
        <div class="archive-list">
          <?php foreach ($archiveByYear as $yr => $cnt): ?>
            <div class="archive-item" onclick="window.location='#year-<?= htmlspecialchars($yr) ?>'">
              <span class="archive-year"><?= htmlspecialchars($yr) ?></span>
              <span class="archive-count"><?= $cnt ?> release<?= $cnt !== 1 ? 's' : '' ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Media Resources</div>
        <div class="download-kit">
          <div class="download-kit-title">SRC Media Kit</div>
          <div class="download-kit-desc">Download our official logos, brand guidelines, and authorised photography for media use.</div>
          <a href="#" class="download-kit-btn">&#9660; Download Media Kit</a>
        </div>
      </div>
    </div>
  </div>

  <?php include 'include/footer.php'; ?>

<script>
    const cursor = document.getElementById('cursor');
    const ring = document.getElementById('cursorRing');
    document.addEventListener('mousemove', e => {
      cursor.style.left = e.clientX + 'px';
      cursor.style.top = e.clientY + 'px';
      setTimeout(() => { ring.style.left = e.clientX + 'px'; ring.style.top = e.clientY + 'px'; }, 80);
    });
    
    // Mobile menu toggle
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
