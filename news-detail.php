<?php
require_once 'config/database.php';
require_once 'models/News.php';

$db = Database::getInstance();
$newsModel = new News($db);

$id = $_GET['id'] ?? null;
$article = $id ? $newsModel->getById($id) : null;
$isLoggedIn = isset($_SESSION) && isset($_SESSION['user_id']);

if (!$article || $article['status'] !== 'PUBLISHED') {
    http_response_code(404);
    $page_title = "Article Not Found";
    $article = null;
} else {
    $page_title = $article['title'];
}

function fmtDate($dateStr) {
    return date('d M Y', strtotime($dateStr));
}

function getTags($tagsJson) {
    if (empty($tagsJson)) return [];
    $decoded = json_decode($tagsJson, true);
    return is_array($decoded) ? $decoded : [];
}

$relatedNews = [];
if ($article) {
    $relatedNews = $newsModel->getByCategories([$article['category']], 4);
    $relatedNews = array_filter($relatedNews, fn($n) => $n['id'] !== $article['id']);
    $relatedNews = array_slice($relatedNews, 0, 3);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?> — HLTU SRC</title>
  <meta name="description" content="<?= htmlspecialchars($article['excerpt'] ?? '') ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/main.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
      --text-muted: rgba(245,240,232,0.55);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body { background: var(--navy); color: var(--cream); font-family: 'Outfit', sans-serif; min-height: 100vh; }

    /* ── BACK BAR ── */
    .back-bar {
      padding: 20px 80px;
      background: var(--navy-mid);
      border-bottom: 1px solid rgba(201,168,76,0.08);
    }
    .back-link {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--text-muted); text-decoration: none;
      transition: color 0.2s;
    }
    .back-link:hover { color: var(--gold); }
    .back-link span { transition: transform 0.2s; }
    .back-link:hover span { transform: translateX(-4px); }

    /* ── HERO HEADER ── */
    .detail-hero {
      padding: 80px 80px 60px;
      position: relative;
      overflow: hidden;
    }
    .detail-hero::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse at 70% 20%, rgba(201,168,76,0.1), transparent 60%),
        linear-gradient(to bottom, var(--navy-mid), var(--navy));
    }

    .detail-eyebrow {
      position: relative; z-index: 1;
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.25em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 20px;
      display: flex; align-items: center; gap: 12px;
    }
    .detail-eyebrow::before { content: ''; width: 30px; height: 1px; background: var(--gold); }

    .detail-title {
      position: relative; z-index: 1;
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(32px, 4vw, 56px);
      font-weight: 400; line-height: 1.15;
      color: var(--cream); margin-bottom: 24px;
      max-width: 800px;
    }
    .detail-title em { font-style: italic; color: var(--gold-light); }

    .detail-meta {
      position: relative; z-index: 1;
      display: flex; align-items: center; gap: 20px;
      flex-wrap: wrap;
    }
    .detail-tag {
      display: inline-block;
      padding: 5px 14px;
      background: var(--gold); color: var(--navy);
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.1em; text-transform: uppercase;
      font-weight: 600;
    }
    .detail-date {
      font-size: 13px; color: var(--text-muted);
      letter-spacing: 0.05em;
    }
    .detail-author {
      font-size: 13px; color: var(--text-muted);
    }

    /* ── FEATURED IMAGE ── */
    .detail-image-wrap {
      padding: 32px 80px 0;
      max-width: 800px;
      margin: 0 auto;
    }
    .detail-image {
      width: 100%;
      max-width: 100%;
      height: auto;
      display: block;
      border: 1px solid rgba(201,168,76,0.12);
      border-radius: 4px;
    }

    /* ── ARTICLE BODY ── */
    .article-body {
      padding: 60px 80px 80px;
      max-width: 800px;
    }
    .article-body p {
      font-size: 15px; line-height: 1.9;
      color: rgba(245,240,232,0.82);
      margin-bottom: 24px;
    }
    .article-body h2 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px; font-weight: 400;
      color: var(--cream); margin: 40px 0 16px;
    }
    .article-body h3 {
      font-size: 20px; font-weight: 500;
      color: var(--gold-light); margin: 32px 0 12px;
    }
    .article-body ul, .article-body ol {
      padding-left: 24px; margin-bottom: 24px;
    }
    .article-body li {
      font-size: 15px; line-height: 1.8;
      color: rgba(245,240,232,0.82);
      margin-bottom: 8px;
    }
    .article-body blockquote {
      border-left: 3px solid var(--gold);
      padding: 16px 24px;
      margin: 32px 0;
      background: rgba(201,168,76,0.04);
      font-style: italic;
      color: var(--gold-light);
      font-size: 17px; line-height: 1.7;
    }
    .article-body strong { color: var(--cream); font-weight: 600; }

    /* ── TAGS BAR ── */
    .tags-bar {
      padding: 0 80px 60px;
      max-width: 800px;
      display: flex; gap: 10px; flex-wrap: wrap;
    }
    .tag-chip {
      padding: 6px 14px;
      border: 1px solid rgba(201,168,76,0.2);
      font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--gold);
      cursor: default;
    }

    /* ── RELATED ── */
    .related-section {
      padding: 60px 80px 80px;
      border-top: 1px solid rgba(201,168,76,0.08);
    }
    .related-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 32px;
      display: flex; align-items: center; gap: 10px;
    }
    .related-label::after { content: ''; flex: 1; height: 1px; background: rgba(201,168,76,0.15); }
    .related-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }
    .related-card {
      border: 1px solid rgba(201,168,76,0.1);
      background: rgba(255,255,255,0.01);
      padding: 28px;
      text-decoration: none;
      transition: all 0.3s ease;
      display: block;
    }
    .related-card:hover {
      border-color: rgba(201,168,76,0.3);
      transform: translateY(-4px);
    }
    .related-card-tag {
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 12px;
    }
    .related-card-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 20px; font-weight: 400;
      color: var(--cream); line-height: 1.3;
      margin-bottom: 12px;
    }
    .related-card-date { font-size: 11px; color: var(--text-muted); letter-spacing: 0.08em; }

    /* ── SHARE BAR ── */
    .share-bar {
      padding: 0 80px 60px;
      max-width: 800px;
      display: flex; align-items: center; gap: 16px;
    }
    .share-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.15em; text-transform: uppercase;
      color: var(--text-muted);
    }
    .share-btn {
      width: 40px; height: 40px;
      display: flex; align-items: center; justify-content: center;
      border: 1px solid rgba(201,168,76,0.2);
      color: var(--text-muted); text-decoration: none;
      font-size: 16px;
      transition: all 0.2s;
    }
    .share-btn:hover { border-color: var(--gold); color: var(--gold); background: rgba(201,168,76,0.05); }

    /* ── 404 ── */
    .not-found {
      min-height: 60vh;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      text-align: center; padding: 80px;
    }
    .not-found-code {
      font-family: 'Cormorant Garamond', serif;
      font-size: 100px; font-weight: 300;
      color: var(--gold); line-height: 1;
      margin-bottom: 24px;
    }
    .not-found-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 36px; font-weight: 400;
      color: var(--cream); margin-bottom: 12px;
    }
    .not-found-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 32px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1024px) {
      .back-bar, .detail-hero, .article-body, .tags-bar, .share-bar { padding-left: 40px; padding-right: 40px; }
      .related-section { padding: 60px 40px; }
      .related-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
      .back-bar, .detail-hero, .article-body, .tags-bar, .share-bar { padding-left: 20px; padding-right: 20px; }
      .related-section { padding: 60px 20px; }
      .related-grid { grid-template-columns: 1fr; }
      .detail-title { font-size: 28px; }
    }
  </style>
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
  <?php include 'include/header.php'; ?>

  <?php if (!$article): ?>
    <div class="not-found">
      <div class="not-found-code">404</div>
      <h1 class="not-found-title">Article Not Found</h1>
      <p class="not-found-desc">The article you are looking for does not exist or is not yet published.</p>
      <a href="latest-news.php" class="btn btn-primary">Browse All News</a>
    </div>
  <?php else: ?>
    <!-- Back Bar -->
    <div class="back-bar">
      <a href="latest-news.php" class="back-link">
        <span>←</span> All News &amp; Announcements
      </a>
    </div>

    <!-- Hero Header -->
    <div class="detail-hero">
      <div class="detail-eyebrow">SRC Communications</div>
      <h1 class="detail-title"><?= nl2br(htmlspecialchars($article['title'])) ?></h1>
      <div class="detail-meta">
        <span class="detail-tag"><?= htmlspecialchars($article['category'] ?? 'News') ?></span>
        <span class="detail-date"><?= fmtDate($article['published_at']) ?></span>
        <span class="detail-author">SRC Communications Office</span>
      </div>
    </div>

    <!-- Featured Image -->
    <?php if (!empty($article['featured_image'])): ?>
    <div class="detail-image-wrap">
      <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="detail-image" />
    </div>
    <?php endif; ?>

    <!-- Article Body -->
    <div class="article-body">
      <?= $article['content'] ?: '<p>' . htmlspecialchars($article['excerpt']) . '</p>' ?>
    </div>

    <!-- Tags -->
    <?php $tags = getTags($article['tags']); ?>
    <?php if (!empty($tags)): ?>
    <div class="tags-bar">
      <?php foreach ($tags as $tag): ?>
        <span class="tag-chip">#<?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Share Bar -->
    <div class="share-bar">
      <span class="share-label">Share</span>
      <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode('https://hltu.edu.gh/src/news-detail.php?id=' . $article['id']) ?>" target="_blank" class="share-btn" title="Share on Twitter">
        <i class="bi bi-twitter-x"></i>
      </a>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://hltu.edu.gh/src/news-detail.php?id=' . $article['id']) ?>" target="_blank" class="share-btn" title="Share on Facebook">
        <i class="bi bi-facebook"></i>
      </a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://hltu.edu.gh/src/news-detail.php?id=' . $article['id']) ?>" target="_blank" class="share-btn" title="Share on LinkedIn">
        <i class="bi bi-linkedin"></i>
      </a>
      <button class="share-btn" onclick="navigator.clipboard.writeText(window.location.href);this.title='Copied!';this.innerHTML='<i class=\'bi bi-check\'></i>';setTimeout(()=>{this.innerHTML='<i class=\'bi bi-link-45deg\'></i>';this.title='Copy Link';},1500)" title="Copy Link">
        <i class="bi bi-link-45deg"></i>
      </button>
    </div>

    <!-- Related Articles -->
    <?php if (!empty($relatedNews)): ?>
    <div class="related-section">
      <div class="related-label">Related Articles</div>
      <div class="related-grid">
        <?php foreach ($relatedNews as $rn): ?>
        <a href="news-detail.php?id=<?= $rn['id'] ?>" class="related-card">
          <div class="related-card-tag"><?= htmlspecialchars($rn['category']) ?></div>
          <div class="related-card-title"><?= htmlspecialchars($rn['title']) ?></div>
          <div class="related-card-date"><?= fmtDate($rn['published_at']) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php include 'include/footer.php'; ?>

  <script>
    (function () {
      const mobileToggle = document.querySelector('.mobile-toggle');
      const navList = document.querySelector('.nav-list');

      if (mobileToggle && navList) {
        mobileToggle.addEventListener('click', () => {
          const isOpen = navList.classList.toggle('active');
          mobileToggle.classList.toggle('active', isOpen);
          mobileToggle.setAttribute('aria-expanded', String(isOpen));
        });
      }

      document.querySelectorAll('.nav-link[data-dropdown]').forEach((link) => {
        link.addEventListener('click', (e) => {
          e.preventDefault();

          const navItem = link.closest('.nav-item');
          const dropdown = navItem?.querySelector('.dropdown');
          if (!navItem || !dropdown) return;

          const willOpen = !navItem.classList.contains('open');

          document.querySelectorAll('.nav-item.open').forEach((item) => {
            item.classList.remove('open');
            item.querySelector('.nav-link[data-dropdown]')?.setAttribute('aria-expanded', 'false');
            item.querySelector('.dropdown')?.classList.remove('open');
          });

          navItem.classList.toggle('open', willOpen);
          dropdown.classList.toggle('open', willOpen);
          link.setAttribute('aria-expanded', String(willOpen));
        });
      });

      document.querySelectorAll('.dropdown-item').forEach((item) => {
        item.addEventListener('click', () => {
          document.querySelectorAll('.nav-item.open').forEach((navItem) => {
            navItem.classList.remove('open');
            navItem.querySelector('.nav-link[data-dropdown]')?.setAttribute('aria-expanded', 'false');
            navItem.querySelector('.dropdown')?.classList.remove('open');
          });
          navList?.classList.remove('active');
          mobileToggle?.classList.remove('active');
          mobileToggle?.setAttribute('aria-expanded', 'false');
        });
      });

      document.addEventListener('click', (e) => {
        if (mobileToggle && navList && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
          mobileToggle.classList.remove('active');
          navList.classList.remove('active');
          mobileToggle.setAttribute('aria-expanded', 'false');
        }

        if (!e.target.closest('.nav-item')) {
          document.querySelectorAll('.nav-item.open').forEach((navItem) => {
            navItem.classList.remove('open');
            navItem.querySelector('.nav-link[data-dropdown]')?.setAttribute('aria-expanded', 'false');
            navItem.querySelector('.dropdown')?.classList.remove('open');
          });
        }
      });
    })();
  </script>
</body>
</html>
