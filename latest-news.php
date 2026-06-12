<?php
require_once 'config/database.php';
require_once 'models/News.php';

$db = Database::getInstance();
$newsModel = new News($db);

$page_title = "Latest News";
$current_page = "news";

// Fetch dynamic data
list($featuredArticle, $otherArticles) = $newsModel->getAllWithFeatured();
$allNews = $newsModel->getAllPublished();

// Build filter category list from DB
$categoryStats = $newsModel->getStatsByCategory();
$filterCategories = [];
foreach ($categoryStats as $stat) {
    $filterCategories[] = $stat['category'];
}
if (empty($filterCategories)) {
    $filterCategories = ['All', 'Academic', 'Campus Life', 'Sports', 'Welfare', 'Governance'];
}

// Helper: format date
function fmtDate($dateStr) {
    return date('d M Y', strtotime($dateStr));
}

// Helper: extract first tag from JSON column
function getTags($tagsJson) {
    if (empty($tagsJson)) return [];
    $decoded = json_decode($tagsJson, true);
    return is_array($decoded) ? $decoded : [];
}

// Helper: build excerpt from content if empty
function buildExcerpt($text, $len = 180) {
    if (empty($text)) return '';
    $text = strip_tags($text);
    if (strlen($text) > $len) {
        return substr($text, 0, $len) . '...';
    }
    return $text;
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

    /* ── PAGE HEADER ── */
    .page-header {
      padding: 160px 80px 80px;
      background: linear-gradient(160deg, var(--navy-mid), var(--navy));
      position: relative;
      overflow: hidden;
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
      position: absolute; top: -100px; right: -100px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(201,168,76,0.08), transparent 70%);
      border-radius: 50%;
      pointer-events: none;
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
      margin-bottom: 16px;
      position: relative; z-index: 1;
    }
    .page-title em { font-style: italic; color: var(--gold-light); }
    .page-subtitle {
      font-size: 15px; font-weight: 300; line-height: 1.8;
      color: rgba(245,240,232,0.55); max-width: 520px;
      position: relative; z-index: 1;
    }

    /* ── FILTER BAR ── */
    .filter-bar {
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 28px 80px;
      background: var(--navy-mid);
      border-bottom: 1px solid rgba(201,168,76,0.08);
    }
    .filter-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.15em; text-transform: uppercase;
      color: var(--text-muted); margin-right: 8px;
    }
    .filter-btn {
      padding: 8px 20px;
      background: transparent;
      border: 1px solid rgba(201,168,76,0.2);
      color: var(--text-muted);
      font-family: 'Outfit', sans-serif;
      font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase;
      cursor: pointer; transition: all var(--transition-fast);
    }
    .filter-btn.active,
    .filter-btn:hover {
      border-color: var(--gold);
      color: var(--gold);
      background: rgba(201,168,76,0.05);
    }
    .filter-search {
      margin-left: auto;
      display: flex; align-items: center; gap: 0;
    }
    .filter-search input {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(201,168,76,0.2);
      border-right: none;
      color: var(--cream);
      padding: 9px 16px;
      font-family: 'Outfit', sans-serif; font-size: 13px;
      outline: none; width: 220px;
      transition: border-color var(--transition-fast);
    }
    .filter-search input:focus { border-color: var(--gold); }
    .filter-search input::placeholder { color: var(--text-muted); }
    .filter-search button {
      padding: 9px 16px;
      background: rgba(201,168,76,0.1);
      border: 1px solid rgba(201,168,76,0.2);
      color: var(--gold); cursor: pointer;
      font-size: 14px; transition: all var(--transition-fast);
    }
    .filter-search button:hover { background: rgba(201,168,76,0.2); }

    /* ── FEATURED NEWS ── */
    .featured-section { padding: 60px 80px 0; }
    .section-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 24px;
      display: flex; align-items: center; gap: 10px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(201,168,76,0.15); }

    .featured-grid {
      display: grid;
      grid-template-columns: 1.4fr 1fr;
      gap: 24px;
      margin-bottom: 24px;
    }
    .featured-card {
      position: relative; overflow: hidden;
      border: 1px solid rgba(201,168,76,0.12);
      background: linear-gradient(160deg, var(--navy-light), var(--navy-mid));
      display: flex; flex-direction: column; justify-content: flex-end;
      padding: 36px;
      min-height: 400px;
      text-decoration: none;
      transition: all var(--transition-med);
    }
    .featured-card:hover { border-color: rgba(201,168,76,0.3); transform: translateY(-4px); }
    .featured-card-bg {
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse at 60% 10%, rgba(201,168,76,0.12), transparent 60%),
        linear-gradient(to bottom, transparent 30%, rgba(10,22,40,0.95));
    }
    .featured-tag {
      position: absolute; top: 24px; left: 24px;
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.15em; text-transform: uppercase;
      color: var(--navy); background: var(--gold); padding: 5px 12px;
    }
    .featured-content { position: relative; z-index: 1; }
    .news-meta { font-size: 10px; letter-spacing: 0.15em; text-transform: uppercase; color: var(--gold); margin-bottom: 10px; }
    .featured-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 26px; font-weight: 400; color: var(--cream);
      line-height: 1.25; margin-bottom: 12px;
    }
    .featured-excerpt { font-size: 13px; color: var(--text-muted); line-height: 1.7; }
    .read-more {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--gold); margin-top: 20px;
      transition: gap var(--transition-fast);
    }
    .read-more:hover { gap: 14px; }
    .read-more::after { content: '→'; }

    .featured-stack {
      display: flex; flex-direction: column; gap: 24px;
    }
    .featured-mini {
      position: relative; overflow: hidden;
      border: 1px solid rgba(201,168,76,0.12);
      background: linear-gradient(160deg, var(--navy-light), var(--navy-mid));
      display: flex; flex-direction: column; justify-content: flex-end;
      padding: 28px;
      min-height: 188px;
      text-decoration: none;
      transition: all var(--transition-med);
    }
    .featured-mini:hover { border-color: rgba(201,168,76,0.3); transform: translateX(4px); }
    .featured-mini .featured-card-bg {
      background:
        radial-gradient(ellipse at 80% 0%, rgba(201,168,76,0.1), transparent 60%),
        linear-gradient(to bottom, transparent 20%, rgba(10,22,40,0.95));
    }
    .featured-mini .featured-title { font-size: 18px; }

    /* ── NEWS LIST ── */
    .news-list-section { padding: 40px 80px 80px; }
    .news-list-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }
    .news-card {
      border: 1px solid rgba(201,168,76,0.1);
      background: rgba(255,255,255,0.01);
      transition: all var(--transition-med);
      text-decoration: none;
      display: flex; flex-direction: column;
    }
    .news-card:hover { border-color: rgba(201,168,76,0.3); transform: translateY(-4px); }
    .news-card-img {
      height: 180px;
      background: linear-gradient(135deg, var(--navy-light), var(--navy-mid));
      position: relative; overflow: hidden;
    }
    .news-card-img-overlay {
      position: absolute; inset: 0;
      background: radial-gradient(ellipse at 60% 20%, rgba(201,168,76,0.1), transparent 60%);
    }
    .news-card-tag {
      position: absolute; bottom: 16px; left: 16px;
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--navy); background: var(--gold); padding: 4px 10px;
    }
    .news-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }
    .news-card-date { font-size: 10px; letter-spacing: 0.15em; text-transform: uppercase; color: var(--gold); margin-bottom: 10px; }
    .news-card-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 20px; font-weight: 400; color: var(--cream);
      line-height: 1.3; margin-bottom: 10px; flex: 1;
    }
    .news-card-excerpt { font-size: 12px; color: var(--text-muted); line-height: 1.7; margin-bottom: 20px; }
    .news-card-footer {
      display: flex; justify-content: space-between; align-items: center;
      padding-top: 16px;
      border-top: 1px solid rgba(201,168,76,0.08);
    }
    .news-card-author { font-size: 11px; color: var(--text-muted); }
    .news-card-link {
      font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--gold); text-decoration: none;
      display: flex; align-items: center; gap: 6px;
      transition: gap var(--transition-fast);
    }
    .news-card-link:hover { gap: 10px; }
    .news-card-link::after { content: '→'; }

    /* ── PAGINATION ── */
    .pagination {
      display: flex; align-items: center; justify-content: center; gap: 4px;
      padding: 0 80px 80px;
    }
    .page-btn {
      width: 40px; height: 40px;
      display: flex; align-items: center; justify-content: center;
      border: 1px solid rgba(201,168,76,0.2);
      background: transparent; color: var(--text-muted);
      font-family: 'Space Mono', monospace; font-size: 12px;
      cursor: pointer; text-decoration: none;
      transition: all var(--transition-fast);
    }
    .page-btn.active,
    .page-btn:hover { border-color: var(--gold); color: var(--gold); background: rgba(201,168,76,0.05); }
    .page-btn.arrow { font-size: 14px; }

    /* ── NEWSLETTER ── */
    .newsletter-bar {
      padding: 60px 80px;
      background: var(--navy-mid);
      border-top: 1px solid rgba(201,168,76,0.1);
      display: flex; align-items: center; gap: 60px;
    }
    .newsletter-text { flex: 1; }
    .newsletter-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px; font-weight: 300; color: var(--cream);
      margin-bottom: 8px;
    }
    .newsletter-subtitle { font-size: 13px; color: var(--text-muted); }
    .newsletter-form { display: flex; gap: 0; }
    .newsletter-form input {
      padding: 14px 20px; width: 280px;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(201,168,76,0.2); border-right: none;
      color: var(--cream); font-family: 'Outfit', sans-serif; font-size: 14px;
      outline: none; transition: border-color var(--transition-fast);
    }
    .newsletter-form input:focus { border-color: var(--gold); }
    .newsletter-form input::placeholder { color: var(--text-muted); }
    .newsletter-form button {
      padding: 14px 28px;
      background: linear-gradient(135deg, var(--gold-light), var(--gold));
      border: none; color: var(--navy);
      font-family: 'Outfit', sans-serif; font-size: 12px;
      font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
      cursor: pointer; transition: all var(--transition-fast);
      clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
    }
    .newsletter-form button:hover { background: linear-gradient(135deg, #fff, var(--gold-light)); }

    /* ── NO DATA ── */
    .no-data {
      padding: 80px;
      text-align: center;
      color: var(--text-muted);
      font-size: 14px;
    }
    .no-data-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      color: var(--cream);
      margin-bottom: 12px;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 1024px) {
      .page-header, .filter-bar, .featured-section, .news-list-section, .newsletter-bar, .pagination { padding-left: 40px; padding-right: 40px; }
      .featured-grid { grid-template-columns: 1fr; }
      .featured-stack { flex-direction: row; }
      .news-list-grid { grid-template-columns: repeat(2, 1fr); }
      .newsletter-bar { flex-direction: column; gap: 32px; }
    }
    @media (max-width: 640px) {
      .page-header, .filter-bar, .featured-section, .news-list-section, .newsletter-bar, .pagination, footer { padding-left: 20px; padding-right: 20px; }
      .news-list-grid { grid-template-columns: 1fr; }
      .featured-stack { flex-direction: column; }
      .filter-search { margin-left: 0; width: 100%; }
      .filter-search input { flex: 1; width: auto; }
      .newsletter-form { flex-direction: column; }
      .newsletter-form input { width: 100%; border-right: 1px solid rgba(201,168,76,0.2); border-bottom: none; }
    }
  </style>
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

  <!-- Custom Cursor -->
  <div class="cursor" id="cursor"></div>
  <div class="cursor-ring" id="cursorRing"></div>

  <?php include 'include/header.php'; ?>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-orb"></div>
    <div class="page-eyebrow">SRC Communications — Latest Updates</div>
    <h1 class="page-title">Stay <em>Informed,</em><br>Stay Connected</h1>
    <p class="page-subtitle">All the latest stories, updates, and developments from across the DHLTU Student Representative Council and campus community.</p>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar">
    <span class="filter-label">Filter:</span>
    <?php foreach ($filterCategories as $i => $cat): ?>
      <button class="filter-btn<?= $i === 0 ? ' active' : '' ?>" data-category="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
    <div class="filter-search">
      <input type="text" id="newsSearch" placeholder="Search news…" />
      <button>&#x2315;</button>
    </div>
  </div>

  <!-- Featured Articles -->
  <section class="featured-section">
    <?php if ($featuredArticle): ?>
    <div class="section-label">Featured Story</div>
    <div class="featured-grid">
      <!-- Main Featured -->
      <a href="news-detail.php?id=<?= $featuredArticle['id'] ?>" class="featured-card">
        <div class="featured-card-bg" style="<?php if (!empty($featuredArticle['featured_image'])): ?>background: url('<?= htmlspecialchars($featuredArticle['featured_image']) ?>') center/cover no-repeat, radial-gradient(ellipse at 60% 10%, rgba(201,168,76,0.12), transparent 60%), linear-gradient(to bottom, transparent 30%, rgba(10,22,40,0.95));<?php endif; ?>"></div>
        <span class="featured-tag"><?= htmlspecialchars($featuredArticle['category']) ?></span>
        <div class="featured-content">
          <div class="news-meta"><?= fmtDate($featuredArticle['published_at']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($featuredArticle['category']) ?></div>
          <h2 class="featured-title"><?= htmlspecialchars($featuredArticle['title']) ?></h2>
          <p class="featured-excerpt"><?= htmlspecialchars(buildExcerpt($featuredArticle['excerpt'] ?: $featuredArticle['content'], 280)) ?></p>
          <span class="read-more">Read Full Story</span>
        </div>
      </a>
    <?php endif; ?>

    <?php
    $sideArticles = array_slice($otherArticles, 0, 2);
    if ($featuredArticle) $sideArticles = array_filter($sideArticles, fn($a) => $a['id'] !== $featuredArticle['id']);
    ?>
      <?php if ($sideArticles): ?>
      <!-- Side Stack -->
      <div class="featured-stack">
        <?php foreach ($sideArticles as $min): ?>
        <a href="news-detail.php?id=<?= $min['id'] ?>" class="featured-mini">
          <div class="featured-card-bg"></div>
          <span class="featured-tag"><?= htmlspecialchars($min['category']) ?></span>
          <div class="featured-content">
            <div class="news-meta"><?= fmtDate($min['published_at']) ?></div>
            <h2 class="featured-title"><?= htmlspecialchars($min['title']) ?></h2>
            <span class="read-more">Read More</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- News List -->
  <section class="news-list-section">
    <div class="section-label">All Recent News</div>
    <div class="news-list-grid">
      <?php
      $displayArticles = $featuredArticle ? array_filter($otherArticles, fn($a) => $a['id'] !== $featuredArticle['id']) : $otherArticles;
      if (empty($displayArticles) && !$featuredArticle):
      ?>
      <div class="no-data" style="grid-column:1/-1;">
        <div class="no-data-title">No News Found</div>
        <p>There are no published news articles yet. Check back soon for the latest updates.</p>
      </div>
      <?php else: ?>
        <?php foreach ($displayArticles as $a): 
          $tags = getTags($a['tags']);
          $mainTag = $a['category'];
          $excerpt = htmlspecialchars(buildExcerpt($a['excerpt'] ?: $a['content']));
        ?>
        <a href="news-detail.php?id=<?= $a['id'] ?>" class="news-card">
          <div class="news-card-img">
            <div class="news-card-img-overlay"></div>
            <span class="news-card-tag"><?= htmlspecialchars($mainTag) ?></span>
          </div>
          <div class="news-card-body">
            <div class="news-card-date"><?= fmtDate($a['published_at']) ?></div>
            <h3 class="news-card-title"><?= htmlspecialchars($a['title']) ?></h3>
            <p class="news-card-excerpt"><?= $excerpt ?></p>
            <div class="news-card-footer">
              <span class="news-card-author">SRC Communications</span>
              <span class="news-card-link">Read More</span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

             <!-- Pagination -->
             <?php
             $total = $newsModel->getTotalPublished();
             $perPage = 9;
             $pages = max(1, (int)ceil($total / $perPage));
             if ($pages > 1):
             ?>
             <div class="pagination">
               <a href="#" class="page-btn arrow">&lsaquo;</a>
               <a href="#" class="page-btn active">1</a>
               <?php for ($i = 2; $i <= $pages; $i++): ?>
                 <a href="#" class="page-btn"><?= $i ?></a>
               <?php endfor; ?>
               <span class="page-btn" style="border:none;cursor:default;">…</span>
               <a href="#" class="page-btn arrow">&rsaquo;</a>
             </div>
             <?php endif; ?>

  <!-- Newsletter -->
  <div class="newsletter-bar">
    <div class="newsletter-text">
      <div class="newsletter-title">Never Miss a Story</div>
      <div class="newsletter-subtitle">Subscribe to get the latest DHLTU SRC news delivered straight to your inbox.</div>
    </div>
    <div class="newsletter-form">
      <input type="email" placeholder="Your university email address" />
      <button>Subscribe</button>
    </div>
  </div>

  <?php include 'include/footer.php'; ?>


</body>
</html>
