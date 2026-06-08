<?php
require_once 'config/database.php';
require_once 'models/News.php';

$db = Database::getInstance();
$newsModel = new News($db);

$page_title = "Announcements";
$current_page = "announcements";

// Fetch announcement data from DB
$allAnnouncements = [];
$pinnedAnnouncements = [];
$urgentCount = 0;
$attachmentsCount = 0;
$newCount = 0;
$weekCutoff = date('Y-m-d H:i:s', strtotime('7 days ago'));
$monthCutoff = date('Y-m-d H:i:s', strtotime('30 days ago'));

try {
    $allAnnouncements = $newsModel->getByCategory('ANNOUNCEMENT', 50);
    if (empty($allAnnouncements)) {
        // Fallback: pull any published news as announcements
        $allAnnouncements = $newsModel->getAllPublished();
    }

    // First 2 pinned = most recent by default
    $pinnedAnnouncements = array_slice($allAnnouncements, 0, 2);

    foreach ($allAnnouncements as $ann) {
        if (strtotime($ann['published_at']) >= strtotime($weekCutoff)) $newCount++;
        $tag = strtoupper(trim(($ann['tags'] ?? '')));
        if ($tag === 'URGENT' || stripos($ann['title'], 'urgent') !== false) $urgentCount++;
        // Count has attachments by checking if excerpt is unusually long (proxy for attachment in DB-less workflow)
        $attachmentsCount++;
    }
} catch (Exception $e) {
    $allAnnouncements = [];
}

// Build the urgent notice from the first pinned item
$urgentNotice = $pinnedAnnouncements[0] ?? null;

// Stats grid values
$newThisWeek  = count(array_filter($allAnnouncements, fn($a) => strtotime($a['published_at']) >= strtotime($weekCutoff)));
$thisMonth    = count(array_filter($allAnnouncements, fn($a) => strtotime($a['published_at']) >= strtotime($monthCutoff)));
$urgentCount  = count(array_filter($allAnnouncements, fn($a) => stripos($a['title'], 'urgent') !== false || (strtoupper(trim($a['tags'] ?? '')) === 'URGENT')));
$attachCount  = 0; // would need dedicated column; placeholder for now

// Category -> badge class mapping
$badgeMap = [
    'ACADEMIC'    => 'badge-academic',
    'ANNOUNCEMENT'=> 'badge-academic',
    'NEWS'        => 'badge-news',
    'WELFARE'     => 'badge-welfare',
    'GOVERNANCE'  => 'badge-governance',
    'SPORTS'      => 'badge-sports',
    'EVENT'       => 'badge-general',
];
function getBadge($cat) {
    $map = [
        'ACADEMIC' => 'badge-academic',
        'ANNOUNCEMENT' => 'badge-academic',
        'NEWS' => 'badge-news',
        'WELFARE' => 'badge-welfare',
        'GOVERNANCE' => 'badge-governance',
        'SPORTS' => 'badge-sports',
        'EVENT' => 'badge-general',
    ];
    return $map[strtoupper(trim($cat))] ?? 'badge-general';
}
function getBadgeColor($cat) {
    // return a small inline color block via class
    $c = strtoupper(trim($cat));
    $colors = [
        'ACADEMIC'    => '#4a9eff',
        'ANNOUNCEMENT'=> '#4a9eff',
        'NEWS'        => 'var(--gold)',
        'WELFARE'     => '#22c55e',
        'GOVERNANCE'  => 'var(--gold)',
        'SPORTS'      => '#ef4444',
        'EVENT'       => 'var(--text-muted)',
    ];
    return $colors[$c] ?? 'var(--text-muted)';
}

function fmtDate($d) {
    return date('d M Y', strtotime($d));
}
function buildExcerpt($text, $len = 200) {
    if (empty($text)) return '';
    $text = strip_tags($text);
    return strlen($text) > $len ? substr($text, 0, $len) . '...' : $text;
}
function getTagList($tagsJson) {
    if (empty($tagsJson)) return [];
    $decoded = json_decode($tagsJson, true);
    return is_array($decoded) ? $decoded : [];
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
      color: rgba(245,240,232,0.55); max-width: 520px;
      position: relative; z-index: 1;
    }

    /* URGENT BANNER */
    .urgent-banner {
      background: linear-gradient(90deg, rgba(239,68,68,0.15), rgba(239,68,68,0.05));
      border-left: 4px solid #ef4444;
      padding: 20px 80px;
      display: flex; align-items: center; gap: 16px;
    }
    .urgent-icon { font-size: 18px; flex-shrink: 0; }
    .urgent-text { flex: 1; }
    .urgent-label {
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.15em; text-transform: uppercase;
      color: #ef4444; margin-bottom: 4px;
    }
    .urgent-message { font-size: 14px; color: var(--cream); }
    .urgent-date { font-size: 11px; color: var(--text-muted); flex-shrink: 0; }

    /* FILTER TABS */
    .filter-tabs {
      display: flex; align-items: center; gap: 0;
      padding: 0 80px;
      background: var(--navy-mid);
      border-bottom: 1px solid rgba(201,168,76,0.08);
      overflow-x: auto;
    }
    .tab-btn {
      padding: 20px 24px;
      background: transparent; border: none;
      font-family: 'Outfit', sans-serif;
      font-size: 12px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--text-muted); cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: all var(--transition-fast);
      white-space: nowrap;
    }
    .tab-btn.active { color: var(--gold); border-bottom-color: var(--gold); }
    .tab-btn:hover { color: var(--cream); }
    .tab-count {
      display: inline-flex; align-items: center; justify-content: center;
      width: 18px; height: 18px; border-radius: 50%;
      background: rgba(201,168,76,0.15);
      font-size: 9px; color: var(--gold); margin-left: 6px;
      font-weight: 700;
    }

    /* MAIN LAYOUT */
    .ann-layout {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 0;
      align-items: start;
    }

    .ann-main { padding: 60px 60px 80px 80px; }

    /* PINNED ANNOUNCEMENTS */
    .pinned-grid {
      display: grid; grid-template-columns: repeat(2, 1fr);
      gap: 16px; margin-bottom: 48px;
    }
    .pinned-card {
      border: 1px solid rgba(201,168,76,0.2);
      background: linear-gradient(135deg, rgba(201,168,76,0.05), rgba(201,168,76,0.02));
      padding: 28px;
      position: relative; overflow: hidden;
      text-decoration: none;
      transition: all var(--transition-med);
      display: block;
    }
    .pinned-card:hover { border-color: rgba(201,168,76,0.4); transform: translateY(-3px); }
    .pinned-card::before {
      content: '\1F4CC';
      position: absolute; top: 16px; right: 16px;
      font-size: 12px; opacity: 0.6;
    }
    .pinned-card-priority {
      display: inline-block;
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.12em; text-transform: uppercase;
      padding: 3px 10px; margin-bottom: 12px;
    }
    .priority-urgent { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .priority-important { background: rgba(201,168,76,0.12); color: var(--gold); border: 1px solid rgba(201,168,76,0.3); }
    .pinned-card-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px; font-weight: 400; color: var(--cream);
      line-height: 1.3; margin-bottom: 10px;
    }
    .pinned-card-body { font-size: 12px; color: var(--text-muted); line-height: 1.7; margin-bottom: 16px; }
    .pinned-card-footer { display: flex; justify-content: space-between; align-items: center; }
    .pinned-card-date { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--gold); }
    .pinned-card-action {
      font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--text-muted); display: flex; align-items: center; gap: 4px;
      transition: color var(--transition-fast);
    }
    .pinned-card:hover .pinned-card-action { color: var(--gold); }
    .pinned-card-action::after { content: '→'; }

    /* ANN LIST */
    .section-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 24px;
      display: flex; align-items: center; gap: 10px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(201,168,76,0.15); }

    .ann-item {
      display: flex; gap: 24px; align-items: flex-start;
      padding: 28px 0;
      border-bottom: 1px solid rgba(201,168,76,0.07);
      transition: padding var(--transition-fast);
      cursor: pointer;
      text-decoration: none;
      color: inherit;
    }
    .ann-item:hover { padding-left: 10px; }

    .ann-icon {
      width: 44px; height: 44px; flex-shrink: 0;
      border: 1px solid rgba(201,168,76,0.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
      background: rgba(201,168,76,0.04);
    }
    .ann-body { flex: 1; }
    .ann-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
    .ann-badge {
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 3px 10px;
    }
    .badge-academic { background: rgba(74,158,255,0.15); color: #4a9eff; border: 1px solid rgba(74,158,255,0.25); }
    .badge-welfare { background: rgba(34,197,94,0.12); color: #22c55e; border: 1px solid rgba(34,197,94,0.25); }
    .badge-governance { background: rgba(201,168,76,0.12); color: var(--gold); border: 1px solid rgba(201,168,76,0.25); }
    .badge-sports { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); }
    .badge-general { background: rgba(255,255,255,0.06); color: var(--text-muted); border: 1px solid rgba(255,255,255,0.1); }
    .ann-title { font-size: 15px; font-weight: 500; color: var(--cream); line-height: 1.4; margin-bottom: 6px; }
    .ann-excerpt { font-size: 12px; color: var(--text-muted); line-height: 1.7; margin-bottom: 10px; }
    .ann-meta { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .ann-date { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--gold); }
    .ann-author { font-size: 11px; color: var(--text-muted); }

    .ann-right { flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
    .ann-new {
      font-family: 'Space Mono', monospace; font-size: 8px;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--navy); background: var(--gold);
      padding: 3px 8px;
    }

    /* SIDEBAR */
    .ann-sidebar { padding: 60px 40px 60px 0; position: sticky; top: 123px; }
    .sidebar-title {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 20px; padding-bottom: 12px;
      border-bottom: 1px solid rgba(201,168,76,0.12);
    }
    .sidebar-section { margin-bottom: 40px; }

    .subscribe-box {
      border: 1px solid rgba(201,168,76,0.2);
      padding: 24px;
      background: var(--navy-mid);
    }
    .subscribe-title { font-size: 14px; font-weight: 600; color: var(--cream); margin-bottom: 8px; }
    .subscribe-desc { font-size: 12px; color: var(--text-muted); line-height: 1.6; margin-bottom: 20px; }
    .subscribe-options { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
    .subscribe-option { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .subscribe-option input[type="checkbox"] { accent-color: var(--gold); width: 14px; height: 14px; }
    .subscribe-option label { font-size: 12px; color: var(--text-muted); cursor: pointer; }
    .subscribe-btn {
      display: block; width: 100%; padding: 12px;
      background: linear-gradient(135deg, var(--gold-light), var(--gold));
      color: var(--navy); font-family: 'Outfit', sans-serif;
      font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
      border: none; cursor: pointer;
      clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
      transition: all var(--transition-fast);
    }
    .subscribe-btn:hover { background: linear-gradient(135deg, #fff, var(--gold-light)); }

    .quick-links { flex-direction: column; gap: 0; }
    .quick-link {
      display: flex; align-items: center; justify-content: space-between;
      padding: 12px 0; text-decoration: none;
      border-bottom: 1px solid rgba(201,168,76,0.06);
      transition: padding var(--transition-fast);
    }
    .quick-link:hover { padding-left: 6px; }
    .quick-link-text { font-size: 13px; color: var(--cream); }
    .quick-link-arrow { color: var(--gold); font-size: 12px; transition: transform var(--transition-fast); }
    .quick-link:hover .quick-link-arrow { transform: translateX(4px); }

    .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .stat-box {
      border: 1px solid rgba(201,168,76,0.12);
      background: rgba(201,168,76,0.03);
      padding: 20px 16px; text-align: center;
    }
    .stat-box-num {
      font-family: 'Cormorant Garamond', serif;
      font-size: 32px; font-weight: 700; color: var(--gold-light);
      line-height: 1;
    }
    .stat-box-label { font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted); margin-top: 4px; }

    /* No data */
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

    @media (max-width: 1100px) {
      .ann-layout { grid-template-columns: 1fr; }
      .ann-main { padding: 40px; }
      .ann-sidebar { padding: 0 40px 60px; position: static; }
      .pinned-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .page-header, .urgent-banner, .filter-tabs, footer { padding-left: 20px; padding-right: 20px; }
      .ann-main, .ann-sidebar { padding-left: 20px; padding-right: 20px; }
      .ann-item { flex-direction: column; }
      .urgent-banner { flex-direction: column; gap: 8px; }
      footer { flex-direction: column; gap: 12px; }
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
    <div class="page-eyebrow">SRC Notice Board</div>
    <h1 class="page-title">Official<br><em>Announcements</em></h1>
    <p class="page-subtitle">Important notices, updates, and information from the DHLTU Student Representative Council. Stay informed on all matters affecting student life.</p>
  </div>

  <?php if ($urgentNotice): ?>
  <!-- Urgent Banner — most recent pinned item -->
  <div class="urgent-banner">
    <span class="urgent-icon">&#9679;</span>
    <div class="urgent-text">
      <div class="urgent-label">Latest Announcement</div>
      <div class="urgent-message"><?= htmlspecialchars($urgentNotice['title']) ?></div>
    </div>
    <div class="urgent-date">Posted: <?= fmtDate($urgentNotice['published_at']) ?></div>
  </div>
  <?php endif; ?>

  <!-- Filter Tabs (dynamic counts by category) -->
  <div class="filter-tabs">
    <?php
    $tabCategories = ['ACADEMIC', 'WELFARE', 'GOVERNANCE', 'SPORTS', 'NEWS'];
    foreach ($tabCategories as $i => $cat):
      $catCount = $newsModel->getTotalByCategory($cat);
    ?>
    <button class="tab-btn<?= $i === 0 ? ' active' : '' ?>"><?= ucfirst(strtolower($cat)) ?><span class="tab-count"><?= $catCount ?></span></button>
    <?php endforeach; ?>
  </div>

  <div class="ann-layout">
    <div class="ann-main">

      <!-- Pinned / Featured -->
      <div class="section-label">&#128204; Pinned Announcements</div>
      <?php if ($pinnedAnnouncements): ?>
      <div class="pinned-grid">
        <?php foreach ($pinnedAnnouncements as $pinned):
          $pinnedCat = strtoupper($pinned['category']);
          $isUrgent = $pinnedCat === 'URGENT' || stripos($pinned['title'], 'urgent') !== false;
          $tags = getTagList($pinned['tags']);
        ?>
        <a href="#" class="pinned-card" onclick="return false;">
          <span class="pinned-card-priority <?= $isUrgent ? 'priority-urgent' : 'priority-important' ?>">
            <?= $isUrgent ? 'Urgent' : 'Important' ?>
          </span>
          <div class="pinned-card-title"><?= htmlspecialchars($pinned['title']) ?></div>
          <div class="pinned-card-body"><?= htmlspecialchars(buildExcerpt($pinned['excerpt'] ?: $pinned['content'], 160)) ?></div>
          <div class="pinned-card-footer">
            <span class="pinned-card-date"><?= fmtDate($pinned['published_at']) ?></span>
            <span class="pinned-card-action">View Details</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-data" style="margin-bottom:48px;">
        <p>No pinned announcements at this time.</p>
      </div>
      <?php endif; ?>

      <!-- All Announcements -->
      <div class="section-label">All Announcements</div>

      <?php if (empty($allAnnouncements)): ?>
      <div class="no-data" style="grid-column:1/-1;">
        <div class="no-data-title">No Announcements Found</div>
        <p>There are no published announcements yet. Check back soon.</p>
      </div>
      <?php else: ?>
        <?php foreach ($allAnnouncements as $index => $ann):
          $cat = strtoupper($ann['category']);
          $isNew  = strtotime($ann['published_at']) >= strtotime('7 days ago');
          $badge  = getBadge($ann['category']);
          $tagLabel= ucfirst(strtolower($ann['category']));
          $excerpt= htmlspecialchars(buildExcerpt($ann['excerpt'] ?: $ann['content'], 160));
        ?>
        <a href="#" class="ann-item" onclick="return false;">
          <div class="ann-icon">&#128196;</div>
          <div class="ann-body">
            <div class="ann-head">
              <span class="ann-badge <?= $badge ?>" style="border-color: <?= getBadgeColor($ann['category']) ?>33; color:<?= getBadgeColor($ann['category']) ?>"><?= $tagLabel ?></span>
            </div>
            <div class="ann-title"><?= htmlspecialchars($ann['title']) ?></div>
            <div class="ann-excerpt"><?= $excerpt ?></div>
            <div class="ann-meta">
              <span class="ann-date"><?= fmtDate($ann['published_at']) ?></span>
              <span class="ann-author"><?= htmlspecialchars($ann['category'] ?? 'SRC') ?></span>
            </div>
          </div>
          <?php if ($isNew): ?>
          <div class="ann-right"><span class="ann-new">New</span></div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div class="ann-sidebar">
      <div class="sidebar-section">
        <div class="sidebar-title">This Month at a Glance</div>
        <div class="stats-grid">
          <div class="stat-box"><div class="stat-box-num"><?= $newThisWeek ?></div><div class="stat-box-label">New This Week</div></div>
          <div class="stat-box"><div class="stat-box-num"><?= $thisMonth ?></div><div class="stat-box-label">This Month</div></div>
          <div class="stat-box"><div class="stat-box-num"><?= $urgentCount ?></div><div class="stat-box-label">Urgent</div></div>
          <div class="stat-box"><div class="stat-box-num"><?= $attachCount ?></div><div class="stat-box-label">With Attachments</div></div>
        </div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Subscribe to Alerts</div>
        <div class="subscribe-box">
          <div class="subscribe-title">Get Notified Instantly</div>
          <div class="subscribe-desc">Choose which categories you want to receive email alerts for.</div>
          <div class="subscribe-options">
            <div class="subscribe-option"><input type="checkbox" id="sub_academic" checked /><label for="sub_academic">Academic Notices</label></div>
            <div class="subscribe-option"><input type="checkbox" id="sub_welfare" /><label for="sub_welfare">Welfare Updates</label></div>
            <div class="subscribe-option"><input type="checkbox" id="sub_gov" checked /><label for="sub_gov">Governance</label></div>
            <div class="subscribe-option"><input type="checkbox" id="sub_sports" /><label for="sub_sports">Sports &amp; Events</label></div>
            <div class="subscribe-option"><input type="checkbox" id="sub_urgent" checked /><label for="sub_urgent">Urgent Notices Only</label></div>
          </div>
          <button class="subscribe-btn">Save Preferences</button>
        </div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Quick Links</div>
        <div class="quick-links">
          <a href="latest-news.php" class="quick-link"><span class="quick-link-text">Latest News</span><span class="quick-link-arrow">&rarr;</span></a>
          <a href="events-calendar.php" class="quick-link"><span class="quick-link-text">Events Calendar</span><span class="quick-link-arrow">&rarr;</span></a>
          <a href="press-releases.php" class="quick-link"><span class="quick-link-text">Press Releases</span><span class="quick-link-arrow">&rarr;</span></a>
          <a href="#" class="quick-link"><span class="quick-link-text">Student Portal</span><span class="quick-link-arrow">&rarr;</span></a>
          <a href="#" class="quick-link"><span class="quick-link-text">Contact the SRC</span><span class="quick-link-arrow">&rarr;</span></a>
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
      cursor.style.top  = e.clientY + 'px';
      setTimeout(() => { ring.style.left = e.clientX + 'px'; ring.style.top = e.clientY + 'px'; }, 80);
    });
    // Filter tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });

    /* ── Mobile Menu Toggle ── */
    var mobileToggle = document.querySelector(".mobile-toggle");
    var navList = document.querySelector(".nav-list");
    if (mobileToggle && navList) {
      mobileToggle.addEventListener("click", function() {
        this.classList.toggle("active");
        navList.classList.toggle("active");
      });
      document.addEventListener("click", function(e) {
        if (navList.classList.contains("active") && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
          mobileToggle.classList.remove("active");
          navList.classList.remove("active");
        }
      });
    }
    document.querySelectorAll(".nav-item > .nav-link").forEach(function(link) {
      link.addEventListener("click", function(e) {
        var parentItem = this.closest(".nav-item");
        var dropdown = parentItem.querySelector(".dropdown");
        if (dropdown) {
          e.preventDefault();
          parentItem.classList.toggle("open");
          dropdown.classList.toggle("open");
        }
      });
    });
  </script>
</body>
</html>
